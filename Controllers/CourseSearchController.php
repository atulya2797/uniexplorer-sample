<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Model\Intake;
use App\Model\Country;
use App\Model\Courses;
use App\Helper\Common;
use App\Model\City;
use App\Model\Institution;
use App\Model\LevelOfStudy;
use App\Model\Subdiscipline;
use App\Model\ShortlistedCourses;
use App\Model\Scholarship;
use Illuminate\Support\Facades\Input;

class CourseSearchController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
//        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function courseListing() {

        $compactData = $this->getCourseSearchData();

        if (\Request::ajax()) {
            return view('basic.allCourseIntakeList', $compactData);
        }
        return view('basic.courseListing', $compactData);
    }

    public function addPreferedCourse() {
        $input = Input::all();
        if ($this->student) {
            if ($input['remove_preferrence']) {
                ShortlistedCourses::where(['Course_id' => $input['course_id'], 'Student_id' => $this->student->getStudent->id])->delete();
                if (\Request::ajax()) {
                    return $this->sendResponse(true, '', 'Course removed from preferred list', 0);
                }
            } else {
                $data['Student_id'] = $this->student->getStudent->id;
                $data['Course_id'] = $input['course_id'];
                ShortlistedCourses::create($data);
                if (\Request::ajax()) {
                    return $this->sendResponse(true, '', 'Course added in preferred one', 1);
                }
            }
        }
        return $this->sendResponse(false, '', 'Please login to add course in preferred list');
    }

    public function getCourseSearchData() {
        //duration filter
        $months = $this->getDurationFilterCourseSearch();
        //intake filter
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterCourseSearch();
        //levelofstudy filter
        $levelOfStudy = $this->getLevelOfStudyFilterCourseSearch();
        //subdisciplines filter
        $subdisciplines = $this->getSubdisciplineFilterCourseSearch();
        //location filter 
        $cities = $this->getLocationFilterCourseSearch();
        //search filter of tution free
        $allIntakes = Intake::all();
        $tutionFeesFilter = [];
        $Intake_tuitionFeesPA = [];
        $Intake_tuitionFeesEP = [];
        foreach ($allIntakes as $Intake) {
            $Intake_tuitionFeesPA[] = $Intake->tuitionFeesPA ?: 0;
            $Intake_tuitionFeesEP[] = $Intake->tuitionFeesEP ?: 0;
        }
        $tutionFeesFilter['Intake_tuitionFeesPA_min'] = (!empty($Intake_tuitionFeesPA)) ? min($Intake_tuitionFeesPA) : 0;
        $tutionFeesFilter['Intake_tuitionFeesPA_max'] = (!empty($Intake_tuitionFeesPA)) ? max($Intake_tuitionFeesPA) : 0;
        $tutionFeesFilter['Intake_tuitionFeesEP_min'] = (!empty($Intake_tuitionFeesEP)) ? min($Intake_tuitionFeesEP) : 0;
        $tutionFeesFilter['Intake_tuitionFeesEP_max'] = (!empty($Intake_tuitionFeesEP)) ? max($Intake_tuitionFeesEP) : 0;
        //search filter of tution free
        //
        //get all course listing 
        $input = Input::all();
        $user = $this->student;
        $search = isset($input['course_name']) ? $input['course_name'] : '';
        $levelofstudy = isset($input['levelOfStudy']) ? $input['levelOfStudy'] : [];
        $subdiscipline = isset($input['subdiscipline']) ? $input['subdiscipline'] : [];
        $location = isset($input['location']) ? $input['location'] : [];
        $defaultTuitionFeesPA = (!empty($Intake_tuitionFeesPA)) ? max($Intake_tuitionFeesPA) : 0;
        $tuitionFeesPA = isset($input['tuitionFeesPA']) ? $input['tuitionFeesPA'] : $defaultTuitionFeesPA;
        $defaultTuitionFeesEP = (!empty($Intake_tuitionFeesEP)) ? max($Intake_tuitionFeesEP) : 0;
        $tuitionFeesEP = isset($input['tuitionFeesEP']) ? $input['tuitionFeesEP'] : $defaultTuitionFeesEP;
        $duration = isset($input['duration']) ? $input['duration'] : [];
        //for intake search of the current and next year
        $intakeCurrentYear = isset($input['intakeCurrentYear']) ? $input['intakeCurrentYear'] : [];
        $intakeNextYear = isset($input['intakeNextYear']) ? $input['intakeNextYear'] : [];
        //
        //sorting
        $sorting = isset($input['sorting']) ? $input['sorting'] : 'institutionRanking';
        //sorting
        //
//        check if all selected
        if (in_array('all', $duration)) {
            $duration = [];
        }
        if (in_array('all', $location)) {
            $location = [];
        }
        if (in_array('all', $intakeCurrentYear)) {
            $intakeCurrentYear = [];
        }
        if (in_array('all', $intakeNextYear)) {
            $intakeNextYear = [];
        }
//        check if all selected
        $allCourseIntake = Courses::where('name', 'LIKE', '%' . $search . '%')
                ->where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])
                ->whereHas('getIntakes', function($query) use ($location, $tuitionFeesPA, $tuitionFeesEP, $duration, $intakeCurrentYear, $intakeNextYear, $sorting) {
                    $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) use ($location, $sorting) {
                        $instQ = $instQuery->whereHas('getInstitution', function($institutionQuery) use ($sorting) {
                            if ($sorting == 'institutionRanking') {
                                $institutionQuery = $institutionQuery->orderBy('QSCurrentNational', 'desc');
                            }
                            $institutionQuery = $institutionQuery
                                    ->where([
                                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                                        'approval' => Institution::APPROVAL,
                                        'verification' => Institution::VERIFICATION_PASS
                                    ])
                                    ->has('getInstitutionAdmin');
                            return $institutionQuery;
                        });
                        if (count($location) > 0) {
                            $instQ = $instQ->whereIn('City_id', $location);
                        }
                        return $instQ;
                    });
                    if (count($intakeCurrentYear) > 0) {
                        foreach ($intakeCurrentYear as $icyKey => $icyVal) {
                            $icyData = explode('-', $icyVal);
                            if ($icyData[0]) {
                                $icyMonth = $icyData[0];
                                $intakeReturn = $intakeReturn->whereYear('commencementDate', '=', $icyMonth);
                            }
                            if (isset($icyData[1])) {
                                $icyYear = $icyData[1];
                                $intakeReturn = $intakeReturn->whereMonth('commencementDate', '=', $icyYear);
                            }
                        }
                    }
                    if (count($intakeNextYear) > 0) {
                        foreach ($intakeNextYear as $inyKey => $inyVal) {
                            $inyData = explode('-', $inyVal);
                            if (isset($inyData[0])) {
                                $inyMonth = $inyData[0];
                                $intakeReturn = $intakeReturn->whereYear('commencementDate', '=', $inyMonth);
                            }
                            if (isset($inyData[1])) {
                                $inyYear = $inyData[1];
                                $intakeReturn = $intakeReturn->whereMonth('commencementDate', '=', $inyYear);
                            }
                        }
                    }
                    //check if course intake deadline is reached
                    $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                    if ($tuitionFeesPA) {
                        $intakeReturn = $intakeReturn->where('tuitionFeesPA', '<=', $tuitionFeesPA);
                    }
                    if ($tuitionFeesEP) {
                        $intakeReturn = $intakeReturn->where('tuitionFeesEP', '<=', $tuitionFeesEP);
                    }
                    if ($sorting == 'tuitionFeesEP') {
                        $intakeReturn = $intakeReturn->orderBy('tuitionFeesEP', 'asc');
                    }
                    if ($sorting == 'tuitionFeesPA') {
                        $intakeReturn = $intakeReturn->orderBy('tuitionFeesPA', 'asc');
                    }
                    if (count($duration) > 0) {
                        $intakeReturn = $intakeReturn->where(function($durationQuery) use ($duration) {
                            foreach ($duration as $d) {
                                $durationQuery = $durationQuery->orWhere('duration', '<=', $d);
                            }
                            return $durationQuery;
                        });
                    }
                    return $intakeReturn;
                })
                ->whereHas('getEditedBy', function($editedByQuery) {
            return $editedByQuery->where(function($checkRoleQuery) {
                        return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                    });
        });
        if ($levelofstudy) {
            $allCourseIntake->whereIn('LevelofStudy_id', $levelofstudy);
        }
        if ($subdiscipline) {
            $allCourseIntake->whereHas('getCourseSubdiscipline', function($q) use ($subdiscipline) {
                return $q->whereIn('subdiscipline_id', $subdiscipline);
            });
        }
        if ($this->student) {
            $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
        }

        $countAllCourseIntake = $allCourseIntake->count();
        $allCourseIntake = $allCourseIntake->paginate(Common::PAGINATION);

        //compare courses section
        $Institution_listing = Institution::has('getInstitutionAdmin')->get();
        $CompareCourseInstitution = Institution::has('getInstitutionAdmin')->first();
        $inst_id = isset($CompareCourseInstitution) ? $CompareCourseInstitution->id : '';
        $institute_data = [];

        if (isset($CompareCourseInstitution)) {
            for ($i = 0; $i < 3; $i++) {
                $Institution_data[] = $CompareCourseInstitution->id;

                $compareCourses_Listing = Courses::whereHas('getIntakes', function($query) use ($Institution_data, $i) {
                            return $query->whereHas('getIntakeBranch', function($instQuery) use ($Institution_data, $i) {
                                        return $instQuery->whereHas('getInstitution', function($instQuery) use ($Institution_data, $i) {
                                                    return $instQuery->where(['id' => $Institution_data[$i]]);
                                                });
                                    });
                        })->first();
                if (isset($compareCourses_Listing))
                    $course_data[] = $compareCourses_Listing->id;
            }
        }

        $Courses_Listing = Courses::whereHas('getIntakes', function($query) use ($inst_id) {
                    return $query->whereHas('getIntakeBranch', function($instQuery) use ($inst_id) {
                                return $instQuery->whereHas('getInstitution', function($instQuery) use ($inst_id) {
                                            return $instQuery->where(['id' => $inst_id]);
                                        });
                            });
                })->get();

        //get all courses listing end
        return compact('allCourseIntake', 'months', 'search', 'user', 'tutionFeesFilter', 'cities', 'subdisciplines', 'levelOfStudy', 'currentYearDetail', 'nextYearDetail', 'countAllCourseIntake', 'Institution_listing', 'Courses_Listing');
    }

    public function getLocationFilterCourseSearch() {
        $cities = City::whereHas('getState', function($q) {
                    return $q->has('getCountry');
                })->limit(City::LIMIT_LIST)
                ->get();
        $user = $this->student;
        foreach ($cities as $ckey => $city) {
            $cityId = $city->id;
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) use ($cityId) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) use ($cityId) {
                            $instQ = $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                        return $institutionCheck
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    })
                                    ->where(['City_id' => $cityId]);
                            return $instQ;
                        });
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        return $intakeReturn;
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $cities[$ckey]['totalCourses'] = $allCourseIntake->count();
        }
        return $cities;
    }

    public function getSubdisciplineFilterCourseSearch() {
        $user = $this->student;

        $subdisciplines = Subdiscipline::has('getFieldofStudy')->get();
        foreach ($subdisciplines as $key => $val) {
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                            return $instQuery->whereHas('getInstitution', function($checkInstitution) {
                                        return $checkInstitution
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    });
                        });
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        return $intakeReturn;
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
//            $allCourseIntake->where(['Subdiscipline_id' => $val->id]);
            $cId = $val->id;
            $allCourseIntake->whereHas('getCourseSubdiscipline', function($q) use ($cId) {
                return $q->where(['subdiscipline_id' => $cId]);
            });
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $subdisciplines[$key]['totalCourses'] = $allCourseIntake->count();
        }
        return $subdisciplines;
    }

    public function getTotalIntakeDateCourseCount($icyData) {
        $user = $this->student;
        $allCourseIntake = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])
                ->whereHas('getIntakes', function($query) use ($icyData) {
                    $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                        return $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                    return $institutionCheck
                                            ->where([
                                                'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                'approval' => Institution::APPROVAL,
                                                'verification' => Institution::VERIFICATION_PASS
                                            ])
                                            ->has('getInstitutionAdmin');
                                });
                    });
                    $icyData = explode('-', $icyData);
                    if ($icyData[0]) {
                        $icyMonth = $icyData[0];
                        $intakeReturn = $intakeReturn->whereYear('commencementDate', '=', $icyMonth);
                    }
                    if (isset($icyData[1])) {
                        $icyYear = $icyData[1];
                        $intakeReturn = $intakeReturn->whereMonth('commencementDate', '=', $icyYear);
                    }
                    //check if course intake deadline is reached
                    $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                    return $intakeReturn;
                })
                ->whereHas('getEditedBy', function($editedByQuery) {
            return $editedByQuery->where(function($checkRoleQuery) {
                        return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                    });
        });
        if ($this->student) {
            $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
        }
        return $allCourseIntake->count();
    }

    public function getDurationFilterCourseSearch() {
        $user = $this->student;
        $months = Intake::select('duration')->groupBy('duration')->get();
        foreach ($months as $mkey => $month) {
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) use ($month) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                            return $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                        return $institutionCheck
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    });
                        });
                        if ($month->duration) {
                            $intakeReturn = $intakeReturn->where('duration', '<=', $month->duration);
                        }
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        return $intakeReturn;
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $months[$mkey]['totalCourses'] = $allCourseIntake->count();
        }
        return $months;
    }

    public function getIntakeFilterCourseSearch() {
        $currentYearDetail = [];
        $nextYearDetail = [];
        $month = date('n');
        $max = (12 - $month) + 1;
        for ($x = 0; $x < $max; $x++) {
            $icyData = date('Y-m-d', mktime(0, 0, 0, $month + $x, 1));
            $getCYD = [
                'monthName' => date('F', mktime(0, 0, 0, $month + $x, 1)),
                'date' => $icyData
            ];
            $getCYD['totalCourses'] = $this->getTotalIntakeDateCourseCount($icyData);
            $currentYearDetail[] = $getCYD;
        }

        for ($m = 1; $m <= 12; $m++) {
            $inyData = date('Y-m-d', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year'))));
            $getNYD = [
                'monthName' => date('F', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year')))),
                'date' => $inyData,
            ];
            $getNYD['totalCourses'] = $this->getTotalIntakeDateCourseCount($inyData);
            $nextYearDetail[] = $getNYD;
        }
        return [$currentYearDetail, $nextYearDetail];
    }

    public function getLevelOfStudyFilterCourseSearch() {
        $user = $this->student;
        $levelOfStudy = LevelOfStudy::all();
        foreach ($levelOfStudy as $key => $val) {
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                            return $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                        return $institutionCheck
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    });
                        });
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        return $intakeReturn;
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
            $allCourseIntake->where(['LevelofStudy_id' => $val->id]);
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $levelOfStudy[$key]['totalCourses'] = $allCourseIntake->count();
        }
        return $levelOfStudy;
    }

    public function studentPreferredSearch($allCourseIntake, $user) {
        $allCourseIntake = $allCourseIntake->where(function($orWhere) use ($user) {
            $orWhereData = $orWhere->orWhereHas('getShortlistedCourses', function($q) use($user) {
                        return $q->where(['Student_id' => $user->getStudent->id]);
                    })->orWhereHas('getDesiredLevelOfStudy', function($LevelQuery) use ($user) {
                        return $LevelQuery->where(['Student_id' => $user->getStudent->id]);
                    })->orWhereHas('getCourseSubdiscipline', function($q) use ($user) {
                return $q->whereHas('getDesiredSubdiscipline', function($SubdQuery) use ($user) {
                            return $SubdQuery->where(['Student_id' => $user->getStudent->id]);
                        });
            });
            $columnName = $user->getStudent->EnglishLanguageTest ? strtoupper($user->getStudent->EnglishLanguageTest) : NULL;
            if ($columnName) {
                $orWhereData = $orWhereData->orWhere($columnName, '<=', $user->getStudent->EnglishLanguageTestScore);
            }
            return $orWhereData;
        });
        return $allCourseIntake;
    }
    public function sponsoredCoursesList($id){
        $userData = $this->student;

        if (!isset($userData->getStudent)) {
            abort(404);
        }
        $compactData = $this->getSponsoredCourseSearchData($id);

        if (\Request::ajax()) {
            return view('basic.allSponsoredCourseIntakeList', $compactData);
        }
        return view('basic.sponsoredCoursesList', $compactData);
    }
    public function getDurationFilterSponsoeredCourse($id) {
        $user = $this->student;
        $months = Intake::select('duration')->groupBy('duration')->get();
        foreach ($months as $mkey => $month) {
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) use ($month, $id) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                            return $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                        return $institutionCheck
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    });
                        });
                        if ($month->duration) {
                            $intakeReturn = $intakeReturn->where('duration', '<=', $month->duration);
                        }
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        return $intakeReturn->whereHas('getIntakeScholarshipIntake', function($intakescholarshipquery) use($id){
                                return $intakescholarshipquery->whereHas('getScholarshipIntake', function($scholarshipintakequery) use($id) {
                                    return $scholarshipintakequery->where('Scholarship_id',$id);

                            });
                        });
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $months[$mkey]['totalCourses'] = $allCourseIntake->count();
        }
        return $months;
    }

    public function getIntakeFilterSponsoeredCourse($id) {
        $currentYearDetail = [];
        $nextYearDetail = [];
        $month = date('n');
        $max = (12 - $month) + 1;
        for ($x = 0; $x < $max; $x++) {
            $icyData = date('Y-m-d', mktime(0, 0, 0, $month + $x, 1));
            $getCYD = [
                'monthName' => date('F', mktime(0, 0, 0, $month + $x, 1)),
                'date' => $icyData
            ];
            $getCYD['totalCourses'] = $this->getTotalIntakeDateCourseSponsoredCount($icyData, $id);
            $currentYearDetail[] = $getCYD;
        }

        for ($m = 1; $m <= 12; $m++) {
            $inyData = date('Y-m-d', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year'))));
            $getNYD = [
                'monthName' => date('F', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year')))),
                'date' => $inyData,
            ];
            $getNYD['totalCourses'] = $this->getTotalIntakeDateCourseSponsoredCount($inyData, $id);
            $nextYearDetail[] = $getNYD;
        }
        return [$currentYearDetail, $nextYearDetail];
    }

    public function getTotalIntakeDateCourseSponsoredCount($icyData, $id) {
        $user = $this->student;
        $allCourseIntake = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])
                ->whereHas('getIntakes', function($query) use ($icyData, $id) {
                    $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                        return $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                    return $institutionCheck
                                            ->where([
                                                'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                'approval' => Institution::APPROVAL,
                                                'verification' => Institution::VERIFICATION_PASS
                                            ])
                                            ->has('getInstitutionAdmin');
                                });
                    });
                    $icyData = explode('-', $icyData);
                    if ($icyData[0]) {
                        $icyMonth = $icyData[0];
                        $intakeReturn = $intakeReturn->whereYear('commencementDate', '=', $icyMonth);
                    }
                    if (isset($icyData[1])) {
                        $icyYear = $icyData[1];
                        $intakeReturn = $intakeReturn->whereMonth('commencementDate', '=', $icyYear);
                    }
                    //check if course intake deadline is reached
                    $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                    return $intakeReturn->whereHas('getIntakeScholarshipIntake', function($intakescholarshipquery) use($id){
                                return $intakescholarshipquery->whereHas('getScholarshipIntake', function($scholarshipintakequery) use($id) {
                                    return $scholarshipintakequery->where('Scholarship_id',$id);

                            });
                        });
                })
                ->whereHas('getEditedBy', function($editedByQuery) {
            return $editedByQuery->where(function($checkRoleQuery) {
                        return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                    });
        });
        if ($this->student) {
            $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
        }
        return $allCourseIntake->count();
    }

    public function getLevelOfStudyFilterSponsoeredCourse($id) {
        $user = $this->student;
        $levelOfStudy = LevelOfStudy::all();
        foreach ($levelOfStudy as $key => $val) {
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) use($id) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                            return $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                        return $institutionCheck
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    });
                        });
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        return $intakeReturn->whereHas('getIntakeScholarshipIntake', function($intakescholarshipquery) use($id){
                                return $intakescholarshipquery->whereHas('getScholarshipIntake', function($scholarshipintakequery) use($id) {
                                    return $scholarshipintakequery->where('Scholarship_id',$id);

                            });
                        });
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
            $allCourseIntake->where(['LevelofStudy_id' => $val->id]);
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $levelOfStudy[$key]['totalCourses'] = $allCourseIntake->count();
        }
        return $levelOfStudy;
    }

    public function getSubdisciplineFilterSponsoeredCourse($id) {
        $user = $this->student;

        $subdisciplines = Subdiscipline::has('getFieldofStudy')->get();
        foreach ($subdisciplines as $key => $val) {
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) use($id) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) {
                            return $instQuery->whereHas('getInstitution', function($checkInstitution) {
                                        return $checkInstitution
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    });
                        });
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        return $intakeReturn->whereHas('getIntakeScholarshipIntake', function($intakescholarshipquery) use($id){
                                return $intakescholarshipquery->whereHas('getScholarshipIntake', function($scholarshipintakequery) use($id) {
                                    return $scholarshipintakequery->where('Scholarship_id',$id);

                            });
                        });
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
//            $allCourseIntake->where(['Subdiscipline_id' => $val->id]);
            $cId = $val->id;
            $allCourseIntake->whereHas('getCourseSubdiscipline', function($q) use ($cId) {
                return $q->where(['subdiscipline_id' => $cId]);
            });
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $subdisciplines[$key]['totalCourses'] = $allCourseIntake->count();
        }
        return $subdisciplines;
    }

    public function getLocationFilterSponsoeredCourse($id) {
        $cities = City::whereHas('getState', function($q) {
                    return $q->has('getCountry');
                })->limit(City::LIMIT_LIST)
                ->get();
        $user = $this->student;
        foreach ($cities as $ckey => $city) {
            $cityId = $city->id;
            $allCourseIntake = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) use ($cityId,$id) {
                        $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) use ($cityId) {
                            $instQ = $instQuery->whereHas('getInstitution', function($institutionCheck) {
                                        return $institutionCheck
                                                ->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])
                                                ->has('getInstitutionAdmin');
                                    })
                                    ->where(['City_id' => $cityId]);
                            return $instQ;
                        });
                        //check if course intake deadline is reached
                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                            return $intakeReturn->whereHas('getIntakeScholarshipIntake', function($intakescholarshipquery) use($id){
                                return $intakescholarshipquery->whereHas('getScholarshipIntake', function($scholarshipintakequery) use($id) {
                                    return $scholarshipintakequery->where('Scholarship_id',$id);

                            });
                        });
                    })
                    ->whereHas('getEditedBy', function($editedByQuery) {
                return $editedByQuery->where(function($checkRoleQuery) {
                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                        });
            });
            if ($this->student) {
                $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
            }
            $cities[$ckey]['totalCourses'] = $allCourseIntake->count();
        }
        return $cities;
    }

    public function getSponsoredCourseSearchData($id) {
        //duration filter
        $scholarship_name = Scholarship::find($id);
        
        if(isset($scholarship_name->name) && !empty($scholarship_name->name))
            $scholarship_name = $scholarship_name->name;
        else
            abort(404);

        $months = $this->getDurationFilterSponsoeredCourse($id);
        //intake filter
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterSponsoeredCourse($id);
        //levelofstudy filter
        $levelOfStudy = $this->getLevelOfStudyFilterSponsoeredCourse($id);
        //subdisciplines filter
        $subdisciplines = $this->getSubdisciplineFilterSponsoeredCourse($id);
        //location filter 
        $cities = $this->getLocationFilterSponsoeredCourse($id);
        //search filter of tution free
        $allIntakes = Intake::all();
        $tutionFeesFilter = [];
        $Intake_tuitionFeesPA = [];
        $Intake_tuitionFeesEP = [];
        foreach ($allIntakes as $Intake) {
            $Intake_tuitionFeesPA[] = $Intake->tuitionFeesPA ?: 0;
            $Intake_tuitionFeesEP[] = $Intake->tuitionFeesEP ?: 0;
        }
        $tutionFeesFilter['Intake_tuitionFeesPA_min'] = (!empty($Intake_tuitionFeesPA)) ? min($Intake_tuitionFeesPA) : 0;
        $tutionFeesFilter['Intake_tuitionFeesPA_max'] = (!empty($Intake_tuitionFeesPA)) ? max($Intake_tuitionFeesPA) : 0;
        $tutionFeesFilter['Intake_tuitionFeesEP_min'] = (!empty($Intake_tuitionFeesEP)) ? min($Intake_tuitionFeesEP) : 0;
        $tutionFeesFilter['Intake_tuitionFeesEP_max'] = (!empty($Intake_tuitionFeesEP)) ? max($Intake_tuitionFeesEP) : 0;
        //search filter of tution free
        //
        //get all course listing 
        $input = Input::all();
        $user = $this->student;
        $search = isset($input['course_name']) ? $input['course_name'] : '';
        $levelofstudy = isset($input['levelOfStudy']) ? $input['levelOfStudy'] : [];
        $subdiscipline = isset($input['subdiscipline']) ? $input['subdiscipline'] : [];
        $location = isset($input['location']) ? $input['location'] : [];
        $defaultTuitionFeesPA = (!empty($Intake_tuitionFeesPA)) ? max($Intake_tuitionFeesPA) : 0;
        $tuitionFeesPA = isset($input['tuitionFeesPA']) ? $input['tuitionFeesPA'] : $defaultTuitionFeesPA;
        $defaultTuitionFeesEP = (!empty($Intake_tuitionFeesEP)) ? max($Intake_tuitionFeesEP) : 0;
        $tuitionFeesEP = isset($input['tuitionFeesEP']) ? $input['tuitionFeesEP'] : $defaultTuitionFeesEP;
        $duration = isset($input['duration']) ? $input['duration'] : [];
        //for intake search of the current and next year
        $intakeCurrentYear = isset($input['intakeCurrentYear']) ? $input['intakeCurrentYear'] : [];
        $intakeNextYear = isset($input['intakeNextYear']) ? $input['intakeNextYear'] : [];
        //
        //sorting
        $sorting = isset($input['sorting']) ? $input['sorting'] : 'institutionRanking';
        //sorting
        //
//        check if all selected
        if (in_array('all', $duration)) {
            $duration = [];
        }
        if (in_array('all', $location)) {
            $location = [];
        }
        if (in_array('all', $intakeCurrentYear)) {
            $intakeCurrentYear = [];
        }
        if (in_array('all', $intakeNextYear)) {
            $intakeNextYear = [];
        }
//        check if all selected
        $allCourseIntake = Courses::where('name', 'LIKE', '%' . $search . '%')
                ->where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])
                ->whereHas('getIntakes', function($query) use ($location, $tuitionFeesPA, $tuitionFeesEP, $duration, $intakeCurrentYear, $intakeNextYear, $sorting, $id) {
                    $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) use ($location, $sorting) {
                        $instQ = $instQuery->whereHas('getInstitution', function($institutionQuery) use ($sorting) {
                            if ($sorting == 'institutionRanking') {
                                $institutionQuery = $institutionQuery->orderBy('QSCurrentNational', 'desc');
                            }
                            $institutionQuery = $institutionQuery
                                    ->where([
                                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                                        'approval' => Institution::APPROVAL,
                                        'verification' => Institution::VERIFICATION_PASS
                                    ])
                                    ->has('getInstitutionAdmin');
                            return $institutionQuery;
                        });
                        if (count($location) > 0) {
                            $instQ = $instQ->whereIn('City_id', $location);
                        }
                        return $instQ;
                    });
                    if (count($intakeCurrentYear) > 0) {
                        foreach ($intakeCurrentYear as $icyKey => $icyVal) {
                            $icyData = explode('-', $icyVal);
                            if ($icyData[0]) {
                                $icyMonth = $icyData[0];
                                $intakeReturn = $intakeReturn->whereYear('commencementDate', '=', $icyMonth);
                            }
                            if (isset($icyData[1])) {
                                $icyYear = $icyData[1];
                                $intakeReturn = $intakeReturn->whereMonth('commencementDate', '=', $icyYear);
                            }
                        }
                    }
                    if (count($intakeNextYear) > 0) {
                        foreach ($intakeNextYear as $inyKey => $inyVal) {
                            $inyData = explode('-', $inyVal);
                            if (isset($inyData[0])) {
                                $inyMonth = $inyData[0];
                                $intakeReturn = $intakeReturn->whereYear('commencementDate', '=', $inyMonth);
                            }
                            if (isset($inyData[1])) {
                                $inyYear = $inyData[1];
                                $intakeReturn = $intakeReturn->whereMonth('commencementDate', '=', $inyYear);
                            }
                        }
                    }
                    //check if course intake deadline is reached
                    $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                    if ($tuitionFeesPA) {
                        $intakeReturn = $intakeReturn->where('tuitionFeesPA', '<=', $tuitionFeesPA);
                    }
                    if ($tuitionFeesEP) {
                        $intakeReturn = $intakeReturn->where('tuitionFeesEP', '<=', $tuitionFeesEP);
                    }
                    if ($sorting == 'tuitionFeesEP') {
                        $intakeReturn = $intakeReturn->orderBy('tuitionFeesEP', 'asc');
                    }
                    if ($sorting == 'tuitionFeesPA') {
                        $intakeReturn = $intakeReturn->orderBy('tuitionFeesPA', 'asc');
                    }
                    if (count($duration) > 0) {
                        $intakeReturn = $intakeReturn->where(function($durationQuery) use ($duration) {
                            foreach ($duration as $d) {
                                $durationQuery = $durationQuery->orWhere('duration', '<=', $d);
                            }
                            return $durationQuery;
                        });
                    }
                    return $intakeReturn->whereHas('getIntakeScholarshipIntake', function($intakescholarshipquery) use($id){
                        return $intakescholarshipquery->whereHas('getScholarshipIntake', function($scholarshipintakequery) use($id) {
                            return $scholarshipintakequery->where('Scholarship_id',$id);

                        });
                    });
                })
                ->whereHas('getEditedBy', function($editedByQuery) {
            return $editedByQuery->where(function($checkRoleQuery) {
                        return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                    });
        });
        if ($levelofstudy) {
            $allCourseIntake->whereIn('LevelofStudy_id', $levelofstudy);
        }
        if ($subdiscipline) {
            $allCourseIntake->whereHas('getCourseSubdiscipline', function($q) use ($subdiscipline) {
                return $q->whereIn('subdiscipline_id', $subdiscipline);
            });
        }
        if ($this->student) {
            $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
        }

        $countAllCourseIntake = $allCourseIntake->count();
        $allCourseIntake = $allCourseIntake->paginate(Common::PAGINATION);

        //compare courses section
        $Institution_listing = Institution::has('getInstitutionAdmin')->get();
        $CompareCourseInstitution = Institution::has('getInstitutionAdmin')->first();
        $inst_id = isset($CompareCourseInstitution) ? $CompareCourseInstitution->id : '';
        $institute_data = [];

        if (isset($CompareCourseInstitution)) {
            for ($i = 0; $i < 3; $i++) {
                $Institution_data[] = $CompareCourseInstitution->id;

                $compareCourses_Listing = Courses::whereHas('getIntakes', function($query) use ($Institution_data, $i) {
                            return $query->whereHas('getIntakeBranch', function($instQuery) use ($Institution_data, $i) {
                                        return $instQuery->whereHas('getInstitution', function($instQuery) use ($Institution_data, $i) {
                                                    return $instQuery->where(['id' => $Institution_data[$i]]);
                                                });
                                    });
                        })->first();
                if (isset($compareCourses_Listing))
                    $course_data[] = $compareCourses_Listing->id;
            }
        }

        $Courses_Listing = Courses::whereHas('getIntakes', function($query) use ($inst_id) {
                    return $query->whereHas('getIntakeBranch', function($instQuery) use ($inst_id) {
                                return $instQuery->whereHas('getInstitution', function($instQuery) use ($inst_id) {
                                            return $instQuery->where(['id' => $inst_id]);
                                        });
                            });
                })->get();

        //get all courses listing end
        return compact('allCourseIntake', 'months', 'search', 'user', 'tutionFeesFilter', 'cities', 'subdisciplines', 'levelOfStudy', 'currentYearDetail', 'nextYearDetail', 'countAllCourseIntake', 'Institution_listing', 'Courses_Listing', 'scholarship_name');
    }

}

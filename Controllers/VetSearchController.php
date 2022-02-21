<?php

namespace App\Http\Controllers;

use App\Model\Courses;
use App\Model\LevelOfStudy;
use App\Model\Intake;
use App\Model\Country;
use App\Helper\Common;
use App\Model\City;
use App\Model\Institution;
use App\Model\Subdiscipline;
use Illuminate\Support\Facades\Input;

class VetSearchController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function VetListing() {

        $compactData = $this->getVetSearchData();

        list($Institution_listing, $Courses_Listing) = $this->getCourseCompareData();

        $compactData['Institution_listing'] = $Institution_listing;
        $compactData['Courses_Listing'] = $Courses_Listing;

        if (\Request::ajax()) {
            return view('basic.allVetList', $compactData);
        }

        return view('basic.vetListing', $compactData);
    }

    public function getCourseCompareData() {

        $Institution_listing = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->get();
        $CompareCourseInstitution = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->first();
        $inst_id = isset($CompareCourseInstitution->id) ? $CompareCourseInstitution->id : '';

        $institute_data = [];

        if (isset($CompareCourseInstitution)) {
            for ($i = 0; $i < 3; $i++) {
                $Institution_data[] = $CompareCourseInstitution->id;

                $compareCourses_Listing = Courses::where([
                            'visibility' => Courses::PUBLISHED,
                            'approval' => Courses::APPROVED
                        ])
                        ->whereHas('getIntakes', function($query) use ($Institution_data, $i) {
                            return $query->whereHas('getIntakeBranch', function($instQuery) use($Institution_data, $i) {
                                        return $instQuery->whereHas('getInstitution', function($instQuery) use($Institution_data, $i) {
                                                    return $instQuery->where([
                                                                'id' => $Institution_data[$i],
                                                                'approval' => Institution::APPROVAL,
                                                                'verification' => Institution::VERIFICATION_PASS,
                                                                'visibility' => Institution::VISIBILITY_PUBLISHED
                                                    ]);
                                                });
                                    });
                        })
                        ->first();

                if (isset($compareCourses_Listing))
                    $course_data[] = $compareCourses_Listing->id;
            }
        }

        $Courses_Listing = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])
                ->whereHas('getIntakes', function($query) use ($inst_id) {
                    return $query->whereHas('getIntakeBranch', function($intQuery) use($inst_id) {
                                return $intQuery->whereHas('getInstitution', function($instQuery) use($inst_id) {
                                            return $instQuery->where([
                                                        'id' => $inst_id,
                                                        'approval' => Institution::APPROVAL,
                                                        'verification' => Institution::VERIFICATION_PASS,
                                                        'visibility' => Institution::VISIBILITY_PUBLISHED
                                            ]);
                                        });
                            });
                })
                ->get();

        return [$Institution_listing, $Courses_Listing];
    }

    public function getVetSearchData() {
        //intake filter
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterInstitutionSearch();
        //levelofstudy filter
        $levelOfStudy = $this->getLevelOfStudyFilterInstitutionSearch();
        //subdisciplines filter
        $subdisciplines = $this->getSubdisciplineFilterInstitutionSearch();
        //location filter 
        $cities = $this->getLocationFilterInstitutionSearch();
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
        $search = isset($input['vet_name']) ? $input['vet_name'] : '';
        $levelofstudy = isset($input['levelOfStudy']) ? $input['levelOfStudy'] : [];
        $subdiscipline = isset($input['subdiscipline']) ? $input['subdiscipline'] : [];
        $location = isset($input['location']) ? $input['location'] : [];
        $defaultTuitionFeesPA = (!empty($Intake_tuitionFeesPA)) ? max($Intake_tuitionFeesPA) : 0;
        $tuitionFeesPA = isset($input['tuitionFeesPA']) ? $input['tuitionFeesPA'] : $defaultTuitionFeesPA;
        $defaultTuitionFeesEP = (!empty($Intake_tuitionFeesEP)) ? max($Intake_tuitionFeesEP) : 0;
        $tuitionFeesEP = isset($input['tuitionFeesEP']) ? $input['tuitionFeesEP'] : $defaultTuitionFeesEP;
        //for intake search of the current and next year
        $intakeCurrentYear = isset($input['intakeCurrentYear']) ? $input['intakeCurrentYear'] : [];
        $intakeNextYear = isset($input['intakeNextYear']) ? $input['intakeNextYear'] : [];
        //
//        check if all selected
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
        $allInstitutions = Institution::has('getInstitutionAdmin')
                ->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->where('name', 'LIKE', '%' . $search . '%')
                ->where(['type' => Institution::TYPE_TRAINING])
                ->whereHas('getBranch', function($getBranch) use ($subdiscipline, $location, $tuitionFeesPA, $tuitionFeesEP, $intakeCurrentYear, $intakeNextYear, $user, $levelofstudy) {
            $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($subdiscipline, $tuitionFeesPA, $tuitionFeesEP, $intakeCurrentYear, $intakeNextYear, $user, $levelofstudy) {
                $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($subdiscipline, $user, $levelofstudy) {
                    $getCourseData->where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ]);
                    if ($this->student) {
                        $getCourseData = $this->studentPreferredSearch($getCourseData, $user);
                    }
                    if ($subdiscipline) {
                        $getCourseData = $getCourseData->whereHas('getCourseSubdiscipline', function($q) use ($subdiscipline) {
                            return $q->whereIn('subdiscipline_id', $subdiscipline);
                        });
                    }
                    if ($levelofstudy) {
                        $getCourseData = $getCourseData->whereIn('LevelofStudy_id', $levelofstudy);
                    }
                    return $getCourseData;
                });
                //check if course intake deadline is reached
                $getIntakeReturn = $getIntakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                if ($tuitionFeesPA) {
                    $getIntakeReturn = $getIntakeReturn->where('tuitionFeesPA', '<=', $tuitionFeesPA);
                }
                if ($tuitionFeesEP) {
                    $getIntakeReturn = $getIntakeReturn->where('tuitionFeesEP', '<=', $tuitionFeesEP);
                }
                if (count($intakeCurrentYear) > 0) {
                    foreach ($intakeCurrentYear as $icyVal) {
                        $icyData = explode('-', $icyVal);
                        if ($icyData[0]) {
                            $icyMonth = $icyData[0];
                            $getIntakeReturn = $getIntakeReturn->whereYear('commencementDate', '=', $icyMonth);
                        }
                        if (isset($icyData[1])) {
                            $icyYear = $icyData[1];
                            $getIntakeReturn = $getIntakeReturn->whereMonth('commencementDate', '=', $icyYear);
                        }
                    }
                }
                if (count($intakeNextYear) > 0) {
                    foreach ($intakeNextYear as $inyVal) {
                        $inyData = explode('-', $inyVal);
                        if (isset($inyData[0])) {
                            $inyMonth = $inyData[0];
                            $getIntakeReturn = $getIntakeReturn->whereYear('commencementDate', '=', $inyMonth);
                        }
                        if (isset($inyData[1])) {
                            $inyYear = $inyData[1];
                            $getIntakeReturn = $getIntakeReturn->whereMonth('commencementDate', '=', $inyYear);
                        }
                    }
                }
                return $getIntakeReturn;
            });
            if ($location) {
                $getBranchReturn = $getBranchReturn->whereIn('City_id', $location);
            }
            return $getBranchReturn;
        });
        $countAllInstitutions = $allInstitutions->count();
        $allInstitutions = $allInstitutions->paginate(Common::PAGINATION);
        return compact('allInstitutions', 'search', 'user', 'tutionFeesFilter', 'cities', 'subdisciplines', 'currentYearDetail', 'nextYearDetail', 'countAllInstitutions', 'levelOfStudy');
    }

    public function getLevelOfStudyFilterInstitutionSearch() {

        $user = $this->student;
        $levelOfStudy = LevelOfStudy::all();
        foreach ($levelOfStudy as $key => $val) {
            $allInstitutions = Institution::has('getInstitutionAdmin')
                    ->where([
                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                        'approval' => Institution::APPROVAL,
                        'verification' => Institution::VERIFICATION_PASS
                    ])
                    ->where(['type' => Institution::TYPE_TRAINING])
                    ->whereHas('getBranch', function($getBranch) use ($user, $val) {
                $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($user, $val) {
                    $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($user, $val) {
                        $getCourseData = $getCourseData->where([
                            'visibility' => Courses::PUBLISHED,
                            'approval' => Courses::APPROVED
                        ]);
                        if ($this->student) {
                            $getCourseData = $this->studentPreferredSearch($getCourseData, $user);
                        }
                        $getCourseData = $getCourseData->where(['LevelofStudy_id' => $val->id]);
                        return $getCourseData;
                    });
                    //check if course intake deadline is reached
                    $getIntakeReturn = $getIntakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                    return $getIntakeReturn;
                });
                return $getBranchReturn;
            });
            $levelOfStudy[$key]['totalCourses'] = $allInstitutions->count();
        }
        return $levelOfStudy;
    }

    public function getLocationFilterInstitutionSearch() {
        $cities = City::whereHas('getState', function($q) {
                    return $q->has('getCountry');
                })->limit(City::LIMIT_LIST)
                ->get();
        $user = $this->student;
        foreach ($cities as $ckey => $city) {
            $cityId = $city->id;
            $allInstitutions = Institution::has('getInstitutionAdmin')
                    ->where([
                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                        'approval' => Institution::APPROVAL,
                        'verification' => Institution::VERIFICATION_PASS
                    ])
                    ->where(['type' => Institution::TYPE_TRAINING])
                    ->whereHas('getBranch', function($getBranch) use ($user, $cityId) {
                $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($user) {
                    $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ( $user) {
                        $getCourseData->where([
                            'visibility' => Courses::PUBLISHED,
                            'approval' => Courses::APPROVED
                        ]);
                        if ($this->student) {
                            $getCourseData = $this->studentPreferredSearch($getCourseData, $user);
                        }
                        return $getCourseData;
                    });
                    //check if course intake deadline is reached
                    $getIntakeReturn = $getIntakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                    return $getIntakeReturn;
                });
                $getBranchReturn = $getBranchReturn->where(['City_id' => $cityId]);
                return $getBranchReturn;
            });
            $cities[$ckey]['totalCourses'] = $allInstitutions->count();
        }
        return $cities;
    }

    public function getSubdisciplineFilterInstitutionSearch() {
        $user = $this->student;

        $subdisciplines = Subdiscipline::has('getFieldofStudy')->get();
        foreach ($subdisciplines as $key => $val) {
            $allInstitutions = Institution::has('getInstitutionAdmin')
                    ->where([
                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                        'approval' => Institution::APPROVAL,
                        'verification' => Institution::VERIFICATION_PASS
                    ])
                    ->where(['type' => Institution::TYPE_TRAINING])
                    ->whereHas('getBranch', function($getBranch) use ($user, $val) {
                $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($user, $val) {
                    $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ( $user, $val) {
                        $getCourseData->where([
                            'visibility' => Courses::PUBLISHED,
                            'approval' => Courses::APPROVED
                        ]);
                        if ($this->student) {
                            $getCourseData = $this->studentPreferredSearch($getCourseData, $user);
                        }
                        $cId = $val->id;
                        $getCourseData = $getCourseData->whereHas('getCourseSubdiscipline', function($q) use ($cId) {
                            return $q->where(['subdiscipline_id' => $cId]);
                        });
                        return $getCourseData;
                    });
                    //check if course intake deadline is reached
                    $getIntakeReturn = $getIntakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                    return $getIntakeReturn;
                });
                return $getBranchReturn;
            });
            $subdisciplines[$key]['totalCourses'] = $allInstitutions->count();
        }
        return $subdisciplines;
    }

    public function getTotalIntakeDateInstitutionCount($icyData) {
        $user = $this->student;
        $allInstitutions = Institution::has('getInstitutionAdmin')
                ->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->where(['type' => Institution::TYPE_TRAINING])
                ->whereHas('getBranch', function($getBranch) use ($user, $icyData) {
            $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($user, $icyData) {
                $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($user) {
                    $getCourseData->where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ]);
                    if ($this->student) {
                        $getCourseData = $this->studentPreferredSearch($getCourseData, $user);
                    }
                    return $getCourseData;
                });
                $icyData = explode('-', $icyData);
                if ($icyData[0]) {
                    $icyMonth = $icyData[0];
                    $getIntakeReturn = $getIntakeReturn->whereYear('commencementDate', '=', $icyMonth);
                }
                if (isset($icyData[1])) {
                    $icyYear = $icyData[1];
                    $getIntakeReturn = $getIntakeReturn->whereMonth('commencementDate', '=', $icyYear);
                }
                //check if course intake deadline is reached
                $getIntakeReturn = $getIntakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                return $getIntakeReturn;
            });
            return $getBranchReturn;
        });
        return $allInstitutions->count();
    }

    public function getIntakeFilterInstitutionSearch() {
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
            $getCYD['totalCourses'] = $this->getTotalIntakeDateInstitutionCount($icyData);
            $currentYearDetail[] = $getCYD;
        }

        for ($m = 1; $m <= 12; $m++) {
            $inyData = date('Y-m-d', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year'))));
            $getNYD = [
                'monthName' => date('F', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year')))),
                'date' => $inyData,
            ];
            $getNYD['totalCourses'] = $this->getTotalIntakeDateInstitutionCount($inyData);
            $nextYearDetail[] = $getNYD;
        }
        return [$currentYearDetail, $nextYearDetail];
    }

    public function studentPreferredSearch($getCourseData, $user) {
        $getCourseData = $getCourseData->where(function($orWhere) use ($user) {
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
        return $getCourseData;
    }

}

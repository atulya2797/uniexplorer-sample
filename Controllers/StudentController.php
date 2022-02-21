<?php

namespace App\Http\Controllers;

use Auth;
use App\Model\User;
use App\Model\City;
use App\Model\Year;
use App\Model\File;
use App\Model\Month;
use App\Model\State;
use App\Model\Image;
use App\Model\Intake;
use App\Helper\Common;
use App\Model\Courses;
use App\Model\Country;
use App\Model\Student;
use App\Model\Facility;
use App\Model\MonthYear;
use App\Model\Condition;
use App\Model\Scholarship;
use App\Model\Institution;
use App\Model\Requirement;
use App\Model\LevelOfStudy;
use App\Model\FieldOfStudy;
use App\Model\DesiredIntake;
use App\Model\Subdiscipline;
use App\Model\ConditionRange;
use App\Model\DesiredLocation;
use App\Model\BranchFacilities;
use App\Model\ApplicationIntake;
use App\Model\ShortlistedCourses;
use App\Model\DesiredLevelOfStudy;
use App\Model\DesiredSubdiscipline;
use App\Model\FormFiles;
use App\Model\ApplicationIntakeFile;
use Illuminate\Support\Facades\Input;
use App\Http\Requests\StudentProfile;
use App\Http\Requests\StudentPreferences;
use App\Http\Requests\studentApplication\SubmitApplicationForm;

class StudentController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function myAccount() {

        $compactData = $this->getCourseListingData();
        if (\Request::ajax()) {
            return view('student.allCourseListingSection', $compactData);
        }

        return view('student.myAccount', $compactData);
    }

    public function getCourseListingData() {

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
        //get all course listing 
        $input = Input::all();
        $user = $this->student;
        $defaultTuitionFeesPA = (!empty($Intake_tuitionFeesPA)) ? max($Intake_tuitionFeesPA) : 0;
        $tuitionFeesPA = isset($input['tuitionFeesPA']) ? $input['tuitionFeesPA'] : $defaultTuitionFeesPA;
        $defaultTuitionFeesEP = (!empty($Intake_tuitionFeesEP)) ? max($Intake_tuitionFeesEP) : 0;
        $tuitionFeesEP = isset($input['tuitionFeesEP']) ? $input['tuitionFeesEP'] : $defaultTuitionFeesEP;

        //sorting
        $sorting = isset($input['sorting']) ? $input['sorting'] : 'institutionRanking';
        //sorting
        // check if all selected
        $allCourseIntake = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])
                ->whereHas('getIntakes', function($query) use ($tuitionFeesPA, $tuitionFeesEP, $sorting) {
                    $intakeReturn = $query->whereHas('getIntakeBranch', function($instQuery) use ($sorting) {
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
                        return $instQ;
                    });
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
                    return $intakeReturn;
                })
                ->whereHas('getEditedBy', function($editedByQuery) {
            return $editedByQuery->where(function($checkRoleQuery) {
                        return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                    });
        });
        // if ($this->student) {
        //     $allCourseIntake = $this->studentPreferredSearch($allCourseIntake, $user);
        // }

        $countAllCourseIntake = $allCourseIntake->count();
        $allCourseIntake = $allCourseIntake->paginate(Common::PAGINATION);

        //compare courses section
        list($Institution_listing, $Courses_Listing) = $this->getCourseCompareData();

        //get all courses listing end
        return compact('allCourseIntake', 'user', 'tutionFeesFilter', 'countAllCourseIntake', 'Institution_listing', 'Courses_Listing');
    }

    public function studentPreferredSearch($allCourseIntake, $user) {
        $allCourseIntake = $allCourseIntake->where(function($orWhere) use ($user) {
            $orWhereData = $orWhere->whereHas('getShortlistedCourses', function($q) use($user) {
                return $q->where(['Student_id' => $user->getStudent->id]);
            });
            $columnName = $user->getStudent->EnglishLanguageTest ? strtoupper($user->getStudent->EnglishLanguageTest) : NULL;
            if ($columnName) {
                $orWhereData = $orWhereData->orWhere($columnName, '<=', $user->getStudent->EnglishLanguageTestScore);
            }
            return $orWhereData;
        });
        return $allCourseIntake;
    }

    public function getCourseCompareData() {
        $Institution_listing = $this->filterAllInstitution()->get();
        $CompareCourseInstitution = $this->filterAllInstitution()->first();
        $inst_id = isset($CompareCourseInstitution->id) ? $CompareCourseInstitution->id : '';
        $course_data = [];
        if (isset($CompareCourseInstitution)) {
            for ($i = 0; $i < 3; $i++) {
                $compareCourses_Listing = $this->filterCourse($inst_id)->first();
                if (isset($compareCourses_Listing)) {
                    $course_data[] = $compareCourses_Listing->id;
                }
            }
        }
        $Courses_Listing = $this->filterCourse()->get();
        return [$Institution_listing, $Courses_Listing];
    }

    public function myProfile() {
        $userData = $this->student;
        $LevelOfStudy = LevelOfStudy::all();
        $Pre_entrance_levelofstudy = LevelOfStudy::where(['Pre_entrance_exam'=>1])->get();
        $FieldOfStudy = FieldOfStudy::all();
        $Subdiscipline = Subdiscipline::all();
        $Countries = Country::all();
        $StudentCountry = Country::where(['id' => $userData->country_code])->first();
        $StudentData = $userData->getStudent;

        if (!$StudentData) {
            abort(404);
        }

        $StudentLevelOfStudy = '';
        if (isset($StudentData->LevelofStudy_id))
            $StudentLevelOfStudy = LevelOfStudy::where(['id' => $StudentData->LevelofStudy_id])->first();

        $StudentFieldOfStudy = '';
        $StudentSubdiscipline = '';
        if (isset($StudentData->Subdiscipline_id)) {
            $StudentSubdiscipline = Subdiscipline::where(['id' => $StudentData->Subdiscipline_id])->first();
            $StudentFieldOfStudy = FieldOfStudy::where(['id' => $StudentSubdiscipline->FieldofStudy_id])->first();
        }

        $StudentFieldOfWorkExperience = '';
        $StudentSubdisciplineExperience = '';
        if (isset($StudentData->Subdiscipline_exp)) {
            $StudentSubdisciplineExperience = Subdiscipline::where(['id' => $StudentData->Subdiscipline_exp])->first();
            $StudentFieldOfWorkExperience = FieldOfStudy::where(['id' => $StudentSubdisciplineExperience->FieldofStudy_id])->first();
        }

        $StudentPreEntranceExams = '';
        if (isset($StudentData->PreEntranceExams))
            $StudentPreEntranceExams = LevelOfStudy::where(['id' => $StudentData->PreEntranceExams])->first();

        return view('student.myProfile', compact('userData', 'StudentData', 'LevelOfStudy', 'FieldOfStudy', 'Subdiscipline', 'StudentLevelOfStudy', 'StudentFieldOfStudy', 'StudentFieldOfWorkExperience', 'StudentCountry', 'Countries', 'StudentPreEntranceExams', 'StudentSubdiscipline', 'StudentSubdisciplineExperience','Pre_entrance_levelofstudy'));
    }

    public function saveMyProfileData(StudentProfile $request) {
        $input = Input::all();
        $user = $this->student;
        $Student = $user->getStudent;
        if (!$Student) {
            abort(404);
        }

        $UserData['firstName'] = $input['firstName'];
        unset($input['firstName']);
        $UserData['lastName'] = $input['lastName'];
        unset($input['lastName']);
        unset($input['FieldOfStudy_id']);
        unset($input['fieldOfStudy_exp']);
        $UserData['country_code'] = $input['Country_id'];
        User::where(['id' => $user->id])->update($UserData);
        Student::where(['User_id' => $user->id])->update($input);

        return $this->sendResponse(true, '', 'Data successfully save', route('myProfile'));
    }

    public function myPreferences() {
        $userData = $this->student;
        $LevelOfStudy = LevelOfStudy::all();
        $FieldOfStudy = FieldOfStudy::all();
        $Subdiscipline = Subdiscipline::all();
        $Months = Month::all();
        $Courses = Courses::all();
        if (isset($userData->country_code)) {
            $country_code = $userData->country_code;
            $Country = Country::where(['id' => $country_code])->first();
        }

        $Cities = [];
        if (isset($Country)) {
            $State = State::where(['Country_id' => $Country->id])->first();
            if (isset($State))
                $Cities = City::where(['State_id' => $State->id])->limit(City::LIMIT_LIST)->get();
        } else {
            $Cities = City::limit(City::LIMIT_LIST)->get();
        }

        $StudentData = $userData->getStudent;

        if (!$StudentData) {
            abort(404);
        }
        $Student_id = $StudentData->id;

        $StudentLevelOfStudy = [];
        $DesiredLevelOfStudy = DesiredLevelOfStudy::where(['Student_id' => $Student_id])->get();
        foreach ($DesiredLevelOfStudy as $dlos) {
            $StudentLevelOfStudy[] = LevelOfStudy::where(['id' => $dlos->LevelofStudy_id])->first();
        }

        $StudentFieldOfStudy = [];
        $StudentSubdiscipline = [];
        $DesiredSubdiscipline = DesiredSubdiscipline::where(['Student_id' => $Student_id])->get();
        foreach ($DesiredSubdiscipline as $ds) {
            $StudentSubdiscipline[] = Subdiscipline::where(['id' => $ds->Subdiscipline_id])->first();
            $StudentFieldOfStudy[] = Fieldofstudy::where(['id' => $ds->Fieldofstudy_id])->first();
        }

        $DesiredIntakes = DesiredIntake::where(['Student_id' => $Student_id])->get();

        $StudentCurrentYearMonth = [];
        $StudentNextYearMonth = [];
        $StudentYear = '';
        $StudentCurrentYear = '';
        $StudentNextYear = '';
        foreach ($DesiredIntakes as $DesiredIntake) {
            $MonthYear = MonthYear::where(['id' => $DesiredIntake->MonthYear_id])->first();
            $StudentYear = Year::where(['id' => $MonthYear->Year_id])->first();
            if ($StudentYear->name == date('Y')) {
                $StudentCurrentYear = $StudentYear->name;
                $StudentCurrentYearMonth[] = Month::where(['id' => $MonthYear->Month_id])->first();
            } elseif (date('Y', strtotime('+1 year'))) {
                $StudentNextYear = $StudentYear->name;
                $StudentNextYearMonth[] = Month::where(['id' => $MonthYear->Month_id])->first();
            }
        }

        $StudentCurrentYearMonths = [];
        foreach ($StudentCurrentYearMonth as $values) {
            $StudentCurrentYearMonths[] = $values->name;
        }

        $StudentNextYearMonths = [];
        foreach ($StudentNextYearMonth as $values) {
            $StudentNextYearMonths[] = $values->name;
        }

        $ShortlistedCourses = ShortlistedCourses::where(['Student_id' => $Student_id])->get();
        $StudentShortlistedCourses = [];
        foreach ($ShortlistedCourses as $ShortlistedCourse) {
            $StudentShortlistedCourses[] = Courses::where(['id' => $ShortlistedCourse->Course_id])->first();
        }

        $StudentShortlistedCourseName = [];
        foreach ($StudentShortlistedCourses as $StudentShortlistedCourse) {
            $StudentShortlistedCourseName[] = $StudentShortlistedCourse->name;
        }

        $DesiredLocations = DesiredLocation::where(['Student_id' => $Student_id])->get();
        $StudentDesiredLocations = [];
        if (isset($DesiredLocations)) {
            foreach ($DesiredLocations as $DesiredLocation) {
                $StudentDesiredLocations[] = City::where(['id' => $DesiredLocation->City_id])->first();
            }
        }

        $StudentDesiredLocations = array_unique($StudentDesiredLocations);
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterCourseSearch();

        return view('student.myPreferences', compact('userData', 'LevelOfStudy', 'DesiredSubdiscipline', 'FieldOfStudy', 'Courses', 'Subdiscipline', 'StudentLevelOfStudy', 'StudentFieldOfStudy', 'Cities', 'StudentShortlistedCourses', 'StudentDesiredLocations', 'Months', 'StudentShortlistedCourseName', 'currentYearDetail', 'nextYearDetail', 'StudentSubdiscipline', 'StudentCurrentYearMonths', 'StudentNextYearMonths', 'StudentCurrentYear', 'StudentNextYear'));
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
            $currentYearDetail[] = $getCYD;
        }

        for ($m = 1; $m <= 12; $m++) {
            $inyData = date('Y-m-d', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year'))));
            $getNYD = [
                'monthName' => date('F', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year')))),
                'date' => $inyData,
            ];
            $nextYearDetail[] = $getNYD;
        }
        return [$currentYearDetail, $nextYearDetail];
    }

    public function addLevelOfStudy() {
        $LevelOfStudy = LevelOfStudy::all();
        return $LevelOfStudy;
    }

    public function saveMyPreferences(StudentPreferences $request) {
        $input = Input::all();
        $user = $this->student;
        $Student = $user->getStudent;
        if (!$Student) {
            abort(404);
        }
        $input['Student_id'] = $Student->id;

        if (isset($input['LevelofStudy'])) {
            $DesiredLevelOfStudyOld = DesiredLevelOfStudy::where(['Student_id' => $input['Student_id']])->delete();
            foreach ($input['LevelofStudy'] as $key => $los) {
                $LevelofStudy_data = LevelofStudy::where(['id' => $los])->first();
                if (isset($LevelofStudy_data)) {
                    $DesiredLevelOfStudyInput['LevelofStudy_id'] = $LevelofStudy_data->id;
                    $DesiredLevelOfStudyInput['Student_id'] = $input['Student_id'];
                    DesiredLevelOfStudy::create($DesiredLevelOfStudyInput);
                }
            }
        }

        $DesiredSubdisciplineOld = DesiredSubdiscipline::where(['Student_id' => $input['Student_id']])->delete();
        if (isset($input['FieldofStudy']) && isset($input['subdiscipline'])) {
            $subdiscipline_val = $input['subdiscipline'];
            $FieldofStudy_val = $input['FieldofStudy'];

            foreach ($FieldofStudy_val as $key_fos => $value_fos) {
                $DesiredSubdisciplineInput['Student_id'] = $input['Student_id'];
                $DesiredSubdisciplineInput['Subdiscipline_id'] = $subdiscipline_val[$key_fos];
                $DesiredSubdisciplineInput['Fieldofstudy_id'] = $value_fos;
                DesiredSubdiscipline::create($DesiredSubdisciplineInput);
            }
            // remove duplicate value from the set of field of study and subdiscipline
            // foreach ($FieldofStudy_val as $key => $value) {
            //     $val[] = $value . '_' . $subdiscipline_val[$key];
            // }
            // $data_value = array_unique($val);
            // foreach ($data_value as $key => $valfos) {
            //     $fos = explode("_", $valfos);
            //     $subdiscipline[] = $fos[1];
            //     $FieldofStudy[] = $fos[0];
            // }
            // if (count($DesiredSubdisciplineOld) <= count($subdiscipline)) {
            //     foreach ($subdiscipline as $key => $sub) {
            //         $Subdiscipline = Subdiscipline::where(['id' => $sub])->first();
            //         if (isset($Subdiscipline)) {
            //             if (isset($FieldofStudy[$key]))
            //                 $DesiredSubdisciplineInput['Fieldofstudy_id'] = $FieldofStudy[$key];
            //             $DesiredSubdisciplineInput['Student_id'] = $input['Student_id'];
            //             $DesiredSubdisciplineInput['Subdiscipline_id'] = $sub;

            //             if (count($DesiredSubdisciplineOld) > $key) {
            //                 DesiredSubdiscipline::where(['id' => $DesiredSubdisciplineOld[$key]->id])->update($DesiredSubdisciplineInput);
            //             } else {
            //                 DesiredSubdiscipline::create($DesiredSubdisciplineInput);
            //             }
            //         }
            //     }
            // } else {
            //     foreach ($DesiredSubdisciplineOld as $key => $ds) {
            //         if (isset($subdiscipline[$key])) {
            //             $sub = $subdiscipline[$key];
            //             $Subdiscipline = Subdiscipline::where(['id' => $sub])->first();
            //             if (isset($Subdiscipline)) {
            //                 if (isset($FieldofStudy[$key]))
            //                     $DesiredSubdisciplineInput['Fieldofstudy_id'] = $FieldofStudy[$key];
            //                 $DesiredSubdisciplineInput['Student_id'] = $input['Student_id'];
            //                 $DesiredSubdisciplineInput['Subdiscipline_id'] = $sub;

            //                 if (count($DesiredSubdisciplineOld) > $key) {
            //                     DesiredSubdiscipline::where(['id' => $DesiredSubdisciplineOld[$key]->id])->update($DesiredSubdisciplineInput);
            //                 } else {
            //                     DesiredSubdiscipline::create($DesiredSubdisciplineInput);
            //                 }
            //             }
            //         } else {
            //             DesiredSubdiscipline::where(['id' => $ds->id])->delete();
            //         }
            //     }
            // }
        }

        // if (isset($input['courses'])) {
        //     $ShortlistedCourses = ShortlistedCourses::where(['Student_id' => $input['Student_id']])->get();
        //     $Courses = array_unique($input['courses']);
        //     if (count($ShortlistedCourses) < count($Courses)) {
        //         foreach ($Courses as $key => $course) {
        //             $Courses = Courses::where(['id' => $course])->first();
        //             if (isset($Courses)) {
        //                 $input['Course_id'] = $Courses->id;
        //                 $input['Student_id'] = $input['Student_id'];
        //                 if (isset($ShortlistedCourses[$key])) {
        //                     $shortlistedCoursesData['Course_id'] = $Courses->id;
        //                     $shortlistedCoursesData['Student_id'] = $input['Student_id'];
        //                     ShortlistedCourses::where(['id' => $ShortlistedCourses[$key]['id']])->update($shortlistedCoursesData);
        //                 } else {
        //                     ShortlistedCourses::create($input);
        //                 }
        //             }
        //         }
        //     } else {
        //         foreach ($ShortlistedCourses as $key => $ShortlistedCourse) {
        //             if (isset($Courses[$key])) {
        //                 $course = $Courses[$key];
        //                 $Courses = Courses::where(['id' => $course])->first();
        //                 if (isset($Courses)) {
        //                     $input['Course_id'] = $Courses->id;
        //                     $input['Student_id'] = $input['Student_id'];
        //                     if (isset($ShortlistedCourses[$key])) {
        //                         $shortlistedCoursesData['Course_id'] = $Courses->id;
        //                         $shortlistedCoursesData['Student_id'] = $input['Student_id'];
        //                         ShortlistedCourses::where(['id' => $ShortlistedCourses[$key]['id']])->update($shortlistedCoursesData);
        //                     } else {
        //                         ShortlistedCourses::create($input);
        //                     }
        //                 }
        //             } else {
        //                 ShortlistedCourses::where(['id' => $ShortlistedCourse->id])->delete();
        //             }
        //         }
        //     }
        // }

        if (isset($input['DesiredIntake'])) {
            $DesiredIntakePrev = DesiredIntake::where(['Student_id' => $input['Student_id']])->get();
            if (isset($input['months'])) {
                if (count($DesiredIntakePrev) < count($input['months'])) {
                    foreach ($input['months'] as $key => $date) {
                        $date_year = date('Y', strtotime($date));
                        $date_month = date('F', strtotime($date));
                        $Year = Year::where(['name' => $date_year])->first();
                        if (!isset($Year)) {
                            $Year_data['name'] = $date_year;
                            $Year = Year::create($Year_data);
                        }
                        $input['Year_id'] = $Year->id;

                        $month = Month::where(['name' => $date_month])->first();
                        $input['Month_id'] = $month->id;

                        $MonthYear = MonthYear::where(['Month_id' => $input['Month_id']])->where(['Year_id' => $input['Year_id']])->first();
                        if (isset($MonthYear)) {
                            if (count($DesiredIntakePrev) > $key) {
                                $desiredIntakeData['MonthYear_id'] = $MonthYear->id;
                                DesiredIntake::where(['id' => $DesiredIntakePrev[$key]->id])->update($desiredIntakeData);
                            } else {
                                $input['MonthYear_id'] = $MonthYear->id;
                                DesiredIntake::create($input);
                            }
                        } else {
                            MonthYear::create($input);
                            $MonthYear = MonthYear::where(['Month_id' => $input['Month_id']])->where(['Year_id' => $input['Year_id']])->first();
                            if (isset($MonthYear)) {
                                $input['MonthYear_id'] = $MonthYear->id;
                                DesiredIntake::create($input);
                            }
                        }
                    }
                } else {
                    foreach ($DesiredIntakePrev as $key => $DesiredIntake) {
                        if (isset($input['months'][$key])) {
                            $date = $input['months'][$key];
                            $date_year = date('Y', strtotime($date));
                            $date_month = date('F', strtotime($date));
                            $Year = Year::where(['name' => $date_year])->first();
                            if (!isset($Year)) {
                                $Year_data['name'] = $date_year;
                                $Year = Year::create($Year_data);
                            }
                            $input['Year_id'] = $Year->id;

                            $month = Month::where(['name' => $date_month])->first();
                            $input['Month_id'] = $month->id;

                            //$input['Month_id'] = $input['months'][$key];
                            $MonthYear = MonthYear::where(['Month_id' => $input['Month_id']])->where(['Year_id' => $input['Year_id']])->first();
                            if (isset($MonthYear)) {
                                if (count($DesiredIntakePrev) > $key) {
                                    $desiredIntakeData['MonthYear_id'] = $MonthYear->id;
                                    DesiredIntake::where(['id' => $DesiredIntake->id])->update($desiredIntakeData);
                                } else {
                                    $input['MonthYear_id'] = $MonthYear->id;
                                    DesiredIntake::create($input);
                                }
                            } else {
                                MonthYear::create($input);
                                $MonthYear = MonthYear::where(['Month_id' => $input['Month_id']])->where(['Year_id' => $input['Year_id']])->first();
                                if (isset($MonthYear)) {
                                    $input['MonthYear_id'] = $MonthYear->id;
                                    DesiredIntake::create($input);
                                }
                            }
                        } else {
                            DesiredIntake::where(['id' => $DesiredIntake->id])->delete();
                        }
                    }
                }
            } else {
                DesiredIntake::where(['Student_id' => $input['Student_id']])->delete();
            }
        }

        if (isset($input['City'])) {
            $DesiredLocationPrev = DesiredLocation::where(['Student_id' => $input['Student_id']])->get();
            $Cities = array_unique($input['City']);
            if (count($DesiredLocationPrev) < count($Cities)) {
                foreach ($Cities as $key => $city) {
                    $City = City::where(['id' => $city])->first();
                    if (isset($City)) {
                        $input['City_id'] = $City->id;
                        $input['state_id'] = $City->State_id;
                        if (isset($DesiredLocationPrev[$key])) {
                            $DesiredLocationData['City_id'] = $input['City_id'];
                            $DesiredLocationData['state_id'] = $input['state_id'];
                            DesiredLocation::where(['id' => $DesiredLocationPrev[$key]['id']])->update($DesiredLocationData);
                        } else {
                            DesiredLocation::create($input);
                        }
                    }
                }
            } else {
                foreach ($DesiredLocationPrev as $key => $DesiredLocation) {
                    if (isset($Cities[$key])) {
                        $city = $Cities[$key];
                        $City = City::where(['id' => $city])->first();
                        if (isset($City)) {
                            $input['City_id'] = $City->id;
                            $input['state_id'] = $City->State_id;
                            if (isset($DesiredLocationPrev[$key])) {
                                $DesiredLocationData['City_id'] = $input['City_id'];
                                $DesiredLocationData['state_id'] = $input['state_id'];
                                DesiredLocation::where(['id' => $DesiredLocation->id])->update($DesiredLocationData);
                            } else {
                                DesiredLocation::create($input);
                            }
                        }
                    } else {
                        DesiredLocation::where(['id' => $DesiredLocation->id])->delete();
                    }
                }
            }
        }
        return $this->sendResponse(true, '', 'Preferences updated successfully', route('myPreferences'));
    }

    public function compareCourses() {
        $input = Input::all();
        $Institution_listing = $this->filterAllInstitution()->get();
        $CompareCourseInstitution = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->first();

        $inst_id = isset($CompareCourseInstitution->id) ? $CompareCourseInstitution->id : '';

        $institute_data = [];
        $Courses_Listing = Courses::where([
                            'visibility' => Courses::PUBLISHED,
                            'approval' => Courses::APPROVED
                        ])
                        ->whereHas('getIntakes', function($query) use ($inst_id) {
                            return $query->whereHas('getIntakeBranch', function($instQuery) use ($inst_id) {
                                        return $instQuery->whereHas('getInstitution', function($instQuery) use ($inst_id) {
                                                    return $instQuery->where([
                                                                'id' => $inst_id,
                                                                'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                                'approval' => Institution::APPROVAL,
                                                                'verification' => Institution::VERIFICATION_PASS
                                                            ])
                                                            ->has('getInstitutionAdmin');
                                                });
                                    })->where('applicationDeadlineDate', '>', date("Y-m-d"));
                        })->whereHas('getEditedBy', function($editedByQuery) {
                    return $editedByQuery->where(function($checkRoleQuery) {
                                return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                                ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                            });
                })->get();

        $courses_1 = '';
        $courses_2 = '';
        $courses_3 = '';
        $Institution_1 = '';
        $Institution_2 = '';
        $Institution_3 = '';
        $course_data = [];
        if (!isset($input['course_data'])) {
            if (isset($CompareCourseInstitution)) {
                for ($i = 0; $i < 3; $i++) {
                    $Institution_data[] = $CompareCourseInstitution->id;

                    $compareCourses_Listing = Courses::where([
                                        'visibility' => Courses::PUBLISHED,
                                        'approval' => Courses::APPROVED
                                    ])
                                    ->whereHas('getIntakes', function($query) use ($Institution_data, $i) {
                                        return $query->whereHas('getIntakeBranch', function($instQuery) use ($Institution_data, $i) {
                                                    return $instQuery->whereHas('getInstitution', function($instQuery) use ($Institution_data, $i) {
                                                                return $instQuery->where([
                                                                            'id' => $Institution_data[$i],
                                                                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                                            'approval' => Institution::APPROVAL,
                                                                            'verification' => Institution::VERIFICATION_PASS
                                                                        ])
                                                                        ->has('getInstitutionAdmin');
                                                            });
                                                })->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                    })->whereHas('getEditedBy', function($editedByQuery) {
                                return $editedByQuery->where(function($checkRoleQuery) {
                                            return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                                            ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                                        });
                            })->first();
                    if (isset($compareCourses_Listing)) {
                        $course_data[] = $compareCourses_Listing->id;
                    }
                }
            }
        } else {

            $course_data = $input['course_data'];
            foreach ($course_data as $key => $value) {
                if ($key == 0)
                    $courses_1 = Courses::where(['id' => $value])->first();
                if ($key == 1)
                    $courses_2 = Courses::where(['id' => $value])->first();
                if ($key == 2)
                    $courses_3 = Courses::where(['id' => $value])->first();
            }

            $Institution_data = $input['institute_data'];
            foreach ($Institution_data as $key => $value) {
                if ($key == 0)
                    $Institution_1 = Institution::where(['id' => $value])->first();
                if ($key == 1)
                    $Institution_2 = Institution::where(['id' => $value])->first();
                if ($key == 2)
                    $Institution_3 = Institution::where(['id' => $value])->first();
            }
        }

        list($Facilities, $institute_data) = $this->getCourseDetailData($course_data, $Institution_data);

        return view('student.compareCourses', compact('Institution_listing', 'institute_data', 'Courses_Listing', 'Facilities', 'courses_1', 'courses_2', 'courses_3', 'Institution_1', 'Institution_2', 'Institution_3'));
    }

    public function getCourseData() {
        $input = Input::all();
        if (isset($input['Institution_Id'])) {
            $institutionId = $input['Institution_Id'];
            $CoursesData = $this->filterCourse($institutionId)->get();
            return $CoursesData;
        }
    }

    public function course_search() {
        $input = Input::all();
        $name = $input['search_text'];
        $courses = Courses::where('name', 'LIKE', '%' . $name . '%')
                        ->where([
                            'visibility' => Courses::PUBLISHED,
                            'approval' => Courses::APPROVED
                        ])->get();
        $course_name = [];
        foreach ($courses as $course) {
            $course_name[] = $course->name;
        }
        return $course_name;
    }

    public function city_search() {
        $input = Input::all();
        $name = $input['search_text'];
        $cities = City::where('name', 'LIKE', '%' . $name . '%')->limit(City::LIMIT_LIST)->get();
        $city_name = [];
        foreach ($cities as $city) {
            $city_name[] = $city->name;
        }
        return $city_name;
    }

    public function getCourseDetail() {
        $input = Input::all();
        $course_data = $input['course_data'];
        $Institution_data = $input['Institution_data'];

        list($Facilities, $institute_data) = $this->getCourseDetailData($course_data, $Institution_data);

        return view('student.compareCourseListing', compact('institute_data', 'Facilities'));
    }

    public function getIntakeDetail() {
        $input = Input::all();
        $id = $input['intake'];
        $Intake_data = Intake::where(['id' => $id])
                        ->where('applicationDeadlineDate', '>', date("Y-m-d"))->first();
        $Location = City::whereHas('getbranch', function($query) use($id) {
                    return $query->whereHas('getintake', function($intQuery) use($id) {
                                return $intQuery->where(['id' => $id]);
                            });
                })->first();
        $Intake_data['location'] = $Location ? $Location->name : '';

        return $Intake_data;
    }

    public function getCourseDetailData($course_data, $Institution_data) {
        $institute_data = [];
        if (isset($course_data) && isset($Institution_data)) {
            $Facilities = Facility::whereHas('getBranchFacilities', function($query) use ($course_data) {
                return $query->whereHas('getBranch', function($branchQuery) use ($course_data) {
                            return $branchQuery->whereHas('getintake', function($intQuery) use ($course_data) {
                                        return $intQuery->whereIn('Course_id' , $course_data);
                                    });
                        });
            })->get(['name','id'])->toArray();
        }

        if (isset($course_data) && isset($Institution_data)) {

            foreach ($course_data as $key => $courseId) {
                $institutionId = $Institution_data[$key];
                if (isset($courseId) && isset($institutionId)) {
                    $Institution = Institution::where(['id' => $institutionId])->first();

                    if (isset($Institution)) {
                        if (isset($Institution->QSCurrentNational)) {
                            $institute_data['QSCurrentNational_' . $key] = $Institution->QSCurrentNational;
                        }

                        if (isset($Institution->THECurrentNational)) {
                            $institute_data['THECurrentNational_' . $key] = $Institution->THECurrentNational;
                        }

                        $CoursesData = Courses::where([
                                            'visibility' => Courses::PUBLISHED,
                                            'approval' => Courses::APPROVED
                                        ])
                                        ->whereHas('getIntakes', function($query) use ($courseId) {
                                            return $query->whereHas('getIntakeBranch', function($intQuery) {
                                                        return $intQuery->whereHas('getInstitution', function($instQuery) {
                                                                    return $instQuery->where([
                                                                                'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                                                'approval' => Institution::APPROVAL,
                                                                                'verification' => Institution::VERIFICATION_PASS
                                                                            ])
                                                                            ->has('getInstitutionAdmin');
                                                                });
                                                    })->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                        })->where(['id' => $courseId])
                                        ->whereHas('getEditedBy', function($editedByQuery) {
                                            return $editedByQuery->where(function($checkRoleQuery) {
                                                        return $checkRoleQuery->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                                                ->orWhere(['Role_id' => User::INSTITUTION_USER]);
                                                    });
                                        })->first();

                        if (isset($CoursesData)) {
                            if (isset($CoursesData->id)) {
                                $institute_data['courseId_' . $key] = $CoursesData->id;
                            }
                            if (isset($CoursesData->overview)) {
                                $institute_data['overview_' . $key] = $CoursesData->overview;
                            }
                            if (isset($CoursesData->requirements)) {
                                $institute_data['requirements_' . $key] = $CoursesData->requirements;
                            }
                            if (isset($CoursesData->IELTS)) {

                                $institute_data['IELTS_' . $key] = $CoursesData->IELTS;
                            }
                            if (isset($CoursesData->TOEFL)) {
                                $institute_data['TOEFL_' . $key] = $CoursesData->TOEFL;
                            }
                            if (isset($CoursesData->PTE)) {
                                $institute_data['PTE_' . $key] = $CoursesData->PTE;
                            }
                            if (isset($CoursesData->CAE)) {
                                $institute_data['CAE_' . $key] = $CoursesData->CAE;
                            }

                            $Intake = Intake::whereHas('getIntakeBranch', function($query) use ($courseId, $institutionId) {
                                                return $query->where(['Institution_id' => $institutionId]);
                                            })->where('applicationDeadlineDate', '>', date("Y-m-d"))
                                            ->where(['Course_id' => $courseId])->first();

                            $Intake_date = Intake::whereHas('getIntakeBranch', function($query) use ($courseId, $institutionId) {
                                                return $query->where(['Institution_id' => $institutionId]);
                                            })->where('applicationDeadlineDate', '>', date("Y-m-d"))
                                            ->where(['Course_id' => $courseId])->get();

                            foreach ($Intake_date as $intake_date) {
                                if (isset($intake_date)) {
                                    $institute_data['Intake_date_' . $key][] = $intake_date;
                                }
                            }

                            if (isset($Intake)) {
                                $institute_data['Intake_' . $key] = $Intake;
                            }
                            if (isset($Intake->tuitionFeesPA)) {
                                $institute_data['tuitionFeesPA_' . $key] = $Intake->tuitionFeesPA;
                            }
                            if (isset($Intake->duration)) {
                                $institute_data['duration_' . $key] = $Intake->duration;
                            }
                            if (isset($Intake->commencementDate)) {
                                $institute_data['commencementDate_' . $key] = $Intake->commencementDate;
                            }
                            if (isset($Intake->tuitionFeesEP)) {
                                $institute_data['Intake_tuitionFeesEP_max' . $key] = $Intake->tuitionFeesEP;
                            }
                            if (isset($Intake->applicationDeadlineDate)) {
                                $institute_data['applicationDeadlineDate_' . $key] = $Intake->applicationDeadlineDate;
                            }
                            if (isset($Intake->tuitionFeesEP)) {
                                $institute_data['tuitionFeesEP_' . $key] = $Intake->tuitionFeesEP;
                            }

                            $Conditions = ConditionRange::whereHas('getRange', function($rangeQuery) use ($courseId) {
                                        return $rangeQuery->whereHas('getPathway', function($pathQuery) use ($courseId) {
                                                    return $pathQuery->whereHas('getRequirement', function($reqQuery) use ($courseId) {
                                                                return $reqQuery->where(['Course_id' => $courseId]);
                                                            });
                                                });
                                    })->get();

                            foreach ($Conditions as $Condition) {
                                if ($Condition->Condition_id === Condition::CGPA_LESS_THEN) {
                                    $institute_data['Minimum_Required_CGPA_' . $key] = $Condition->value;
                                } else if ($Condition->Condition_id === Condition::CGPA_EQUAL_TO_OR_GREATER_THAN) {
                                    $institute_data['Minimum_Required_CGPA_' . $key] = $Condition->value;
                                }

                                if ($Condition->Condition_id === Condition::WORKING_EXPERIENCE) {
                                    $institute_data['Minimum_Required_Professional_Requirements_' . $key] = $Condition->value;
                                }
                            }

                            if (!isset($institute_data['Minimum_Required_Professional_Requirements_' . $key])) {
                                $institute_data['Minimum_Required_Professional_Requirements_' . $key] = 'None';
                            }

                            if (!isset($institute_data['Minimum_Required_CGPA_' . $key])) {
                                $institute_data['Minimum_Required_CGPA_' . $key] = 'None';
                            }

                            $Location = City::whereHas('getbranch', function($query) use ($courseId, $institutionId) {
                                        $query->where(['Institution_id' => $institutionId]);
                                        return $query->whereHas('getintake', function($intQuery) use ($courseId) {
                                                    return $intQuery->where(['Course_id' => $courseId]);
                                                });
                                    })->first();

                            if (isset($Location->name)) {
                                $institute_data['Location_' . $key] = $Location->name;
                            }

                            $allImages = Image::whereHas('getGallery', function($query) use ($courseId, $institutionId) {
                                        $query->where(['Institution_id' => $institutionId]);
                                        return $query->whereHas('getInstitution', function($instQuery) use ($courseId) {
                                                    return $instQuery->whereHas('getBranch', function($branchQuery) use ($courseId) {
                                                                return $branchQuery->whereHas('getintake', function($intQuery) use ($courseId) {
                                                                            return $intQuery->where(['Course_id' => $courseId]);
                                                                        });
                                                            });
                                                });
                                    })->get();

                            foreach ($allImages as $value) {
                                $institute_data['Images_' . $key][] = $value->URL;
                            }

                            foreach ($Facilities as $keys => $facility_data) {
                                $allCoursesFacilities = Facility::where(['id' => $facility_data['id']])->whereHas('getBranchFacilities', function($query) use ($courseId) {
                                        return $query->whereHas('getBranch', function($branchQuery) use ($courseId) {
                                                    return $branchQuery->whereHas('getintake', function($intQuery) use ($courseId) {
                                                                return $intQuery->where(['Course_id' => $courseId]);
                                                            });
                                                });
                                    })->get();
                                if(count($allCoursesFacilities) > 0){
                                    $institute_data['CoursesFacilitiesData_' . $key][] = 'class=right_green';
                                }else{
                                    $institute_data['CoursesFacilitiesData_' . $key][] = 'class=right_gray';
                                }
                            }

                            $allScholarships = Scholarship::whereHas('getScholarshipIntake', function($q) use ($courseId) {
                                        $q->whereHas('getScholarshipIntakeId', function($query) use ($courseId) {
                                            return $query->whereHas('getIntakeId', function($qintake) use ($courseId) {
                                                        return $qintake->where(['Course_id' => $courseId]);
                                                    });
                                        });
                                    })->get();

                            foreach ($allScholarships as $keys => $scholarship) {
                                $institute_data['scholarship_' . $key][$keys]['name'] = $scholarship->name;
                                if ($scholarship->scholarshiptype == 0) {
                                    $institute_data['scholarship_' . $key][$keys]['scholarshiptype'] = 'Tuition';
                                } else {
                                    $institute_data['scholarship_' . $key][$keys]['scholarshiptype'] = 'Fixed';
                                }
                                $institute_data['scholarship_' . $key][$keys]['scholarshipvalue'] = $scholarship->scholarshipvalue;
                            }
                        }
                    }
                }
            }
        }
        return [$Facilities, $institute_data];
    }

    public function myApplications() {
        $user = Auth::user();
        $student_data = $user->getStudent()->first();
        $applicationIntake = ApplicationIntake::where('Student_id', $student_data->id)->get();
        return view('student.myApplications', compact('applicationIntake'));
    }

    public function applicationForm($id = null) {
        $applicationIntake = [];
        if ($id) {
            $studentId = Auth::user()->getStudent->id;
            $applicationIntake = ApplicationIntake::where(['id' => $id, 'Student_id' => $studentId])->first();
            if (!$applicationIntake) {
                abort(404);
            }
        } else {
           $input = Input::all();
           if (!isset($input['intake'])) {
               abort(404);
           }
           $checkIntake = Intake::where(['id' => $input['intake']])
                           ->whereHas('getIntakeBranch', function($query) {
                               return $query->whereHas('getInstitution', function($instQuery) {
                                           return $instQuery->has('getInstitutionAdmin');
                                       });
                           })->first();
           if (!$checkIntake) {
               abort(404);
           }
           $input['Student_id'] = Auth::user()->getStudent->id;
           $input['Intake_id'] = $input['intake'];
           $input['Intake_id'] = ApplicationIntake::VISIBILITY;
           $input['dateCreated'] = date("Y-m-d H:i:s", time());
           $input['step'] = ApplicationIntake::APPLICATION_STEP0;
           $applicationIntake = ApplicationIntake::create($input);
           return redirect()->route('applicationForm', $applicationIntake->id);
       }

       if (
               ($applicationIntake->InternalReviewerEligibility != ApplicationIntake::INTERNAL_REVIEW_ELIGIBLE) &&
               ($applicationIntake->InternalReviewerEligibility !== null) &&
               ($applicationIntake->step == ApplicationIntake::APPLICATION_STEP1)
       ) {
           abort(404);
       }
       if (
               ($applicationIntake->InstitutionEligibility != ApplicationIntake::INSTITUTION_ELIGIBILITY) &&
               ($applicationIntake->InstitutionEligibility !== null) &&
               ($applicationIntake->step == ApplicationIntake::APPLICATION_STEP4)
       ) {
           abort(404);
       }
       if (($applicationIntake->step >= ApplicationIntake::APPLICATION_STEP12)) {
           abort(404);
       }
       $countries = Country::all();
       $user = Auth::user();
       return view('student.applicationForm', compact('countries', 'user', 'applicationIntake'));
    }

    public function submitApplicationForm($id, SubmitApplicationForm $request) {
        $input = Input::all();
        //copy uploaded file
        $copiedFile = Common::copyTempFile($input['file'], 'studentApplication');
        $fileData['name'] = $copiedFile['name'];
        $fileData['URL'] = $copiedFile['url'];
        $input['Student_id'] = Auth::user()->getStudent->id;
        $input['step'] = ApplicationIntake::APPLICATION_STEP1;
        $input['dateSubmission'] = date("Y-m-d H:i:s", time());
        $applicationIntake = ApplicationIntake::find($id)->update($input);
        if ($applicationIntake) {
            $createdFile = File::create($fileData);
            $applicationIntakeData = [
                'File_id' => $createdFile->id,
                'Application_id' => $id,
                'type' => ApplicationIntakeFile::SUPPORTING_FILE
            ];
            ApplicationIntakeFile::create($applicationIntakeData);
        }
        $applicationIntake = ApplicationIntake::find($id);
        $title = 'A New Application Submitted By Student: ' . $this->student->firstName . ' ' . $this->student->lastName;
        $message = 'A Application Submitted for Course: ' . $applicationIntake->getIntakeData->getCourseData->name . ' and intake: ' . $applicationIntake->getIntakeData->commencementDate;
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], $title, $message);
        return $this->sendResponse(true, route('applicationForm', $id), 'Application Form Submit Success.');
    }

    public function applicationFeePaymentPageView($id) {
        $getApplicationIntake = ApplicationIntake::find($id);
        if (!$getApplicationIntake) {
            abort(404);
        }
        $getApplicationIntake->update(['step' => ApplicationIntake::APPLICATION_STEP3]);
        return $this->sendResponse(true, route('applicationForm', $id));
    }

    public function applicationFeePaymentSubmitReceipt($id) {
        $getApplicationIntake = ApplicationIntake::find($id);
        if (!$getApplicationIntake) {
            abort(404);
        }
        $input = Input::all();
        if (isset($input['skip']) && ($input['skip'] == 'skip')) {
            $getApplicationIntake->update(['step' => ApplicationIntake::APPLICATION_STEP6]);
            return $this->sendResponse(true, route('applicationForm', $id));
        }
        if (!isset($input['file']) || ($input['file'] == '')) {
            return $this->sendResponse(false, '', 'Please Select File');
        }
        $copiedFile = Common::copyTempFile($input['file'], 'studentApplication');
        $fileData['name'] = $copiedFile['name'];
        $fileData['URL'] = $copiedFile['url'];

        $createdFile = File::create($fileData);
        if ($createdFile) {
            $applicationIntakeData = [
                'File_id' => $createdFile->id,
                'Application_id' => $id,
                'type' => ApplicationIntakeFile::APPLICATION_FEE_PAYMENT_SLIP
            ];
            ApplicationIntakeFile::create($applicationIntakeData);
            $getApplicationIntake->update(['step' => ApplicationIntake::APPLICATION_STEP4]);
        }
        return $this->sendResponse(true, route('applicationForm', $id));
    }

    public function viewSupportiveDocumentForm($id) {
        $getApplicationIntake = ApplicationIntake::find($id);
        if (!$getApplicationIntake) {
            abort(404);
        }
        $getApplicationIntake->update(['step' => ApplicationIntake::APPLICATION_STEP8]);
        return $this->sendResponse(true, route('applicationForm', $id));
    }

    public function supportiveDocumentFormSubmit($id) {
        $getApplicationIntake = ApplicationIntake::find($id);
        if (!$getApplicationIntake) {
            abort(404);
        }
        $input = Input::all();
        //
        //check validation to upload and no of file to upload
        $types_nonCommon = $types_common = [];
        $file_count_for_application = FormFiles::where(['Intake_id' => $getApplicationIntake->Intake_id, 'ApplicationIntake_id' => $id]);
        $file_count_for_application_get = $file_count_for_application->get('type')->toArray();
        if ($file_count_for_application_get) {
            $types_nonCommon = array_column($file_count_for_application_get, 'type');
        }

        $file_count_for_all = FormFiles::where(['Intake_id' => $getApplicationIntake->Intake_id, 'ApplicationIntake_id' => null]);
        $file_count_for_all_get = $file_count_for_all->get('type')->toArray();
        if ($file_count_for_all_get) {
            $types_common = array_column($file_count_for_all_get, 'type');
        }
        $totalTypes = array_merge($types_nonCommon, $types_common);
        $total_file_count = count($types_nonCommon) + count($types_common);
        //count of student application

        $uploadedFile = ApplicationIntakeFile::whereIn('type', array_filter($totalTypes))
                ->whereHas('getApplicationIntake', function($q) use ($id) {
                    $q->where(['id' => $id]);
                })
                ->count();

        $totalRemailFile = $total_file_count - $uploadedFile;
        $totalOtherForm = ($totalRemailFile < 0) ? 0 : $totalRemailFile;

        if ($totalRemailFile > 0) {
            if (!isset($input['file']) || (count($input['file']) != $totalOtherForm)) {
                return $this->sendResponse(false, '', 'Please Select All Files');
            }
            foreach ($input['file'] as $file) {
                foreach ($file as $otherKey => $otherval) {
                    if ($otherKey == ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_OFFER_LETTER) {
                        $getApplicationIntake->update(['step' => ApplicationIntake::APPLICATION_STEP9]);
                    }
                    $this->uploadAllSupportiveFile($id, $otherval, $otherKey);
                }
            }
        } else {
            return $this->sendResponse(false, '', 'Wait for your offer latter');
        }

        $title = 'All Supportive Document Submitted By Student: ' . $this->student->firstName . ' ' . $this->student->lastName;
        $message = 'Supportive Documents Submitted for Course ' . $getApplicationIntake->getIntakeData->getCourseData->name . ' and intake ' . $getApplicationIntake->getIntakeData->commencementDate;
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], $title, $message);
        return $this->sendResponse(true, route('applicationForm', $id));
    }

    public function uploadAllSupportiveFile($applicationId, $file, $applicationIntakeFileType) {
        $copiedFile = Common::copyTempFile($file, 'studentApplication');
        $fileData['name'] = $copiedFile['name'];
        $fileData['URL'] = $copiedFile['url'];

        $createdFile = File::create($fileData);
        if ($createdFile) {
            $applicationIntakeData = [
                'File_id' => $createdFile->id,
                'Application_id' => $applicationId,
                'type' => $applicationIntakeFileType
            ];
            ApplicationIntakeFile::create($applicationIntakeData);
        }
    }

    public function tuitionFeePaymentSubmitReceipt($id) {
        $getApplicationIntake = ApplicationIntake::find($id);
        if (!$getApplicationIntake) {
            abort(404);
        }
        $input = Input::all();
        if (isset($input['skip']) && ($input['skip'] == 'skip')) {
//            return $this->sendResponse(true, route('applicationForm', $id));
            return $this->sendResponse(true, route('myApplications'));
        }
        if (!isset($input['file']) || ($input['file'] == '')) {
            return $this->sendResponse(false, '', 'Please Select File');
        }
        $copiedFile = Common::copyTempFile($input['file'], 'studentApplication');
        $fileData['name'] = $copiedFile['name'];
        $fileData['URL'] = $copiedFile['url'];

        $createdFile = File::create($fileData);
        if ($createdFile) {
            $applicationIntakeData = [
                'File_id' => $createdFile->id,
                'Application_id' => $id,
                'type' => ApplicationIntakeFile::TUITION_FEE_PAYMENT_SLIP
            ];
            ApplicationIntakeFile::create($applicationIntakeData);
            $getApplicationIntake->update(['step' => ApplicationIntake::APPLICATION_STEP10]);
        }
        return $this->sendResponse(true, route('applicationForm', $id));
    }
}

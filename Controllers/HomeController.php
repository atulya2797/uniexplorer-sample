<?php

namespace App\Http\Controllers;

use Auth;
use App\Model\Type;
use App\Model\City;
use App\Model\User;
use App\Model\State;
use App\Model\Branch;
use App\Model\Intake;
use App\Model\Country;
use App\Helper\Common;
use App\Model\Courses;
use App\Model\Student;
use App\Model\Enquiry;
use App\Model\Criteria;
use App\Model\Institution;
use App\Model\Scholarship;
use App\Model\FieldOfStudy;
use App\Model\LevelOfStudy;
use App\Model\Subdiscipline;
use App\Model\ScholarshipIntake;
use App\Model\ScholarshipProvider;
use App\Model\IntakeScholarshipIntake;
use Illuminate\Support\Facades\Input;
use App\Http\Requests\addEnquiry;

class HomeController extends Controller {

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
    public function index() {
        // $levelofstudy = LevelOfStudy::all();
        $levelofstudy = LevelOfStudy::whereHas('getCourses', function($query) {
            return $query->where(['visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED]);
        })->get();
        //print_r($levelofstudy);die;
        // $fieldofstudy = FieldOfStudy::all();
        $fieldofstudy = FieldOfStudy::whereHas('getSubdisipline', function($query) {
            return $query->whereHas('getSubdisplineCourse', function($query1) {
                return $query1->whereHas('getCourse', function($query2) {
                    return $query2->where(['visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED]);
                });
            });
        })->get();
        //print_r($fieldofstudy);die;
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterCourseSearch();
        $los_id = (count($levelofstudy) > 0) ? $levelofstudy[0]->id : '';
        $fos_id = (count($fieldofstudy) > 0) ? $fieldofstudy[0]->id : '';
        $subIds = [];
        if ($fos_id) {
            $subIds = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get('id')->toArray();
            $subdisipline_data = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get();
        }
        $allInstitutions = Institution::has('getInstitutionAdmin')
                        ->where([
                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                    'approval' => Institution::APPROVAL,
                                    'verification' => Institution::VERIFICATION_PASS
                                ])
                        ->where(['type' => Institution::TYPE_UNIVERSITY])
                        ->whereHas('getBranch', function($getBranch) use ($los_id) {
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($los_id) {
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($los_id) {
                                    if ($los_id) {
                                        return $getCourseData->where(['LevelofStudy_id' => $los_id]);
                                    }
                                    return $getCourseData;
                                });
                            });
                        })->limit(6)->get();

        foreach ($allInstitutions as $key => $institution) {
            // $query = Courses::whereHas('getCourseSubdiscipline', function($q) use ($subIds) {
            //             return $q->whereIn('subdiscipline_id', $subIds);
            //         });
            // if ($los_id) {
            //     $query = $query->where(['LevelofStudy_id' => $los_id]);
            // }
            // $query = $query->whereHas('getIntakes', function($getintake) use ($institution) {
            //     $getintakereturn = $getintake->whereHas('getIntakeBranch', function($getbranch) use ($institution) {
            //         $getbranch->where(['Institution_id' => $institution->id]);
            //     });
            // });
            // $courselisting = $query->get();
            $allInstitutions[$key]['subdisipline_data'] = $subdisipline_data;
        }

        $vetInstitutions = Institution::has('getInstitutionAdmin')
                        ->where([
                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                    'approval' => Institution::APPROVAL,
                                    'verification' => Institution::VERIFICATION_PASS
                                ])
                        ->where(['type' => Institution::TYPE_TRAINING])
                        ->whereHas('getBranch', function($getBranch) use ($los_id) {
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($los_id) {
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($los_id) {
                                    // if ($los_id) {
                                    //     return $getCourseData->where(['LevelofStudy_id' => $los_id]);
                                    // }
                                    return $getCourseData;
                                });
                            });
                        })->limit(6)->get();

        foreach ($vetInstitutions as $key => $institution) {
            // $query = Courses::whereHas('getCourseSubdiscipline', function($q) use ($subIds) {
            //             return $q->whereIn('subdiscipline_id', $subIds);
            //         });
            // if ($los_id) {
            //     $query = $query->where(['LevelofStudy_id' => $los_id]);
            // }
            // $query = $query->whereHas('getIntakes', function($getintake) use ($institution) {
            //     $getintakereturn = $getintake->whereHas('getIntakeBranch', function($getbranch) use ($institution) {
            //         $getbranch->where(['Institution_id' => $institution->id]);
            //     });
            // });
            // $courselisting = $query->get();
            $vetInstitutions[$key]['subdisipline_data'] = $subdisipline_data;
        }

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
                ])->whereHas('getIntakes', function($query) use ($Institution_data, $i) {
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

        $Courses_Listing = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])->whereHas('getIntakes', function($query) use ($inst_id) {
                    return $query->whereHas('getIntakeBranch', function($instQuery) use ($inst_id) {
                                return $instQuery->whereHas('getInstitution', function($instQuery) use ($inst_id) {
                                            return $instQuery->where([
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS
                                                ])->where(['id' => $inst_id]);
                                            });
                                        });
                })->get();
        return view('welcome', compact('levelofstudy', 'fieldofstudy', 'allInstitutions', 'vetInstitutions', 'los_id', 'currentYearDetail', 'nextYearDetail', 'Institution_listing', 'Courses_Listing'));
    }

    public function getCoursesData() {
        $input = Input::all();
        $institutionId = $input['Institution_Id'];
        $CoursesData = Courses::whereHas('getIntakes', function($query) use ($institutionId) {
                    return $query->whereHas('getIntakeBranch', function($instQuery) use ($institutionId) {
                                return $instQuery->whereHas('getInstitution', function($instQuery) use ($institutionId) {
                                            return $instQuery->where([
                                                'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                'approval' => Institution::APPROVAL,
                                                'verification' => Institution::VERIFICATION_PASS
                                            ])->where(['id' => $institutionId]);
                                        });
                                    });
                })
                ->where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])
                ->get();
        return $CoursesData;
    }

    public function courseFilterAutoSuggestSearch() {
        $input = Input::all();
        $name = isset($input['name']) ? $input['name'] : '';
        $getAllCourse = $this->filterCourse()
                ->where('name', 'LIKE', '%' . $name . '%')
                ->get();
        return $getAllCourse;
    }

    public function institutionFilterAutoSuggestSearch() {
        $input = Input::all();
        $name = isset($input['name']) ? $input['name'] : '';
        $getAllInstitution = $this->filterInstitution()
                ->where('name', 'LIKE', '%' . $name . '%')
                ->get();
        return $getAllInstitution;
    }

    public function vetFilterAutoSuggestSearch() {
        $input = Input::all();
        $name = isset($input['name']) ? $input['name'] : '';
        $getAllInstitution = $this->filterVet()
                ->where('name', 'LIKE', '%' . $name . '%')
                ->get();
        return $getAllInstitution;
    }

    public function scholarshipFilterAutoSuggestSearch() {
        $input = Input::all();
        $name = isset($input['name']) ? $input['name'] : '';
        $getAllScholarship = $this->filterScholarship()
                ->where('name', 'LIKE', '%' . $name . '%')
                ->get();
        return $getAllScholarship;
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

    public function exploreCourses() {
        $input = Input::all();
        $los_id = ($input['losId']) ? $input['losId'] : '';
        $fieldofstudy = FieldOfStudy::all();
        return view('explorecourselisting', compact('los_id', 'fieldofstudy'));
    }

    public function exploreCourselisting() {
        $input = Input::all();
        $los_id = ($input['losId']) ? $input['losId'] : '';
        $subId = ($input['subId']) ? $input['subId'] : '';
        $courselisting = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])->where(['LevelofStudy_id' => $los_id])
                ->whereHas('getCourseSubdiscipline', function($q) use ($subId) {
                    return $q->where(['subdiscipline_id' => $subId]);
                })
                ->get();
        return view('explorecoursesearch', compact('courselisting'));
    }

    public function exploreUniversities() {
        $input = Input::all();

        $fieldofstudy = FieldOfStudy::whereHas('getSubdisipline', function($query) {
            return $query->whereHas('getSubdisplineCourse', function($query1) {
                return $query1->whereHas('getCourse', function($query2) {
                    return $query2->where(['visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED]);
                });
            });
        })->get();
        $los_id = ($input['losId']) ? $input['losId'] : '';
        $fos_id = (count($fieldofstudy) > 0) ? $fieldofstudy[0]->id : '';
        $subIds = [];
        if ($fos_id) {
            $subIds = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get('id')->toArray();
            $subdisipline_data = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get();
        }

        $allInstitutions = Institution::has('getInstitutionAdmin')->where([
                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                            'approval' => Institution::APPROVAL,
                            'verification' => Institution::VERIFICATION_PASS
                        ])
                        ->where(['type' => Institution::TYPE_UNIVERSITY])
                        ->whereHas('getBranch', function($getBranch) use ($los_id) {
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($los_id) {
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($los_id) {
                                    if ($los_id) {
                                        return $getCourseData->where(['LevelofStudy_id' => $los_id]);
                                    }
                                    return $getCourseData;
                                });
                            });
                        })->limit(6)->get();

        foreach ($allInstitutions as $key => $institution) {
            // $query = Courses::whereHas('getCourseSubdiscipline', function($q) use ($subIds) {
            //             return $q->whereIn('subdiscipline_id', $subIds);
            //         });
            // if ($los_id) {
            //     $query = $query->where(['LevelofStudy_id' => $los_id]);
            // }
            // $query = $query->whereHas('getIntakes', function($getintake) use ($institution) {
            //     $getintakereturn = $getintake->whereHas('getIntakeBranch', function($getbranch) use ($institution) {
            //         $getbranch->where(['Institution_id' => $institution->id]);
            //     });
            // });
            // $courselisting = $query->get();
            $allInstitutions[$key]['subdisipline_data'] = $subdisipline_data;
        }
        return view('universitieslisting', compact('fieldofstudy', 'allInstitutions', 'los_id'));
    }

    public function Homecourselisting() {
        $input = Input::all();
        $los_id = isset($input['losId']) ? $input['losId'] : '';
        $ins_id = isset($input['ins_id']) ? $input['ins_id'] : '';
        $fos_id = isset($input['FOS_id']) ? $input['FOS_id'] : '';
        $sub_id = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get('id')->toArray();
        $subdisipline_data = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get();

        // $courselisting = Courses::where(['LevelofStudy_id' => $losId])
        //         ->whereHas('getCourseSubdiscipline', function($q) use ($sub_id) {
        //             return $q->whereIn('subdiscipline_id', $sub_id);
        //         })
        //         ->whereHas('getIntakes', function($getintake) use ($ins_id) {
        //     $getintakereturn = $getintake->whereHas('getIntakeBranch', function($getbranch) use ($ins_id) {
        //         $getbranch->where(['Institution_id' => $ins_id]);
        //     });
        // });
        // $courselisting = $courselisting->get();
        return view('courselisting', compact('subdisipline_data', 'los_id', 'ins_id'));
    }

    public function Explorevetcourselisting() {
        $input = Input::all();
        $losId = isset($input['losId']) ? $input['losId'] : '';
        $ins_id = isset($input['ins_id']) ? $input['ins_id'] : '';
        $fos_id = isset($input['FOS_id']) ? $input['FOS_id'] : '';
        $sub_id = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get('id')->toArray();
        $subdisipline_data = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get();

        // $courselisting = Courses::where(['LevelofStudy_id' => $losId])
        //         ->whereHas('getCourseSubdiscipline', function($q) use ($sub_id) {
        //             return $q->whereIn('subdiscipline_id', $sub_id);
        //         })
        //         ->whereHas('getIntakes', function($getintake) use ($ins_id) {
        //     $getintakereturn = $getintake->whereHas('getIntakeBranch', function($getbranch) use ($ins_id) {
        //         $getbranch->where(['Institution_id' => $ins_id]);
        //     });
        // });
        // $courselisting = $courselisting->get();
        return view('vetcourselisting', compact('subdisipline_data', 'losId', 'ins_id'));
    }

    public function exploreVetInstitute() {
        $input = Input::all();

        $fieldofstudy = FieldOfStudy::all();
        $los_id = ($input['losId']) ? $input['losId'] : '';
        $fos_id = (count($fieldofstudy) > 0) ? $fieldofstudy[0]->id : '';
        $subIds = [];
        if ($fos_id) {
            $subIds = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get('id')->toArray();
            $subdisipline_data = Subdiscipline::where(['FieldofStudy_id' => $fos_id])->get();
        }

        $vetInstitutions = Institution::has('getInstitutionAdmin')
                        ->where([
                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                            'approval' => Institution::APPROVAL,
                            'verification' => Institution::VERIFICATION_PASS
                        ])
                        ->where(['type' => Institution::TYPE_TRAINING])
                        ->whereHas('getBranch', function($getBranch) use ($los_id) {
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($los_id) {
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($los_id) {
                                    if ($los_id) {
                                        return $getCourseData->where(['LevelofStudy_id' => $los_id]);
                                    }
                                    return $getCourseData;
                                });
                            });
                        })->limit(6)->get();

        foreach ($vetInstitutions as $key => $institution) {
            // $query = Courses::whereHas('getCourseSubdiscipline', function($q) use ($subIds) {
            //             return $q->whereIn('subdiscipline_id', $subIds);
            //         });
            // if ($los_id) {
            //     $query = $query->where(['LevelofStudy_id' => $los_id]);
            // }
            // $query = $query->whereHas('getIntakes', function($getintake) use ($institution) {
            //     $getintakereturn = $getintake->whereHas('getIntakeBranch', function($getbranch) use ($institution) {
            //         $getbranch->where(['Institution_id' => $institution->id]);
            //     });
            // });
            // $courselisting = $query->get();
            $vetInstitutions[$key]['subdisipline_data'] = $subdisipline_data;
        }
        list($Institution_listing, $Courses_Listing) = $this->getCourseCompareData();
        return view('vetlisting', compact('fieldofstudy', 'vetInstitutions', 'Institution_listing', 'Courses_Listing'));
    }

    public function universitiesHome() {
        $levelofstudy = LevelOfStudy::all();
        $fieldofstudy = FieldOfStudy::all();
        $depandent_subdiscipline = [];
        $subdiscipline = Subdiscipline::all();
        foreach ($fieldofstudy as $key => $value) {
            $depandent_subdiscipline[$value['id']] = Subdiscipline::where('FieldofStudy_id',$value['id'])->get(['name','id']);
        }
        $los_id = (count($levelofstudy) > 0) ? $levelofstudy[0]->id : '';
        $ranking_name = 'QS';
        $cities = City::whereHas('getState', function($q) {
                    return $q->has('getCountry');
                })
                ->limit(City::LIMIT_LIST)->get();

        list($university_in_australiya) = $this->exploreuniversity();
        list($leasttutionfees) = $this->explorecheapestuniversity();
        list($institution) = $this->universitiesHomeRanking();


        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterCourseSearch();

        list($Institution_listing, $Courses_Listing) = $this->getCourseCompareData();

        return view('universitiesHome', compact('cities', 'levelofstudy', 'fieldofstudy', 'institution', 'currentYearDetail', 'nextYearDetail', 'university_in_australiya', 'subdiscipline', 'Institution_listing', 'Courses_Listing', 'los_id', 'leasttutionfees', 'ranking_name', 'depandent_subdiscipline'));
    }

    public function universitiesHomeRanking() {
        $input = Input::all();
        $ranking_type = (isset($input['ranking_type'])) ? $input['ranking_type'] : '';
        $ranking_name = (isset($input['ranking_name'])) ? $input['ranking_name'] : '';
        $view_all_ranking = (isset($input['view_all_ranking'])) ? $input['view_all_ranking'] : 0;

        if ($view_all_ranking) {
            $institution = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->where('type', Institution::TYPE_UNIVERSITY)->whereHas('getBranch', function($getBranch){
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake){
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData){
                                    return $getCourseData;
                                });
                            });
                        })->get();
            $view_list = 'd-none';
        } else {

            $institution = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->where('type', Institution::TYPE_UNIVERSITY)->whereHas('getBranch', function($getBranch){
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake){
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData){
                                    return $getCourseData;
                                });
                            });
                        })->get();
            $view_list = '';
        }
        if (\Request::ajax()) {
            return view('rankingUniversityHomeList', compact('institution', 'ranking_type', 'ranking_name', 'view_list'));
        }

        return [$institution];
    }

    public function exploreuniversity() {
        $input = Input::all();
        $levelofstudy = LevelOfStudy::all();
        $los_id = (isset($input['losId'])) ? $input['losId'] : $levelofstudy['0']->id;
        $fieldofstudy = FieldOfStudy::all();
        $depandent_subdiscipline = [];
        $subdiscipline = Subdiscipline::all();
        foreach ($fieldofstudy as $key => $value) {
            $depandent_subdiscipline[$value['id']] = Subdiscipline::where('FieldofStudy_id',$value['id'])->get(['name','id']);
        }
        $university_in_australiya = Institution::has('getInstitutionAdmin')->where([
                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                            'approval' => Institution::APPROVAL,
                            'verification' => Institution::VERIFICATION_PASS
                        ])
                        ->where(['type' => Institution::TYPE_UNIVERSITY])
                        ->whereHas('getBranch', function($getBranch) use ($los_id) {
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) use ($los_id) {
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) use ($los_id) {
                                    if ($los_id) {
                                        return $getCourseData->where(['LevelofStudy_id' => $los_id]);
                                    }
                                    return $getCourseData;
                                });
                            });
                        })->get();
        $sub1_name[] = (isset($input['sub1_name'])) ? $input['sub1_name'] : '';
        if (\Request::ajax()) {
            return view('exploreuniversitylisting', compact('university_in_australiya', 'levelofstudy', 'fieldofstudy', 'subdiscipline', 'los_id', 'sub1_name', 'depandent_subdiscipline'));
        }
        return [$university_in_australiya];
    }

    public function explorecheapestuniversity() {
        $input = Input::all();
        $fieldofstudy = FieldOfStudy::all();
        $subdiscipline = Subdiscipline::all();
        $depandent_subdiscipline = [];
        foreach ($fieldofstudy as $key => $value) {
            $depandent_subdiscipline[$value['id']] = Subdiscipline::where('FieldofStudy_id',$value['id'])->get(['name','id']);
        }
        $institution = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->where('type', Institution::TYPE_UNIVERSITY)->whereHas('getBranch', function($getBranch){
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake){
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData){
                                    return $getCourseData;
                                });
                            });
                        })->get();
        $leasttutionfees = [];
        foreach ($institution as $key => $value) {
            $cousecount = 0;
            $fees = 0;
            $courseIds = [];
            foreach ($value->getBranch as $keybranch => $valuebranch) {
                foreach ($valuebranch->getintake as $keyintake => $valueintake) {
                    $courseId = $valueintake->getCourseData->id;
                    if (!in_array($courseId, $courseIds)) {
                        $courseIds[] = $courseId;
                    } else {
                        continue;
                    }
                    $fees += $valueintake->getCourseData->commissionValue;
                    $cousecount++;
                }
            }
            if ($cousecount > 0) {
                $leasttutionfees[$value->id] = $fees / $cousecount;
            }
        }
        arsort($leasttutionfees);
        $cheapestsub1[] = (isset($input['cheapestsub1'])) ? $input['cheapestsub1'] : '';
        if (\Request::ajax()) {
            return view('explorecheapestuniversity', compact('fieldofstudy', 'subdiscipline', 'cheapestsub1', 'leasttutionfees'));
        }
        return [$leasttutionfees];
    }

    public function explorecheapestvet() {
        $input = Input::all();
        $fieldofstudy = FieldOfStudy::all();
        $subdiscipline = Subdiscipline::all();
        $depandent_subdiscipline = [];
        foreach ($fieldofstudy as $key => $value) {
            $depandent_subdiscipline[$value['id']] = Subdiscipline::where('FieldofStudy_id',$value['id'])->get(['name','id']);
        }
        $institution = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->where('type', Institution::TYPE_TRAINING)->get();
        $leasttutionfeesvet = [];
        foreach ($institution as $key => $value) {
            $cousecount = 0;
            $fees = 0;
            $courseIds = [];
            foreach ($value->getBranch as $keybranch => $valuebranch) {
                foreach ($valuebranch->getintake as $keyintake => $valueintake) {
                    $courseId = $valueintake->getCourseData->id;
                    if (!in_array($courseId, $courseIds)) {
                        $courseIds[] = $courseId;
                    } else {
                        continue;
                    }
                    $fees += $valueintake->getCourseData->commissionValue;
                    $cousecount++;
                }
            }
            if ($cousecount > 0) {
                $leasttutionfeesvet[$value->id] = $fees / $cousecount;
            }
        }
        arsort($leasttutionfeesvet);
        $cheapestvetsub1[] = (isset($input['cheapestvetsub1'])) ? $input['cheapestvetsub1'] : '';
        if (\Request::ajax()) {
            return view('explorecheapestvet', compact('fieldofstudy', 'subdiscipline', 'cheapestvetsub1', 'leasttutionfeesvet', 'depandent_subdiscipline'));
        }
        return [$leasttutionfeesvet];
    }

    public function vetHome() {
        $levelofstudy = LevelOfStudy::all();
        $fieldofstudy = FieldOfStudy::all();
        $depandent_subdiscipline = [];
        $subdiscipline = Subdiscipline::all();
        foreach ($fieldofstudy as $key => $value) {
            $depandent_subdiscipline[$value['id']] = Subdiscipline::where('FieldofStudy_id',$value['id'])->get(['name','id']);
        }
        $los_id = (count($levelofstudy) > 0) ? $levelofstudy[0]->id : '';
        $cities = City::whereHas('getState', function($q) {
                    return $q->has('getCountry');
                })->limit(City::LIMIT_LIST)
                ->get();

        $institutions = Institution::where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->has('getInstitutionAdmin')
                ->get();

        list($university_in_australiya) = $this->explorevet();

        list($Institution_listing, $Courses_Listing) = $this->getCourseCompareData();
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterCourseSearch();
        list($leasttutionfeesvet) = $this->explorecheapestvet();

        return view('vetHome', compact('cities', 'levelofstudy', 'fieldofstudy', 'currentYearDetail', 'nextYearDetail', 'institutions', 'Institution_listing', 'Courses_Listing', 'los_id', 'university_in_australiya', 'subdiscipline', 'leasttutionfeesvet', 'depandent_subdiscipline'));
    }

    public function explorevet() {
        $input = Input::all();
        $levelofstudy = LevelOfStudy::all();
        $fieldofstudy = FieldOfStudy::all();
        $depandent_subdiscipline = [];
        $subdiscipline = Subdiscipline::all();
        foreach ($fieldofstudy as $key => $value) {
            $depandent_subdiscipline[$value['id']] = Subdiscipline::where('FieldofStudy_id',$value['id'])->get(['name','id']);
        }
        $university_in_australiya = Institution::has('getInstitutionAdmin')->where([
                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                            'approval' => Institution::APPROVAL,
                            'verification' => Institution::VERIFICATION_PASS
                        ])
                        ->where(['type' => Institution::TYPE_TRAINING])
                        ->whereHas('getBranch', function($getBranch) {
                            return $getBranchReturn = $getBranch->whereHas('getintake', function($getIntake) {
                                return $getIntakeReturn = $getIntake->whereHas('getCourseData', function($getCourseData) {
                                    return $getCourseData;
                                });
                            });
                        })->get();
        $sub1_name[] = (isset($input['sub1_name'])) ? $input['sub1_name'] : '';
        if (\Request::ajax()) {
            return view('explorevetlisting', compact('university_in_australiya', 'levelofstudy', 'fieldofstudy', 'subdiscipline', 'sub1_name', 'depandent_subdiscipline'));
        }
        return [$university_in_australiya];
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
                ])->whereHas('getIntakes', function($query) use ($Institution_data, $i) {
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

        $Courses_Listing = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])->whereHas('getIntakes', function($query) use ($inst_id) {
                    return $query->whereHas('getIntakeBranch', function($instQuery) use ($inst_id) {
                                return $instQuery->whereHas('getInstitution', function($instQuery) use ($inst_id) {
                                            return $instQuery->where(['id' => $inst_id]);
                                        });
                            });
                })->get();

        return [$Institution_listing, $Courses_Listing];
    }

    // public function scholarshipsHome() {
    //     $levelofstudy = LevelOfStudy::all();
    //     $fieldofstudy = FieldOfStudy::all();
    //     list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterCourseSearch();
    //     $scholarshipType = Type::get();
    //     $CriteriaBasedScholarship = Criteria::get();
    //     return view('scholarshipsHome', compact('levelofstudy', 'fieldofstudy', 'currentYearDetail', 'nextYearDetail', 'scholarshipType', 'CriteriaBasedScholarship'));
    // }

    public function enquiry($slug,$slug_id) {
        $data = [];
        if($slug == Enquiry::SOURCE_COURSE){
            $data['Intake_id'] = $slug_id;
            $data['institution_id'] = Intake::where(['Course_id'=>$slug_id])->first()->getIntakeBranch->Institution_id;
        }elseif($slug == Enquiry::SOURCE_INSTITUTION){
            $data['institution_id'] = $slug_id;
            $courseDataDetail = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])->whereHas('getIntakes', function($q) use($slug_id,$data){
                return $q->whereHas('getIntakeScholarshipIntake',function($query) use($slug_id){
                    return $query->whereHas('getScholarshipIntake', function($query1) use($slug_id) {
                        return $query1->where(['Scholarship_id'=>$slug_id]);
                    });

                })->whereHas('getIntakeBranch', function($query2) use($data) {
                    return $query2->where(['Institution_id'=>$data['institution_id']]);
                });
            })->first();
            $data['Intake_id'] = isset($courseDataDetail->id) ? $courseDataDetail->id : '' ;
        }else{
            $data['institution_id'] = Institution::where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->whereHas('getBranch', function($q) use($slug_id){
                    return $q->whereHas('getintake', function($query) use($slug_id){
                        return $query->whereHas('getIntakeScholarshipIntake', function($query1) use($slug_id) {
                            return $query1->whereHas('getScholarshipIntake', function($query2) use($slug_id) {
                                return $query2->where(['Scholarship_id'=>$slug_id]);
                            });
                        });
                    });

                })->first()->id;
            $data['ScholarshipData'] =  Scholarship::find($slug_id);   
            $data['ScholarshipProviderData'] =  ScholarshipProvider::find($data['ScholarshipData']['ScholarshipProvider_id']);

            $data['Intake_id'] = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])->whereHas('getIntakes', function($q) use($slug_id,$data){
                return $q->whereHas('getIntakeScholarshipIntake',function($query) use($slug_id){
                    return $query->whereHas('getScholarshipIntake', function($query1) use($slug_id) {
                        return $query1->where(['Scholarship_id'=>$slug_id]);
                    });

                })->whereHas('getIntakeBranch', function($query2) use($data) {
                    return $query2->where(['Institution_id'=>$data['institution_id']]);
                });
            })->first()->id;
        }
            
        $user_data = User::find(Auth::id());
        if ($user_data && $user_data->Role_id == User::STUDENT) {
            $course_list = [];
            if(isset($data['Intake_id'])){
                $instID = $data['institution_id'];
               $course_list = Courses::whereHas('getIntakes', function($query) use ($instID) {
                    return $query->whereHas('getIntakeBranch', function($instQuery) use ($instID) {
                        return $instQuery->whereHas('getInstitution', function($institutionQuery) use ($instID) {
                            return $institutionQuery->where(['id'=>$instID]);
                        });
                    });
                })->get();
            }
            $institution_list = Institution::where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->get();
            $previous_url = url()->previous();
            return view('enquiry', compact('user_data', 'institution_list', 'slug', 'slug_id','data','course_list', 'previous_url'));
        } else {
            return redirect()->route('loginStudent');
        }
    }

    public function aboutUniexplorers() {
        return view('aboutUniexplorers');
    }

    public function getCoursesEnquiry() {
        $data = Input::all();
        $branch = Branch::where(['Institution_id' => $data['id']])->get(['id'])->toArray();
        if (count($branch)) {
            $intak = Intake::whereIn('Branch_id', $branch)->get(['Course_id'])->toArray();
            $intak = array_column($intak, 'Course_id');
            $unique_course = array_unique($intak);
            $course_data = Courses::where(['visibility' => Courses::PUBLISHED, 'approval' => Courses::APPROVED])->whereIn('id', $unique_course)->get()->toArray();
            return $course_data;
        }
        return '';
    }

    public function getScholarshipEnquiry() {
        $data = Input::all();
        $intake = Intake::where(['Course_id' => $data['id']])->get(['id'])->toArray();
        if (count($intake)) {
            $intake_scholarship_intake = IntakeScholarshipIntake::whereIn('Intake_id', $intake)->get(['ScholarshipIntake_id'])->toArray();
            $intake_scholarship_intake = array_column($intake_scholarship_intake, 'ScholarshipIntake_id');
            $intake_scholarship_intake = array_unique($intake_scholarship_intake);
            $ScholarshipIntake = ScholarshipIntake::whereIn('id', $intake_scholarship_intake)->get(['Scholarship_id'])->toArray();
            $ScholarshipIntake = array_column($ScholarshipIntake, 'Scholarship_id');
            $ScholarshipIntake = array_unique($ScholarshipIntake);
            $scholarship = Scholarship::whereIn('id', $ScholarshipIntake)->get()->toArray();
            return $scholarship;
        }
        return '';
    }

    public function addEnquiry(addEnquiry $request) {
        //addEnquiry $request
        $data = Input::all();
        if($data['source'] == Enquiry::SOURCE_SCHOLARSHIP){
            $data['source'] = Scholarship::where(['id'=>$data['Scholarship_id']])->first()->name;

            $data['Institution_id'] = Institution::where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->whereHas('getBranch', function($q) use($data){
                    return $q->whereHas('getintake', function($query) use($data){
                        return $query->whereHas('getIntakeScholarshipIntake', function($query1) use($data) {
                            return $query1->whereHas('getScholarshipIntake', function($query2) use($data) {
                                return $query2->where(['Scholarship_id'=>$data['slug_id']]);
                            });
                        });
                    });
                })->first()->id;


            $data['Intake_id'] = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
                ])->whereHas('getIntakes', function($q) use($data){
                    return $q->whereHas('getIntakeScholarshipIntake',function($query) use($data){
                        return $query->whereHas('getScholarshipIntake', function($query1) use($data) {
                            return $query1->where(['Scholarship_id'=>$data['slug_id']]);
                        });
                    })->whereHas('getIntakeBranch', function($query2) use($data) {
                        return $query2->where(['Institution_id'=>$data['Institution_id']]);
                    });
                })->first()->id;

            $userData = User::where(['Role_id'=>User::SCHOLARSHIP_PROVIDER_ADMIN_USER])->whereHas('getScholarshipProviderUser', function($q) use ($data) {
                return $q->where(['ScholarshipProvider_id'=>$data['ScholarshipProvider_id']]);
            })->first()->id;
            $notyUrl = route('scholarshipProviderManageEnquiries');
            $message = ' Pending response of student enquiry about scholarship';
            Common::saveNotification([User::SCHOLARSHIP_PROVIDER_ADMIN_USER], Common::STUDENT_ENQUIRY, $message, $notyUrl, $userData);
            unset($data['Scholarship_id']);
        }
        if($data['source'] == Enquiry::SOURCE_COURSE){
            $data['source'] = Courses::find($data['slug_id'])->name;
            $course_id = $data['slug_id'];

            $id = Branch::whereHas('getintake', function($query) use ($course_id) {
                return $query->where(['Course_id'=>$course_id]);
            })->first(['Institution_id']);

            $userData = User::where(['Role_id'=>User::INSTITUTION_ADMIN_USER])->whereHas('getInstitutionUser', function($query) use ($id) {
                return $query->whereHas('getInstitution', function($query) use ($id) {
                    return $query->where(['id'=>$id['Institution_id']]);
                });
            })->first();
            $notyUrl = route('institutionManageEnquiries');
            $message = ' Pending response of student enquiry about institution course';
            Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::STUDENT_ENQUIRY, $message, $notyUrl, $userData->id);
        }
        if($data['source'] == Enquiry::SOURCE_INSTITUTION){
            $id = $data['slug_id'];
            $data['source'] = Institution::find($data['slug_id'])->name;
            $userData = User::where(['Role_id'=>User::INSTITUTION_ADMIN_USER])->whereHas('getInstitutionUser', function($query) use ($id) {
                return $query->whereHas('getInstitution', function($query) use ($id) {
                    return $query->where(['id'=>$id]);
                });
            })->first();
            $notyUrl = route('institutionManageEnquiries');
            $message = ' Pending response of student enquiry about institution';
            Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::STUDENT_ENQUIRY, $message, $notyUrl, $userData->id);
        }
        $data['Student_id'] = Student::where(['User_id' => Auth::id()])->first()->id;
        $data['status'] = Enquiry::PENDING_RESPONSE;
        $data['enquiryDate'] = date("Y-m-d");
        if (isset($data['ScholarshipProvider_id'])) {
            $data['ScholarshipProvider_id'] = Scholarship::find($data['ScholarshipProvider_id'])->ScholarshipProvider_id;
        }
        Enquiry::create($data);
        return $this->sendResponse(true, $data['previous_url'], 'Enquiry send successfully');
    }

    public function ourMarketingServices() {
        return view('ourMarketingServices');
    }

    public function careers() {
        return view('careers');
    }

    public function contactUs() {
        return view('contactUs');
    }

    public function termsOfUse() {
        return view('termsOfUse');
    }

    public function privacyAndCookieStatement() {
        return view('privacyAndCookieStatement');
    }

    public function disclaimer() {
        return view('disclaimer');
    }

    public function getSubdisciplineDropdown() {
        $data = Input::all();
        $response = [];
        $field_of_study_id = (isset($data['field_of_study_id'])) ? $data['field_of_study_id'] : 0;
        if ($field_of_study_id) {
            $response = Subdiscipline::where(['FieldofStudy_id' => $field_of_study_id])->get();
        }
        return $response;
    }

    public function getCountry() {
        $data = Country::all();
        return $data;
    }

    public function getPhoneCode() {
        $data = Country::all();
        return $data;
    }

    public function getState() {
        $data = Input::all();
        $response = [];
        $countryId = (isset($data['countryId'])) ? $data['countryId'] : 0;
        if ($countryId) {
            $response = State::where(['Country_id' => $countryId])->get();
        }
        return $response;
    }

    public function getCity() {
        $data = Input::all();
        $response = [];
        $stateId = (isset($data['stateId'])) ? $data['stateId'] : 0;
        if ($stateId) {
            $response = City::where(['State_id' => $stateId])->get();
        }
        return $response;
    }

    public function refreshToken() {
        session()->regenerate();
        return response()->json([
                    "message" => 'Token Refreshed',
                    "token" => csrf_token()], 200);
    }

    public function uploadBase64File() {

        $input = Input::all();
        return Common::uploadBase62File($input['base64'], $input['fileName']);
    }

    public function courseListing() {
        return view('courseListing');
    }

}

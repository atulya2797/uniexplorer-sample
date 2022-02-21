<?php

namespace App\Http\Controllers;

use App\Model\BranchFacilities;
use Auth;
use App\Model\Subdiscipline;
use App\Model\Branch;
use App\Model\Intake;
use App\Model\FieldOfStudy;
use App\Model\LevelOfStudy;
use App\Model\Courses;
use App\Model\Facility;
use App\Model\Institution;
use Illuminate\Support\Facades\Input;
use App\Model\InstitutionMatch;
use App\Model\Scholarship;

class BasicController extends Controller {

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
    public function institutionDetail($slug) {
        $user = Auth::user();
        $Institution = $this->getInstitutionDetailInstitution($slug);
        if (!$Institution) {
            abort(404);
        }

        $getCourses = $this->getInstitutionDetailCourse($slug);

        list($InstitutionQSRanking, $InstitutionTHERanking) = Institution::InstitutionRankingData($Institution);
        list($Institution_listing, $Courses_Listing) = Courses::getCourseCompareData();
        $slug = $slug . " Detail page";

        /**
         * Course search filters
         */
        $levelOfStudy = LevelOfStudy::get();
        $fieldOfStudy = FieldOfStudy::get();
        $intake = Intake::select('id', 'applicationStartDate')->whereHas('getIntakeBranch', function($query) use ($Institution) {
                    return $query->where(['Institution_id' => $Institution->id]);
                })->get();
        $branch = Branch::where(['Institution_id' => $Institution->id])->get();
        /**
         * get all scholarship
         */
        $institutionId = $Institution->id;
        $getScholarship = Scholarship::whereHas('getScholarshipIntake', function($q) use ($institutionId) {
                    return $q->whereHas('getScholarshipIntakeId', function($gsi)use ($institutionId) {

                                $gsi->whereHas('getIntakeId', function($gii) use ($institutionId) {
                                    return $gii->whereHas('getIntakeBranch', function($gib) use ($institutionId) {
                                                return $gib->where(['Institution_id' => $institutionId]);
                                            });
                                });
                            });
                })->get();

        /**
         * branch and facilities
         */
        $getAllFacility = Facility::get();
        $Facilities = [];
        foreach ($branch as $val) {
            $branchFacilities = [];
            $allFacility = BranchFacilities::where(['Branch_id' => $val->id])->get();
            foreach ($allFacility as $key => $value) {
                $branchFacilities[] = isset($value->getFacilities->name) ? $value->getFacilities->name : '';
            }
            $Facilities[] = $branchFacilities;
        }

        return view('basic.institutionDetail', compact('Institution', 'user', 'Institution_listing', 'Courses_Listing', 'Facilities', 'InstitutionQSRanking', 'InstitutionTHERanking', 'getCourses', 'slug', 'levelOfStudy', 'fieldOfStudy', 'intake', 'branch', 'getScholarship', 'getAllFacility'));
    }

    public function getInstitutionDetailInstitution($slug) {
        return Institution::where([
                            'slug' => $slug,
                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                            'approval' => Institution::APPROVAL,
                            'verification' => Institution::VERIFICATION_PASS
                        ])
                        ->has('getInstitutionAdmin')
                        ->first();
    }

    public function getInstitutionDetailCourse($slug) {
        $user = Auth::user();
        $Institution = $this->getInstitutionDetailInstitution($slug);

        $ajaxRequest = Input::all();
        $search = (isset($ajaxRequest['search']) && !empty($ajaxRequest['search'])) ? $ajaxRequest['search'] : null;
        $los = (isset($ajaxRequest['los']) && !empty($ajaxRequest['los'])) ? $ajaxRequest['los'] : null;
        $fos = (isset($ajaxRequest['fos']) && !empty($ajaxRequest['fos'])) ? $ajaxRequest['fos'] : null;
        $intake = (isset($ajaxRequest['intake']) && !empty($ajaxRequest['intake'])) ? $ajaxRequest['intake'] : null;
        $branch = (isset($ajaxRequest['branch']) && !empty($ajaxRequest['branch'])) ? $ajaxRequest['branch'] : null;
        $getSubdiscipline = null;
        if ($fos) {
            $getSubdisciplineData = Subdiscipline::select('id')->where(['FieldofStudy_id' => $fos])->get()->toArray();
            if ($getSubdisciplineData) {
                $getSubdiscipline = array_column($getSubdisciplineData, 'id');
            }
        }

        $getCourses = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
        ]);

        if ($search) {
            $getCourses = $getCourses->where('name', 'LIKE', '%' . $search . '%');
        }
        if ($los) {
            $getCourses = $getCourses->where(['LevelofStudy_id' => $los]);
        }
        $getCourses = $getCourses->whereHas('getCourseSubdiscipline', function($q) use ($getSubdiscipline) {
            if ($getSubdiscipline) {
                return $q->whereIn('subdiscipline_id', $getSubdiscipline);
            }
        });

        $getCourses = $getCourses->whereHas('getIntakes', function($query) use ($slug, $intake, $branch) {
                    if ($intake) {
                        $query = $query->where(['id' => $intake]);
                    }
                    $query = $query->whereHas('getIntakeBranch', function($intQuery) use($slug, $branch) {
                        if ($branch) {
                            $intQuery = $intQuery->where(['id' => $branch]);
                        }
                        $intQuery = $intQuery->whereHas('getInstitution', function($instQuery) use($slug) {
                            return $instQuery->where([
                                        'slug' => $slug,
                                        'approval' => Institution::APPROVAL,
                                        'verification' => Institution::VERIFICATION_PASS,
                                        'visibility' => Institution::VISIBILITY_PUBLISHED
                            ]);
                        });
                        return $intQuery;
                    });
                    return $query;
                })->get();
        if (\Request::ajax()) {
            return view('basic.institutionDetailCourses', compact('getCourses', 'user', 'Institution'));
        }
        return $getCourses;
    }

    // public function InstitutionRankingData($Institution) {
    //     $InstitutionQSRanking = InstitutionMatch::wherehas('getMatch', function($query) use ($Institution) {
    //         return $query->where(['type' => 0]);
    //     })->where(['Institution_id' => $Institution->id])->get();
    //     $InstitutionTHERanking = InstitutionMatch::wherehas('getMatch', function($query) use ($Institution) {
    //             return $query->where(['type' => 1]);
    //     })->where(['Institution_id' => $Institution->id])->get();
    //      return [$InstitutionQSRanking, $InstitutionTHERanking];
    // }
    // public function getCourseCompareData() {
    //     $Institution_listing = Institution::has('getInstitutionAdmin')->where([
    //                 'visibility' => Institution::VISIBILITY_PUBLISHED,
    //                 'approval' => Institution::APPROVAL,
    //                 'verification' => Institution::VERIFICATION_PASS
    //             ])->get();
    //     $CompareCourseInstitution = Institution::has('getInstitutionAdmin')->where([
    //                 'visibility' => Institution::VISIBILITY_PUBLISHED,
    //                 'approval' => Institution::APPROVAL,
    //                 'verification' => Institution::VERIFICATION_PASS
    //             ])->first();
    //     $inst_id = isset($CompareCourseInstitution->id) ? $CompareCourseInstitution->id : '';
    //     $institute_data = [];
    //     if (isset($CompareCourseInstitution)) {
    //         for ($i = 0; $i < 3; $i++) {
    //             $Institution_data[] = $CompareCourseInstitution->id;
    //             $compareCourses_Listing = Courses::where([
    //                     'visibility' => Courses::PUBLISHED,
    //                     'approval' => Courses::APPROVED
    //                 ])
    //                 ->whereHas('getIntakes', function($query) use ($Institution_data, $i) {
    //                         return $query->whereHas('getIntakeBranch', function($instQuery) use($Institution_data, $i) {
    //                                 return $instQuery->whereHas('getInstitution', function($instQuery) use($Institution_data, $i) {
    //                                         return $instQuery->where([
    //                                                 'id' => $Institution_data[$i],
    //                                                 'approval' => Institution::APPROVAL,
    //                                                 'verification' => Institution::VERIFICATION_PASS,
    //                                                 'visibility' => Institution::VISIBILITY_PUBLISHED
    //                                        ]);
    //                                 });
    //                         });
    //                 })
    //                 ->first();
    //             if (isset($compareCourses_Listing))
    //                 $course_data[] = $compareCourses_Listing->id;
    //         }
    //     }
    //     $Courses_Listing = Courses::where([
    //             'visibility' => Courses::PUBLISHED,
    //             'approval' => Courses::APPROVED
    //         ])
    //         ->whereHas('getIntakes', function($query) use ($inst_id) {
    //                 return $query->whereHas('getIntakeBranch', function($intQuery) use($inst_id) {
    //                         return $intQuery->whereHas('getInstitution', function($instQuery) use($inst_id) {
    //                                 return $instQuery->where([
    //                                         'id' => $inst_id,
    //                                         'approval' => Institution::APPROVAL,
    //                                         'verification' => Institution::VERIFICATION_PASS,
    //                                         'visibility' => Institution::VISIBILITY_PUBLISHED
    //                                ]);
    //                         });
    //                 });
    //         })
    //         ->get();
    //     return [$Institution_listing, $Courses_Listing];
    // }

    public function couserDetails($institution_slug, $course_slug) {
        $user = Auth::user();
        $allFacility = Facility::get();

        $Facilities = [];
        foreach ($allFacility as $key => $value) {
            $Facilities[] = $value->name;
        }
        $getCourses = Courses::where([
                            'slug' => $course_slug,
                            'visibility' => Courses::PUBLISHED,
                            'approval' => Courses::APPROVED
                        ])
                        ->whereHas('getIntakes', function($query) use ($institution_slug) {
                            return $query->whereHas('getIntakeBranch', function($intQuery) use($institution_slug) {
                                        return $intQuery->whereHas('getInstitution', function($instQuery) use($institution_slug) {
                                                    return $instQuery->where([
                                                                'slug' => $institution_slug,
                                                                'approval' => Institution::APPROVAL,
                                                                'verification' => Institution::VERIFICATION_PASS,
                                                                'visibility' => Institution::VISIBILITY_PUBLISHED
                                                    ]);
                                                });
                                    });
                        })->first();
        //print_r($getCourses);die;
        if (!$getCourses) {
            abort(404);
        }

        $Institution = Institution::where([
                    'slug' => $institution_slug,
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->has('getInstitutionAdmin')
                ->first();

        $InstitutionDetails = [];
        foreach ($Institution->getBranch as $key => $branch) {
            $InstitutionDetails[$key]['Branch'] = $branch->name;
            $InstitutionDetails[$key]['Branch_id'] = $branch->id;
            foreach ($branch->getBranchFacilities as $branchFacilities) {
                $InstitutionFacility = Facility::where(['id' => $branchFacilities->Facility_id])->first();
                $InstitutionDetails[$key]['Facilities'][] = $InstitutionFacility->name;
            }
        }

        list($InstitutionQSRanking, $InstitutionTHERanking) = Institution::InstitutionRankingData($Institution);
        list($Institution_listing, $Courses_Listing) = Courses::getCourseCompareData();
        $countCourseScholarshipData = [];
        $repeateScholarshipData = [];
        foreach ($getCourses->getIntakes as $Intake_scholar) {
            foreach ($Intake_scholar->getIntakeScholarshipIntake as $Intake) {
                $scholarshipDetail = $Intake->getScholarshipIntake->getScholarship;
                if (in_array($Intake->ScholarshipIntake_id, $countCourseScholarshipData)) {
                    continue;
                }
                if (in_array($scholarshipDetail->id, $repeateScholarshipData)) {
                    continue;
                }
                if ($scholarshipDetail->approval !== Scholarship::APPROVED) {
                    continue;
                }
                $countCourseScholarshipData[] = $Intake->ScholarshipIntake_id;
                $repeateScholarshipData[] = $scholarshipDetail->id;
            }
        }
        $slug = $institution_slug . '-' . $course_slug . " Detail page";
        return view('basic.courseDetails', compact('Institution', 'user', 'Facilities', 'getCourses', 'InstitutionDetails', 'Institution_listing', 'Courses_Listing', 'InstitutionQSRanking', 'InstitutionTHERanking', 'countCourseScholarshipData', 'slug'));
    }

}

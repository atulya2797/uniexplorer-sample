<?php

namespace App\Http\Controllers;

use App\Model\Intake;
use App\Model\Type;
use App\Model\Country;
use App\Model\Courses;
use App\Helper\Common;
use App\Model\Criteria;
use App\Model\Institution;
use App\Model\Scholarship;
use App\Model\City;
use App\Model\LevelOfStudy;
use App\Model\FieldOfStudy;
use App\Model\Subdiscipline;
use App\Model\ScholarshipType;
use Illuminate\Support\Facades\Input;

class ScholarshipSearchController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function scholarshipsHome() {
        $compactData = $this->getScholarshipSearchData();
        $compactData['levelofstudy'] = LevelOfStudy::all();
        $compactData['fieldofstudy'] = FieldOfStudy::all();
        list($Institution_listing, $Courses_Listing) = $this->getCourseCompareData();
        $compactData['Courses_Listing'] = $Courses_Listing;
        $compactData['Institution_listing'] = $Institution_listing;
        return view('scholarshipsHome', $compactData);
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

    public function scholarshipListing() {

        $compactData = $this->getScholarshipSearchData();

        list($Institution_listing, $Courses_Listing) = $this->getCourseCompareData();

        $compactData['Institution_listing'] = $Institution_listing;
        $compactData['Courses_Listing'] = $Courses_Listing;

        if (\Request::ajax()) {
            return view('basic.scholarshipListingall', $compactData);
        }
        return view('basic.scholarshipListing', $compactData);
    }

    public function getScholarshipSearchData() {
        //Subdisciplines filter
        $subdisciplines = $this->getSubdisciplineFilterScholarshipSearch();
        //Intake filter
        $levelOfStudy = $this->getLevelOfStudyFilterScholarshipSearch();
        //Intake filter
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterScholarshipSearch();
        //Type filter
        $scholarshipType = $this->getSubdisciplineTypeFilterScholarshipSearch();
        //Criteria filter
        $CriteriaBasedScholarship = $this->getCriteriaFilterScholarshipSearch();
        //Criteria filter end
        //location filter 

        $cities = $this->getLocationFilterScholarshipSearch();
        //location filter end
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

        $input = Input::all();
        $user = $this->student;
        $search = isset($input['scholarship_name']) ? $input['scholarship_name'] : '';
        $levelofstudy = isset($input['levelOfStudy']) ? $input['levelOfStudy'] : [];
        $subdiscipline = isset($input['subdiscipline']) ? $input['subdiscipline'] : [];
        $location = isset($input['location']) ? $input['location'] : [];
        $defaultTuitionFeesPA = (!empty($Intake_tuitionFeesPA)) ? max($Intake_tuitionFeesPA) : 0;
        $tuitionFeesPA = isset($input['tuitionFeesPA']) ? $input['tuitionFeesPA'] : $defaultTuitionFeesPA;
        $defaultTuitionFeesEP = (!empty($Intake_tuitionFeesEP)) ? max($Intake_tuitionFeesEP) : 0;
        $tuitionFeesEP = isset($input['tuitionFeesEP']) ? $input['tuitionFeesEP'] : $defaultTuitionFeesEP;
        $criteria = isset($input['criteria_name']) ? $input['criteria_name'] : [];
        $type = isset($input['type_name']) ? $input['type_name'] : [];
        //for intake search of the current and next year
        $intakeCurrentYear = isset($input['intakeCurrentYear']) ? $input['intakeCurrentYear'] : [];
        $intakeNextYear = isset($input['intakeNextYear']) ? $input['intakeNextYear'] : [];
        //sorting
        $sorting = isset($input['sorting']) ? $input['sorting'] : 'scholarshipvalue';
        //sorting
        if (in_array('all', $location)) {
            $location = [];
        }
        if (in_array('all', $intakeCurrentYear)) {
            $intakeCurrentYear = [];
        }
        if (in_array('all', $intakeNextYear)) {
            $intakeNextYear = [];
        }
        $courseSearch = $search;

        $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                    return $q->whereHas('getScholarshipProviderUser', function($query) {
                                return $query->has('getUser');
                            });
                })
                ->where(['is_deleted' => Scholarship::ALIVE])
                ->whereHas('getScholarshipIntake', function($q) use ($subdiscipline, $location, $intakeCurrentYear, $intakeNextYear, $user, $levelofstudy, $tuitionFeesPA, $tuitionFeesEP, $search) {
                    $q->whereHas('getScholarshipIntakeId', function($query) use ($subdiscipline, $location, $intakeCurrentYear, $intakeNextYear, $user, $levelofstudy, $tuitionFeesPA, $tuitionFeesEP, $search) {
                        return $query->whereHas('getIntakeId', function($qintake) use ($subdiscipline, $location, $intakeCurrentYear, $intakeNextYear, $user, $levelofstudy, $tuitionFeesPA, $tuitionFeesEP, $search) {
                                    $intakeReturn = $qintake->whereHas('getCourseData', function($qcourse) use ($subdiscipline, $user, $levelofstudy, $search) {
                                                $qcourseResult = $qcourse->where([
                                                    'visibility' => Courses::PUBLISHED,
                                                    'approval' => Courses::APPROVED
                                                ])->where('name', 'LIKE', '%' . $search . '%');
                                                if ($subdiscipline) {
                                                    $qcourseResult = $qcourseResult->whereHas('getCourseSubdiscipline', function($q) use ($subdiscipline) {
                                                        return $q->whereIn('subdiscipline_id', $subdiscipline);
                                                    });
                                                }
                                                if ($levelofstudy) {
                                                    $qcourseResult = $qcourseResult->whereIn('LevelofStudy_id', $levelofstudy);
                                                }
                                                if ($this->student) {
                                                    $qcourseResult = $this->studentPreferredSearch($qcourseResult, $user);
                                                }
                                                return $qcourseResult;
                                            })
                                            ->whereHas('getIntakeBranch', function($intBranch) use ($location) {
                                        $intB = $intBranch->whereHas('getInstitution', function($checkInstitutionUser) {
                                            return $checkInstitutionUser->where([
                                                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                        'approval' => Institution::APPROVAL,
                                                        'verification' => Institution::VERIFICATION_PASS
                                                    ])
                                                    ->has('getInstitutionAdmin');
                                        });
                                        if (count($location) > 0) {
                                            $intB = $intB->whereIn('City_id', $location);
                                        }
                                        return $intB;
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
                                    return $intakeReturn;
                                });
                    });
                })
                ->whereHas('getScholarshipCriteriaId', function($qcriteria) use ($criteria) {
                    return $qcriteria->whereHas('getCriteria', function($queryCriteria) use ($criteria) {
                                if ($criteria) {
                                    $queryCriteria->whereIn('id', $criteria);
                                }
                                return $queryCriteria;
                            });
                })
                ->whereHas('getScholarshipTypeId', function($schType) use ($type) {
            return $schType->whereHas('getType', function($stype) use ($type) {
                        if ($type) {
                            return $stype->whereIn('id', $type);
                        }
                        return $stype;
                    });
        });
        $scholarship = $scholarship->orderBy($sorting, 'asc');
        $countScholarship = $scholarship->count();
        $scholarship = $scholarship->paginate(Common::PAGINATION);
        return compact('subdisciplines', 'currentYearDetail', 'nextYearDetail', 'scholarshipType', 'CriteriaBasedScholarship', 'cities', 'scholarship', 'user', 'courseSearch', 'countScholarship', 'levelOfStudy', 'Intake_tuitionFeesPA', 'Intake_tuitionFeesEP', 'tutionFeesFilter');
    }

    public function getLevelOfStudyFilterScholarshipSearch() {
        $user = $this->student;
        $levelOfStudy = LevelOfStudy::all();
        foreach ($levelOfStudy as $key => $val) {
            $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                        return $q->whereHas('getScholarshipProviderUser', function($query) {
                                    return $query->has('getUser');
                                });
                    })
                    ->where(['is_deleted' => Scholarship::ALIVE])
                    ->whereHas('getScholarshipIntake', function($q) use ($user, $val) {
                        $q->whereHas('getScholarshipIntakeId', function($query) use ($user, $val) {
                            return $query->whereHas('getIntakeId', function($qintake) use ($user, $val) {
                                        $intakeReturn = $qintake->whereHas('getCourseData', function($qcourse) use ($user, $val) {
                                                    $qcourseResult = $qcourse->where([
                                                        'visibility' => Courses::PUBLISHED,
                                                        'approval' => Courses::APPROVED
                                                    ]);
                                                    if ($val) {
                                                        $qcourseResult = $qcourseResult->where(['LevelofStudy_id' => $val->id]);
                                                    }
                                                    if ($this->student) {
                                                        $qcourseResult = $this->studentPreferredSearch($qcourseResult, $user);
                                                    }
                                                    return $qcourseResult;
                                                })
                                                ->whereHas('getIntakeBranch', function($intBranch) {
                                            $intB = $intBranch->whereHas('getInstitution', function($institutionCheck) {
                                                return $institutionCheck->where([
                                                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                            'approval' => Institution::APPROVAL,
                                                            'verification' => Institution::VERIFICATION_PASS
                                                        ])
                                                        ->has('getInstitutionAdmin');
                                            });
                                            return $intB;
                                        });
                                        //check if course intake deadline is reached
                                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                        return $intakeReturn;
                                    });
                        });
                    })
                    ->whereHas('getScholarshipCriteriaId', function($qcriteria) {
                        return $qcriteria->whereHas('getCriteria', function($queryCriteria) {
                                    return $queryCriteria;
                                });
                    })
                    ->whereHas('getScholarshipTypeId', function($schType) {
                return $schType->whereHas('getType', function($stype) {
                            return $stype;
                        });
            });
            $levelOfStudy[$key]['totalCourses'] = $scholarship->count();
        }
        return $levelOfStudy;
    }

    public function getSubdisciplineFilterScholarshipSearch() {

        $user = $this->student;

        $subdisciplines = Subdiscipline::has('getFieldofStudy')->get();
        foreach ($subdisciplines as $key => $val) {
            $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                        return $q->whereHas('getScholarshipProviderUser', function($query) {
                                    return $query->has('getUser');
                                });
                    })
                    ->where(['is_deleted' => Scholarship::ALIVE])
                    ->whereHas('getScholarshipIntake', function($q) use ($val, $user) {
                        $q->whereHas('getScholarshipIntakeId', function($query) use ($val, $user) {
                            return $query->whereHas('getIntakeId', function($qintake) use ($val, $user) {
                                        $intakeReturn = $qintake->whereHas('getCourseData', function($qcourseResult) use ($val, $user) {
                                                    $qcourseResult = $qcourseResult->where([
                                                        'visibility' => Courses::PUBLISHED,
                                                        'approval' => Courses::APPROVED
                                                    ]);
                                                    $cId = $val->id;
                                                    $qcourseResult = $qcourseResult
                                                            ->whereHas('getCourseSubdiscipline', function($q) use ($cId) {
                                                        return $q->where(['subdiscipline_id' => $cId]);
                                                    });
                                                    if ($this->student) {
                                                        $qcourseResult = $this->studentPreferredSearch($qcourseResult, $user);
                                                    }
                                                    return $qcourseResult;
                                                })
                                                ->whereHas('getIntakeBranch', function($intBranch) {
                                            $intB = $intBranch->whereHas('getInstitution', function($institutionCheck) {
                                                return $institutionCheck->where([
                                                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                            'approval' => Institution::APPROVAL,
                                                            'verification' => Institution::VERIFICATION_PASS
                                                        ])
                                                        ->has('getInstitutionAdmin');
                                            });
                                            return $intB;
                                        });
                                        //check if course intake deadline is reached
                                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                        return $intakeReturn;
                                    });
                        });
                    })
                    ->whereHas('getScholarshipCriteriaId', function($qcriteria) {
                        return $qcriteria->whereHas('getCriteria', function($queryCriteria) {
                                    return $queryCriteria;
                                });
                    })
                    ->whereHas('getScholarshipTypeId', function($schType) {
                return $schType->whereHas('getType', function($stype) {
                            return $stype;
                        });
            });
            $subdisciplines[$key]['totalCourses'] = $scholarship->count();
        }
        return $subdisciplines;
    }

    public function getSubdisciplineTypeFilterScholarshipSearch() {
        $user = $this->student;
        $scholarshipType = ScholarshipType::get();
        foreach ($scholarshipType as $key => $val) {
            $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                        return $q->whereHas('getScholarshipProviderUser', function($query) {
                                    return $query->has('getUser');
                                });
                    })
                    ->where(['is_deleted' => Scholarship::ALIVE])
                    ->whereHas('getScholarshipIntake', function($q) use ($user) {
                        $q->whereHas('getScholarshipIntakeId', function($query) use ($user) {
                            return $query->whereHas('getIntakeId', function($qintake)use ($user) {
                                        $intakeReturn = $qintake->whereHas('getCourseData', function($qcourseResult) use ($user) {
                                                    $qcourseResult = $qcourseResult->where([
                                                        'visibility' => Courses::PUBLISHED,
                                                        'approval' => Courses::APPROVED
                                                    ]);
                                                    if ($this->student) {
                                                        $qcourseResult = $this->studentPreferredSearch($qcourseResult, $user);
                                                    }
                                                    return $qcourseResult;
                                                })
                                                ->whereHas('getIntakeBranch', function($intBranch) {
                                            $intB = $intBranch->whereHas('getInstitution', function($institutionCheck) {
                                                return $institutionCheck->where([
                                                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                            'approval' => Institution::APPROVAL,
                                                            'verification' => Institution::VERIFICATION_PASS
                                                        ])
                                                        ->has('getInstitutionAdmin');
                                            });
                                            return $intB;
                                        });
                                        //check if course intake deadline is reached
                                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                        return $intakeReturn;
                                    });
                        });
                    })
                    ->whereHas('getScholarshipCriteriaId', function($qcriteria) {
                        return $qcriteria->whereHas('getCriteria', function($queryCriteria) {
                                    return $queryCriteria;
                                });
                    })
                    ->whereHas('getScholarshipTypeId', function($schType) use ($val) {
                return $schType->whereHas('getType', function($stype) use ($val) {

                            return $stype->where(['id' => $val->id]);
                        });
            });
            $scholarshipType[$key]['totalScholarship'] = $scholarship->count();
        }
        return $scholarshipType;
    }

    public function getCriteriaFilterScholarshipSearch() {
        $user = $this->student;
        $CriteriaBasedScholarship = Criteria::get();
        foreach ($CriteriaBasedScholarship as $key => $val) {
            $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                        return $q->whereHas('getScholarshipProviderUser', function($query) {
                                    return $query->has('getUser');
                                });
                    })
                    ->where(['is_deleted' => Scholarship::ALIVE])
                    ->whereHas('getScholarshipIntake', function($q) use ($user) {
                        $q->whereHas('getScholarshipIntakeId', function($query)use ($user) {
                            return $query->whereHas('getIntakeId', function($qintake) use ($user) {
                                        $intakeReturn = $qintake->whereHas('getCourseData', function($qcourseResult) use ($user) {
                                                    $qcourseResult = $qcourseResult->where([
                                                        'visibility' => Courses::PUBLISHED,
                                                        'approval' => Courses::APPROVED
                                                    ]);
                                                    if ($this->student) {
                                                        $qcourseResult = $this->studentPreferredSearch($qcourseResult, $user);
                                                    }
                                                    return $qcourseResult;
                                                })
                                                ->whereHas('getIntakeBranch', function($intBranch) {
                                            $intB = $intBranch->whereHas('getInstitution', function($institutionCheck) {
                                                return $institutionCheck->where([
                                                            'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                            'approval' => Institution::APPROVAL,
                                                            'verification' => Institution::VERIFICATION_PASS
                                                        ])
                                                        ->has('getInstitutionAdmin');
                                            });
                                            return $intB;
                                        });
                                        //check if course intake deadline is reached
                                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                        return $intakeReturn;
                                    });
                        });
                    })
                    ->whereHas('getScholarshipCriteriaId', function($qcriteria) use ($val) {
                        return $qcriteria->whereHas('getCriteria', function($queryCriteria) use ($val) {
                                    $queryCriteria->where(['id' => $val->id]);
                                    return $queryCriteria;
                                });
                    })
                    ->whereHas('getScholarshipTypeId', function($schType) {
                return $schType->whereHas('getType', function($stype) {
                            return $stype;
                        });
            });
            $CriteriaBasedScholarship[$key]['totalScholarship'] = $scholarship->count();
        }
        return $CriteriaBasedScholarship;
    }

    public function getIntakeFilterScholarshipSearch() {
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
            $getCYD['totalCourses'] = $this->getTotalIntakeDateScholarshipCount($icyData);
            $currentYearDetail[] = $getCYD;
        }

        for ($m = 1; $m <= 12; $m++) {
            $inyData = date('Y-m-d', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year'))));
            $getNYD = [
                'monthName' => date('F', mktime(0, 0, 0, $m, 1, date('Y', strtotime('+1 year')))),
                'date' => $inyData,
            ];
            $getNYD['totalCourses'] = $this->getTotalIntakeDateScholarshipCount($inyData);
            $nextYearDetail[] = $getNYD;
        }
        return [$currentYearDetail, $nextYearDetail];
    }

    public function getTotalIntakeDateScholarshipCount($inyVal) {
        $user = $this->student;
        $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                    return $q->whereHas('getScholarshipProviderUser', function($query) {
                                return $query->has('getUser');
                            });
                })
                ->where(['is_deleted' => Scholarship::ALIVE])
                ->whereHas('getScholarshipIntake', function($q) use ($inyVal, $user) {
                    $q->whereHas('getScholarshipIntakeId', function($query) use ($inyVal, $user) {
                        return $query->whereHas('getIntakeId', function($qintake) use ($inyVal, $user) {
                                    $intakeReturn = $qintake->whereHas('getCourseData', function($qcourseResult) use ($user) {
                                                $qcourseResult = $qcourseResult->where([
                                                    'visibility' => Courses::PUBLISHED,
                                                    'approval' => Courses::APPROVED
                                                ]);
                                                if ($this->student) {
                                                    $qcourseResult = $this->studentPreferredSearch($qcourseResult, $user);
                                                }
                                                return $qcourseResult;
                                            })
                                            ->whereHas('getIntakeBranch', function($intBranch) {
                                        $intB = $intBranch->whereHas('getInstitution', function($institutionCheck) {
                                            return $institutionCheck->where([
                                                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                        'approval' => Institution::APPROVAL,
                                                        'verification' => Institution::VERIFICATION_PASS
                                                    ])
                                                    ->has('getInstitutionAdmin');
                                        });
                                        return $intB;
                                    });

                                    $inyData = explode('-', $inyVal);
                                    if (isset($inyData[0])) {
                                        $inyMonth = $inyData[0];
                                        $intakeReturn = $intakeReturn->whereYear('commencementDate', '=', $inyMonth);
                                    }
                                    if (isset($inyData[1])) {
                                        $inyYear = $inyData[1];
                                        $intakeReturn = $intakeReturn->whereMonth('commencementDate', '=', $inyYear);
                                    }
                                    //check if course intake deadline is reached
                                    $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                    return $intakeReturn;
                                });
                    });
                })
                ->whereHas('getScholarshipCriteriaId', function($qcriteria) {
                    return $qcriteria->whereHas('getCriteria', function($queryCriteria) {
                                return $queryCriteria;
                            });
                })
                ->whereHas('getScholarshipTypeId', function($schType) {
            return $schType->whereHas('getType', function($stype) {
                        return $stype;
                    });
        });
        return $scholarship->count();
    }

    public function getLocationFilterScholarshipSearch() {
        $cities = City::whereHas('getState', function($q) {
                    return $q->has('getCountry');
                })->limit(City::LIMIT_LIST)
                ->get();
        $user = $this->student;
        foreach ($cities as $ckey => $city) {
            $cityId = $city->id;
            $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                        return $q->whereHas('getScholarshipProviderUser', function($query) {
                                    return $query->has('getUser');
                                });
                    })
                    ->where(['is_deleted' => Scholarship::ALIVE])
                    ->whereHas('getScholarshipIntake', function($q) use ($cityId, $user) {
                        $q->whereHas('getScholarshipIntakeId', function($query) use ($cityId, $user) {
                            return $query->whereHas('getIntakeId', function($qintake) use ($cityId, $user) {
                                        $intakeReturn = $qintake->whereHas('getCourseData', function($qcourseResult) use ($user) {
                                                    $qcourseResult = $qcourseResult->where([
                                                        'visibility' => Courses::PUBLISHED,
                                                        'approval' => Courses::APPROVED
                                                    ]);
                                                    if ($this->student) {
                                                        $qcourseResult = $this->studentPreferredSearch($qcourseResult, $user);
                                                    }
                                                    return $qcourseResult;
                                                })
                                                ->whereHas('getIntakeBranch', function($intBranch) use ($cityId) {
                                            $intB = $intBranch->whereHas('getInstitution', function($institutionCheck) {
                                                        return $institutionCheck->where([
                                                                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                                    'approval' => Institution::APPROVAL,
                                                                    'verification' => Institution::VERIFICATION_PASS
                                                                ])
                                                                ->has('getInstitutionAdmin');
                                                    })
                                                    ->where(['City_id' => $cityId]);
                                            return $intB;
                                        });
                                        //check if course intake deadline is reached
                                        $intakeReturn = $intakeReturn->where('applicationDeadlineDate', '>', date("Y-m-d"));
                                        return $intakeReturn;
                                    });
                        });
                    })
                    ->whereHas('getScholarshipCriteriaId', function($qcriteria) {
                        return $qcriteria->whereHas('getCriteria', function($queryCriteria) {
                                    return $queryCriteria;
                                });
                    })
                    ->whereHas('getScholarshipTypeId', function($schType) {
                return $schType->whereHas('getType', function($stype) {
                            return $stype;
                        });
            });
            $cities[$ckey]['totalCourses'] = $scholarship->count();
        }
        return $cities;
    }

    public function studentPreferredSearch($qcourseResult, $user) {
        $qcourseResult = $qcourseResult->where(function($orWhere) use ($user) {
            $orWhereData = $orWhere->orWhereHas('getShortlistedCourses', function($q) use($user) {
                        return $q->where(['Student_id' => $user->getStudent->id]);
                    })->orWhereHas('getCourseSubdiscipline', function($q) use ($user) {
                return $q->whereHas('getDesiredSubdiscipline', function($SubdQuery) use ($user) {
                            return $SubdQuery->where(['Student_id' => $user->getStudent->id]);
                        });
            });
            $columnName = $user->getStudent->EnglishLanguageTest ? strtoupper($user->getStudent->EnglishLanguageTest) : NULL;
            if ($columnName && $user->getStudent->EnglishLanguageTestScore) {
                $orWhereData = $orWhereData->orWhere($columnName, '<=', $user->getStudent->EnglishLanguageTestScore);
            }
            return $orWhereData;
        });
        return $qcourseResult;
    }

}

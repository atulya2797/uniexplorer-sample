<?php

namespace App\Http\Controllers;

use Auth;
use App\Model\Tag;
use App\Model\File;
use App\Model\FormFiles;
use App\Model\FormFilesCountry;
use App\Model\Month;
use App\Model\FormFilesCondition;
use App\Model\Condition;
use Session;
use App\Model\TypeOfValue;
use App\Model\ConditionRange;
use App\Model\Range;
use App\Model\User;
use App\Model\Pages;
use App\Model\Match;
use App\Model\Image;
use App\Model\Intake;
use App\Model\Branch;
use App\Model\Gallery;
use App\Model\Student;
use App\Helper\Common;
use App\Model\Enquiry;
use App\Model\Country;
use App\Model\Courses;
use App\Model\Facility;
use App\Model\Criteria;
use App\Model\Permission;
use App\Model\CourseTags;
use App\Model\PageSection;
use App\Model\Scholarship;
use App\Model\Institution;
use Illuminate\Support\Str;
use App\Model\LevelOfStudy;
use App\Model\FieldOfStudy;
use App\Model\Notification;
use App\Model\Subdiscipline;
use App\Model\RolePermission;
use App\Model\ScholarshipType;
use App\Model\InstitutionUser;
use App\Model\RegistrationMail;
use App\Model\BranchFacilities;
use App\Model\InstitutionMatch;
use App\Model\ReqSubdiscipline;
use App\Model\ScholarshipTypes;
use App\Model\ApplicationIntake;
use App\Model\ScholarshipIntake;
use App\Model\UserRolePermission;
use App\Model\MustGetApproveUser;
use App\Http\Requests\AddCourses;
use App\Model\ScholarshipCriteria;
use App\Model\ScholarshipProvider;
use App\Model\CourseSubdiscipline;
use App\Http\Requests\MatchRanking;
use App\Http\Requests\ManageCMSUser;
use App\Model\CourseApplicationForm;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddRequirments;
use Illuminate\Support\Facades\Input;
use App\Http\Requests\RespondEnquiry;
use App\Http\Requests\AddScholarship;
use App\Model\ApprovePublishingPages;
use App\Http\Requests\CourseOtherForm;
use App\Model\ScholarshipProviderUser;
use App\Model\IntakeScholarshipIntake;
use App\Http\Requests\SendMailRequest;
use App\Http\Requests\InternalReviewer;
use App\Http\Requests\AddApplicationForm;
use App\Http\Requests\AdminProfileUpdate;
use App\Http\Requests\AdminAddInstitution;
use App\Http\Requests\AddScholarshipProvider;
use App\Http\Requests\InstitutionManageProfile;
use App\Model\applicationintakefiles;
use App\Model\MatchLogics;
use App\Model\Requirement;
use App\Model\Pathway;
use App\Model\ApplicationIntakeFile;
use App\Http\Requests\AddCoursesIntake;
use App\Http\Requests\MatchInstitutionRanking;
use App\Http\Requests\StudentApplicationInternalReview;
use App\Http\Requests\studentApplicationVisaCredentials;
use App\Http\Requests\MatchInternationalInstitutionRanking;
use App\Http\Requests\studentApplicationFinalStep;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function notification() {
        $input = Input::all();
        Common::minimalNotification();
        $outstanding_enquiry = 0;
        if (Auth::User()->Role_id == User::INSTITUTION_USER || Auth::User()->Role_id == User::INSTITUTION_ADMIN_USER) {
            $InstitutionId = InstitutionUser::whereHas('getUser', function($q) {
                        return $q->where(['id' => Auth::User()->id]);
                    })->first()->Institution_id;

            $user_id = User::where(['Role_id' => User::INSTITUTION_ADMIN_USER])->wherehas('getInstitutionUser', function($q) use ($InstitutionId) {
                        return $q->where(['Institution_id' => $InstitutionId]);
                    })->first()->id;

            $notification = Notification::where([
                        'role' => User::INSTITUTION_ADMIN_USER,
                    ])->where(function($q) use($user_id) {
                return $q->where(['user_id' => $user_id])
                                ->orWhere(['user_id' => null]);
            });
            $manageEnquiries_listData = Enquiry::where(['Institution_id' => $InstitutionId, 'ScholarshipProvider_id' => NULL])
                    ->whereHas('getStudentUser', function($query) {
                return $query;
            });
            $outstanding_enquiry = $manageEnquiries_listData->where(['status' => 0])->count();
        } else if (Auth::User()->Role_id == User::SCHOLARSHIP_PROVIDER_USER || Auth::User()->Role_id == User::SCHOLARSHIP_PROVIDER_ADMIN_USER) {
            $scholarshipProviderId = ScholarshipProviderUser::where(['User_id' => Auth::User()->id])->first()->ScholarshipProvider_id;
            $user_id = User::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_ADMIN_USER])->wherehas('getScholarshipProviderUser', function($q) use ($scholarshipProviderId) {
                        return $q->where(['ScholarshipProvider_id' => $scholarshipProviderId]);
                    })->first()->id;

            $notification = Notification::where([
                        'role' => User::SCHOLARSHIP_PROVIDER_ADMIN_USER,
                    ])->where(function($q) use($user_id) {
                return $q->where(['user_id' => $user_id])
                                ->orWhere(['user_id' => null]);
            });
            $manageEnquiries_listData = Enquiry::where('ScholarshipProvider_id', $scholarshipProviderId)
                    ->whereHas('getStudentUser', function($query) {
                return $query;
            });
            $outstanding_enquiry = $manageEnquiries_listData->where(['status' => 0])->count();
        } else if (Auth::User()->Role_id == User::INTERNAL_REVIEWER || Auth::User()->Role_id == User::ADMIN) {

            $notification = Notification::where([
                        'role' => Auth::User()->Role_id,
            ]);
            $manageEnquiries_listData = Enquiry::whereHas('getStudentUser', function($query) {
                        return $query;
                    });
            $outstanding_enquiry = $manageEnquiries_listData->where(['status' => 0])->count();
        } else {
            //print_r("hello");die;
            $notification = Notification::where([
                        'role' => Auth::User()->Role_id,
            ]);
        }

        $notification = $notification->orderBy('is_read', 'asc')->paginate(Common::PAGINATION);
        return view('admin.notification', compact('notification', 'outstanding_enquiry'));
    }

    public function profileEdit() {
        $user = User::find(Auth::id());
        return view('admin.adminProfileEdit', compact('user'));
    }

    public function profileUpdate(AdminProfileUpdate $request) {
        $input = Input::all();
        unset($input['Role_id']);
        $input['password'] = bcrypt($input['password']);
        $updateUser = User::find(Auth::id());
        $updateUser->update($input);
        return $this->sendResponse(true, route('profileEdit'), 'Profile Has Been Updated');
    }

    public function sendRegistrationMailView() {
        $token = Str::random(60) . uniqid();
        return view('admin.sendRegistrationMail', compact('token'));
    }

    public function sendRegistrationMail(SendMailRequest $request) {
        $input = Input::all();
        unset($input['_token']);
        $input['token'] = $input['mailToken'];
        $check = RegistrationMail::where(['token' => $input['token']])->first();
        if ($check) {
            $check->update($input);
        } else {
            RegistrationMail::create($input);
        }
        Common::sendMail($input['email'], $input['subject'], $input['mailBody'], 'mail.sendRegistrationMail');
        return $this->sendResponse(true, route('sendRegistrationMailView'), 'Registration link has been send successfully');
    }

    public function readNotification() {
        $data = Input::all();
        Notification::where('id', $data['id'])->update(['is_read' => '1']);
        $response['status'] = true;
        return $response;
    }

    public function managePageAdmin() {
        if (User::find(Auth::id())['Role_id'] == User::CMSUSER) {
            $must_get_approve_pages = [];
            $cmsuser_permission = UserRolePermission::where(['User_id' => Auth::id()])->get();
            if (count($cmsuser_permission) == 0) {
                $mustGetApproveUser = MustGetApproveUser::select(DB::raw('assignFromId as id'))->where(['assignToId' => Auth::id()])->get()->toArray();
                foreach ($mustGetApproveUser as $key => $value) {
                    $must_get_approve_pages_data = Pages::where(['editor_id' => $value['id'], 'publish_status' => Pages::PUBLISH_PENDING])->get(['id'])->toArray();
                    $must_get_approve_pages = array_merge($must_get_approve_pages, array_column($must_get_approve_pages_data, 'id'));
                }
                if (count($must_get_approve_pages) == 0) {
                    abort(401);
                }
            }
        }
        $get = Input::all();
        $name = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';

        $data = Pages::where(function($query) use ($name) {
                    return $query->where('page_name', 'LIKE', '%' . $name . '%')->orWhere('url', 'LIKE', '%' . $name . '%');
                });

        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {
                $data = $data->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }

        $data1 = Pages::where('publish_by_id', '=', Null)->get(['page_id']);
        $data->where(function($q) use($data1) {
            $q->whereNotIn('page_id', $data1);
            //->orWhere('publish_by_id', '=', Null);
        });
        $data = $data->where(function($q) {
                    return $q->where('publish_status', '!=', Pages::PUBLISH_PENDING)->Where('publish_status', '!=', Pages::DISAVOWED);
                })->orderBy('updated_at', 'desc')->paginate(Common::PAGINATION);

        $permission = User::find(Auth::id())->getUserRolePermission;
        $permission_status = 'hidden';
        if (User::find(Auth::id())['Role_id'] == User::ADMIN) {
            $permission_status = '';
        } else {
            foreach ($permission as $key => $value) {
                if ($value->getRolePermission['Role_id'] == User::ADMIN || $value->getRolePermission->getPermission['id'] == Permission::MANAGE_CMS) {
                    $permission_status = '';
                } else {
                    if ($value->getRolePermission->getPermission['id'] == Permission::CAN_APPROVE_PUBLUSHING) {
                        $permission_status = '';
                    }
                }
            }
        }

        if (\Request::ajax()) {
            return view('admin.dataManagePageAdmin', compact('data', 'permission_status'));
        }
        return view('admin.managePageAdmin', compact('data', 'permission_status'));
    }

    public function manageEnquiries() {
        $get = Input::all();
        $user_id = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $institution_ids = isset($get['id']) && !empty($get['id']) ? $get['id'] : '';
        $scholarship_ids = isset($get['scholar_id']) && !empty($get['scholar_id']) ? $get['scholar_id'] : '';

        $institution_list = Institution::whereHas('getInstitutionAdmin', function($q) {
                    return $q->has('getUser');
                })->get();
        // $scholarshipProvider_list = ScholarshipProvider::has('getVerifiedUser')->get();
        $scholarshipProvider_list = ScholarshipProvider::where(['approval' => ScholarshipProvider::APPROVAL])
                ->whereHas('getScholarshipProviderUser', function($q) {
                    return $q->has('getUser');
                })
                ->get();
        $manageEnquiries_listData = Enquiry::whereHas('getStudentUser', function($query) use ($user_id) {

                    return $query->where('id', 'like', '%' . $user_id . '%')->orwhere(function($query1) use($user_id) {
                                return $query1->whereHas('getUserDetails', function($q) use ($user_id) {
                                            return $q->where('firstName', 'like', '%' . $user_id . '%')
                                                            ->orWhere('lastName', 'like', '%' . $user_id . '%')
                                                            ->orWhere('email', 'like', '%' . $user_id . '%');
                                        });
                            });
                });

        if ($institution_ids && $scholarship_ids) {
            $manageEnquiries_listData = $manageEnquiries_listData->where(function($query2) use($institution_ids, $scholarship_ids) {
                return $query2->whereIn('Institution_id', $institution_ids)->orwhereIn('ScholarshipProvider_id', $scholarship_ids);
            });
        } else if ($institution_ids) {
            $manageEnquiries_listData = $manageEnquiries_listData->whereIn('Institution_id', $institution_ids);
        } else if ($scholarship_ids) {
            $manageEnquiries_listData = $manageEnquiries_listData->whereIn('ScholarshipProvider_id', $scholarship_ids);
        }

        $manageEnquiries_list = $manageEnquiries_listData->paginate(Common::PAGINATION);
        $outstanding_enquiry = Enquiry::whereHas('getStudentUser', function($query) {
                    return $query;
                });
        $outstanding_enquiry = $outstanding_enquiry->where(['status' => 0])->count();
        if (\Request::ajax()) {
            return view('admin.dataManageEnquiries', compact('manageEnquiries_list', 'institution_list', 'scholarshipProvider_list'));
        }
        return view('admin.manageEnquiries', compact('manageEnquiries_list', 'institution_list', 'scholarshipProvider_list', 'outstanding_enquiry'));
    }

    public function manageEnquiriesView() {
        $data = Input::all();
        $enquiry_data = Enquiry::find($data['enquiryid']);
        $role_admin = User::ADMIN;
        echo view('admin.manageEnquiriesView', compact('enquiry_data'), ['role_admin' => $role_admin]);
    }

    public function internalReviewerViewActivityAdmin($id) {
        $InternalReviewer = User::find($id);

        $permission = [];
        foreach ($InternalReviewer->getUserRolePermission as $userRolePermission) {
            if ($userRolePermission->granted != UserRolePermission::GRANTED) {
                continue;
            }
            $permission[] = isset($userRolePermission->getRolePermission) ? $userRolePermission->getRolePermission->getPermission : '';
        }

        $permissions = RolePermission::where(['Role_id' => User::INTERNAL_REVIEWER, 'granted' => UserRolePermission::GRANTED])
                        ->whereHas('getPermission', function($q) {
                            return $q->where(['cms_type' => Permission::NOT_CMS_TYPE]);
                        })->get();
        $course = $this->filterCourse()->wherehas('getApprovedBy', function($query) use($id) {
                    return $query->where(['id' => $id]);
                })->get();

        $scholarship = $this->filterScholarship()->wherehas('getScholarshipApprovedByUser', function($query) use($id) {
                    return $query->where(['id' => $id]);
                })->get();

        $course_list = Courses::where(['visibility' => Courses::PUBLISHED, 'User_approvedby' => $id])->whereIn('approval', [Courses::NOT_APPROVED, Courses::APPROVED])->get();

        $course_count = count($course_list);

        $scholarship_list = Scholarship::where(['visibility' => Scholarship::PUBLISHED, 'User_approvedby' => $id])->whereIn('approval', [Scholarship::NOT_APPROVED, Scholarship::APPROVED])->get();

        $scholarship_count = count($scholarship_list);

        $varified_institution_list = Institution::where(['visibility' => Institution::VISIBILITY_PUBLISHED, 'User_approvedby' => $id])->whereIn('approval', [Institution::NOT_APPROVAL, Institution::APPROVAL])->get();
        $varified_institution_count = count($varified_institution_list);

        $varified_scholarship_list = ScholarshipProvider::where(['visibility' => ScholarshipProvider::PUBLISHED_VISIBILITY, 'User_approvedby' => $id])->whereIn('approval', [ScholarshipProvider::NOT_APPROVAL, ScholarshipProvider::APPROVAL])->get();

        $varified_scholarship_count = count($varified_scholarship_list);

        $varified_institution = $this->filterAllInstitution()->whereHas('getUserApprovedBy', function($query) use($id) {
                    return $query->where('User_approvedby', $id);
                })->get();

        $varified_scholarship_provider = ScholarshipProvider::where(['visibility' => ScholarshipProvider::PUBLISHED_VISIBILITY, 'User_approvedby' => $id, 'approval' => ScholarshipProvider::APPROVAL])->get();

        return view('admin.internalReviewerViewActivityAdmin', compact('permission', 'InternalReviewer', 'permissions', 'id', 'course', 'scholarship', 'varified_scholarship_count', 'varified_institution_count', 'varified_institution', 'varified_scholarship_provider', 'course_count', 'scholarship_count', 'varified_scholarship_list', 'varified_institution_list', 'scholarship_list', 'course_list'));
    }

    public function pagesDisavowed($id) {
        $data['publish_status'] = Pages::DISAVOWED;
        $data['publish_by_id'] = Auth::id();
        Pages::where(['page_id' => $id])->update($data);
        //->where(['publish_by_id' => Null])->delete();
        //PageSection::where(['page_id' => $id, 'flag' => '0'])->delete();
        Session::put('success', 'Page Disavowed.');
        return redirect()->route('managePublishing');
    }

    public function deleteDisavowed($id) {
        Pages::where(['page_id' => $id])->delete();
        PageSection::where(['page_id' => $id, 'flag' => '0'])->delete();
        Session::put('success', 'Page Deleted.');
        return redirect()->route('managePublishing');
    }

    public function managePagesPublishing($id) {
        $data['publish_by_id'] = Auth::id();
        $data['publish_status'] = Pages::PUBLISHED;
        $update_page = Pages::where(['page_id' => $id])->where(['publish_by_id' => Null])->first();
        if ($update_page) {
            Pages::where(['page_id' => $id])->where('publish_by_id', '!=', Null)->delete();
            Pages::where(['page_id' => $id])->where(['publish_by_id' => Null])->update($data);
            $section['flag'] = 1;
            PageSection::where(['page_id' => $id, 'flag' => '1'])->delete();
            PageSection::where(['page_id' => $id, 'flag' => '0'])->update($section);
        } else {
            Pages::where(['page_id' => $id])->update($data);
        }
        Session::put('success', 'Page published successfully.');
        return redirect()->route('managePageAdmin');
    }

    public function deletePage($id) {
        $data['is_deleted'] = 1;
        $abc = Pages::where(['page_id' => $id])->update($data);
        Session::put('success', 'Page Deleted');
        return redirect()->route('managePageAdmin');
    }

    public function redoPage($id) {
        $data['is_deleted'] = 0;
        $abc = Pages::where(['page_id' => $id])->update($data);
        Session::put('success', 'Page Undo');
        return redirect()->route('managePageAdmin');
    }

    public function responseEnquirySubmit($id, RespondEnquiry $request) {
        $data = Input::all();
        $user_id = Auth::id();
        $data['responseDate'] = date('Y-m-d');
        $data['User_Responder'] = $user_id;
        $data['status'] = 1;
        Enquiry::find($id)->update($data);
        return $this->sendResponse(true, route('manageEnquiries'), 'Enuiry has been Responded successfully.');
    }

    public function manageInstitutions() {
        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $allInstitution = User::whereIn('Role_id', [
                    User::INSTITUTION_ADMIN_USER
                ])->whereHas('getInstitutionUser', function($query) use ($search) {
                    return $query->whereHas('getInstitution', function($q) use ($search) {
                                return $q->where('name', 'LIKE', '%' . $search . '%');
                            });
                })
                ->orderBy('updated_at', 'desc')
                ->paginate(Common::PAGINATION);

        // $allInstitution = User::whereIn('Role_id', [
        //             User::INSTITUTION_ADMIN_USER
        // ])->whereHas('getInstitutionUser', function($query) use ($search) {
        //     return $query->whereHas('getInstitution', function($q) use ($search) {
        //         return $q->where('name', 'LIKE', '%' . $search . '%');
        //     });
        // })
        // ->orderBy('updated_at', 'desc')
        // ->paginate(Common::PAGINATION);

        $allInstitution = Institution::where('name', 'LIKE', '%' . $search . '%')
                ->has('getInstitutionAdmin')
                ->orderBy('updated_at', 'desc')
                ->paginate(Common::PAGINATION);

        foreach ($allInstitution as $key => $getInstitution) {
            //$getInstitution = $getInstitution;
            $verificationText = '-';
            $approvalTExt = '<span style="color:#ffc36a">' . Institution::PENDING_APPROVAL_TEXT . '</span>';

            $visibilityHideAction = "data-url='" . route("manageInstitutionsUpdateAction", $getInstitution->id) . "' data-visibility-hide='" . Institution::VISIBILITY_HIDE . "' class='manageInstitutionsUpdateVisibilityHide'";
            $visibilityShowAction = "data-url='" . route("manageInstitutionsUpdateAction", $getInstitution->id) . "' data-visibility-show='" . Institution::VISIBILITY_PUBLISHED . "' class='manageInstitutionsUpdateVisibilityShow'";
            $visibilityButton = '<li class="d-inline"><a ' . $visibilityHideAction . ' href="javascript:void(0)"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                            </li>
                                            <li class="d-inline"><a ' . $visibilityShowAction . ' href="javascript:void(0)"><i class="fas fa-eye-slash" aria-hidden="true"></i></a>
                                            </li>';

            $visibilityText = Institution::VISIBILITY_DRAFT_TEXT;
            $dataUrlVerify = "data-url='" . route("manageInstitutionsUpdateAction", $getInstitution->id) . "' data-verify='" . Institution::VERIFICATION_PASS . "' class='manageInstitutionsUpdateVerify' data-verify-by='" . Auth::id() . "' ";
            $dataUrlVerifyDeny = "data-url='" . route("manageInstitutionsUpdateAction", $getInstitution->id) . "' data-deny='" . Institution::VERIFICATION_DENY . "' class='manageInstitutionsUpdateDeny'";
            $dataUrlApprove = "data-url='" . route("manageInstitutionsUpdateAction", $getInstitution->id) . "' data-approve='" . Institution::APPROVAL . "' class='manageInstitutionsApprove' data-approve-by='" . Auth::id() . "'  ";
            $actionButton = '<button ' . $dataUrlVerify . ' style="color:#4ce675">Verify</button><button ' . $dataUrlVerifyDeny . ' style="color:#FF6A6A">' . Institution::VERIFICATION_DENY_TEXT . '</button>';

            if ($getInstitution->verification == Institution::VERIFICATION_DENY && $getInstitution->verification !== NULL) {
//                if ($getInstitution->cricosCode) {
                $actionButton = '<button ' . $dataUrlVerify . ' style="color:#4ce675">Verify</button>';
//                }
                $verificationText = '<span style="color:#FF6A6A">' . Institution::VERIFICATION_DENY_TEXT . '</span>';
            } elseif ($getInstitution->verification == Institution::VERIFICATION_PENDING) {
//                if ($getInstitution->cricosCode) {
                $actionButton = '<button ' . $dataUrlVerify . ' style="color:#4ce675">Verify</button><button ' . $dataUrlVerifyDeny . ' style="color:#FF6A6A">' . Institution::VERIFICATION_DENY_TEXT . '</button>';
//                } else {
//                    $actionButton = '<button ' . $dataUrlVerifyDeny . ' style="color:#FF6A6A">' . Institution::VERIFICATION_DENY_TEXT . '</button>';
//                }
                $verificationText = '<span style="color:#ffc36a">' . Institution::VERIFICATION_PENDING_TEXT . '</span>';
            }

            if ($getInstitution->visibility == Institution::VISIBILITY_HIDE && $getInstitution->visibility !== NULL) {
                $visibilityText = Institution::VISIBILITY_HIDE_TEXT;
                $visibilityButton = '<li class="d-inline"><a ' . $visibilityShowAction . ' href="javascript:void(0)"><i class="fas fa-eye-slash" aria-hidden="true"></i></a>
                                            </li>';
            } elseif ($getInstitution->visibility == Institution::VISIBILITY_PUBLISHED) {
                $visibilityText = Institution::VISIBILITY_PUBLISHED_TEXT;
                $visibilityButton = '<li class="d-inline"><a ' . $visibilityHideAction . ' href="javascript:void(0)"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                            </li>';
            }


            if ($getInstitution->verification == Institution::VERIFICATION_DENY) {
                $verificationText = '<span style="color:#FF6A6A">' . Institution::VERIFICATION_DENY_TEXT . '</span>';
            }
            if ($getInstitution->verification == Institution::VERIFICATION_PENDING) {
                $verificationText = '<span style="color:#ffc36a">' . Institution::VERIFICATION_PENDING_TEXT . '</span>';
            }
            if ($getInstitution->verification == Institution::VERIFICATION_PASS) {
                $verificationText = Institution::VERIFICATION_PASS_TEXT;
                $actionButton = '<button ' . $dataUrlApprove . ' style="color:#4ce675;">Approve</button>';
            }
            if ($getInstitution->verification == Institution::VERIFICATION_PASS) {
                if ($getInstitution->approval === Institution::PENDING_APPROVAL) {
                    $approvalTExt = '<span style="color:#ffc36a">' . Institution::PENDING_APPROVAL_TEXT . '</span>';
                }
                if ($getInstitution->approval === Institution::APPROVAL) {
                    $approvalTExt = '<span style="color:#4ce675">' . Institution::APPROVAL_TEXT . '</span>';
                }
            }
            if ($getInstitution->approval === Institution::APPROVAL) {
                if ($getInstitution->visibility == Institution::VISIBILITY_PUBLISHED) {
                    $visibilityText = Institution::VISIBILITY_PUBLISHED_TEXT;
                }
                if ($getInstitution->visibility == Institution::VISIBILITY_HIDE) {
                    $visibilityText = Institution::VISIBILITY_HIDE_TEXT;
                }
            }

            if ($getInstitution->approval !== Institution::APPROVAL) {
                $visibilityButton = '';
            } else {
                $actionButton = '';
            }

            $getInstitution->action = [
                'visibilityText' => $visibilityText,
                'verificationText' => $verificationText,
                'visibilityButton' => $visibilityButton,
                'approvalTExt' => $approvalTExt,
                'actionButton' => $actionButton,
            ];
        }
        if (\Request::ajax()) {
            return view('admin.dataManageInstitution', compact('allInstitution'));
        }

        return view('admin.manageInstitutions', compact('allInstitution'));
    }

    public function manageInstitutionsUpdateAction($id) {
        $data = Input::all();
        $institution_data = Institution::find($id);
        $institution = Institution::find($id)->update($data);
        $userData = User::where(['Role_id' => User::INSTITUTION_ADMIN_USER])->whereHas('getInstitutionUser', function($query) use ($id) {
                    return $query->whereHas('getInstitution', function($query) use ($id) {
                                return $query->where(['id' => $id]);
                            });
                })->first();
        $notyUrl = route('manageProfile');
        if (isset($data['approval'])) {
            if ($data['approval'] == 1) {
                $message = $institution_data['name'] . ' institute has approved ';
                Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::INSTITUTION_APPROVED, $message, $notyUrl, $userData->id);
            } else {
                $message = $institution_data['name'] . ' institute has declined ';
                Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::INSTITUTION_NOT_APPROVED, $message, $notyUrl, $userData->id);
            }
        }
        if (isset($data['visibility'])) {
            if ($data['visibility'] == 1) {
                $message = $institution_data['name'] . ' institute is visibal ';
                Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::INSTITUTION_VISIBILITY_SHOW, $message, $notyUrl, $userData->id);
            } else {
                $message = $institution_data['name'] . " institute's visibility hide ";
                Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::INSTITUTION_VISIBILITY_HIDE, $message, $notyUrl, $userData->id);
            }
        }
        return $this->sendResponse(true, route('manageInstitutions'), 'Institutoin Status Updated.');
    }

    public function hideCourses($id) {
        $data = Input::all();
        $course = Courses::find($id);
        Courses::find($id)->update($data);
        $route = (isset($data['institute_data'])) ? route('manageCoursesAdmin', $data['institute_data']) : route('manageCoursesAdmin');

        $userData = User::where(['Role_id' => User::INSTITUTION_ADMIN_USER])->whereHas('getInstitutionUser', function($query) use ($id) {
                    return $query->whereHas('getInstitution', function($query) use ($id) {
                                return $query->whereHas('getBranch', function($query) use ($id) {
                                            return $query->whereHas('getintake', function($query) use ($id) {
                                                        return $query->whereHas('getCourseData', function($query) use ($id) {
                                                                    return $query->where(['id' => $id]);
                                                                });
                                                    });
                                        });
                            });
                })->first();
        $notyUrl = route('institutionManageCourses');
        if ($data['visibility'] == 1) {
            $message = $course['name'] . ' is Visibility is Shown ';
            Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::COURSE_VISIBILITY_SHOW, $message, $notyUrl, $userData->id);
        } else {
            $message = $course['name'] . ' Visibility is Hidden ';
            Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::COURSE_VISIBILITY_HIDE, $message, $notyUrl, $userData->id);
        }
        return $this->sendResponse(true, $route);
    }

    public function institutionsContactInformation($id) {
        $institution = Institution::find($id);
        $branch = $institution->getBranch;
        return view('admin.institutionsContactInformation', compact('institution', 'branch'));
    }

    public function institutionsProfileView($id) {
        $institution = Institution::find($id);
        $branch = $institution->getBranch;
        $allFacility = Facility::get();
        list($InstitutionData, $user, $Institution_listing, $Courses_Listing, $Facilities, $InstitutionDetails, $getCourses, $levelOfStudy, $fieldOfStudy, $intake, $branch, $getScholarship) = $this->institutionPreview($institution['id']);
        return view('admin.institutionsProfileView', compact('institution', 'branch', 'allFacility', 'InstitutionData', 'user', 'Institution_listing', 'Courses_Listing', 'Facilities', 'InstitutionDetails', 'getCourses', 'levelOfStudy', 'fieldOfStudy', 'intake', 'branch', 'getScholarship'));
    }

    public function institutionPreview($id) {
        $user = Auth::user();
        $InstitutionData = Institution::where([
                    'id' => $id
                ])
                ->has('getInstitutionAdmin')
                ->first();
        if (!$InstitutionData) {
            abort(404);
        }
        $getCourses = $this->getInstitutionPreviewCourse($id);

        $allFacility = Facility::get();
        $Facilities = [];
        foreach ($allFacility as $key => $value) {
            $Facilities[] = $value->name;
        }

        $InstitutionDetails = [];
        foreach ($InstitutionData->getBranch as $key => $branch) {
            $InstitutionDetails[$key]['Branch'] = $branch->name;
            $InstitutionDetails[$key]['Branch_id'] = $branch->id;
            foreach ($branch->getBranchFacilities as $branchFacilities) {
                $InstitutionFacility = Facility::where(['id' => $branchFacilities->Facility_id])->first();
                $InstitutionDetails[$key]['Facilities'][] = $InstitutionFacility->name;
            }
        }

        // list($InstitutionQSRanking, $InstitutionTHERanking) = Institution::InstitutionRankingData($Institution);
        list($Institution_listing, $Courses_Listing) = Courses::getCourseCompareData();
        // $slug = $slug . " Detail page";

        /**
         * Course search filters
         */
        $levelOfStudy = LevelOfStudy::get();
        $fieldOfStudy = FieldOfStudy::get();
        $intake = Intake::select('id', 'applicationStartDate')->whereHas('getIntakeBranch', function($query) use ($InstitutionData) {
                    return $query->where(['Institution_id' => $InstitutionData->id]);
                })->get();
        $branch = Branch::where(['Institution_id' => $InstitutionData->id])->get();
        /**
         * get all scholarship
         */
        $institutionId = $InstitutionData->id;
        $getScholarship = Scholarship::whereHas('getScholarshipIntake', function($q) use ($institutionId) {
                    return $q->whereHas('getScholarshipIntakeId', function($gsi)use ($institutionId) {

                                $gsi->whereHas('getIntakeId', function($gii) use ($institutionId) {
                                    return $gii->whereHas('getIntakeBranch', function($gib) use ($institutionId) {
                                                return $gib->where(['Institution_id' => $institutionId]);
                                            });
                                });
                            });
                })->get();

        return [$InstitutionData, $user, $Institution_listing, $Courses_Listing, $Facilities, $InstitutionDetails, $getCourses, $levelOfStudy, $fieldOfStudy, $intake, $branch, $getScholarship];
    }

    public function getInstitutionPreviewCourse($id) {
        $user = Auth::user();
        $Institution = Institution::where([
                    'id' => $id
                ])
                ->has('getInstitutionAdmin')
                ->first();
        $getSubdiscipline = null;

        $getCourses = Courses::where([
                    'visibility' => Courses::PUBLISHED,
                    'approval' => Courses::APPROVED
        ]);

        $getCourses = $getCourses->whereHas('getCourseSubdiscipline', function($q) use ($getSubdiscipline) {
            if ($getSubdiscipline) {
                return $q->whereIn('subdiscipline_id', $getSubdiscipline);
            }
        });

        $getCourses = $getCourses->whereHas('getIntakes', function($query) use ($id) {
                    $query = $query->whereHas('getIntakeBranch', function($intQuery) use($id) {
                        $intQuery = $intQuery->whereHas('getInstitution', function($instQuery) use($id) {
                            return $instQuery->where([
                                        'id' => $id,
                                        'approval' => Institution::APPROVAL,
                                        'verification' => Institution::VERIFICATION_PASS,
                                        'visibility' => Institution::VISIBILITY_PUBLISHED
                            ]);
                        });
                        return $intQuery;
                    });
                    return $query;
                })->get();
        return $getCourses;
    }

    public function manageProfileSubmitAdmin(InstitutionManageProfile $request, $id) {
        $input = Input::all();
        //$institutionId = InstitutionUser::where(['User_id' => Auth::id()])->first()->Institution_id;

        /**
         * 
         * Delete Gallery Images start
         * 
         */
        if (isset($input['deleteImage'])) {
            $this->deleteInstitutionProfileGalleryImages($input['deleteImage']);
        }
        /**
         * 
         * Delete Gallery Images end
         * 
         */
        //copy file from temp folder
        $tempPath = public_path() . '/temp/';
        if (isset($input['logo']) && $input['logo']) {
            $imageData = [
                'name' => $input['logo'],
                'URL' => url('/') . '/' . Institution::IMAGE_FOLDER . '/' . $input['logo'],
                'ImageTitle' => $input['logo'],
                'ImageDescription' => $input['logo'],
            ];
            $image = Image::create($imageData);
            $input['Image_logo'] = $image->id;
            $from = $tempPath . $input['logo'];
            $to = public_path() . '/' . Institution::IMAGE_FOLDER . '/' . $input['logo'];
            rename($from, $to);
        }
        if (isset($input['coverphoto']) && $input['coverphoto']) {
            $imageData = [
                'name' => $input['coverphoto'],
                'URL' => url('/') . '/' . Institution::IMAGE_FOLDER . '/' . $input['coverphoto'],
                'ImageTitle' => $input['coverphoto'],
                'ImageDescription' => $input['coverphoto'],
            ];
            $image = Image::create($imageData);
            $input['Image_coverphoto'] = $image->id;
            $from = $tempPath . $input['coverphoto'];
            $to = public_path() . '/' . Institution::IMAGE_FOLDER . '/' . $input['coverphoto'];
            rename($from, $to);
        }

        if (isset($input['galleryImage']) && $input['galleryImage']) {
            foreach ($input['galleryImage'] as $val) {
                $imageData = [
                    'name' => $val,
                    'URL' => url('/') . '/' . Institution::IMAGE_FOLDER . '/' . $val,
                    'ImageTitle' => $val,
                    'ImageDescription' => $val,
                ];
                $image = Image::create($imageData);
                $imageData = [
                    'Institution_id' => $id,
                    'Image_id' => $image->id
                ];
                Gallery::create($imageData);
                $from = $tempPath . $val;
                $to = public_path() . '/' . Institution::IMAGE_FOLDER . '/' . $val;
                rename($from, $to);
            }
        }
        if (User::find(Auth::id())['Role_id'] == User::ADMIN || User::find(Auth::id())['Role_id'] == User::INTERNAL_REVIEWER) {
            $input['approval'] = Institution::APPROVAL;
            $input['User_approvedby'] = Auth::id();
        } else {
            $input['approval'] = Institution::PENDING_APPROVAL;
            $input['User_approvedby'] = NULL;
        }
        $institution = Institution::find($id);
        $institution->update($input);
        if (isset($input['facilities'])) {
            foreach ($input['facilities'] as $branchId => $facility) {
                BranchFacilities::where(['Branch_id' => $branchId])->delete();
                foreach ($facility as $facilityId) {
                    $newData = ['Facility_id' => $facilityId, 'Branch_id' => $branchId];
                    BranchFacilities::create($newData);
                }
            }
        }
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], 'Institution Submit For Approval', 'Institution ' . $institution->name . ' has update there profile, submited for approval', route('manageInstitutions'));
        return $this->sendResponse(true, route('institutionsProfileView', $id), 'Your profile has been submited for approval');
    }

    public function deleteInstitutionProfileGalleryImages($allImages) {
        if (count($allImages)) {
            foreach ($allImages as $val) {
                $checkImageGallery = Gallery::where(['Image_id' => $val])->first();
                if ($checkImageGallery) {
                    $checkImage = Image::find($checkImageGallery->Image_id);
                    if ($checkImage) {
                        $checkImagePath = public_path() . '/institutionImage/' . $checkImage->name;
                        if ($checkImage->name && file_exists($checkImagePath)) {
                            unlink($checkImagePath);
                        }
                        $checkImageGallery->delete();
                        $checkImage->delete();
                    }
                }
            }
        }
    }

    public function addInstitutions(AdminAddInstitution $request) {
        $data = Input::all();
        $data['Role_id'] = User::INSTITUTION_ADMIN_USER;
        $data['verification'] = Institution::VERIFICATION_PASS;
        $data['User_verifiedby'] = Auth::id();
        $uniquePassword = uniqid();
        $data['password'] = Hash::make($uniquePassword);
        $create_user = User::create($data);
        $data['slug'] = Institution::slug($data['name']);
        $crete_institute = Institution::create($data);
        $data['User_id'] = $create_user->id;
        $data['Institution_id'] = $crete_institute->id;
        InstitutionUser::create($data);
        Institution::createInstitutionPage($crete_institute->id);
        //create notification for created institute
        $create_user['password'] = $uniquePassword;
        Common::sendMail($create_user->email, 'Uniexplorers', $create_user, 'mail.adminInstituteAdd');
        return $this->sendResponse(true, route('manageInstitutions'), 'Institution has been added successfully.');
    }

    public function addScholarshipProvider(AddScholarshipProvider $request) {
        $data = Input::all();
        $uniquePassword = uniqid();
        $data['password'] = Hash::make($uniquePassword);
        $data['Role_id'] = User::SCHOLARSHIP_PROVIDER_ADMIN_USER;
        $data['visibility'] = ScholarshipProvider::PUBLISHED_VISIBILITY;
        $data['verified'] = ScholarshipProvider::VERIFIED;
        $data['approval'] = ScholarshipProvider::APPROVAL;
        $data['User_approvedby'] = Auth::id();
        $crete_provider = ScholarshipProvider::create($data);
        $create_user = User::create($data);
        $data['User_id'] = $create_user['id'];
        $data['ScholarshipProvider_id'] = $crete_provider->id;
        ScholarshipProviderUser::create($data);
        $create_user['password'] = $uniquePassword;
        Common::sendMail($create_user->email, 'Uniexplorers', $create_user, 'mail.addscholarship_provider');
        return $this->sendResponse(true, route('manageScholarshipProvider'), 'Scholarship Provider added successfully');
    }

    public function manageInternalReviewers() {
        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $permissions = RolePermission::where(['Role_id' => User::INTERNAL_REVIEWER, 'granted' => UserRolePermission::GRANTED])
                        ->whereHas('getPermission', function($q) {
                            return $q->where(['cms_type' => Permission::NOT_CMS_TYPE]);
                        })->get();

        //print_r($permissions['0']->getPermission->name);die;
        $userdata = User::where(["is_deleted" => User::NOT_DELETED])->whereHas('getUserRolePermission', function($query) {
                    return $query->whereHas('getRolePermission', function($q) {
                                return $q->whereHas('getPermission', function($q) {
                                            return $q->where(['cms_type' => Permission::NOT_CMS_TYPE]);
                                        });
                            });
                })->where(['Role_id' => User::INTERNAL_REVIEWER])
                ->where(function($q) use ($search) {
                    return $q->orWhere('firstName', 'LIKE', '%' . $search . '%')
                            ->orWhere('email', 'LIKE', '%' . $search . '%')
                            ->orWhere('id', 'LIKE', '%' . $search . '%');
                })
                ->orderBy('updated_at', 'desc')
                ->paginate(Common::PAGINATION);
        foreach ($userdata as $key => $value) {
            $array = [];
            foreach ($value->getUserRolePermission as $userRolePermission) {
                if ($userRolePermission->granted != UserRolePermission::GRANTED) {
                    continue;
                }
                $array[] = isset($userRolePermission->getRolePermission) ? $userRolePermission->getRolePermission->getPermission : '';
            }
            $userdata[$key]['grant_permission'] = $array;
        }

        if (\Request::ajax()) {
            return view('admin.dataManageInternalReviewers', compact('permissions', 'userdata'));
        }
        return view('admin.manageInternalReviewers', compact('permissions', 'userdata'));
    }

    public function managePublishing() {
        $can_approve_pages = [];
        $must_get_approve_pages = [];
        if (User::find(Auth::id())['Role_id'] == User::CMSUSER) {
            $mustGetApproveUser = MustGetApproveUser::select(DB::raw('assignFromId as id'))->where(['assignToId' => Auth::id()])->get()->toArray();
            foreach ($mustGetApproveUser as $key => $value) {
                $must_get_approve_pages_data = Pages::where(['editor_id' => $value['id'], 'publish_status' => Pages::PUBLISH_PENDING])->get(['id'])->toArray();
                $must_get_approve_pages = array_merge($must_get_approve_pages, array_column($must_get_approve_pages_data, 'id'));
            }
            $cmsuser_permission = UserRolePermission::where(['User_id' => Auth::id()])->get()->toArray();
            if (count($cmsuser_permission) == 0 && count($must_get_approve_pages) == 0) {
                abort(401);
            }
            $rolepermission_id = RolePermission::where(['Role_id' => User::CMSUSER, 'Permission_id' => Permission::CAN_APPROVE_PUBLUSHING])->first();
            if (in_array($rolepermission_id['id'], array_column($cmsuser_permission, 'RolePermission_id'))) {
                $can_approve_pages = ApprovePublishingPages::select(DB::raw('page_id as id'))->where(['user_id' => Auth::id(), 'publish_directly' => ApprovePublishingPages::CAN_APPROVE_PUBLISHING])->get()->toArray();
                $can_approve_pages = array_column($can_approve_pages, 'id');
            } else {
                $rolepermission_id = RolePermission::where(['Role_id' => User::CMSUSER, 'Permission_id' => Permission::MUST_GET_APPROVED])->first();
                if (in_array($rolepermission_id['id'], array_column($cmsuser_permission, 'RolePermission_id'))) {
                    abort(401);
                } else {
                    $rolepermission_id = RolePermission::where(['Role_id' => User::CMSUSER, 'Permission_id' => Permission::PUBLISH_DIRECTLY])->first();
                    if (in_array($rolepermission_id['id'], array_column($cmsuser_permission, 'RolePermission_id'))) {
                        abort(401);
                    }
                }
            }
        }
        $get = Input::all();
        $name = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $approve_pages = User::find(Auth::id())->getApprovePublishingPages->toArray();
        ;
        $approve_val = array_column($approve_pages, 'page_id');
        $approve_val = array_merge($must_get_approve_pages, $approve_val);
        $data = Pages::where(function($query) use ($name) {
                    return $query->where('page_name', 'LIKE', '%' . $name . '%')->orWhere('url', 'LIKE', '%' . $name . '%');
                });
        $permission = User::find(Auth::id())->getUserRolePermission;
        $permission_status = 'hidden';

        if (User::find(Auth::id())['Role_id'] == User::ADMIN) {
            $permission_status = '';
        } else {
            foreach ($permission as $key => $value) {
                if ($value->getRolePermission['Role_id'] == User::ADMIN || $value->getRolePermission['Role_id'] == User::INTERNAL_REVIEWER) {
                    $permission_status = '';
                } else {
                    if ($value->getRolePermission->getPermission['id'] == Permission::CAN_APPROVE_PUBLUSHING) {
                        $permission_status = '';
                    }
                }
                if ($value->getRolePermission->getPermission['id'] == Permission::CAN_APPROVE_PUBLUSHING) {
                    $permission_status = '';
                    $data = $data->whereIn('page_id', $approve_val);
                }
            }
        }
        if ($permission_status == 'hidden') {
            $data = $data->whereIn('page_id', $approve_val);
        }
        $can_approve_pages = array_merge($must_get_approve_pages, $can_approve_pages);
        //$data1 = Pages::where('publish_by_id', '=', Null)->get(['page_id']);
        $data = $data->where(['is_deleted' => 0])->where(function($q) {
                    return $q->where(['publish_status' => Pages::PUBLISH_PENDING])->orWhere(['publish_status' => Pages::DISAVOWED]);
                })->orderBy('updated_at', 'desc')->paginate(Common::PAGINATION);
        if (\Request::ajax()) {
            return view('admin.dataManagePublishing', compact('data', 'permission_status', 'can_approve_pages'));
        }
        return view('admin.managePublishing', compact('data', 'permission_status', 'can_approve_pages'));
    }

    public function manageScholarship() {
        $get = Input::all();
        $user_id = Auth::id();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $total_scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                    return $q->whereHas('getScholarshipProviderUser', function($query) {
                                return $query->has('getUser');
                            });
                })
                ->where('name', 'LIKE', '%' . $search . '%')
                ->where(['is_deleted' => Scholarship::ALIVE])
                ->orderBy('created_at', 'desc');
        $totalScholarshipCount = $total_scholarship->count();
        $total_scholarship = $total_scholarship->paginate(Common::PAGINATION);

        if (\Request::ajax()) {
            return view('admin.dataManageScholarship', ['data' => $total_scholarship, 'user_id' => $user_id, 'totalScholarshipCount' => $totalScholarshipCount]);
        }
        return view('admin.manageScholarship', ['data' => $total_scholarship, 'user_id' => $user_id, 'totalScholarshipCount' => $totalScholarshipCount]);
    }

    public function manageScholarshipUpdateVisibility($id) {
        $data = Input::all();
        Scholarship::find($id)->update($data);
        $scholarship = Scholarship::find($id);
        $approved_by = User::find($scholarship->User_approvedby);
        $notyUrl = route('scholarshipProviderManageScholarship');

        $userData = User::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_ADMIN_USER])->whereHas('getScholarshipProviderUser', function($q) use ($id) {
                    return $q->wherehas('getScholarshipProvider', function($q2) use ($id) {
                                return $q2->wherehas('getScholarship', function($q1) use ($id) {
                                            return $q1->where(['id' => $id]);
                                        });
                            });
                })->first()->id;
        $title = '';
        if ($data['visibility'] == 1) {

            $message = 'Scholarship: ' . $scholarship->name . ' - visibility shown by: ' . $approved_by->firstName . ' ' . $approved_by->lastName . ' - Email: ' . $approved_by->email;
            $title = Common::SCHOLARSHIP_SHOW;
        } else {
            $message = 'Scholarship: ' . $scholarship->name . ' - visibility hidden by: ' . $approved_by->firstName . ' ' . $approved_by->lastName . ' - Email: ' . $approved_by->email;
            $title = Common::SCHOLARSHIP_HIDE;
        }
        Common::saveNotification([User::SCHOLARSHIP_PROVIDER_ADMIN_USER], $title, $message, $notyUrl, $userData);
        return $this->sendResponse(true, route('manageScholarship'), 'Scholarship update successfully');
    }

    public function manageScholarshipUpdateAction($id) {
        $data = Input::all();
        Scholarship::find($id)->update($data);
        $scholarship = Scholarship::find($id);
        $approved_by = User::find($scholarship->User_approvedby);

        $userData = User::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_ADMIN_USER])->whereHas('getScholarshipProviderUser', function($q) use ($id) {
                    return $q->wherehas('getScholarshipProvider', function($q2) use ($id) {
                                return $q2->wherehas('getScholarship', function($q1) use ($id) {
                                            return $q1->where(['id' => $id]);
                                        });
                            });
                })->first()->id;

        $notyUrl = route('scholarshipProviderManageScholarship');
        $title = '';
        if ($data['approval'] == 1) {
            $message = 'Scholarship: ' . $scholarship->name . ' - Approved by: ' . $approved_by->firstName . ' ' . $approved_by->lastName . ' - Email: ' . $approved_by->email;
            $title = Common::SCHOLARSHIP_APPROVED;
        } else {
            $message = 'Scholarship: ' . $scholarship->name . ' - Unapproved by: ' . $approved_by->firstName . ' ' . $approved_by->lastName . ' - Email: ' . $approved_by->email;
            $title = Common::SCHOLARSHIP_UNAPPROVED;
        }
        Common::saveNotification([User::SCHOLARSHIP_PROVIDER_ADMIN_USER], $title, $message, $notyUrl, $userData);

        return $this->sendResponse(true, route('manageScholarship'), 'Scholarship update successfully');
    }

    public function manageScholarshipView($id) {
        $scholarship = Scholarship::find($id);
        $countries = Country::all();
        $institutions = Institution::all();
        $Scholarship_Criteria = Criteria::all();
        $Scholarship_type = ScholarshipType::all();
        return view('admin.manageScholarshipView', compact('scholarship', 'countries', 'institutions', 'Scholarship_Criteria', 'Scholarship_type'));
    }

    public function scholarshipProviderContactInfo($id) {
        $scholarship = Scholarship::find($id);
        $scholarshipProvider_id = $scholarship->ScholarshipProvider_id;
        $scholarshipProviderUser = ScholarshipProviderUser::select()->where('scholarshipProvider_id', $scholarshipProvider_id)->get();
        foreach ($scholarshipProviderUser as $key => $value) {
            $user_id = $value['User_id'];
        }
        $user_data = User::find($user_id);
        return view('admin.scholarshipProviderContactInfoView', compact('user_data'));
    }

    public function viewPagePublishing($id) {
        $page = Pages::where(['id' => $id])->first();
        return view('admin.viewPagePublishing', compact('page'));
    }

    public function viewManageEditPage($id) {
        $page = Pages::where(['id' => $id])->first();
        return view('admin.viewManageEditPage', compact('page'));
    }

    public function manageInternalReviewersView($id) {
        $InternalReviewer = User::where(['id' => $id, 'is_deleted' => User::NOT_DELETED])->first();
        $userRolePermission = UserRolePermission::where(['User_id' => $InternalReviewer['id']])->get();
        return view('admin.viewInternalReviewer', compact('InternalReviewer', 'userRolePermission'));
    }

    public function editInternalReviewer($id) {
        $InternalReviewer = User::where(['id' => $id, 'is_deleted' => User::NOT_DELETED])->first();
        $userRolePermission = UserRolePermission::where(['User_id' => $InternalReviewer['id']])->get();
        $permission = Permission::where(['cms_type' => Permission::NOT_CMS_TYPE])->get();
        return view('admin.editInternalReviewer', compact('InternalReviewer', 'userRolePermission', 'permission'));
    }

    public function manageScholarshipEdit($id) {
        $scholarship = Scholarship::find($id);
        $scholarship_provider_name = ScholarshipProvider::where(['id' => $scholarship->ScholarshipProvider_id])->first()->name;
        $countries = Country::all();
        $institutions = Institution::where([
                            'approval' => Institution::APPROVAL,
                            'verification' => Institution::VERIFICATION_PASS
                        ])
                        ->has('getInstitutionAdmin')->get();
        $Scholarship_Criteria = Criteria::all();
        $Scholarship_type = ScholarshipType::all();
        $scholarshipintake_data = IntakeScholarshipIntake::whereHas('getScholarshipIntake', function($q) use ($id) {
                    return $q->where('Scholarship_id', $id);
                })->get();
        $scholarshipintake = array();
        $selcted_institute_arr = array();
        foreach ($scholarshipintake_data as $intake_val) {
            $scholarshipintake[$intake_val->ScholarshipIntake_id]['start_date'] = $intake_val->getScholarshipIntake->applicationStartDate;
            $scholarshipintake[$intake_val->ScholarshipIntake_id]['end_date'] = $intake_val->getScholarshipIntake->applicationDeadline;
            $scholarshipintake[$intake_val->ScholarshipIntake_id]['seats'] = $intake_val->getScholarshipIntake->maxNumberRecipients;

            $choosen_course = $intake_val->getIntakeId->getIntakeBranch->getInstitution->name . ' - ' . $intake_val->getIntakeId->getCourseData->name . 'Intake (' . date('F Y', strtotime($intake_val->getScholarshipIntake->applicationStartDate)) . ' - ' . date('F Y', strtotime($intake_val->getScholarshipIntake->applicationDeadline)) . ')';
            $scholarshipintake[$intake_val->ScholarshipIntake_id]['course'][$intake_val->getIntakeId->id] = $choosen_course;

            $selcted_institute_arr['institutions'][] = $intake_val->getIntakeId->getIntakeBranch->getInstitution->id;
            $selcted_institute_arr['course'][] = $intake_val->getIntakeId->getCourseData->id;
            $selcted_institute_arr['intake'][] = $intake_val->getIntakeId->getIntakeBranch->getInstitution->id . '_' . $intake_val->Intake_id;
            $selcted_institute_arr['level_of_study'][] = $intake_val->getIntakeId->getIntakeBranch->getInstitution->id . '_' . $intake_val->getIntakeId->getCourseData->LevelofStudy_id;
//            $selcted_institute_arr['subdiscipline_id'][] = $intake_val->getIntakeId->getIntakeBranch->getInstitution->id . '_' . $intake_val->getIntakeId->getCourseData->Subdiscipline_id;
            foreach ($intake_val->getIntakeId->getCourseData->getCourseSubdiscipline as $courseSub) {
                $selcted_institute_arr['subdiscipline_id'][] = $intake_val->getIntakeId->getIntakeBranch->getInstitution->id . '_' . $courseSub->subdiscipline_id;
            }
        }
        return view('admin.manageScholarshipEdit', compact('scholarship', 'countries', 'institutions', 'Scholarship_Criteria', 'Scholarship_type', 'scholarshipintake', 'selcted_institute_arr', 'scholarship_provider_name'));
    }

    public function manageScholarshipUpdate($id, AddScholarship $request) {
        $get = Input::all();
        $user_id = Auth::id();
        $get['User_editedby'] = $user_id;
        $get['updated_at'] = date('Y-m-d H:i:s');
        $get['Scholarship_id'] = $id;
        Scholarship::find($id)->update($get);
        if ($get['type_name'] != '' && $get['Type_id'] == 'other') {
            $get['unique'] = 1;
            $addtype = ScholarshipType::create($get);
            $get['Type_id'] = $addtype->id;
        }
        if ($get['new_criteria_name'] != '' && $get['Criteria_id'] == 'other') {
            $get['criteria_name'] = $get['new_criteria_name'];
            $get['unique'] = 1;
            $addCriteria = Criteria::create($get);
            $get['Criteria_id'] = $addCriteria->id;
        }
        $scholarshipTypes_exist = ScholarshipTypes::where('Scholarship_id', $id)->get();
        if (count($scholarshipTypes_exist) > 0) {
            ScholarshipTypes::where('Scholarship_id', $id)->update(['Type_id' => $get['Type_id']]);
        } else {
            ScholarshipTypes::create($get);
        }
        $scholarshipCriteria_exist = ScholarshipCriteria::where('Scholarship_id', $id)->get();
        if (count($scholarshipCriteria_exist) > 0) {
            ScholarshipCriteria::where('Scholarship_id', $id)->update(['Criteria_id' => $get['Criteria_id']]);
        } else {
            ScholarshipCriteria::create($get);
        }
        if (array_key_exists('Course_intake_id', $get)) {
            if (count($get['Course_intake_id']) > 0) {
                $get_scholarship_intake = ScholarshipIntake::where(['Scholarship_id' => $id])->get();

                foreach ($get_scholarship_intake as $inkey => $invalue) {
                    IntakeScholarshipIntake::where(['ScholarshipIntake_id' => $invalue->id])->delete();
                }
                ScholarshipIntake::where(['Scholarship_id' => $id])->delete();

                for ($j = 0; $j < count($get['application_start_date']); $j++) {
                    $get['applicationStartDate'] = $get['application_start_date'][$j];
                    $get['applicationDeadline'] = $get['application_deadline'][$j];
                    $get['maxNumberRecipients'] = $get['maxNumber_recipients'][$j];
                    $added_scholarshipintake = ScholarshipIntake::create($get);
                    $get['ScholarshipIntake_id'] = $added_scholarshipintake->id;
                    if (count($get['Course_intake_id'][$j]) > 0) {
                        for ($i = 0; $i < count($get['Course_intake_id'][$j]); $i++) {
                            $get['Intake_id'] = $get['Course_intake_id'][$j][$i];
                            IntakeScholarshipIntake::create($get);
                        }
                    }
                }
            }
        }
        Scholarship::find($id)->update($get);
        return $this->sendResponse(true, route('manageScholarship'), 'Scholarship Edited successfully.');
    }

    public function getCourseAdmin() {
        $getdata = Input::all();
        $selected_course = $getdata['institution_id'];
        $institution = array_keys($getdata['institution_id']);

        $data = Branch::whereIn('Institution_id', $institution)->get();
        $main_arr = array();
        foreach ($data as $key => $value) {
            $course_list = array();
            $levelofstudy = array();
            $fieldofstudy = array();
            $check_arr2 = array();
            $main_course = $value->getintake;
            foreach ($main_course as $cour_value) {
                foreach ($cour_value->getCourseData->getIntakes as $intake_value) {
                    if ($value->id == $intake_value->Branch_id) {
                        $course_list[$cour_value->getCourseData->id][$cour_value->getCourseData->name][$intake_value->id] = $intake_value->applicationStartDate;
                    }
                }

                if (isset($cour_value->getCourseData->getLevelofStudyId->id)) {
                    $levelofstudy[$cour_value->getCourseData->getLevelofStudyId->id] = $cour_value->getCourseData->getLevelofStudyId->name;
                }

                foreach ($cour_value->getCourseData->getCourseSubdiscipline as $sub_value) {
                    $subDiscipline = $sub_value->getSubdiscipline;
                    if (!in_array($subDiscipline->name, $check_arr2)) {
                        $fieldofstudy[$subDiscipline->getFieldofStudy->id][$subDiscipline->getFieldofStudy->name][$subDiscipline->id] = $subDiscipline->name;
                        $check_arr2[] = $subDiscipline->name;
                    }
                }
                $main_arr[$value->Institution_id]['course'] = $course_list;
                $main_arr[$value->Institution_id]['levelofstudy'] = $levelofstudy;
                $main_arr[$value->Institution_id]['fieldofstudy'] = $fieldofstudy;
            }
        }
        if (count($institution) > 0) {
            $main_arr_key = array_keys($main_arr);
            for ($i = 0; $i < count($institution); $i++) {
                if (!in_array($institution[$i], $main_arr_key)) {
                    $main_arr[$institution[$i]] = array();
                }
            }
        }
        //print_r($main_arr);die;
        return view('admin.course_ajax', compact('main_arr', 'selected_course'));
    }

    public function getCourseListforLeveOfStudyAdmin() {
        $data = Input::all();
        $institution = $data['institution'];
        $level_of_study = $data['level_of_study'];
        $level_of_study_ids = array_keys($data['level_of_study']);
        $data = Courses::whereIn('LevelofStudy_id', $level_of_study)->whereHas('getIntakes', function($query) use ($institution) {
                    return $query->whereHas('getIntakeBranch', function($query) use ($institution) {
                                return $query->Where('Institution_id', $institution);
                            });
                })->get();
        $main_arr = array();
        $course_list = array();
        foreach ($data as $course_value) {
            $get_intak = $course_value->getIntakes;
            foreach ($get_intak as $cour_value) {
                $course_list[$course_value->id][$course_value->name][$cour_value->id] = $cour_value->applicationStartDate;
            }
        }
        $main_arr[$institution]['course'] = $course_list;
        //print_r($main_arr);die;
        return view('admin.courseListforLeveOfStudy', compact('main_arr', 'institution'));
    }

    public function getCourseListforsubdisciplineAdmin() {
        $data = Input::all();
        $institution = $data['institution'];
        $subdiscipline_id = $data['subdiscipline_id'];
//        $subdiscipline_ids = array_keys($data['subdiscipline_id']);
        $data = Courses::whereHas('getCourseSubdiscipline', function($q) use ($subdiscipline_id) {
                    return $q->whereIn('subdiscipline_id', $subdiscipline_id);
                })->whereHas('getIntakes', function($query) use ($institution) {
                    return $query->whereHas('getIntakeBranch', function($query) use ($institution) {
                                return $query->Where('Institution_id', $institution);
                            });
                })->get();
        $main_arr = array();
        $course_list = array();
        foreach ($data as $course_value) {
            $get_intak = $course_value->getIntakes;
            foreach ($get_intak as $cour_value) {
                $course_list[$course_value->id][$course_value->name][$cour_value->id] = $cour_value->applicationStartDate;
            }
        }
        $main_arr[$institution]['course'] = $course_list;
        //print_r($main_arr);die;
        return view('admin.courseListforLeveOfStudy', compact('main_arr', 'institution'));
    }

    public function deleteScholarship() {
        $data = Input::all();
        $getScholarship = Scholarship::find($data['id']);
        Scholarship::where('id', $data['id'])->update(['is_deleted' => Scholarship::DELETE]);
        $message = 'Your scholarship ' . $getScholarship->name . ' has been deleted by the admin';
        Common::saveNotification([User::SCHOLARSHIP_PROVIDER_ADMIN_USER, User::SCHOLARSHIP_PROVIDER_USER], 'Scholarship has been deleted', $message, route('scholarshipProviderManageScholarship'));
        return $this->sendResponse(true, route('manageScholarship'), 'Scholarship has been deleted');
    }

    public function deleteInternalReviewerAdmin() {
        $data = Input::all();
        User::where('id', $data['id'])->update(['is_deleted' => User::DELETED]);
        return $this->sendResponse(true, route('manageInternalReviewers'), 'InternalReviewer has been deleted');
    }

    public function deleteCourseAdmin() {
        $data = Input::all();
        $intake = Intake::where(['Course_id' => $data['id']])->first();

        if (isset($intake['id']) && !empty($intake['id'])) {
            $app_intake = ApplicationIntake::where(['Intake_id' => $intake['id']])->first();
            if (isset($app_intake['id']) && !empty($app_intake['id'])) {
                $message = 'This course can not be deleted. Students have already enrolled for this course.';
                return $this->sendResponse(false, '', $message);
            } else {
                ApplicationIntake::where(['Intake_id' => $intake['id']])->delete();
            }
            $coursetag_data = CourseTags::where(['Course_id' => $data['id']])->get();
            foreach ($coursetag_data as $key => $value) {
                $tag_id = $value['Tag_id'];
                CourseTags::where(['id' => $value['id']])->delete();
                //Tag::where(['id' => $tag_id])->delete();
            }
            CourseSubdiscipline::where(['course_id' => $data['id']])->delete();
            $Requirement_data = Requirement::where(['Course_id' => $data['id']])->get();
            foreach ($Requirement_data as $key => $value) {
                $pathway_data = Pathway::where(['Requirement_id' => $value['id']])->get();
                foreach ($pathway_data as $key1 => $value1) {
                    $range_data = Range::where(['pathway_id' => $value1['id']])->get();
                    foreach ($range_data as $key2 => $value2) {
                        ConditionRange::where(['range_id' => $value2['id']])->delete();
                        Range::where(['id' => $value2['id']])->delete();
                        //$value2->delete();
                    }
                    Pathway::where(['id' => $value1['id']])->delete();
                    //$value1->delete();
                }
                ReqSubdiscipline::where(['Requirement_id' => $value['id']])->delete();
                //$value->delete();
                Requirement::where(['id' => $value['id']])->delete();
            }

            $intake_data = Intake::where(['Course_id' => $data['id']])->get();
            foreach ($intake_data as $key => $value) {
                $formfile_data = FormFiles::where(['Course_id' => $data['id'], 'Intake_id' => $value['id']])->get();
                foreach ($formfile_data as $key1 => $value1) {
                    FormFilesCountry::where(['FormFiles_id' => $value1['id']])->delete();
                    FormFiles::where(['id' => $value1['id']])->delete();
                    CourseApplicationForm::where(['File_id' => $value1['File_id'], 'Course_id' => $data['id']])->delete();
                    //File::where(['id'=>$value1['File_id']])->delete();
                    //$value1->delete();
                }
                Intake::where(['id' => $value['id']])->delete();
                //$value->delete();
            }
            Courses::where(['id' => $data['id']])->delete();
            $route = (isset($data['institute_data'])) ? route('manageCoursesAdmin', $data['institute_data']) : route('manageCoursesAdmin');
            return $this->sendResponse(true, $route, 'Course has been deleted successfully.');
        }
    }

    public function pauseApplicationStudent() {
        $data = Input::all();
        ApplicationIntake::where('id', $data['id'])->update(['visibility' => ApplicationIntake::HIDE_VISIBILITY]);
        return $this->sendResponse(true, route('manageStudentData'));
    }

    public function resumeApplicationStudent() {
        $data = Input::all();
        ApplicationIntake::find($data['id'])->update(['visibility' => ApplicationIntake::VISIBILITY, 'User_reviewedby' => Auth::id()]);
        return $this->sendResponse(true, route('manageStudentData'));
    }

    public function deleteApplicationStudent() {
        $data = Input::all();
        ApplicationIntake::find($data['id'])->delete();
        return $this->sendResponse(true, route('manageStudentData'));
    }

    public function sendApplicationToInstitute() {
        $data = Input::all();
        $app_data['appFeeStatus'] = ApplicationIntake::SEND_APP_FEE_STATUS;
        $app_data['step'] = ApplicationIntake::APPLICATION_STEP6;
        ApplicationIntake::where(['id' => $data['id']])->update($app_data);
        return $this->sendResponse(true, route('manageStudentData'), 'Application send to institution for varification');
    }

    public function approveCourseAdmin($id) {
        $data = Input::all();
        $data['approval'] = ($data['approval']) ? $data['approval'] : $data['approval'];
        $data['User_approvedby'] = Auth::id();
        $data['dateApproval'] = date('Y-m-d H:i:s');
        $course = Courses::find($id);
        Courses::find($id)->update($data);
        $route = (isset($data['institute_data'])) ? route('manageCoursesAdmin', $data['institute_data']) : route('manageCoursesAdmin');

        $userData = User::where(['Role_id' => User::INSTITUTION_ADMIN_USER])->whereHas('getInstitutionUser', function($query) use ($id) {
                    return $query->whereHas('getInstitution', function($query) use ($id) {
                                return $query->whereHas('getBranch', function($query) use ($id) {
                                            return $query->whereHas('getintake', function($query) use ($id) {
                                                        return $query->whereHas('getCourseData', function($query) use ($id) {
                                                                    return $query->where(['id' => $id]);
                                                                });
                                                    });
                                        });
                            });
                })->first();
        $notyUrl = route('institutionManageCourses');
        if ($data['approval'] == 1) {
            $message = $course['name'] . ' course has approved ';
            Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::COURSE_APPROVED, $message, $notyUrl, $userData->id);
        } else {
            $message = $course['name'] . ' course has declined ';
            Common::saveNotification([User::INSTITUTION_ADMIN_USER], Common::COURSE_DECLINE, $message, $notyUrl, $userData->id);
        }

        return $this->sendResponse(true, $route);
    }

    public function visibilityScholarshipProvider($id) {
        $data = Input::all();
        ScholarshipProvider::find($id)->update($data);
        $route = route('manageScholarshipProvider');
        if ($data['visibility']) {
            $userData = User::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_ADMIN_USER])->whereHas('getScholarshipProviderUser', function($q) use ($id) {
                        return $q->where(['ScholarshipProvider_id' => $id]);
                    })->first()->id;

            $notyUrl = '';
            $message = " Scholarship Provider's visibility is published";
            Common::saveNotification([User::SCHOLARSHIP_PROVIDER_ADMIN_USER], Common::SCHOLARSHIP_PROVIDER_SHOWN, $message, $notyUrl, $userData);
        } else {
            $userData = User::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_ADMIN_USER])->whereHas('getScholarshipProviderUser', function($q) use ($id) {
                        return $q->where(['ScholarshipProvider_id' => $id]);
                    })->first()->id;

            $notyUrl = '';
            $message = " Scholarship Provider's visibility is hidden";
            Common::saveNotification([User::SCHOLARSHIP_PROVIDER_ADMIN_USER], Common::SCHOLARSHIP_PROVIDER_HIDE, $message, $notyUrl, $userData);
        }
        return $this->sendResponse(true, $route);
    }

    public function verifyScholarshipProvider($id) {
        $data['User_approvedby'] = Auth::id();
        $data['approval'] = ScholarshipProvider::APPROVAL;
        $data['verified'] = ScholarshipProvider::VERIFIED;
        ScholarshipProvider::find($id)->update($data);
        $route = route('manageScholarshipProvider');
        return $this->sendResponse(true, $route);
    }

    public function manageScholarshipProvider() {
        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $scholarship_provider = ScholarshipProvider::whereHas('getScholarshipProviderUser', function($q) {
                    return $q->has('getUser');
                })
                ->where('name', 'LIKE', '%' . $search . '%')
                ->orderBy('updated_at', 'desc')
                ->paginate(Common::PAGINATION);

        $country = Country::select(['id', 'name'])->get();

        if (\Request::ajax()) {
            return view('admin.dataManageScholarshipProvider', compact('scholarship_provider', 'country'));
        }
        return view('admin.manageScholarshipProvider', compact('scholarship_provider', 'country'));
    }

    public function adminDeleteScholarshipProviderUser($userId) {
        User::find($userId)->update(['is_deleted' => User::DELETED]);
        return $this->sendResponse(true, route('manageScholarshipProvider'), 'Scholar Provider has been deleted');
    }

    public function viewStudentApplication() {
        return view('admin.viewStudentApplication');
    }

    public function studentViewApplication($id) {
        $studentAppData = ApplicationIntake::find($id);
        if ($studentAppData['step'] == ApplicationIntake::APPLICATION_STEP0) {
            $studentData = Student::where(['id' => $studentAppData->Student_id])->first();
            return view('admin.studentViewApplicationDraft', compact('studentAppData', 'studentData'));
        } else {
            abort(404);
        }
    }

    public function studentViewApplicationPendingReview($id) {
        $studentAppData = ApplicationIntake::find($id);
        if ($studentAppData['step'] == ApplicationIntake::APPLICATION_STEP1) {
            $studentData = Student::where(['id' => $studentAppData->Student_id])->first();
            $formFileConition = FormFilesCondition::get();
            $country = Country::get();
            return view('admin.studentViewApplicationPendingReview', compact('studentAppData', 'studentData', 'formFileConition', 'country'));
        } else {
            abort(404);
        }
    }

    public function studentViewApplicationPendingFees($id) {
        $studentAppData = ApplicationIntake::find($id);
        if ($studentAppData['step'] == ApplicationIntake::APPLICATION_STEP6 || $studentAppData['step'] == ApplicationIntake::APPLICATION_STEP5 || $studentAppData['step'] == ApplicationIntake::APPLICATION_STEP4) {
            $studentData = Student::where(['id' => $studentAppData->Student_id])->first();
            return view('admin.studentViewApplicationPendingFees', compact('studentAppData', 'studentData', 'id'));
        } else {
            abort(404);
        }
    }

    public function studentViewApplicationTutionFeesPayment($id) {
        $studentAppData = ApplicationIntake::find($id);
        if ($studentAppData['step'] == ApplicationIntake::APPLICATION_STEP10 || $studentAppData['step'] == ApplicationIntake::APPLICATION_STEP8) {
            $studentData = Student::where(['id' => $studentAppData->Student_id])->first();
            $formFileConition = FormFilesCondition::get();
            $country = Country::get();
            return view('admin.studentViewApplicationTutionFeesPayment', compact('studentAppData', 'studentData', 'formFileConition', 'country'));
        } else {
            abort(404);
        }
    }

    public function studentViewApplicationPendingCommission($id) {
        $studentAppData = ApplicationIntake::find($id);
        if ($studentAppData['step'] == ApplicationIntake::APPLICATION_STEP11) {
            $studentData = Student::where(['id' => $studentAppData->Student_id])->first();
            return view('admin.studentViewApplicationPendingCommission', compact('studentAppData', 'studentData'));
        } else {
            abort(404);
        }
    }

    public function studentViewApplicationCommission($id) {
        $studentAppData = ApplicationIntake::find($id);
        if ($studentAppData['step'] == ApplicationIntake::APPLICATION_STEP12) {
            $studentData = Student::where(['id' => $studentAppData->Student_id])->first();
            return view('admin.studentViewApplicationCommission', compact('studentAppData', 'studentData'));
        } else {
            abort(404);
        }
    }

    public function getManageStudentDataInDraft() {
        $get = Input::all();
        $draftSearch = isset($get['draftSearch']) && !empty($get['draftSearch']) ? $get['draftSearch'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';
        $ApplicationDraftData = ApplicationIntake::where(['step' => ApplicationIntake::APPLICATION_DRAFT]);
        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {

                $ApplicationDraftData = $ApplicationDraftData->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }
        if ($draftSearch) {
            $ApplicationDraftData->where(function($query1) use($draftSearch) {
                $query1->whereHas('getStudent', function($q) use ($draftSearch) {
                    return $q->whereHas('getUserDetails', function($query) use ($draftSearch) {
                                return $query->where('firstName', 'LIKE', '%' . $draftSearch . '%')
                                                ->orWhere('lastName', 'LIKE', '%' . $draftSearch . '%')
                                                ->orWhere('email', 'LIKE', '%' . $draftSearch . '%');
                            })->orWhereHas('getCountry', function($c)use ($draftSearch) {
                                return $c->where('name', 'LIKE', '%' . $draftSearch . '%');
                            });
                })->orWhere('id', 'LIKE', '%' . $draftSearch . '%');
            });
        }
        return $ApplicationDraft = $ApplicationDraftData->paginate(Common::PAGINATION, ['*'], 'inDraftApplication');
    }

    public function getManageStudentDataPanding() {
        $get = Input::all();
        $pendingSearch = isset($get['pendingSearch']) && !empty($get['pendingSearch']) ? $get['pendingSearch'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';
        $ApplicationPendingData = ApplicationIntake::where(['step' => ApplicationIntake::APPLICATION_STEP1,]);
        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {
                $ApplicationPendingData = $ApplicationPendingData->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }
        if ($pendingSearch) {
            $ApplicationPendingData->where(function($q1) use($pendingSearch) {
                $q1->whereHas('getStudent', function($query) use ($pendingSearch) {
                    return $query->whereHas('getUserDetails', function($q) use ($pendingSearch) {
                                return $q->where('firstName', 'LIKE', '%' . $pendingSearch . '%')
                                                ->orWhere('lastName', 'LIKE', '%' . $pendingSearch . '%')
                                                ->orWhere('email', 'LIKE', '%' . $pendingSearch . '%');
                            })->orWhereHas('getCountry', function($c)use ($pendingSearch) {
                                return $c->where('name', 'LIKE', '%' . $pendingSearch . '%');
                            });
                })->orWhere('id', 'LIKE', '%' . $pendingSearch . '%');
            });
        }

        return $ApplicationPending = $ApplicationPendingData->paginate(Common::PAGINATION, ['*'], 'pandingApplication');
    }

    public function getManageStudentDataPandingApplicationFee() {
        $get = Input::all();
        $pendingFeeSearch = isset($get['pendingFeeSearch']) && !empty($get['pendingFeeSearch']) ? $get['pendingFeeSearch'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';

        $ApplicationPendingFeesData = ApplicationIntake::where(function($q) {
                    $q->where('step', ApplicationIntake::APPLICATION_STEP4)->orWhere('step', ApplicationIntake::APPLICATION_STEP5)->orWhere('step', ApplicationIntake::APPLICATION_STEP6);
                })->where(['InternalReviewerEligibility' => ApplicationIntake::INTERNAL_REVIEW_ELIGIBLE, 'User_reviewedby' => Auth::id()]);

        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {
                $ApplicationPendingFeesData = $ApplicationPendingFeesData->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }

        if ($pendingFeeSearch) {
            $ApplicationPendingFeesData->where(function($q1) use($pendingFeeSearch) {

                $q1->whereHas('getStudent', function($query) use ($pendingFeeSearch) {
                    return $query->whereHas('getUserDetails', function($q) use ($pendingFeeSearch) {
                                return $q->where('firstName', 'LIKE', '%' . $pendingFeeSearch . '%')
                                                ->orWhere('lastName', 'LIKE', '%' . $pendingFeeSearch . '%')
                                                ->orWhere('email', 'LIKE', '%' . $pendingFeeSearch . '%');
                            })->orWhereHas('getCountry', function($c)use ($pendingFeeSearch) {
                                return $c->where('name', 'LIKE', '%' . $pendingFeeSearch . '%');
                            });
                })->orWhere('id', 'LIKE', '%' . $pendingFeeSearch . '%');
            });
        }
        return $ApplicationPendingFees = $ApplicationPendingFeesData->paginate(Common::PAGINATION, ['*'], 'pandingApplicationFee');
    }

    public function getManageStudentPandingTutionFee() {
        $get = Input::all();
        $tutionFeeSearch = isset($get['tutionFeeSearch']) && !empty($get['tutionFeeSearch']) ? $get['tutionFeeSearch'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';
        $ApplicationTuitionFeesData = ApplicationIntake::where(function($q) {
                    return $q->where(['step' => ApplicationIntake::APPLICATION_STEP8])
                                    ->orWhere(['step' => ApplicationIntake::APPLICATION_STEP10]);
                });
        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {
                $ApplicationTuitionFeesData = $ApplicationTuitionFeesData->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }
        if ($tutionFeeSearch) {
            $ApplicationTuitionFeesData->where(function($q1) use($tutionFeeSearch) {
                $q1->whereHas('getStudent', function($query) use ($tutionFeeSearch) {
                    return $query->whereHas('getUserDetails', function($q) use ($tutionFeeSearch) {
                                return $q->where('firstName', 'LIKE', '%' . $tutionFeeSearch . '%')
                                                ->orWhere('lastName', 'LIKE', '%' . $tutionFeeSearch . '%')
                                                ->orWhere('email', 'LIKE', '%' . $tutionFeeSearch . '%');
                            })->orWhereHas('getCountry', function($c)use ($tutionFeeSearch) {
                                return $c->where('name', 'LIKE', '%' . $tutionFeeSearch . '%');
                            });
                })->orWhere('id', 'LIKE', '%' . $tutionFeeSearch . '%');
            });
        }
        return $ApplicationTuitionFees = $ApplicationTuitionFeesData->paginate(Common::PAGINATION, ['*'], 'pandingTutionFee');
    }

    public function getManageStudentPandingCommission() {
        $get = Input::all();
        $commissionSearch = isset($get['commissionSearch']) && !empty($get['commissionSearch']) ? $get['commissionSearch'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';
        $ApplicationCommissionClaimData = ApplicationIntake::where(['step' => ApplicationIntake::APPLICATION_STEP11]);
        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {
                $ApplicationCommissionClaimData = $ApplicationCommissionClaimData->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }
        if ($commissionSearch) {
            $ApplicationCommissionClaimData->where(function($q1) use($commissionSearch) {

                $q1->whereHas('getStudent', function($query) use ($commissionSearch) {
                    return $query->whereHas('getUserDetails', function($q) use ($commissionSearch) {
                                return $q->where('firstName', 'LIKE', '%' . $commissionSearch . '%')
                                                ->orWhere('lastName', 'LIKE', '%' . $commissionSearch . '%')
                                                ->orWhere('email', 'LIKE', '%' . $commissionSearch . '%');
                            })->orWhereHas('getCountry', function($c)use ($commissionSearch) {
                                return $c->where('name', 'LIKE', '%' . $commissionSearch . '%');
                            });
                })->orWhere('id', 'LIKE', '%' . $commissionSearch . '%');
            });
        }
        return $ApplicationCommissionClaim = $ApplicationCommissionClaimData->paginate(Common::PAGINATION, ['*'], 'pandingCommissionClaim');
    }

    public function getManageStudentCommission() {
        $get = Input::all();
        $commissionSearchAdmin = isset($get['commissionSearchAdmin']) && !empty($get['commissionSearchAdmin']) ? $get['commissionSearchAdmin'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';
        $ApplicationCommissionClaimData = ApplicationIntake::where(['step' => ApplicationIntake::APPLICATION_STEP12, 'commissionStatus' => ApplicationIntake::COMMISSION_CLAIM]);
        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {
                $ApplicationCommissionClaimData = $ApplicationCommissionClaimData->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }
        if ($commissionSearchAdmin) {
            $ApplicationCommissionClaimData->where(function($q1) use($commissionSearchAdmin) {
                $q1->whereHas('getStudent', function($query) use ($commissionSearchAdmin) {
                    return $query->whereHas('getUserDetails', function($q) use ($commissionSearchAdmin) {
                                return $q->where('firstName', 'LIKE', '%' . $commissionSearchAdmin . '%')
                                                ->orWhere('lastName', 'LIKE', '%' . $commissionSearchAdmin . '%')
                                                ->orWhere('email', 'LIKE', '%' . $commissionSearchAdmin . '%');
                            })->orWhereHas('getCountry', function($c)use ($commissionSearchAdmin) {
                                return $c->where('name', 'LIKE', '%' . $commissionSearchAdmin . '%');
                            });
                })->orWhere('id', 'LIKE', '%' . $commissionSearchAdmin . '%');
            });
        }
        return $ApplicationCommissionClaimForAdmin = $ApplicationCommissionClaimData->paginate(Common::PAGINATION, ['*'], 'CommissionClaimAdmin');
    }

    public function manageStudentDataInDraft() {
        $ApplicationDraft = $this->getManageStudentDataInDraft();
        return view('admin.dataManageStudentDataInDraft', compact('ApplicationDraft'));
    }

    public function manageStudentDataPanding() {
        $ApplicationPending = $this->getManageStudentDataPanding();
        return view('admin.dataManageStudentApplicationPending', compact('ApplicationPending'));
    }

    public function manageStudentDataPandingApplicationFee() {
        $ApplicationPendingFees = $this->getManageStudentDataPandingApplicationFee();
        return view('admin.dataManageStudentApplicationPandingFee', compact('ApplicationPendingFees'));
    }

    public function manageStudentPandingTutionFee() {
        $ApplicationTuitionFees = $this->getManageStudentPandingTutionFee();
        return view('admin.dataManageStudentPandingTutionFee', compact('ApplicationTuitionFees'));
    }

    public function manageStudentPandingCommission() {
        $ApplicationCommissionClaim = $this->getManageStudentPandingCommission();
        return view('admin.dataManageStudentPandingCommission', compact('ApplicationCommissionClaim'));
    }

    public function manageStudentCommission() {
        $ApplicationCommissionClaimForAdmin = $this->getManageStudentCommission();
        return view('admin.dataManageStudentCommission', compact('ApplicationCommissionClaimForAdmin'));
    }

    public function manageStudentData() {
        $ApplicationDraft = $this->getManageStudentDataInDraft();
        $ApplicationPending = $this->getManageStudentDataPanding();
        $ApplicationPendingFees = $this->getManageStudentDataPandingApplicationFee();
        $ApplicationTuitionFees = $this->getManageStudentPandingTutionFee();
        $ApplicationCommissionClaim = $this->getManageStudentPandingCommission();
        $ApplicationCommissionClaimForAdmin = $this->getManageStudentCommission();
        return view('admin.manageStudentData', compact('ApplicationDraft', 'ApplicationPending', 'ApplicationPendingFees', 'ApplicationTuitionFees', 'ApplicationCommissionClaim', 'ApplicationCommissionClaimForAdmin'));
    }

    public function manageUsers() {
        $get = Input::all();
        $pages = Pages::all();
        $mustGetApprovedUser = User::where(['is_deleted' => User::NOT_DELETED])
                ->where(['Role_id' => User::CMSUSER])
                ->get();

        $searchname = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $permissions = Permission::select()->where(['cms_type' => Permission::CMS_TYPE])->get();
        $userdeatils['permissions'] = $permissions;

        $userdata = User::select(['id', 'firstName', 'lastName', 'email'])
                ->where(['is_deleted' => User::NOT_DELETED])
                ->where(function($query) use ($searchname) {
                    return $query->where('firstName', 'LIKE', '%' . $searchname . '%');
                })
                // ->whereHas('getUserRolePermission', function($query) {
                //     return $query->whereHas('getRolePermission', function($q) {
                //                 return $q->whereHas('getPermission', function($q) {
                //                             return $q->where(['cms_type' => Permission::CMS_TYPE]);
                //                         });
                //             });
                // })
                ->where(['Role_id' => User::CMSUSER])
                ->orderBy('updated_at', 'desc')
                ->paginate(Common::PAGINATION);
        $userdeatils['permissions'] = $permissions;

        foreach ($userdata as $key => $value) {
            $userdata[$key]['user'] = $value;
            $array = array();
            foreach ($value->getUserRolePermission as $userRolePermission) {
                if ($userRolePermission->granted != UserRolePermission::GRANTED) {
                    continue;
                }
                $array[] = isset($userRolePermission->getRolePermission) ? $userRolePermission->getRolePermission->getPermission : '';
            }
            $userdata[$key]['grant_permission'] = $array;
        }
        $userdeatils['userdata'] = $userdata;
        if (\Request::ajax()) {
            return view('admin.dataManageUserAdmin', ['data' => $userdeatils, 'pages' => $pages, 'mustGetApprovedUser' => $mustGetApprovedUser]);
        }
        return view('admin.manageUsers', ['data' => $userdeatils, 'pages' => $pages, 'mustGetApprovedUser' => $mustGetApprovedUser]);
    }

    public function editInternalReviewerData($id, InternalReviewer $request) {
        $data = Input::all();
        $data['Role_id'] = User::INTERNAL_REVIEWER;
        $create_user = User::find($id)->update($data);

        $data['granted'] = 1;
        $rolePermissions = RolePermission::select()->where(['Role_id' => User::INTERNAL_REVIEWER, 'granted' => 1])->get();
        UserRolePermission::where(['User_id' => $id])->delete();
        foreach ($rolePermissions as $value) {
            if (isset($data["permission"][$value['Permission_id']]) && !empty($data["permission"][$value['Permission_id']])) {
                $data['User_id'] = $id;
                $data['RolePermission_id'] = $value['id'];
                $userRolePermission = UserRolePermission::create($data);
            }
        }
        return $this->sendResponse(true, route('manageInternalReviewers'), 'Internal Reviewer updated successfully');
    }

    public function editInternalreviewerAjax($id) {
        $data = Input::all();
        $data['granted'] = 1;
        $rolePermissions = RolePermission::select()->where(['Role_id' => User::INTERNAL_REVIEWER, 'granted' => 1])->get();
        UserRolePermission::where(['User_id' => $id])->delete();
        foreach ($rolePermissions as $value) {
            if (isset($data["permission"][$value['Permission_id']]) && !empty($data["permission"][$value['Permission_id']])) {
                $data['User_id'] = $id;
                $data['RolePermission_id'] = $value['id'];
                $userRolePermission = UserRolePermission::create($data);
            }
        }
        return $this->sendResponse(true, '', 'Internal Reviewer updated successfully');
    }

    public function addInternalReviewer(InternalReviewer $request) {
        $data = Input::all();
        $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&');
        $password = substr($random, 0, 8);
        $data['password'] = Hash::make($password);

        $data['Role_id'] = User::INTERNAL_REVIEWER;
        $create_user = User::create($data);

        $data['granted'] = 1;
        $rolePermissions = RolePermission::select()->where(['Role_id' => User::INTERNAL_REVIEWER, 'granted' => 1])->get();

        foreach ($rolePermissions as $value) {
            if (isset($data["permission"][$value['Permission_id']]) && !empty($data["permission"][$value['Permission_id']])) {
                $data['User_id'] = $create_user['id'];
                $data['RolePermission_id'] = $value['id'];
                $userRolePermission = UserRolePermission::create($data);
            }
        }
        $mail_data = [
            "password" => $password,
            "email" => $data['email'],
            "firstName" => $data['firstName'],
            "lastName" => $data['lastName']
        ];
        Common::sendMail($data['email'], 'Uniexplorers', $mail_data, 'mail.addInternalReviewerMail');
        if (\Request::ajax()) {
            return $this->sendResponse(true, route('manageInternalReviewers'), 'Internal Reviewer created successfully');
        }
    }

    public function addUserCMS(ManageCMSUser $request) {
        $data = Input::all();
        $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&');
        $password = substr($random, 0, 8);
        $data['password'] = Hash::make($password);

        $data['Role_id'] = User::CMSUSER;
        $create_user = User::create($data);

        $data['granted'] = RolePermission::GRANTED_YES;
        foreach ($data['permission'] as $key => $value) {
            $rolePermissions = RolePermission::select()->where([
                        'Permission_id' => $value,
                        'granted' => RolePermission::GRANTED_YES,
                        'Role_id' => User::CMSUSER
                    ])->first();
            $data['User_id'] = $create_user['id'];
            $data['RolePermission_id'] = $rolePermissions['id'];
            $userRolePermission = UserRolePermission::create($data);
        }


        $mail_data = [
            "password" => $password,
            "email" => $data['email'],
            "firstName" => $data['firstName'],
            "lastName" => $data['lastName']
        ];
        Common::sendMail($data['email'], 'Uniexplorers', $mail_data, 'mail.addInternalReviewerMail');
        if (\Request::ajax()) {
            return $this->sendResponse(true, route('manageUsers'), 'User created successfully');
        }
    }

    public function editUserCMSView($id) {
        $permissions = Permission::select()->where(['cms_type' => Permission::CMS_TYPE])->get();

        $userdata = User::where(['id' => $id])
                ->where(['is_deleted' => User::NOT_DELETED])
                // ->whereHas('getUserRolePermission', function($query) {
                //     return $query->whereHas('getRolePermission', function($q) {
                //                 return $q->whereHas('getPermission', function($q) {
                //                             return $q->where(['cms_type' => Permission::CMS_TYPE]);
                //                         });
                //             });
                // })
                ->where(['Role_id' => User::CMSUSER])
                ->first();

        $array = array();
        foreach ($userdata->getUserRolePermission as $userRolePermission) {
            if ($userRolePermission->granted != UserRolePermission::GRANTED) {
                continue;
            }
            $array[] = isset($userRolePermission->getRolePermission) ? $userRolePermission->getRolePermission->getPermission : '';
        }
        $userdata['grant_permission'] = $array;
        $userdata['permissions'] = $permissions;
        return view('admin.editUserCMSView', ['data' => $userdata]);
    }

    public function editUserCMS(\App\Http\Requests\ManageCMSUserEdit $request, $id) {
        $data = Input::all();
        $data['Role_id'] = User::CMSUSER;
        User::find($id)->update($data);
        $data['granted'] = RolePermission::GRANTED_YES;
        $data['User_id'] = $id;
        UserRolePermission::where(['User_id' => $id])->delete();
        foreach ($data['permission'] as $key => $value) {
            $rolePermissions = RolePermission::select()->where([
                        'Permission_id' => $value,
                        'granted' => RolePermission::GRANTED_YES,
                        'Role_id' => User::CMSUSER
                    ])->first();
            $data['RolePermission_id'] = $rolePermissions['id'];
            UserRolePermission::create($data);
        }

        if (\Request::ajax()) {
            return $this->sendResponse(true, route('manageUsers'), 'User Updated successfully');
        }
    }

    public function fetchPermissionData($user_id) {
        $data = Input::all();
        $response = [];

        if ($data['check'] == 'true') {
            if ($data['permission_type'] == Permission::CAN_APPROVE_PUBLUSHING || $data['permission_type'] == Permission::PUBLISH_DIRECTLY) {
                $response['data'] = Pages::select(DB::raw('page_name as name'), 'id')->get();
                $response['tagline'] = "Please select pages";
                if ($data['permission_type'] == Permission::CAN_APPROVE_PUBLUSHING) {

                    $response['selected_data'] = ApprovePublishingPages::select(DB::raw('page_id as selected_id'))->where(['user_id' => $user_id, 'publish_directly' => ApprovePublishingPages::CAN_APPROVE_PUBLISHING])->get();
                }
                if ($data['permission_type'] == Permission::PUBLISH_DIRECTLY) {
                    $response['selected_data'] = ApprovePublishingPages::select(DB::raw('page_id as selected_id'))->where(['user_id' => $user_id, 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY])->get();
                }
            }
            if ($data['permission_type'] == Permission::MUST_GET_APPROVED) {
                $response['data'] = User::select(DB::raw('firstName as name'), 'id')->where('id', '!=', $user_id)->where(['is_deleted' => User::NOT_DELETED])
                        // ->whereHas('getUserRolePermission', function($query) {
                        //     return $query->whereHas('getRolePermission', function($q) {
                        //                 return $q->whereHas('getPermission', function($q) {
                        //                             return $q->where(['cms_type' => Permission::CMS_TYPE])
                        //                             ;
                        //                         });
                        //             });
                        // })
                        ->where(['Role_id' => User::CMSUSER])
                        ->get();
                $response['tagline'] = "Please select users";
                $response['selected_data'] = MustGetApproveUser::select(DB::raw('assignToId as selected_id'))->where(['assignFromId' => $user_id])->get();
            }
            $response['user_id'] = $user_id;
            $response['permission_type'] = $data['permission_type'];
            if (\Request::ajax()) {
                return $this->sendResponse(true, '', '', $response, 'manageCmsModal');
            }
        } else {
            $rolePermissions = RolePermission::select()->where([
                        'Permission_id' => $data['permission_type'],
                        'granted' => RolePermission::GRANTED_YES,
                        'Role_id' => User::CMSUSER
                    ])->first();
            UserRolePermission::where(['User_id' => $user_id, 'RolePermission_id' => $rolePermissions['id']])->delete();
            if ($data['permission_type'] == Permission::CAN_APPROVE_PUBLUSHING) {

                ApprovePublishingPages::where(['user_id' => $user_id, 'publish_directly' => ApprovePublishingPages::CAN_APPROVE_PUBLISHING])->delete();
            }
            if ($data['permission_type'] == Permission::PUBLISH_DIRECTLY) {
                ApprovePublishingPages::where(['user_id' => $user_id, 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY])->delete();
            }
            if ($data['permission_type'] == Permission::MUST_GET_APPROVED) {
                MustGetApproveUser::where(['assignFromId' => $user_id])->delete();
            }
            if (\Request::ajax()) {
                return $this->sendResponse(true, '', 'User Updated successfully');
            }
        }
    }

    public function deleteCmsUser($id) {
        User::find($id)->update(['is_deleted' => User::DELETED]);
        Session::put('success', 'User Has Been Deleted');
        return redirect()->route('manageUsers');
    }

    public function manageCoursesAdmin($id = null) {

        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $FOSsearch = isset($get['FOSsearch']) && !empty($get['FOSsearch']) ? $get['FOSsearch'] : [];
        $SubdisplineSearch = isset($get['SubdisplineSearch']) && !empty($get['SubdisplineSearch']) ? $get['SubdisplineSearch'] : [];
        $branchSerach = isset($get['branchSerach']) && !empty($get['branchSerach']) ? $get['branchSerach'] : '';
        $LOSsearch = isset($get['LOSsearch']) && !empty($get['LOSsearch']) ? $get['LOSsearch'] : '';

        $institution_list = Institution::where([
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->has('getInstitutionAdmin')
                ->get();

        if ($id) {
            $allBranch = Branch::where(['Institution_id' => $id])->get();
        } else {
            $allBranch = Branch::get();
        }
        $allLOS = LevelOfStudy::get();
        // $allFOS = FieldOfStudy::get();
        $allFOS = FieldOfStudy::get();
        foreach ($allFOS as $key => $value) {

            $allFOS[$key]['Subdiscipline'] = Subdiscipline::select('id')
                    ->where(['FieldofStudy_id' => $value['id']])
                    ->get();
        }

        $userdata = Courses::where('name', 'like', '%' . $search . '%')
                ->whereHas('getIntakes', function($q) use ($branchSerach, $id) {
            return $q->whereHas('getIntakeBranch', function($query) use ($branchSerach, $id) {
                        $query = $query->whereHas('getInstitution', function($iq) use ($id) {
                            if ($id) {
                                $iq = $iq->where(['id' => $id]);
                            }
                            return $iq->where([
                                        'approval' => Institution::APPROVAL,
                                        'verification' => Institution::VERIFICATION_PASS
                            ]);
                        });
                        if ($branchSerach) {
                            $query = $query->where(['Branch_id' => $branchSerach]);
                        }
                        return $query;
                    });
        });
        if ($LOSsearch) {
            $userdata = $userdata->where(['LevelofStudy_id' => $LOSsearch]);
        }

        if (isset($SubdisplineSearch) && count($SubdisplineSearch) > 0) {
            // $subdisciplineIds = Subdiscipline::select('id')
            //         ->where(['FieldofStudy_id' => $FOSsearch])
            //         ->get()
            //         ->toArray();
            //$subdisciplineIds = array_column($subdisciplineIds, 'id');
            $userdata = $userdata->whereHas('getCourseSubdiscipline', function($q) use ($SubdisplineSearch) {
                return $q->whereIn('subdiscipline_id', $SubdisplineSearch);
            });
        } else {
            if (isset($FOSsearch) && count($FOSsearch) > 0) {
                //print_r($FOSsearch);die;
                $fos_subdisciplineId = Subdiscipline::select('id')
                        ->whereIn('FieldofStudy_id', $FOSsearch)
                        ->get()
                        ->toArray();
                $fos_subdisciplineId = array_column($fos_subdisciplineId, 'id');
                $userdata = $userdata->whereHas('getCourseSubdiscipline', function($q) use ($fos_subdisciplineId) {
                    return $q->whereIn('subdiscipline_id', $fos_subdisciplineId);
                });
            }
        }

        // if ($FOSsearch) {
        //     $subdisciplineIds = Subdiscipline::select('id')
        //             ->where(['FieldofStudy_id' => $FOSsearch])
        //             ->get()
        //             ->toArray();
        //     $subdisciplineIds = array_column($subdisciplineIds, 'id');
        //     $userdata = $userdata->whereHas('getCourseSubdiscipline', function($q) use ($subdisciplineIds) {
        //         return $q->whereIn('subdiscipline_id', $subdisciplineIds);
        //     });
        // }
        $userdata = $userdata->orderBy('updated_at', 'desc')->paginate(Common::PAGINATION);

        if (\Request::ajax()) {
            return view('admin.dataManageCoursesAdmin', compact('institution_list', 'userdata', 'allBranch', 'allLOS', 'allFOS'));
        }
        $subdisciplineIds = Subdiscipline::get();
        return view('admin.manageCoursesAdmin', compact('institution_list', 'userdata', 'allBranch', 'allLOS', 'allFOS', 'subdisciplineIds'));
    }

    public function viewActivityAdmin($id = null) {
        $user = User::find($id);
        $permission_data = [];
        //($user->getUserRolePermission[0]->getRolePermission->getPermission->cms_type == Permission::CMS_TYPE))
        if ($user->getUserRolePermission && isset($user->getUserRolePermission)) {
            $user_role_permissions = userRolePermission::where(['User_id' => $user->id])->get();
            foreach ($user_role_permissions as $key => $value) {
//                $permission_data['Permission_id'][] = $value->getRolePermission->Permission_id;
                $data = [];
                if (Permission::MUST_GET_APPROVED == $value->getRolePermission->Permission_id) {
                    $mustGetApproveUser = MustGetApproveUser::select(DB::raw('assignToId as selected_id'))->where(['assignFromId' => $user->id])->get()->toArray();
                    if (count($mustGetApproveUser)) {
                        $mustGetApproveUser = array_column($mustGetApproveUser, 'selected_id');
                        $permission_data[Permission::MUST_GET_APPROVED] = count($mustGetApproveUser) ? $mustGetApproveUser : [];
                    }
                }
                if (Permission::CAN_APPROVE_PUBLUSHING == $value->getRolePermission->Permission_id) {
                    $canApprovePublisingPages = ApprovePublishingPages::select('*', DB::raw('page_id as selected_id'))->where(['user_id' => $user->id, 'publish_directly' => ApprovePublishingPages::CAN_APPROVE_PUBLISHING])->get()->toArray();
                    if (count($canApprovePublisingPages)) {
                        $canApprovePublisingPages = array_column($canApprovePublisingPages, 'selected_id');
                        $permission_data[Permission::CAN_APPROVE_PUBLUSHING] = count($canApprovePublisingPages) ? $canApprovePublisingPages : [];
                    }
                }
                if (Permission::PUBLISH_DIRECTLY == $value->getRolePermission->Permission_id) {
                    $approvePublisingPages = ApprovePublishingPages::select('*', DB::raw('page_id as selected_id'))->where(['user_id' => $user->id, 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY])->get()->toArray();
                    if (count($approvePublisingPages)) {
                        $approvePublisingPages = array_column($approvePublisingPages, 'selected_id');
                        $permission_data[Permission::PUBLISH_DIRECTLY] = count($approvePublisingPages) ? $approvePublisingPages : [];
                    }
                }
            }
        } else {
            abort(404);
        }

        $allUsers = User::select(DB::raw('firstName as name'), 'id')->where(['is_deleted' => User::NOT_DELETED])->where('id', '!=', $user->id)
                //->whereHas('getUserRolePermission', function($query) {
                //return $query;
                // ->whereHas('getRolePermission', function($q) {
                //             return $q->whereHas('getPermission', function($q) {
                //                         return $q->where(['cms_type' => Permission::CMS_TYPE])
                //                         ;
                //                     });
                //         });
                //})
                ->where(['Role_id' => User::CMSUSER])
                ->get();
        $must_get_approve_pages = [];
        if (User::find($id)['Role_id'] == User::CMSUSER) {
            $mustGetApproveUser = MustGetApproveUser::select(DB::raw('assignFromId as id'))->where(['assignToId' => $id])->get()->toArray();
            foreach ($mustGetApproveUser as $key => $value) {
                $must_get_approve_pages_data = Pages::where(['editor_id' => $value['id'], 'publish_status' => Pages::PUBLISH_PENDING])->get(['id'])->toArray();
                $must_get_approve_pages = array_merge($must_get_approve_pages, array_column($must_get_approve_pages_data, 'id'));
            }
        }
        $allPages = Pages::get();
        //page listing
        $get = Input::all();
        $name = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $pages = Pages::where(function($query) use ($name) {
                    return $query->where('page_name', 'LIKE', '%' . $name . '%')
                                    ->orWhere('url', 'LIKE', '%' . $name . '%');
                });
        $approve_pages = User::find($id)->getApprovePublishingPages->toArray();
        $approve_val = array_column($approve_pages, 'page_id');
        $approve_val = array_merge($must_get_approve_pages, $approve_val);
        $permission = User::find($id)->getUserRolePermission;
        $permission_status = 'hidden';
        foreach ($permission as $key => $value) {
            if ($value->getRolePermission['Role_id'] == User::ADMIN || $value->getRolePermission['Role_id'] == User::INTERNAL_REVIEWER) {
                $permission_status = '';
            } else {
                if ($value->getRolePermission->getPermission['id'] == Permission::CAN_APPROVE_PUBLUSHING) {
                    $permission_status = '';
                }
            }
            if ($value->getRolePermission->getPermission['id'] == Permission::CAN_APPROVE_PUBLUSHING) {
                $permission_status = '';
                //$pages = $pages->whereIn('page_id', $approve_val);
            }
        }
        if ($permission_status == 'hidden') {
            //$pages = $pages->whereIn('page_id', $approve_val);
        }

        $pages = $pages->where(['is_deleted' => 0])
//                ->where(function($q) {
//                    return $q->where(['publish_status' => Pages::PUBLISH_PENDING])
//                            ->orWhere(['publish_status' => Pages::DISAVOWED]);
//                })
                ->where(function($q) use ($id) {
                    return $q->where(['publish_by_id' => $id])
                            ->orWhere(['editor_id' => $id]);
                })
                ->whereIn('publish_status', [Pages::EDITED, Pages::PUBLISH_PENDING, Pages::DISAVOWED, Pages::PUBLISHED])
                ->orderBy('updated_at', 'desc')
                ->paginate(Common::PAGINATION);
        // ->where(function($q) use ($id) {
        //     return $q->where(['editor_id' => $id])->orWhere(['publish_by_id' => $id]);
        // })
        //->paginate(Common::PAGINATION);

        $permissions = [
            Permission::MUST_GET_APPROVED => 'Must Get Approved',
            Permission::CAN_APPROVE_PUBLUSHING => 'Can Approve Publishing',
            Permission::PUBLISH_DIRECTLY => 'Publish Directly'
        ];

        if (\Request::ajax()) {
            return view('admin.viewActivityAdminPages', compact('pages'));
        }
        return view('admin.viewActivityAdmin', compact('user', 'permission_data', 'permissions', 'pages', 'allUsers', 'allPages'));
    }

    public function viewActivityPermission() {
        $input = Input::all();
        $input = json_decode($input['data'], true);
        $userId = $input['userId'];
        $val = $input['value'];
        $action = $input['action'];
        $permission_id = $input['permission'];
        $rolePermissions = RolePermission::select()->where([
                        'Permission_id' => $permission_id,
                        'granted' => RolePermission::GRANTED_YES,
                        'Role_id' => User::CMSUSER
                    ])->first();

        if ($action) {
            $data['User_id'] = $userId;
            $data['granted'] = RolePermission::GRANTED_YES;
            $data['RolePermission_id'] = $rolePermissions['id'];
            $check_permission = UserRolePermission::where(['RolePermission_id'=> $rolePermissions['id'],'User_id' => $userId])->get()->toArray();
            if(!count($check_permission)){
                $userRolePermission = UserRolePermission::create($data);
            }

            if ($input['permission'] == Permission::CAN_APPROVE_PUBLUSHING) {
                ApprovePublishingPages::create(['user_id' => $userId, 'page_id' => $val]);
            }
            if ($input['permission'] == Permission::MUST_GET_APPROVED) {
                MustGetApproveUser::create(['assignFromId' => $userId, 'assignToId' => $val]);
            }
            if ($input['permission'] == Permission::PUBLISH_DIRECTLY) {
                ApprovePublishingPages::create(['user_id' => $userId, 'page_id' => $val, 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY]);
            }
        } else {
            if ($input['permission'] == Permission::CAN_APPROVE_PUBLUSHING) {
                ApprovePublishingPages::where(['user_id' => $userId, 'page_id' => $val])->delete();
            }
            if ($input['permission'] == Permission::MUST_GET_APPROVED) {
                MustGetApproveUser::where(['assignFromId' => $userId, 'assignToId' => $val])->delete();
            }
            if ($input['permission'] == Permission::PUBLISH_DIRECTLY) {
                ApprovePublishingPages::where(['user_id' => $userId, 'page_id' => $val, 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY])->delete();
            }
        }

        return $this->sendResponse(true, '', 'Permission Updated.');
    }

    public function adminCourseSubdispline() {
        $get = Input::all();
        $FOSsearch = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        // print_r($FOSsearch);die;
        $Subdiscipline = Subdiscipline::whereIn('FieldofStudy_id', $FOSsearch)
                ->get();
        return $this->sendResponse(true, '', '', $Subdiscipline);
        //return $this->sendResponse(true, $route, "Course approval notification has been sent for approval");
    }

    public function viewCourseDetail($id) {
        $course = Courses::find($id);
        if (!$course) {
            abort(404);
        }
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        $authUser = User::find(Auth::id());
//        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $getInstitutionId = isset($course->getIntakes[0]->getIntakeBranch->getInstitution->id) ? $course->getIntakes[0]->getIntakeBranch->getInstitution->id : '';
        $institutionDetail = [];
        if ($getInstitutionId) {
            $institutionDetail = Institution::find($getInstitutionId);
        }
        $Branch = Branch::where(['Institution_id' => $institutionDetail->id])->get();
        $Tag = Tag::get();
        $month = Month::get();
        $Condition = Condition::get();
        return view('admin.viewCourseDetail', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'month'));
    }

    public function getSubdisciplineAdmin() {
        $data = Input::all();
        if (!isset($data['FOS'])) {
            return '';
        }
        $response = Subdiscipline::with('getFieldofStudy')->whereIn('FieldofStudy_id', $data['FOS'])->get();
        if (isset($data['step']) && !empty($data['step'])) {
            return $response;
        } else {
            $data1 = [];
            foreach ($data['FOS'] as $key => $value) {
                $sub_data = 0;
                foreach ($response as $key1 => $value1) {
                    if ($value == $value1['FieldofStudy_id']) {
                        $data1[$key]['fos_name'] = $value1->getFieldofStudy['name'];
                        $data1[$key]['Subdiscipline'][$sub_data] = $value1['name'];
                        $sub_data++;
                    }
                }
                $sub_data = 0;
            }
            return array_values($data1);
        }
    }

    public function getFieldOfStudyAdmin() {
        $input = Input::all();
        $subdisciplineId = isset($input['id']) ? $input['id'] : '';
        if (!$subdisciplineId) {
            return '';
        }
        $Subdiscipline = Subdiscipline::whereIn('id', $subdisciplineId)->get();
        $fieldOfStudyId = [];
        foreach ($Subdiscipline as $sub) {
            $fosId = (isset($sub->getFieldofStudy) && $sub->getFieldofStudy) ? $sub->getFieldofStudy->id : '';
            if (!in_array($fosId, $fieldOfStudyId)) {
                $fieldOfStudyId[] = $fosId;
            }
        }
        return array_filter($fieldOfStudyId);
    }

    public function manageRankingAdmin($id = NULL) {
        list($match_qs_list, $match_the_list) = $this->matchDataForm();
        list($institution_list, $match_list, $institution_data, $qs_previousInternational, $qs_previousNational, $qs_currentInternational, $qs_currentNational, $the_previousInternational, $the_previousNational, $the_currentInternational, $the_currentNational) = $this->institutionRankingForm($id);
        //$ranking_match_list = Match::has('getInstitutionMatch', '<', 1)->get();
        // $ranking_match_list = Match::whereHas('getInstitutionMatch', function($query) use($id) {
        //                     return $query->where('Institution_id','!=',$id);
        //                 })->get();

        $ranking_match_list1 = Match::all();
        $ranking_match_list = [];
        foreach ($ranking_match_list1 as $key => $value) {
            $data = InstitutionMatch::where(['Match_id' => $value['id'], 'Institution_id' => $id])->first();
            if (empty($data) && !isset($data['id'])) {
                $ranking_match_list[] = $value;
            }
        }
        return view('admin.manageRankingAdmin', compact('institution_list', 'match_qs_list', 'match_the_list', 'match_list', 'institution_data', 'qs_previousInternational', 'qs_previousNational', 'qs_currentInternational', 'qs_currentNational', 'the_previousInternational', 'the_previousNational', 'the_currentInternational', 'the_currentNational', 'ranking_match_list'));
    }

    public function editRanking($id) {
        $editRanking_data = InstitutionMatch::find($id);
        $match_list_data = Match::find($editRanking_data['Match_id']);
        $data[0] = $editRanking_data;
        $data[1] = $match_list_data;
        $ranking_match_list = Match::has('getInstitutionMatch', '<', 1)->get();
        return view('admin.modelManageRankingEdit', compact('data', 'ranking_match_list'));
    }

    public function addRanking($id) {
        $input = Input::all();
        if ($input['type']) {
            $ranking_match_list1 = Match::where(['type' => 1])->get();
        } else {
            $ranking_match_list1 = Match::where(['type' => 0])->get();
        }
        $ranking_match_list = [];
        foreach ($ranking_match_list1 as $key => $value) {
            $data = InstitutionMatch::where(['Match_id' => $value['id'], 'Institution_id' => $id])->first();
            if (empty($data) && !isset($data['id'])) {
                $ranking_match_list[] = $value;
            }
        }
        return view('admin.addRankingModal', compact('data', 'ranking_match_list'));
    }

    public function editInternationalRanking($id) {
        $type = Input::all()['type'];
        if ($type == Institution::THE_RANKING) {
            $editRanking_data = Institution::find($id);
            $data[0] = $editRanking_data['THEPreviousInternational'];
            $data[1] = $editRanking_data['THECurrentInternational'];
        } else {
            $editRanking_data = Institution::find($id);
            $data[0] = $editRanking_data['QSPreviousInternational'];
            $data[1] = $editRanking_data['QSCurrentInternational'];
        }
        return view('admin.modelManageInternationalRankingEdit', compact('data', 'type', 'id'));
    }

    public function editMatch($id) {
        $match_data = Match::find($id);
        $matchLogic_data = MatchLogics::where(['match_id' => $match_data['id']])->get();
        return view('admin.modelEditMatchRanking', compact('match_data', 'matchLogic_data'));
    }

    public function saveEditMatch($id, MatchRanking $request) {
        $data = Input::all();
        Match::find($id)->update($data);
        MatchLogics::where(['match_id' => $id])->delete();
        $data1['match_id'] = $id;
        $data1['valueLogic'] = $data['valueLogic0'];
        $data1['value'] = $data['value0'];
        MatchLogics::create($data1);
        if (isset($data['value1']) && !empty($data['value1'])) {
            $data1['logic'] = $data['logic'];
            $data1['valueLogic'] = $data['valueLogic1'];
            $data1['value'] = $data['value1'];
            MatchLogics::create($data1);
        }
        list($match_qs_list, $match_the_list) = $this->matchDataForm();
        ob_start();
        echo view('admin.manageMatchData', compact('match_qs_list', 'match_the_list'));
        $data = ob_get_clean();
        return $this->sendResponse(true, '', 'Match has been added successfully.', ['html' => $data], 'editManageRankingAdmin');
    }

    public function saveCMSUserPages() {
        $data = Input::all();
        $message;
        if (isset($data['permission_type']) && !empty($data['permission_type'])) {
            $permission_data['granted'] = RolePermission::GRANTED_YES;
            $rolePermissions = RolePermission::select()->where([
                        'Permission_id' => $data['permission_type'],
                        'granted' => RolePermission::GRANTED_YES,
                        'Role_id' => User::CMSUSER
                    ])->first();
            UserRolePermission::where(['User_id' => $data['user_id'], 'RolePermission_id' => $rolePermissions['id']])->delete();
            $permission_data['User_id'] = $data['user_id'];
            $permission_data['RolePermission_id'] = $rolePermissions['id'];
            UserRolePermission::create($permission_data);
        }

        if ($data['permission_type'] == Permission::CAN_APPROVE_PUBLUSHING) {
            if (isset($data['user_id'])) {
                $id = $data['user_id'];
                $user = User::find($id);
                if ($user->getUserRolePermission && ($user->getUserRolePermission[0]->getRolePermission->getPermission->cms_type == Permission::CMS_TYPE)) {
                    $message = 'Page Approval Update Successfully';
                    ApprovePublishingPages::where(['user_id' => $id, 'publish_directly' => ApprovePublishingPages::CAN_APPROVE_PUBLISHING])->delete();
                    foreach ($data['permission'] as $val) {
                        ApprovePublishingPages::create(['user_id' => $id, 'page_id' => $val, 'publish_directly' => ApprovePublishingPages::CAN_APPROVE_PUBLISHING]);
                    }
                } else {
                    abort(401);
                }
            }
        }
        if ($data['permission_type'] == Permission::MUST_GET_APPROVED) {
            if (isset($data['user_id']) && isset($data['permission'])) {
                $id = $data['user_id'];
                $user = User::find($id);
                if ($user->getUserRolePermission && ($user->getUserRolePermission[0]->getRolePermission->getPermission->cms_type == Permission::CMS_TYPE)) {
                    $message = 'Page Approval Update Successfully';
                    MustGetApproveUser::where(['assignFromId' => $id])->delete();
                    foreach ($data['permission'] as $val) {
                        MustGetApproveUser::create(['assignFromId' => $id, 'assignToId' => $val]);
                    }
                } else {
                    abort(401);
                }
            }
        }
        if ($data['permission_type'] == Permission::PUBLISH_DIRECTLY) {
            if (isset($data['user_id'])) {
                $id = $data['user_id'];
                $user = User::find($id);
                if ($user->getUserRolePermission && ($user->getUserRolePermission[0]->getRolePermission->getPermission->cms_type == Permission::CMS_TYPE)) {
                    $message = 'Approve Assign Update Successfully';
                    ApprovePublishingPages::where(['user_id' => $id, 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY])->delete();
                    foreach ($data['permission'] as $val) {
                        ApprovePublishingPages::create(['user_id' => $id, 'page_id' => $val, 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY]);
                    }
                } else {
                    abort(401);
                }
            }
        }
        return $this->sendResponse(true, '', $message);
    }

    // public function editCMSUserAjax($id) {
    //     $data = Input::all();
    //     $data['granted'] = RolePermission::GRANTED_YES;
    //     UserRolePermission::where(['User_id' => $id])->delete();
    //     foreach ($data['permission'] as $key => $value) {
    //         $rolePermissions = RolePermission::select()->where([
    //                     'Permission_id' => $value,
    //                     'granted' => RolePermission::GRANTED_YES
    //                 ])->first();
    //         $data['User_id'] = $id;
    //         $data['RolePermission_id'] = $rolePermissions['id'];
    //         UserRolePermission::create($data);
    //     }
    //     if (!in_array(Permission::CAN_APPROVE_PUBLUSHING,$data['permission'])) {
    //         MustGetApproveUser::where(['assignFromId' => $id])->delete();
    //     }
    //     if (!in_array(Permission::MUST_GET_APPROVED,$data['permission'])) {
    //         ApprovePublishingPages::where(['user_id' => $id])->delete();
    //     }
    //     if (!in_array(Permission::PUBLISH_DIRECTLY,$data['permission'])) {
    //         ApprovePublishingPages::where(['user_id' => $id])->delete();
    //     }
    //     if (\Request::ajax()) {
    //         return $this->sendResponse(true, '', 'User Updated successfully');
    //     }
    // }

    public function saveEditRanking($id, MatchInstitutionRanking $request) {
        $data = Input::all();
        $data['previousNational'] = $data['previousInternational'];
        $data['currentNational'] = $data['currentInternational'];
        $data1 = InstitutionMatch::find($id);
        $data1->update($data);
        Institution::where('id', $data1['Institution_id'])->update(['User_editedby' => Auth::id(), 'dateEditing' => date('Y-m-d')]);
        list($institution_list, $match_list, $institution_data, $qs_previousInternational, $qs_previousNational, $qs_currentInternational, $qs_currentNational, $the_previousInternational, $the_previousNational, $the_currentInternational, $the_currentNational) = $this->institutionRankingForm($data1['Institution_id']);
        ob_start();
        echo view('admin.instituteRanking', compact('institution_list', 'match_list', 'institution_data', 'qs_previousInternational', 'qs_previousNational', 'qs_currentInternational', 'qs_currentNational', 'the_previousInternational', 'the_previousNational', 'the_currentInternational', 'the_currentNational'));
        $data = ob_get_clean();
        return $this->sendResponse(true, '', 'Match has been added successfully.', ['html' => $data], 'editInstitutionRankingForm');
    }

    public function saveEditInternationalRanking($id, MatchInternationalInstitutionRanking $request) {
        $data = Input::all();
        $data1 = [];
        if ($data['type'] == Institution::THE_RANKING) {
            $data1['THEPreviousInternational'] = $data['THEPreviousInternational'];
            $data1['THECurrentInternational'] = $data['THECurrentInternational'];
        } else {
            $data1['QSPreviousInternational'] = $data['QSPreviousInternational'];
            $data1['QSCurrentInternational'] = $data['QSCurrentInternational'];
        }

        $data1['User_editedby'] = Auth::id();
        $data1['dateEditing'] = date('Y-m-d');
        Institution::find($id)->update($data1);
        list($institution_list, $match_list, $institution_data, $qs_previousInternational, $qs_previousNational, $qs_currentInternational, $qs_currentNational, $the_previousInternational, $the_previousNational, $the_currentInternational, $the_currentNational) = $this->institutionRankingForm($id);
        ob_start();
        echo view('admin.instituteRanking', compact('institution_list', 'match_list', 'institution_data', 'qs_previousInternational', 'qs_previousNational', 'qs_currentInternational', 'qs_currentNational', 'the_previousInternational', 'the_previousNational', 'the_currentInternational', 'the_currentNational'));
        $data = ob_get_clean();
        return $this->sendResponse(true, '', 'Match has been added successfully.', ['html' => $data], 'editInstitutionRankingForm');
    }

    public function addNewFacilitiesAdmin() {
        $data = Input::all();
        $checkFacility = Facility::where(['name' => $data['name']])->first();
        if (!$checkFacility) {
            $newFacilityData = Facility::create($data);
            if (!$newFacilityData) {
                return $this->sendResponse(false, '', 'Please Refresh page and try again.');
            }
            return $this->sendResponse(true, route('manageProfile'), 'New Facility Added', $newFacilityData);
        }
        return $this->sendResponse(false, '', 'Facility already exist');
    }

    public function addMatchSubmit(MatchRanking $request) {
        $data = Input::all();
        //print_r($data['type']);die;
        $match['type'] = $data['type'];
        $match['matchby'] = $data['matchby'];
        $match['name'] = $data['name'];
        $match['User_id'] = User::find(Auth::id())->id;
        $match['dateEditing'] = date('Y-m-d H:i:s');
        $match_data = Match::create($match);
        $data1['match_id'] = $match_data->id;
        //$data1['match_id'] = 1;
        $data1['valueLogic'] = $data['valueLogic0'];
        $data1['value'] = $data['value0'];
        MatchLogics::create($data1);
        if (isset($data['value1']) && !empty($data['value1'])) {
            $data1['logic'] = $data['logic'];
            $data1['valueLogic'] = $data['valueLogic1'];
            $data1['value'] = $data['value1'];
            MatchLogics::create($data1);
        }
        list($match_qs_list, $match_the_list) = $this->matchDataForm();
        ob_start();
        echo view('admin.manageMatchData', compact('match_qs_list', 'match_the_list'));
        $data = ob_get_clean();
        return $this->sendResponse(true, '', 'Match has been added successfully.', ['html' => $data], 'manageRankingAdmin');
    }

    public function matchDataForm() {
        $match_qs_list = Match::where(['type' => Match::TYPE_QS, 'is_deleted' => Match::ACTIVE])->orderBy('name', 'asc')->get();
        $match_the_list = Match::where(['type' => Match::TYPE_THE, 'is_deleted' => Match::ACTIVE])->orderBy('name', 'asc')->get();
        return [$match_qs_list, $match_the_list];
    }

    public function addRankingSubmit($id, MatchInstitutionRanking $request) {
        $data = Input::all();
        $data['previousNational'] = $data['previousInternational'];
        $data['currentNational'] = $data['currentInternational'];
        $data['User_id'] = User::find(Auth::id())->id;
        InstitutionMatch::create($data);

        Institution::where('id', $data['Institution_id'])->update(['User_editedby' => Auth::id(), 'dateEditing' => date('Y-m-d')]);
        list($institution_list, $match_list, $institution_data, $qs_previousInternational, $qs_previousNational, $qs_currentInternational, $qs_currentNational, $the_previousInternational, $the_previousNational, $the_currentInternational, $the_currentNational) = $this->institutionRankingForm($data['Institution_id']);
        ob_start();
        echo view('admin.instituteRanking', compact('institution_list', 'match_list', 'institution_data', 'qs_previousInternational', 'qs_previousNational', 'qs_currentInternational', 'qs_currentNational', 'the_previousInternational', 'the_previousNational', 'the_currentInternational', 'the_currentNational'));
        $data = ob_get_clean();
        return $this->sendResponse(true, '', 'Match has been added successfully.', ['html' => $data], 'institutionRankingForm');
    }

    public function institutionRankingForm($id) {
        $institution_list = Institution::where(['approval' => Institution::APPROVAL, 'verification' => Institution::VERIFICATION_PASS])->has('getInstitutionAdmin')->get();
        if ($id) {
            $match_list = InstitutionMatch::where(['Institution_id' => $id, 'is_deleted' => Match::ACTIVE])->get();
            $institution_data = Institution::find($id);
            $qs_previousInternational = $institution_data->QSPreviousInternational;
            $qs_previousNational = $institution_data->QSPreviousNational;
            $qs_currentInternational = $institution_data->QSCurrentInternational;
            $qs_currentNational = $institution_data->QSCurrentNational;

            $the_previousInternational = $institution_data->THEPreviousInternational;
            $the_previousNational = $institution_data->THEPreviousNational;
            $the_currentInternational = $institution_data->THECurrentInternational;
            $the_currentNational = $institution_data->THECurrentNational;
        } else {
            $match_list = array();
            $institution_data = array();
            $qs_previousInternational = '';
            $qs_previousNational = '';
            $qs_currentInternational = '';
            $qs_currentNational = '';

            $the_previousInternational = '';
            $the_previousNational = '';
            $the_currentInternational = '';
            $the_currentNational = '';
        }
        return [$institution_list, $match_list, $institution_data, $qs_previousInternational, $qs_previousNational, $qs_currentInternational, $qs_currentNational, $the_previousInternational, $the_previousNational, $the_currentInternational, $the_currentNational];
    }

    public function deleteMatchAdmin($id) {
        //$data = Input::all();
        MatchLogics::where('match_id', $id)->delete();
        InstitutionMatch::where('Match_id', $id)->delete();
        Match::where('id', $id)->delete();
        return $this->sendResponse(true, '', 'Match has been deleted.', ['id' => $id], 'manageRankingRemoveAdmin');
    }

    public function deleteRankingAdmin($id) {
        $data = Input::all();
        $result = InstitutionMatch::find($id);
        $result->delete();
        return $this->sendResponse(true, '', 'Match has been deleted.', ['id' => $id], 'institutionRankingFormAdmin');
    }

    public function updateInstutionRankingStatus() {
        $data = Input::all();
        $result = Institution::where('id', $data['id'])->update(['rankingStatus' => $data['rankingStatus'], 'User_editedby' => Auth::id(), 'dateEditing' => date('Y-m-d')]);
        return $this->sendResponse(true, '', 'Ranking status changed.');
    }

    public function adminInstitutionAjax() {
        $data = Input::all();
        $match_list = InstitutionMatch::where(['Institution_id' => $data['institution_id']])->get();
        $institution_data = Institution::find($data['institution_id']);
        echo view('admin.manageInstitutionAdminAjax', compact('match_list', 'institution_data'));
    }

    public function getAllPage() {
        $page = Pages::distinct()->where('is_deleted', '=', '0')->get(['page_id', 'page_name']);
        return $page;
    }

    public function pageEditorAdmin() {
        if (User::find(Auth::id())['Role_id'] == User::CMSUSER) {
            $must_get_approve_pages = [];
            $cmsuser_permission = UserRolePermission::where(['User_id' => Auth::id()])->get();
            if (count($cmsuser_permission) == 0) {
                $mustGetApproveUser = MustGetApproveUser::select(DB::raw('assignFromId as id'))->where(['assignToId' => Auth::id()])->get()->toArray();
                foreach ($mustGetApproveUser as $key => $value) {
                    $must_get_approve_pages_data = Pages::where(['editor_id' => $value['id'], 'publish_status' => Pages::PUBLISH_PENDING])->get(['id'])->toArray();
                    $must_get_approve_pages = array_merge($must_get_approve_pages, array_column($must_get_approve_pages_data, 'id'));
                }
                if (count($must_get_approve_pages) == 0) {
                    abort(401);
                }
            }
        }
        $page = Pages::distinct()->where('is_deleted', '=', '0')->get(['page_id', 'page_name', 'url']);
        return view('admin.pageEditorAdmin', compact('page'));
    }

    public function hideInstitution() {
        $data = Input::all();
        Institution::find($data['id'])->update(['visibility' => $data['value']]);
        return $this->sendResponse(true, route('manageCoursesAdmin', $data['id']), 'Visibility changed successfully.');
    }

    public function resetPageEditorForm($id) {
        Pages::where(['publish_by_id' => NULL, 'page_id' => $id])->delete();
        PageSection::where(['page_id' => $id, 'flag' => 0])->delete();
        $page = Pages::distinct()->where('is_deleted', '=', '0')->get(['page_id', 'page_name']);
        return $page;
    }

    public function pageEditorForm($id) {
        $reset_btn = 'show';
        $page_data = Pages::where(['publish_by_id' => NULL, 'page_id' => $id])->first();
        if (empty($page_data)) {
            $reset_btn = 'hide';
            $page_data = Pages::where('publish_by_id', '!=', NULL)->where(['page_id' => $id])->first();
        }
        $page = Pages::where(['publish_by_id' => NULL, 'page_id' => $id]);
        if ($page_data['publish_by_id'] != Null) {
            $page = Pages::where('publish_by_id', '!=', NULL)->where(['page_id' => $id]);
        }

        $checkPageSection = PageSection::where(['page_id' => $id])->count();
        if ($checkPageSection) {
            if ($page_data['publish_by_id'] == Null) {
                $page = $page->whereHas('getPageSection', function($q) {
                            return $q->where('flag', '=', 0);
                        })->first();
            } else {
                $page = $page->whereHas('getPageSection', function($q) {
                            return $q->where('flag', '=', 1);
                        })->first();
            }
        } else {
            $page = $page->first();
        }
        $allPages = $this->getAllPage();
        return view('admin.pageEditorForm', compact('id', 'page', 'allPages', 'reset_btn'));
    }

    public function googleSetting($GAS, $GASValue, $id) {
        Pages::where(['id' => $id])->update($GASValue);
        if (empty($GAS)) {
            $page_data_id = Pages::get();
            foreach ($page_data_id as $GASkey => $Gvalue) {
                /* If we do not set this then all the record updated at will change */
                $GASValue['created_at'] = $Gvalue->created_at;
                $GASValue['updated_at'] = $Gvalue->updated_at;
                Pages::where(['page_id' => $Gvalue->page_id])->update($GASValue);
            }
        } else {
            foreach ($GAS as $GASkey => $Gvalue) {
                $getOldUpdatedAt = Pages::where(['page_id' => $Gvalue->page_id])->get();
                foreach ($getOldUpdatedAt as $okey => $oval) {
                    $GASValue['created_at'] = $oval->created_at;
                    $GASValue['updated_at'] = $oval->updated_at;
                    Pages::where(['id' => $oval->id])->update($GASValue);
                }
            }
        }
    }

    public function pageEditorSubmit($id) {
        $input = Input::all();
        $data = Pages::find($id)->first();
        $oldData = Pages::find($id)->toArray();
        $input = array_merge($oldData, $input);
        /* remove older updated at timestamp for updated timestamp */
        unset($input['created_at']);
        unset($input['updated_at']);
        $GAS = isset($input['google_anylysis_setting']) ? $input['google_anylysis_setting'] : '';
        $input['publish_by_id'] = Null;
        $input['editor_id'] = Auth::id();
        $input['edited_on'] = date('Y-m-d H:i:s');
        $is_published_default = 0;
        $must_approve_permission = 0;
        $GASValue['google_analysis'] = $input['google_analysis'];
        $permission = User::find(Auth::id())->getUserRolePermission;
        if (User::find(Auth::id())['Role_id'] == User::ADMIN) {
            $input['publish_by_id'] = Auth::id();
            $input['publish_status'] = Pages::PUBLISHED;
            $is_published_default = 1;
        }
        if (User::find(Auth::id())['Role_id'] == User::CMSUSER) {
            //PUBLISH_DIRECTLY
            //MUST_GET_APPROVED
            $input['publish_by_id'] = Auth::id();
            $input['publish_status'] = Pages::PUBLISH_PENDING;
            $is_published_default = 1;
            $cmsuser_permission = UserRolePermission::where(['User_id' => Auth::id()])->get()->toArray();
            $rolepermission_id = RolePermission::where(['Role_id' => User::CMSUSER, 'Permission_id' => Permission::CAN_APPROVE_PUBLUSHING])->first();
            if (in_array($rolepermission_id['id'], array_column($cmsuser_permission, 'RolePermission_id'))) {
                $must_approve_permission = 1;
            }
            $rolepermission_id = RolePermission::where(['Role_id' => User::CMSUSER, 'Permission_id' => Permission::MUST_GET_APPROVED])->first();
            if (in_array($rolepermission_id['id'], array_column($cmsuser_permission, 'RolePermission_id'))) {
                $must_approve_permission = 1;
            }

            $rolepermission_id = RolePermission::where(['Role_id' => User::CMSUSER, 'Permission_id' => Permission::PUBLISH_DIRECTLY])->first();
            if (in_array($rolepermission_id['id'], array_column($cmsuser_permission, 'RolePermission_id'))) {

                $publish_directly_data = ApprovePublishingPages::where(['page_id' => $id, 'user_id' => Auth::id(), 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY])->first();
                if (!empty($publish_directly_data)) {
                    $input['publish_by_id'] = Auth::id();
                    $input['publish_status'] = Pages::PUBLISHED;
                    $is_published_default = 1;
                } else {
                    $must_approve_permission = 1;
                }
            }
        }

        // foreach ($permission as $key => $value) {
        //     if ($value->getRolePermission->getPermission['id'] == Permission::MANAGE_CMS) {
        //         $input['publish_by_id'] = Auth::id();
        //         $input['publish_status'] = Pages::PUBLISHED;
        //         $is_published_default = 1;
        //     }
        //     $publish_directly_data = ApprovePublishingPages::where(['page_id' => $id, 'user_id' => Auth::id(), 'publish_directly' => ApprovePublishingPages::PUBLISH_DIRECTLY])->first();
        //     if (!empty($publish_directly_data)) {
        //         $input['publish_by_id'] = Auth::id();
        //         $input['publish_status'] = Pages::PUBLISHED;
        //         $is_published_default = 1;
        //     } else {
        //         if ($value->getRolePermission->getPermission['id'] == Permission::MUST_GET_APPROVED) {
        //             $must_approve_permission = 1;
        //         }
        //     }
        // }
        unset($input['id']);
        if ($is_published_default) {
            $update_page = Pages::where(['page_id' => $oldData['page_id']])->where(['publish_by_id' => Null])->first();
            if ($update_page) {
                Pages::where(['page_id' => $oldData['page_id']])->where('publish_by_id', '!=', Null)->delete();
                Pages::find($id)->update($input);
                $this->googleSetting($GAS, $GASValue, $id);

                $section['flag'] = 1;
                PageSection::where(['page_id' => $oldData['page_id'], 'flag' => '1'])->delete();
                PageSection::where(['page_id' => $oldData['page_id'], 'flag' => '0'])->update($section);
            } else {
                Pages::find($id)->update($input);
                $this->googleSetting($GAS, $GASValue, $id);
                PageSection::where(['page_id' => $oldData['page_id'], 'flag' => '1'])->delete();

                if (isset($input['content'])) {
                    foreach ($input['content'] as $pageSectionKey => $pageSectionValue) {
                        $pageSection = [
                            'name' => $input['name'][$pageSectionKey],
                            'page_id' => $oldData['page_id'],
                            'content' => $pageSectionValue,
                            'flag' => 1,
                            'section_type' => $input['section_type'][$pageSectionKey]
                        ];

                        PageSection::create($pageSection);
                        $section['flag'] = 1;
                        PageSection::where(['page_id' => $oldData['page_id'], 'flag' => '0'])->update($section);
                    }
                }
            }

            return $this->sendResponse(true, route('managePageAdmin'), 'Page published successfully');
        } else {
            //check if only one page is copy
            $totalCount = Pages::where(['page_id' => $oldData['page_id']])->count();
            $this->googleSetting($GAS, $GASValue, $id);
            if (!$oldData['publish_by_id'] || ($totalCount > 1)) {
                $pageUpdate = Pages::find($id)->update($input);
            } else {
                $pageUpdate = Pages::create($input);
            }
            //$checkSection = PageSection::where(['page_id' => $id])->count();
            if ($pageUpdate && isset($input['id_section'])) {
                $getOlderSection = PageSection::where(['page_id' => $oldData['page_id'], 'flag' => 0]);
                if ($getOlderSection) {
                    $getOlderSection->delete();
                }
                if (isset($input['content'])) {
                    foreach ($input['content'] as $key => $value) {
                        $pageSection = [
                            'name' => $input['name'][$key],
                            'page_id' => $oldData['page_id'],
                            'content' => $value,
                            'flag' => 0,
                            'section_type' => $input['section_type'][$key]
                        ];
                        PageSection::create($pageSection);
                    }
                }
            }

            // if ($must_approve_permission) {
            //     $assignuser = MustGetApproveUser::where(['assignFromId' => Auth::id()])->get();
            //     foreach ($assignuser as $key => $value) {
            //         if (!count(ApprovePublishingPages::where(['user_id' => $value->assignToId, 'page_id' => $id])->get())) {
            //             ApprovePublishingPages::create(['user_id' => $value->assignToId, 'page_id' => $id]);
            //         }
            //     }
            // }
            return $this->sendResponse(true, route('managePageAdmin'), 'Edited page saved as draft');
        }
    }

    public function studentApplicationInternalReview($id, StudentApplicationInternalReview $request) {
        $input = Input::all();
        $checkInternalReviewerEliginlity = ($input['InternalReviewerEligibility'] == ApplicationIntake::INTERNAL_REVIEW_ELIGIBLE) ? true : false;
        $input['InternalReviewerEligibility'] = ($input['InternalReviewerEligibility'] == ApplicationIntake::INTERNAL_REVIEW_ELIGIBLE) ? $input['InternalReviewerEligibility'] : $input['reason'];
        $input['dateReviewed'] = date('Y-m-d H:i:s');
        $input['User_reviewedby'] = Auth::id();
        ApplicationIntake::find($id)->update($input);
        $application_data = ApplicationIntake::find($id);
        $data = $application_data->getStudent->getUserDetails;
        $data->applicationintakeid = $application_data->id;
        $data->Eligibility_name = "Internal Eligibility check";
        if ($checkInternalReviewerEliginlity) {
            $input['step'] = ApplicationIntake::APPLICATION_STEP2;
            ApplicationIntake::find($id)->update($input);
            Common::sendMail($data->email, 'Uniexplorers', $data, 'mail.eligibalInInternalReview');
        } else {
            if ($input['reason'] == 4) {
                $getDeleteApplicationFile = ApplicationIntake::find($id);
                foreach ($getDeleteApplicationFile->getApplicationIntakeFiles as $val) {
                    $getIntakeFile = $val->getFile;
                    if ($getIntakeFile) {
                        Common::removeFile($getIntakeFile->name, ApplicationIntakeFile::FILE_FOLDER);
                    }
                    $val->delete();
                }
                ApplicationIntake::where(['id' => $id])->delete();
                Common::sendMail($data->email, 'Uniexplorers', $data, 'mail.deleteApplicationInInternalReviewcheck');
            }
        }
        return $this->sendResponse(true, route('manageStudentData'), 'Application Status updated successfully');
    }

    public function studentApplicationInstitutionReview($id) {
        $input = Input::all();
        $input['Dateinstitutionreview'] = date('Y-m-d H:i:s');
        $application_data = ApplicationIntake::find($id);
        $data = $application_data->getStudent->getUserDetails;
        $data->applicationintakeid = $application_data->id;
        $data->Eligibility_name = "Institution Eligibility Check";
        if ($input['InstitutionEligibility'] == ApplicationIntake::INSTITUTION_ELIGIBILITY) {
            $input['step'] = ApplicationIntake::APPLICATION_STEP7;
            Common::sendMail($data->email, 'Uniexplorers', $data, 'mail.eligibalInInternalReview');
        }
        ApplicationIntake::find($id)->update($input);
        return $this->sendResponse(true, route('manageStudentData'), 'Application Status updated successfully');
    }

    public function studentApplicationVisaCredentials($id, studentApplicationVisaCredentials $request) {
        $input = Input::all();
        $application = ApplicationIntake::find($id);
        if ($application->step == 10) {
            $input['step'] = ApplicationIntake::APPLICATION_STEP11;
        }
        $application->update($input);
        return $this->sendResponse(true, route('manageStudentData'), 'Application Status updated successfully');
    }

    public function studentApplicationFinalStep($id, studentApplicationFinalStep $request) {

        $input = Input::all();
        if (isset($input['Commission_claimed_payment_slip'])) {
            $copiedFile = Common::copyTempFile($input['Commission_claimed_payment_slip'], 'studentApplication');
            $fileData['name'] = $copiedFile['name'];
            $fileData['URL'] = $copiedFile['url'];
        }
        if (isset($input['Proff_of_payment_transfer'])) {
            $copiedFile1 = Common::copyTempFile($input['Proff_of_payment_transfer'], 'studentApplication');
            $fileData1['name'] = $copiedFile1['name'];
            $fileData1['URL'] = $copiedFile1['url'];
        }

        $input['visaStatus'] = isset($input['visaStatus']) ? 1 : 0;
        $input['commissionStatus'] = isset($input['commissionStatus']) ? 1 : 0;
        $input['step'] = ApplicationIntake::APPLICATION_STEP12;
        $application = ApplicationIntake::find($id)->update($input);
        $application_data = ApplicationIntake::find($id);
        if ($application) {
            if (isset($fileData)) {
                $createdFile = File::create($fileData);
                $applicationIntakeData = [
                    'File_id' => $createdFile->id,
                    'Application_id' => $id,
                    'type' => ApplicationIntakeFile::COMMISSION_CLAIMED_PAYMENT_SLIP
                ];
                ApplicationIntakeFile::create($applicationIntakeData);
            }

            if (isset($fileData1)) {
                $createdFile1 = File::create($fileData1);
                $applicationIntakeData1 = [
                    'File_id' => $createdFile1->id,
                    'Application_id' => $id,
                    'type' => ApplicationIntakeFile::PROFF_OF_PAYMENT_TRANSFER
                ];
                ApplicationIntakeFile::create($applicationIntakeData1);
            }
        }

        if ($input['refundStatus'] == 1) {
            $data = $application_data->getStudent->getUserDetails;
            $data->applicationintakeid = $application_data->id;
            Common::sendMail($data->email, 'Uniexplorers', $data, 'mail.eligibalForRefund');
        }
        return $this->sendResponse(true, route('manageStudentData'), 'Application Status updated successfully');
    }

    public function updatePageApprovalPermission() {
        $input = Input::all();
        if (isset($input['userId']) && isset($input['pageIds'])) {
            $id = $input['userId'];
            $user = User::find($id);
            if ($user->getUserRolePermission && ($user->getUserRolePermission[0]->getRolePermission->getPermission->cms_type == Permission::CMS_TYPE)) {
                ApprovePublishingPages::where(['user_id' => $id])->delete();
                foreach ($input['pageIds'] as $val) {
                    ApprovePublishingPages::create(['user_id' => $id, 'page_id' => $val]);
                }
                return $this->sendResponse(true, '', 'Page Approval Update Successfully');
            } else {
                abort(401);
            }
        }
        abort(404);
    }

    public function updateMustGetApprovePermission() {
        $input = Input::all();

        if (!isset($input['assignToId'])) {
            $input['assignToId'] = [];
        }

        if (isset($input['assignFromId']) && isset($input['assignToId'])) {
            $id = $input['assignFromId'];
            $user = User::find($id);
            if ($user->getUserRolePermission && ($user->getUserRolePermission[0]->getRolePermission->getPermission->cms_type == Permission::CMS_TYPE)) {
                MustGetApproveUser::where(['assignFromId' => $id])->delete();
                foreach ($input['assignToId'] as $val) {
                    MustGetApproveUser::create(['assignFromId' => $id, 'assignToId' => $val]);
                }
                return $this->sendResponse(true, '', 'Approve Assign Update Successfully');
            } else {
                abort(401);
            }
        }
        abort(404);
    }

    public function editApplicationOtherFormAdmin(CourseOtherForm $request, $id) {
        $input = Input::all();
        $file;

        $tempPath = public_path() . '/temp/';
        $filePath_to = public_path() . '/' . Courses::APPLICATION_FOLDER . '/';
        if (!file_exists($filePath_to)) {
            mkdir($filePath_to, 0777, true);
        }

        if (isset($input['file']) && $input['file']) {
            $fileUrl = url('/') . '/' . Courses::APPLICATION_FOLDER . '/' . $input['file'];
            $input['name'] = $input['file'];
            $input['URL'] = $fileUrl;
            $from = $tempPath . $input['file'];
            $filePath_to = $filePath_to . $input['file'];
            copy($from, $filePath_to);

            $file_data = File::create($input);
            if ($file_data) {
                $input['File_id'] = $file_data['id'];
                $input['Course_id'] = $id;
                $Intake_ids = $input['Intake_id'];
                unset($input['Intake_id']);
                foreach ($Intake_ids as $intake_value) {
                    $input['Intake_id'] = $intake_value;
                    $formFilesData = FormFiles::create($input);
                }
                if (isset($input['Country_id']) && !empty($input['Country_id'])) {
                    foreach ($input['Country_id'] as $val) {
                        $data['Country_id'] = $val;
                        $data['FormFiles_id'] = $formFilesData->id;
                        FormFilesCountry::create($data);
                    }
                }
                $formFilesData = FormFiles::find($formFilesData->id);
                $formFilesData->getFile = $formFilesData->getFile;
                return $this->sendResponse(true, '', 'Other Form Uploaded', $formFilesData, 'otherFileUploaded');
            }
        }
        return $this->sendResponse(false, '', 'Please Select File');
    }

    public function removeOtherFormAdmin($id) {
        $getFormFiles = FormFiles::find($id);
        if ($getFormFiles) {
            FormFilesCountry::where(['FormFiles_id' => $id])->delete();
            $getFileData = File::find($getFormFiles->File_id);
            $fileExistPath = Courses::APPLICATION_FOLDER;
            Common::removeFile($getFileData->name, $fileExistPath);
            $getFormFiles->delete();
            $getFileData->delete();
        } else {
            return $this->sendResponse('false', '', 'File Not Found');
        }
    }

    /////////////////////// add/edit course functionality////////////////////////
    // add courses

    public function addTagsAdmin() {
        $data = Input::all();
        $tag_data = Tag::where(['name' => $data['name']])->get();
        if (count($tag_data) == 0) {
            Tag::create($data);
            $tags = Tag::get();
            return $this->sendResponse(true, '', 'Tag added successfully', $tags);
            //return $tags;
        }
        return $this->sendResponse(false, '', 'Tag already exists');
    }

    public function addCourseAdmin($id) {
        //$authUser = User::find(Auth::id());
        // $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        // if (!$institutionDetail->approval) {
        //     abort(401);
        // }
        $course_id = isset(Input::all()['course_id']) ? Input::all()['course_id'] : '';
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        $Branch = Branch::where(['Institution_id' => $id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        return view('admin.addCourseAdmin', compact('levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'course_id', 'TypeOfValue', 'Condition'));
    }

    public function adminEditCoursesBasicDetailsView($Institution_id, $id) {
        $course = Courses::find($id);
        if (!$course) {
            abort(404);
        }
        $course_tag = CourseTags::where(['Course_id' => $id])->get(['Tag_id'])->toArray();
        $course_tag = array_column($course_tag, 'Tag_id');
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        //$authUser = User::find(Auth::id());
        //$institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $Branch = Branch::where(['Institution_id' => $Institution_id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        return view('admin.adminEditCoursesStep1', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'course_tag'));
    }

    public function addCoursesBasicDetailsAdmin(AddCourses $request) {
        $data = Input::all();
        $data["step_count"] = 1;
        $subdisciplineId = $data['Subdiscipline_id'];
        unset($data['Subdiscipline_id']);
        $data["User_editedby"] = Auth::id();
        $data['slug'] = Courses::slug($data['name']);
        $tag_data = $data['tag_id'];
        unset($data['tag_id']);
        $course_data = Courses::create($data);
        $coursetag_data['Course_id'] = $course_data['id'];
        if (isset($tag_data) && count($tag_data)) {
            foreach ($tag_data as $key => $value) {
                $coursetag_data['Tag_id'] = $value;
                CourseTags::create($coursetag_data);
            }
        }
        foreach ($subdisciplineId as $subValue) {
            $subData = ['subdiscipline_id' => $subValue, 'course_id' => $course_data->id];
            CourseSubdiscipline::create($subData);
        }
        // $authUser = User::find(Auth::id());
        // $institutionDetail_slug = $authUser->getInstitutionUser->getInstitution->slug;
        $institutionDetail_slug = Institution::where(['id' => $data['Institution_id']])->first()->slug;
        Courses::createCoursePage($course_data->id, $institutionDetail_slug);
        // print_r($data['Institution_id']);
        // print_r($course_data->id);die;
        return $this->sendResponse(true, route('adminEditRequirmentsPathwayView', ['id' => $course_data->id, 'Institution_id' => $data['Institution_id']]));
    }

    public function adminEditCoursesBasicDetails($id, AddCourses $request) {
        //AddCourses $request
        $data = Input::all();
        if (!$data['interview']) {
            $data['interviewDetails'] = NULL;
        }
        if (!$data['researchProposal']) {
            $data['researchProposalDetails'] = NULL;
            CourseApplicationForm::where(['File_type' => CourseApplicationForm::RESEARCH_PROPOSAL_FORM, 'Course_id' => $id])->delete();
        }
        $coursetag_data['Course_id'] = $id;
        CourseTags::where(['Course_id' => $id])->delete();
        if (isset($data['tag_id']) && count($data['tag_id'])) {
            foreach ($data['tag_id'] as $key => $value) {
                $coursetag_data['Tag_id'] = $value;
                CourseTags::create($coursetag_data);
            }
        }
        unset($data['tag_id']);
        $data["step_count"] = 1;
        $subdisciplineId = $data['Subdiscipline_id'];
        unset($data['Subdiscipline_id']);
        $data["User_editedby"] = Auth::id();
        $data['approval'] = Courses::PENDING_APPROVAL;
        $data['User_approvedby'] = Courses::PENDING_APPROVAL;
        $data['dateSubmission'] = date('Y-m-d H:i:s');
        unset($data['name']);
        Courses::find($id)->update($data);
        CourseSubdiscipline::where(['course_id' => $id])->delete();
        foreach ($subdisciplineId as $subValue) {
            $subData = ['subdiscipline_id' => $subValue, 'course_id' => $id];
            CourseSubdiscipline::create($subData);
        }
        // $authUser = User::find(Auth::id());
        $institutionDetail = Institution::where(['id' => $data['Institution_id']])->first();
        $Course = Courses::find($id);
        $message = $institutionDetail->name . ' Institute wants approval of course ' . $Course['name'];
        $notyUrl = route('manageCoursesAdmin') . "/" . $data['Institution_id'];
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], Common::COURSE_FOR_APPROVAL, $message, $notyUrl);

        return $this->sendResponse(true, route('adminEditRequirmentsPathwayView', ['id' => $Course['id'], 'Institution_id' => $data['Institution_id']]));
    }

    public function adminEditRequirmentsPathwayView($Institution_id, $id) {
        $course = Courses::find($id);
        if (!$course) {
            abort(404);
        }
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        $authUser = User::find(Auth::id());
        $institutionDetail = Institution::where(['id' => $Institution_id])->first();
        $Branch = Branch::where(['Institution_id' => $institutionDetail->id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        return view('admin.adminEditCoursesStep2', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition'));
    }

    public function adminEditRequirmentsPathway($id, AddRequirments $request) {
        $data = Input::all();
        $course_data["User_editedby"] = Auth::id();
        Courses::find($id)->update($course_data);
//        Requirement -> getPathway -> getRange -> getConditionRange
//        Requirement -> getReqSubdiscipline
//        delete data requirement start

        $getRequirement = Requirement::where(['Course_id' => $id])->get();
        foreach ($getRequirement as $rkey => $rval) {
            if ((isset($data['update_requirement_id'])) && ($data['update_requirement_id'] == $rval->id)) {
                if (isset($rval->getPathway) && isset($rval->getPathway->getRange)) {
                    foreach ($rval->getPathway->getRange as $rangeKey => $rangeValue) {
                        foreach ($rangeValue->getConditionRange as $crval) {
                            $crval ? $crval->delete() : '';
                        }
                        $rangeValue ? $rangeValue->delete() : '';
                    }
                }
                foreach ($rval->getReqSubdiscipline as $rsval) {
                    $rsval ? $rsval->delete() : '';
                }
                $rval->getPathway ? $rval->getPathway->delete() : '';
                $rval ? $rval->delete() : '';
            }
        }

        $course_id = $id;
        $ReqSubdiscipline_data;
        $condition_range_data;
        foreach ($data['requirement'] as $value) {
            $value['Course_id'] = $course_id;
            $requirement_data = Requirement::create($value);
            $ReqSubdiscipline_data['Requirement_id'] = $requirement_data['id'];
            $pathway_data = Pathway::create($ReqSubdiscipline_data);
            $range_pathway['pathway_id'] = $pathway_data['id'];
            if (isset($value['fieldOfStudy']) && !empty($value['fieldOfStudy'])) {
                foreach ($value['fieldOfStudy'] as $value1) {
                    $Subdiscipline_data = Subdiscipline::where(['FieldofStudy_id' => $value1])->get();
                    foreach ($Subdiscipline_data as $value2) {
                        $ReqSubdiscipline_data['Subdiscipline_id'] = $value2->FieldofStudy_id;
                        ReqSubdiscipline::create($ReqSubdiscipline_data);
                    }
                }
            }
            foreach ($value['range'] as $value3) {
                $range_data = Range::create($range_pathway);
                $condition_range_data['range_id'] = $range_data['id'];
                foreach ($value3['value'] as $key => $value4) {
                    if ($value3['name'][$key]) {
                        $condition_range_data['Condition_id'] = $value3['name'][$key];
                        $condition_range_data['value'] = $value4 ?: 0;
                        ConditionRange::create($condition_range_data);
                    }
                }
            }
        }

        $reqId = $requirement_data ? $requirement_data->id : '';
        return $this->sendResponse(true, '', 'Pathway update successfully', ['reqId' => $reqId, 'deleteUrl' => route('deleteRequirement', $reqId)], 'addPathway');
    }

    public function adminDeleteRequirement($id) {
        $getRequirement = Requirement::find($id);
        if (isset($getRequirement->getPathway) && isset($getRequirement->getPathway->getRange)) {
            foreach ($getRequirement->getPathway->getRange as $rangeKey => $rangeValue) {
                foreach ($rangeValue->getConditionRange as $crval) {
                    $crval ? $crval->delete() : '';
                }
                $rangeValue ? $rangeValue->delete() : '';
            }
        }
        foreach ($getRequirement->getReqSubdiscipline as $rsval) {
            $rsval ? $rsval->delete() : '';
        }
        $getRequirement->getPathway ? $getRequirement->getPathway->delete() : '';
        $getRequirement ? $getRequirement->delete() : '';
    }

    public function adminEditCoursesIntakeView($Institution_id, $id) {
        $course = Courses::find($id);
        if (!$course) {
            abort(404);
        }
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        $authUser = User::find(Auth::id());
        //$institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $Branch = Branch::where(['Institution_id' => $Institution_id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        $month = Month::get();
        return view('admin.adminEditCoursesStep3', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'month'));
    }

    public function adminEditCoursesIntake($Institution_id, $id, AddCoursesIntake $request) {
        //AddCoursesIntake $request
        $data = Input::all();
        $course_data["User_editedby"] = Auth::id();
        Courses::find($id)->update($course_data);
        $getCourse = Courses::find($id);
        $oldIntakeIds = [];
        $createIntakeData = [];
        $updateIntakeData = [];
        $updateIntakeIds = [];
        $deleteInatakeIds = [];

        foreach ($getCourse->getIntakes as $key => $val) {
            $oldIntakeIds[] = $val->id;
        }
        foreach ($data['intake'] as $Key => $val) {
            if (isset($val['id'])) {
                $updateIntakeData[] = $val;
                $updateIntakeIds[] = $val['id'];
            } else {
                $createIntakeData[] = $val;
            }
        }
        foreach ($oldIntakeIds as $val) {
            if (!in_array($val, $updateIntakeIds)) {
                $deleteInatakeIds[] = $val;
            }
        }

        Intake::whereIn('Course_id', $deleteInatakeIds)->delete();

        foreach ($createIntakeData as $key => $value) {
            $value['Course_id'] = $id;
            Intake::create($value);
        }

        foreach ($updateIntakeData as $key => $value) {
            Intake::find($value['id'])->update($value);
        }
        return $this->sendResponse(true, route('adminEditApplicationFormView', ['Institution_id' => $Institution_id, 'id' => $id]));
    }

    public function adminEditApplicationForm($id, AddApplicationForm $request) {
//        AddApplicationForm $request
        $input = Input::all();
        $file;
        // print_r($input);
        // die;
        $tempPath = public_path() . '/temp/';
        $filePath_to = public_path() . '/' . Courses::APPLICATION_FOLDER . '/';
        if (!file_exists($filePath_to)) {
            mkdir($filePath_to, 0777, true);
        }

        if ($input['application_from'] !== 'update_application_form') {
            $getAppForm = CourseApplicationForm::where([
                        'Course_id' => $id,
                        'File_type' => CourseApplicationForm::APPLICATION_FORM
                    ])
                    ->first();
            $fileUrl = url('/') . '/' . Courses::APPLICATION_FOLDER . '/' . $input['application_from'];
            $from = $tempPath . $input['application_from'];
            $filePath_to = $filePath_to . $input['application_from'];
            $data['name'] = $input['application_from'];
            $data['URL'] = $fileUrl;
            copy($from, $filePath_to);
            $file_data = File::create($data);
            $file['File_id'] = $file_data['id'];
            $file['Course_id'] = $id;
            $file['File_type'] = CourseApplicationForm::APPLICATION_FORM;
            $createApplicationForm = CourseApplicationForm::create($file);
            if ($createApplicationForm) {
                $getFile = $getAppForm ? $getAppForm->getFile : '';
                $getFile ? $getFile->delete() : '';
                $getAppForm ? $getAppForm->delete() : '';
            }
        }

        if (isset($input['research_proposal_form']) && $input['research_proposal_form'] !== 'research_proposal_form') {
            $getAppForm = CourseApplicationForm::where([
                        'Course_id' => $id,
                        'File_type' => CourseApplicationForm::RESEARCH_PROPOSAL_FORM
                    ])
                    ->first();
            $fileUrl = url('/') . '/' . Courses::APPLICATION_FOLDER . '/' . $input['research_proposal_form'];
            $from = $tempPath . $input['research_proposal_form'];
            $filePath_to = $filePath_to . $input['research_proposal_form'];
            $data['name'] = $input['research_proposal_form'];
            $data['URL'] = $fileUrl;
            copy($from, $filePath_to);
            $file_data = File::create($data);
            $file['File_id'] = $file_data['id'];
            $file['Course_id'] = $id;
            $file['File_type'] = CourseApplicationForm::RESEARCH_PROPOSAL_FORM;
            $createApplicationForm = CourseApplicationForm::create($file);
            if ($createApplicationForm) {
                $getFile = $getAppForm ? $getAppForm->getFile : '';
                $getFile ? $getFile->delete() : '';
                $getAppForm ? $getAppForm->delete() : '';
            }
        }
        return $this->sendResponse(true, '', '', route('manageCoursesAdmin'), ' submitCourseForm');
    }

    public function adminEditApplicationFormView($Institution_id, $id) {
//        AddApplicationForm $request
        $course = Courses::find($id);
        if (!$course) {
            abort(404);
        }
        $course_data["User_editedby"] = Auth::id();
        Courses::find($id)->update($course_data);
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        //$authUser = User::find(Auth::id());
        $institutionDetail = Institution::find($Institution_id);
        $Branch = Branch::where(['Institution_id' => $Institution_id])->get();
        $Tag = Tag::get();
        $month = Month::get();
        $Condition = Condition::get();
        $country = Country::get();
        $formFileConition = FormFilesCondition::get();
        $course_tag = CourseTags::where(['Course_id' => $id])->get(['Tag_id'])->toArray();
        $course_tag = array_column($course_tag, 'Tag_id');
        list($Institution, $user, $Facilities, $getCourses, $InstitutionDetails, $Institution_listing, $Courses_Listing, $InstitutionQSRanking, $InstitutionTHERanking, $countCourseScholarshipData) = $this->adminCouserPreview($id);
        return view('admin.adminEditCoursesStep4', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'month', 'country', 'formFileConition', 'course_tag', 'Institution', 'user', 'Facilities', 'getCourses', 'InstitutionDetails', 'Institution_listing', 'Courses_Listing', 'InstitutionQSRanking', 'InstitutionTHERanking', 'countCourseScholarshipData'));
    }

    public function adminCouserPreview($id) {
        $user = Auth::user();
        $allFacility = Facility::get();

        $Facilities = [];
        foreach ($allFacility as $key => $value) {
            $Facilities[] = $value->name;
        }

        $getCourses = Courses::where([
                            'id' => $id
                        ])
                        ->whereHas('getIntakes', function($query) {
                            return $query->whereHas('getIntakeBranch', function($intQuery) {
                                        return $intQuery->whereHas('getInstitution', function($instQuery) {
                                                    return $instQuery;
                                                });
                                    });
                        })->first();
        if (!$getCourses) {
            abort(404);
        }

        $Institution = Institution::whereHas('getBranch', function($q) use($id) {
                    return $q->whereHas('getintake', function($q1) use($id) {
                                return $q1->where(['Course_id' => $id]);
                            });
                })
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
        return [$Institution, $user, $Facilities, $getCourses, $InstitutionDetails, $Institution_listing, $Courses_Listing, $InstitutionQSRanking, $InstitutionTHERanking, $countCourseScholarshipData];
    }

    ////////////////////// end add/edit functionality/////////////////////////// 

    public function callAction($method, $parameters) {
// code that runs before any action
        $userRoleAction = [
            User::ADMIN => [
                'notification', 'sendRegistrationMail', 'sendRegistrationMailView', 'manageEnquiries', 'manageInstitutions', 'readNotification',
                'addCourses', 'manageInternalReviewers', 'managePublishing', 'manageScholarship',
                'addInternalReviewer', 'pageEditorAdmin', 'managePageAdmin', 'manageScholarshipProvider',
                'manageStudentData', 'manageStudentDataInDraft', 'manageStudentDataPanding', 'manageStudentDataPandingApplicationFee', 'manageStudentPandingTutionFee', 'manageStudentPandingCommission', 'manageUsers', 'addUserCMS', 'manageRankingAdmin', 'manageCoursesAdmin',
                'addInstitutions', 'adminInstitutionAjax', 'deleteScholarship', 'deleteInternalReviewerAdmin',
                'manageScholarshipEdit', 'manageScholarshipUpdate', 'manageInstitutionsUpdateAction', 'manageScholarshipView',
                'editInternalReviewer', 'manageInternalReviewersView', 'institutionsProfileView', 'institutionsContactInformation',
                'editInternalReviewerData', 'manageScholarshipUpdateAction', 'pauseApplicationStudent', 'approveCourseAdmin', 'deleteCourseAdmin', 'hideInstitution', 'scholarshipProviderContactInfo', 'manageEnquiriesView', 'resumeApplicationStudent', 'deleteApplicationStudent', 'hideCourses', 'responseEnquirySubmit', 'internalReviewerViewActivityAdmin', 'deleteRankingAdmin', 'deleteMatchAdmin', 'addMatchSubmit', 'addRankingSubmit', 'viewStudentApplication', 'studentViewApplication', 'visibilityScholarshipProvider', 'editRanking', 'saveEditRanking', 'addScholarshipProvider', 'getCourseAdmin', 'getCourseListforLeveOfStudyAdmin', 'pageEditorForm', 'pageEditorSubmit', 'managePagesPublishing', 'updateInstutionRankingStatus', 'viewPagePublishing', 'pagesDisavowed', 'deletePage', 'redoPage', 'resetPageEditorForm',
                'editUserCMSView', 'editUserCMS', 'deleteCmsUser', 'viewManageEditPage', 'verifyScholarshipProvider', 'getCourseListforsubdisciplineAdmin', 'manageScholarshipUpdateVisibility', 'studentViewApplicationPendingReview', 'adminDeleteScholarshipProviderUser',
                'studentViewApplicationPendingFees', 'studentViewApplicationTutionFeesPayment', 'studentApplicationInternalReview', 'sendApplicationToInstitute', 'viewCourseDetail', 'getSubdisciplineAdmin', 'getFieldOfStudyAdmin', 'editInternalreviewerAjax', 'editCMSUserAjax',
                'updatePageApprovalPermission', 'updateMustGetApprovePermission', 'studentApplicationInstitutionReview', 'studentViewApplicationPendingCommission', 'studentApplicationVisaCredentials', 'editApplicationOtherFormAdmin', 'removeOtherFormAdmin', 'studentApplicationFinalStep',
                'manageStudentCommission', 'studentViewApplicationCommission', 'addCourseAdmin', 'addCoursesBasicDetailsAdmin', 'addTagsAdmin', 'adminEditRequirmentsPathwayView', 'adminEditCoursesBasicDetailsView', 'adminEditCoursesBasicDetails', 'adminEditCoursesIntakeView',
                'adminEditRequirmentsPathway', 'adminDeleteRequirement', 'adminEditCoursesIntake', 'adminEditApplicationForm', 'adminEditApplicationFormView', 'adminCouserPreview', 'adminCourseSubdispline', 'deleteDisavowed', 'editMatch', 'saveEditMatch', 'manageProfileSubmitAdmin',
                'viewActivityAdmin', 'fetchPermissionData', 'saveCMSUserPages', 'viewActivityPermission', 'addNewFacilitiesAdmin', 'editInternationalRanking', 'saveEditInternationalRanking', 'addRanking'
            ],
            User::INTERNAL_REVIEWER => [
                'notification', 'sendRegistrationMail', 'sendRegistrationMailView', 'manageEnquiries', 'manageInstitutions', 'readNotification',
                'addCourses', 'managePublishing', 'manageScholarship',
                'pageEditorAdmin', 'managePageAdmin', 'manageScholarshipProvider',
                'manageStudentData', 'manageStudentDataInDraft', 'manageStudentDataPanding', 'manageStudentDataPandingApplicationFee', 'manageStudentPandingTutionFee', 'manageStudentPandingCommission', 'manageRankingAdmin', 'manageCoursesAdmin',
                'addInstitutions', 'adminInstitutionAjax', 'deleteScholarship', 'manageScholarshipEdit',
                'manageScholarshipUpdate', 'manageInstitutionsUpdateAction', 'manageScholarshipView',
                'institutionsProfileView', 'institutionsContactInformation', 'manageScholarshipUpdateAction', 'pauseApplicationStudent', 'approveCourseAdmin', 'deleteCourseAdmin', 'hideInstitution', 'scholarshipProviderContactInfo', 'manageEnquiriesView', 'resumeApplicationStudent', 'deleteApplicationStudent', 'hideCourses', 'responseEnquirySubmit', 'internalReviewerViewActivityAdmin', 'deleteRankingAdmin', 'deleteMatchAdmin', 'addMatchSubmit', 'addRankingSubmit', 'viewStudentApplication', 'studentViewApplication', 'visibilityScholarshipProvider', 'editRanking', 'saveEditRanking', 'addScholarshipProvider', 'getCourseAdmin', 'getCourseListforLeveOfStudyAdmin', 'pageEditorForm', 'pageEditorSubmit', 'managePagesPublishing', 'updateInstutionRankingStatus', 'viewPagePublishing', 'pagesDisavowed', 'deletePage', 'redoPage', 'resetPageEditorForm',
                'editUserCMSView', 'editUserCMS', 'deleteCmsUser', 'viewManageEditPage', 'verifyScholarshipProvider', 'getCourseListforsubdisciplineAdmin', 'manageScholarshipUpdateVisibility', 'studentViewApplicationPendingReview', 'adminDeleteScholarshipProviderUser',
                'studentViewApplicationPendingFees', 'studentViewApplicationTutionFeesPayment', 'studentApplicationInternalReview', 'sendApplicationToInstitute', 'viewCourseDetail', 'getSubdisciplineAdmin', 'getFieldOfStudyAdmin', 'editInternalreviewerAjax', 'editCMSUserAjax', 'updatePageApprovalPermission', 'updateMustGetApprovePermission', 'studentApplicationInstitutionReview', 'studentViewApplicationPendingCommission', 'studentApplicationVisaCredentials', 'editApplicationOtherFormAdmin', 'removeOtherFormAdmin', 'studentApplicationFinalStep', '
                studentViewApplicationCommission', 'addCourseAdmin', 'addCoursesBasicDetailsAdmin', 'addTagsAdmin', 'adminEditRequirmentsPathwayView', 'adminEditCoursesBasicDetailsView', 'adminEditCoursesBasicDetails', 'adminEditCoursesIntakeView', 'adminEditRequirmentsPathway',
                'adminDeleteRequirement', 'adminEditCoursesIntake', 'adminEditApplicationForm', 'adminEditApplicationFormView', 'adminCouserPreview', 'adminCourseSubdispline', 'deleteDisavowed', 'editMatch', 'saveEditMatch', 'manageProfileSubmitAdmin', 'viewActivityAdmin',
                'fetchPermissionData', 'saveCMSUserPages', 'viewActivityPermission', 'addNewFacilitiesAdmin', 'editInternationalRanking', 'saveEditInternationalRanking', 'addRanking'
            ],
            User::CMSUSER => [
                'managePageAdmin', 'pageEditorAdmin', 'managePublishing', 'pageEditorForm', 'pageEditorSubmit', 'managePagesPublishing', 'viewPagePublishing', 'pagesDisavowed', 'deletePage', 'redoPage', 'resetPageEditorForm', 'viewManageEditPage', 'editCMSUserAjax', 'updatePageApprovalPermission', 'updateMustGetApprovePermission', 'deleteDisavowed', 'fetchPermissionData', 'viewActivityPermission'
            ]
        ];
        $bypassMethod = ['notification', 'readNotification', 'profileEdit', 'profileUpdate'];
        if (in_array($method, $bypassMethod)) {
            return parent::callAction($method, $parameters);
        }
        if (!isset($userRoleAction[Auth::user()->Role_id]) || !in_array($method, $userRoleAction[Auth::user()->Role_id])) {
            return abort(404);
        }
        if (!$this->isAccess($method)) {
            return abort(401, Common::UNAUTHORIZED_401_MESSAGE);
        }
        return parent::callAction($method, $parameters);
    }

    public function isAccess($method) {
        //There must be only one function name exist in all of array.
        $permiddionAction = [
            Permission::MANAGE_INSTITUTION => [
                'manageInstitutions', 'manageCoursesAdmin', 'manageRankingAdmin', 'addInstitutions', 'viewCourseDetail', 'getSubdisciplineAdmin', 'getFieldOfStudyAdmin',
                'manageInstitutionsUpdateAction', 'institutionsContactInformation', 'institutionsProfileView', 'approveCourseAdmin', 'deleteCourseAdmin', 'hideInstitution', 'hideCourses', 'deleteRankingAdmin', 'deleteMatchAdmin', 'addMatchSubmit', 'addRankingSubmit', 'editRanking', 'saveEditRanking', 'updateInstutionRankingStatus', 'addCourseAdmin', 'addCoursesBasicDetailsAdmin', 'addTagsAdmin', 'adminEditRequirmentsPathwayView', 'adminEditCoursesBasicDetailsView', 'adminEditCoursesBasicDetails', 'adminEditCoursesIntakeView', 'adminEditRequirmentsPathway', 'adminDeleteRequirement', 'adminEditCoursesIntake', 'adminEditApplicationForm', 'adminEditApplicationFormView', 'adminCouserPreview', 'adminCourseSubdispline', 'editMatch', 'saveEditMatch', 'manageProfileSubmitAdmin', 'viewActivityAdmin', 'saveCMSUserPages', 'addNewFacilitiesAdmin', 'editInternationalRanking', 'saveEditInternationalRanking', 'addRanking'
            ],
            Permission::MANAGE_SCHOOLARSHIP_PROVIERS => [
                'manageScholarship', 'manageScholarshipView', 'manageScholarshipEdit', 'manageScholarshipUpdate', 'adminDeleteScholarshipProviderUser',
                'delete-scholarship', 'manageScholarshipUpdateAction', 'scholarshipProviderContactInfo', 'manageScholarshipProvider', 'visibilityScholarshipProvider', 'addScholarshipProvider', 'getCourseAdmin', 'getCourseListforLeveOfStudyAdmin', 'verifyScholarshipProvider', 'getCourseListforsubdisciplineAdmin', 'manageScholarshipUpdateVisibility'
            ],
            Permission::MANAGE_CMS => [
                'managePageAdmin', 'pageEditorAdmin', 'managePublishing', 'pageEditorForm', 'pageEditorSubmit', 'managePagesPublishing', 'viewPagePublishing', 'pagesDisavowed', 'deletePage', 'redoPage', 'resetPageEditorForm', 'viewManageEditPage', 'editCMSUserAjax', 'updatePageApprovalPermission', 'updateMustGetApprovePermission', 'deleteDisavowed', 'fetchPermissionData', 'viewActivityPermission'
            ],
            Permission::MANAGE_STUDENT_APPLICATIONS => [
                'manageStudentData', 'manageStudentDataInDraft', 'manageStudentDataPanding', 'manageStudentDataPandingApplicationFee', 'manageStudentPandingTutionFee', 'manageStudentPandingCommission', 'pauseApplicationStudent', 'resumeApplicationStudent', 'deleteApplicationStudent', 'viewStudentApplication', 'studentViewApplication', 'studentViewApplicationPendingReview',
                'studentViewApplicationPendingFees', 'studentViewApplicationTutionFeesPayment', 'studentApplicationInternalReview', 'sendApplicationToInstitute', 'studentApplicationInstitutionReview', 'studentViewApplicationPendingCommission', 'studentApplicationVisaCredentials', 'editApplicationOtherFormAdmin', 'removeOtherFormAdmin', 'studentApplicationFinalStep', 'studentViewApplicationCommission'
            ],
            Permission::MANAGE_ENQUIRIES => [
                'manageEnquiries', 'manageEnquiriesView', 'responseEnquirySubmit',
            ],
            Permission::SEND_REGISTER_REQUEST => [
                'sendRegistrationMailView', 'sendRegistrationMail'
            ],
            Permission::MUST_GET_APPROVED => [
                'managePageAdmin', 'pageEditorAdmin', 'pageEditorForm', 'pageEditorSubmit', 'resetPageEditorForm'
            ],
            Permission::CAN_APPROVE_PUBLUSHING => [
                'managePageAdmin', 'pageEditorAdmin', 'managePublishing', 'viewPagePublishing', 'pagesDisavowed', 'pageEditorForm', 'pageEditorSubmit', 'managePagesPublishing', 'deletePage', 'redoPage', 'resetPageEditorForm', 'viewManageEditPage', 'deleteDisavowed'
            ]
        ];
        $permission = Auth::user()->Role_id;
        $user = User::find(Auth::id());
        if ($permission == User::ADMIN) {
            return true;
        }
        if ($permission !== User::INTERNAL_REVIEWER) {
            return true;
        }
        $return = false;
        foreach ($user->getUserRolePermission as $key => $val) {
            $permissionId = $val->getRolePermission->getPermission->id;
            if (isset($permiddionAction[$permissionId]) && in_array($method, $permiddionAction[$permissionId])) {
                $return = true;
            }
        }
        return $return;
    }

}

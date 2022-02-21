<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use App\Model\Tag;
use App\Model\File;
use App\Model\User;
use App\Model\Range;
use App\Model\Image;
use App\Model\Month;
use App\Model\Intake;
use App\Model\Branch;
use App\Model\Country;
use App\Model\Gallery;
use App\Helper\Common;
use App\Model\Courses;
use App\Model\Pathway;
use App\Model\Enquiry;
use App\Model\Student;
use App\Model\Facility;
use App\Model\FormFiles;
use App\Model\Condition;
use App\Model\CourseTags;
use App\Model\Permission;
use App\Model\Requirement;
use App\Model\TypeOfValue;
use App\Model\Scholarship;
use App\Model\Institution;
use App\Model\LevelOfStudy;
use App\Model\FieldOfStudy;
use App\Model\ContactPerson;
use App\Model\Subdiscipline;
use App\Model\ConditionRange;
use App\Model\RolePermission;
use App\Model\InstitutionUser;
use App\Model\ReqSubdiscipline;
use App\Model\FormFilesCountry;
use App\Model\BranchFacilities;
use App\Model\ApplicationIntake;
use App\Model\UserRolePermission;
use App\Http\Requests\AddCourses;
use App\Model\FormFilesCondition;
use App\Model\CourseSubdiscipline;
use App\Model\CourseApplicationForm;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Requests\MakeUserAdmin;
use App\Http\Requests\AddRequirments;
use App\Http\Requests\RespondEnquiry;
use Illuminate\Support\Facades\Input;
use App\Http\Requests\CourseOtherForm;
use App\Http\Requests\AddCoursesIntake;
use App\Http\Requests\AddApplicationForm;
use App\Http\Requests\EditInstitutionUser;
use App\Http\Requests\InstitutionManageProfile;
use App\Http\Requests\InstitutionAddInstitution;
use App\Http\Requests\InstitutionContactInformationSubmit;

class InstitutionController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function contactInformation() {
        $user = User::find(Auth::id());
        $institution = $user->getInstitutionUser->getInstitution;
        $branch = $institution->getBranch;
        return view('institution.contactInformation', compact('institution', 'branch'));
    }

    public function contactInformationSubmit(InstitutionContactInformationSubmit $request) {
        $input = Input::all();
        $branch_name = array_column($input['branch'], 'name');
        $unique_branch_name = [];
        foreach ($branch_name as $key => $value) {
            if (in_array($value, $unique_branch_name)) {
                $message = "Sorry, Branch " . ++$key . " name has already taken, select another Name of Branch";
                return $this->sendResponse(false, '', $message);
            }
            $unique_branch_name[] = $value;
        }
        $user = User::find(Auth::id());
        $institution = $user->getInstitutionUser->getInstitution;
        $institutionData = [
            'cricosCode' => $input['cricosCode']
        ];
        $institution->update($institutionData);


        $oldBranchIds = [];
        $updateBranchId = [];
        $updateBranchDetail = [];
        $createBranchDetail = [];

        foreach ($institution->getBranch as $okey => $oval) {
            $oldBranchIds[] = $oval->id;
        }

        foreach ($input['branch'] as $val) {
            if (isset($val['id']) && (in_array($val['id'], $oldBranchIds))) {
                $updateBranchDetail[] = $val;
                $updateBranchId[] = $val['id'];
            } else {
                $createBranchDetail[] = $val;
            }
        }
        //update branch data
        foreach ($updateBranchDetail as $val) {
            $getBranchDetail = Branch::find($val['id']);
            if (isset($getBranchDetail->getContactPerson)) {
                foreach ($getBranchDetail->getContactPerson as $dContactPerson) {
                    $dContactPerson->delete();
                }
            }

            $branchData = [
                'Institution_id' => $institution->id,
                'name' => $val['name'],
                'streetAddress' => $val['streetAddress'],
                'streetAddress2' => $val['streetAddress2'],
                'City_id' => $val['city_id'],
                'zipcode' => $val['zipcode'],
                'type' => $val['type'],
                'URLApplicationFee' => $val['URLApplicationFee'],
                'URLTuitionFee' => $val['URLTuitionFee']
            ];
            $createdBranch = $getBranchDetail->update($branchData);

            $contactPersonDetail[] = [
                'Branch_id' => $getBranchDetail->id,
                'name' => $val['ContactPerson_name_payment'],
                'email' => $val['ContactPerson_email_payment'],
                'phone' => $val['ContactPerson_phone_payment'],
                'type' => ContactPerson::APPLICATION_FEE
            ];
            $contactPersonDetail[] = [
                'Branch_id' => $getBranchDetail->id,
                'name' => $val['ContactPerson_name'],
                'email' => $val['ContactPerson_email'],
                'phone' => $val['ContactPerson_phone'],
                'type' => ContactPerson::COMMISSION
            ];
            $contactPersonDetail[] = [
                'Branch_id' => $getBranchDetail->id,
                'name' => $val['ContactPerson_name_tution'],
                'email' => $val['ContactPerson_email_tution'],
                'phone' => $val['ContactPerson_phone_tution'],
                'type' => ContactPerson::TUITION_FEE
            ];
            if ($createdBranch) {
                foreach ($contactPersonDetail as $contactPersonData) {
                    ContactPerson::create($contactPersonData);
                }
            }
            $contactPersonDetail = [];
        }


        //create new branch data
        foreach ($createBranchDetail as $val) {
            $branchData = [
                'Institution_id' => $institution->id,
                'name' => $val['name'],
                'streetAddress' => $val['streetAddress'],
                'streetAddress2' => $val['streetAddress2'],
                'City_id' => $val['city_id'],
                'zipcode' => $val['zipcode'],
                'type' => $val['type'],
                'URLApplicationFee' => $val['URLApplicationFee'],
                'URLTuitionFee' => $val['URLTuitionFee']
            ];
            $createdBranch = Branch::create($branchData);
            $contactPersonDetail[] = [
                'Branch_id' => $createdBranch->id,
                'name' => $val['ContactPerson_name_payment'],
                'email' => $val['ContactPerson_email_payment'],
                'phone' => $val['ContactPerson_phone_payment'],
                'type' => ContactPerson::APPLICATION_FEE
            ];
            $contactPersonDetail[] = [
                'Branch_id' => $createdBranch->id,
                'name' => $val['ContactPerson_name'],
                'email' => $val['ContactPerson_email'],
                'phone' => $val['ContactPerson_phone'],
                'type' => ContactPerson::COMMISSION
            ];
            $contactPersonDetail[] = [
                'Branch_id' => $createdBranch->id,
                'name' => $val['ContactPerson_name_tution'],
                'email' => $val['ContactPerson_email_tution'],
                'phone' => $val['ContactPerson_phone_tution'],
                'type' => ContactPerson::TUITION_FEE
            ];
            if ($createdBranch) {
                foreach ($contactPersonDetail as $contactPersonData) {
                    ContactPerson::create($contactPersonData);
                }
            }
            $contactPersonDetail = [];
        }


        //delete branch data
        foreach ($oldBranchIds as $dval) {
            if (!in_array($dval, $updateBranchId)) {
                $dBranch = Branch::find($dval);
                if (isset($dBranch->getContactPerson)) {
                    foreach ($dBranch->getContactPerson as $dContactPerson) {
                        $dContactPerson->delete();
                    }
                    if (isset($dBranch->getBranchFacilities)) {
                        foreach ($dBranch->getBranchFacilities as $dfaciliey) {
                            $dfaciliey->delete();
                        }
                    }
                    $dBranch->delete();
                }
            }
        }

        return $this->sendResponse(true, route('contactInformation'), 'Institute Update Successfully');
    }

    public function manageProfile() {

        $institutionId = InstitutionUser::where(['User_id' => Auth::id()])->first()->Institution_id;
        $institution = Institution::find($institutionId);
        $branch = $institution->getBranch;
        $allFacility = Facility::get();
        list($InstitutionData, $user, $Institution_listing, $Courses_Listing, $Facilities, $InstitutionDetails, $getCourses, $levelOfStudy, $fieldOfStudy, $intake, $branch, $getScholarship) = $this->institutionPreview($institutionId);
        return view('institution.manageProfile', compact('institution', 'branch', 'allFacility', 'InstitutionData', 'user', 'Institution_listing', 'Courses_Listing', 'Facilities', 'InstitutionDetails', 'getCourses', 'levelOfStudy', 'fieldOfStudy', 'intake', 'branch', 'getScholarship'));
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

    public function manageProfileSubmit(InstitutionManageProfile $request) {
        $input = Input::all();
        $institutionId = InstitutionUser::where(['User_id' => Auth::id()])->first()->Institution_id;

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
                    'Institution_id' => $institutionId,
                    'Image_id' => $image->id
                ];
                Gallery::create($imageData);
                $from = $tempPath . $val;
                $to = public_path() . '/' . Institution::IMAGE_FOLDER . '/' . $val;
                rename($from, $to);
            }
        }
        $input['approval'] = Institution::PENDING_APPROVAL;
        $input['User_approvedby'] = NULL;
        $institution = Institution::find($institutionId);
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
        return $this->sendResponse(true, route('manageProfile'), 'Your profile has been submited for approval');
    }

    public function addInstitutions(InstitutionAddInstitution $request) {
        $data = Input::all();
        $data['Role_id'] = User::INSTITUTION_USER;
        $uniquePassword = uniqid();
        $data['password'] = Hash::make($uniquePassword);
        $create_user = User::create($data);
        $user = User::find(Auth::id());
        $institution = $user->getInstitutionUser->getInstitution;
        $data['User_id'] = $create_user->id;
        $data['Institution_id'] = $institution->id;
        InstitutionUser::create($data);
        $data['granted'] = 1;
        $rolePermissions = RolePermission::select()->where(['Role_id' => User::INSTITUTION_USER, 'granted' => 1])->get();
        UserRolePermission::where(['User_id' => $create_user->id])->delete();
        foreach ($rolePermissions as $value) {
            if (isset($data["permission"][$value['Permission_id']]) && !empty($data["permission"][$value['Permission_id']])) {
                $data['User_id'] = $create_user->id;
                $data['RolePermission_id'] = $value['id'];
                UserRolePermission::create($data);
            }
        }
        //create notification for created institute
        $create_user['password'] = $uniquePassword;
        Common::sendMail($create_user->email, 'Uniexplorers', $create_user, 'mail.addinstitutionUser');
        return $this->sendResponse(true, route('manageUser'), 'Institution user has been added successfully.');
    }

    public function makeAdminUser(MakeUserAdmin $request) {
        $input = Input::all();
        $data['Role_id'] = User::INSTITUTION_ADMIN_USER;
        $permission_data = [];
        User::where('id', $input['id'])->update($data);
        if ($input['condition_name'] == Condition::TRANSFER_ADMIN_STATUS) {
            $data['Role_id'] = User::INSTITUTION_USER;
            User::where('id', Auth::id())->update($data);
            $rolePermissions = RolePermission::select()->where(['Role_id' => User::INSTITUTION_USER, 'granted' => 1])->get();
            $permission_data['granted'] = 1;
            foreach ($rolePermissions as $value) {
                $permission_data['User_id'] = Auth::id();
                $permission_data['RolePermission_id'] = $value['id'];
                UserRolePermission::create($permission_data);
            }
            return $this->sendResponse(true, route('notification'), 'User transfer successfully.');
        }
        return $this->sendResponse(true, route('manageUser'), 'User transfer successfully.');
    }

    public function manageUser() {
        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $user = User::find(Auth::id());
        $institution = $user->getInstitutionUser->getInstitution;
        $allInstitution = InstitutionUser::where(['Institution_id' => $institution->id])
                        ->where('User_id', '!=', Auth::id())
                        ->whereHas('getUser', function($q) use ($search) {
                            return $q->where(['Role_id' => User::INSTITUTION_USER])
                                    ->where('firstName', 'LIKE', '%' . $search . '%')
                                    ->orWhere('lastName', 'like', '%' . $search . '%')
                                    ->orWhere('email', 'like', '%' . $search . '%')
                                    ->orWhere('id', 'like', '%' . $search . '%')
                                    ->orWhere(DB::raw('concat(firstName," ",lastName)'), 'like', '%' . $search . '%');
                        })
                        ->orderBy('updated_at', 'desc')->paginate(Common::PAGINATION);

        $permissions = RolePermission::where(['Role_id' => User::INSTITUTION_USER, 'granted' => UserRolePermission::GRANTED])
                        ->whereHas('getPermission', function($q) {
                            return $q->where(['cms_type' => Permission::TYPE_INSTITUTION]);
                        })->get();
        if (\Request::ajax()) {
            return view('institution.dataManageUser', compact('allInstitution', 'permissions'));
        }

        return view('institution.manageUser', compact('allInstitution', 'permissions'));
    }

    public function deleteInstitutionUser($userId) {
        $user = User::find(Auth::id());
        if (Auth::id() == $userId) {
            abort(401);
        }
        $institution = $user->getInstitutionUser->getInstitution;
        $allInstitution = InstitutionUser::where(['Institution_id' => $institution->id])
                ->where('User_id', '!=', Auth::id())
                ->whereHas('getUser', function($q) use ($userId) {
                    return $q->where(['Role_id' => User::INSTITUTION_USER])
                            ->where(['id' => $userId]);
                })
                ->first();
        if ($allInstitution) {
            UserRolePermission::where(['User_id' => $userId])->delete();
            $allInstitution->delete();
            User::find($userId)->delete();
        }
        return redirect()->route('manageUser');
    }

    public function editInstitutionUserView($userId) {
        $user = User::find(Auth::id());
        if (Auth::id() == $userId) {
            abort(401);
        }
        $institution = $user->getInstitutionUser->getInstitution;
        $allInstitution = InstitutionUser::where(['Institution_id' => $institution->id])
                ->where('User_id', '!=', Auth::id())
                ->whereHas('getUser', function($q) use ($userId) {
                    return $q->where(function($q1) {

                                return $q1->where(['Role_id' => User::INSTITUTION_USER])->orWhere(['Role_id' => User::INSTITUTION_ADMIN_USER]);
                            })
                            ->where(['id' => $userId]);
                })
                ->first();
        if ($allInstitution) {
            $institutionUser = User::find($userId);
            $userRolePermission = UserRolePermission::where(['User_id' => $userId])->get();
            $permissions = RolePermission::where(['Role_id' => User::INSTITUTION_USER, 'granted' => UserRolePermission::GRANTED])
                            ->whereHas('getPermission', function($q) {
                                return $q->where(['cms_type' => Permission::TYPE_INSTITUTION]);
                            })->get();
            return view('institution.editManageUserForm', compact('institutionUser', 'permissions', 'userRolePermission'));
        } else {
            abort(404);
        }
    }

    public function editInstitutionUser(EditInstitutionUser $request, $userId) {
        $input = Input::all();
        $user = User::find(Auth::id());
        if (Auth::id() == $userId) {
            abort(401);
        }
        $institution = $user->getInstitutionUser->getInstitution;
        $allInstitution = InstitutionUser::where(['Institution_id' => $institution->id])
                ->where('User_id', '!=', Auth::id())
                ->whereHas('getUser', function($q) use ($userId) {
                    return $q->where(['Role_id' => User::INSTITUTION_USER])
                            ->where(['id' => $userId]);
                })
                ->first();
        if ($allInstitution) {
            $input['granted'] = 1;
            $rolePermissions = RolePermission::select()->where(['Role_id' => User::INSTITUTION_USER, 'granted' => 1])->get();
            UserRolePermission::where(['User_id' => $userId])->delete();
            foreach ($rolePermissions as $value) {
                if (isset($input["permission"][$value['Permission_id']]) && !empty($input["permission"][$value['Permission_id']])) {
                    $input['User_id'] = $userId;
                    $input['RolePermission_id'] = $value['id'];
                    UserRolePermission::create($input);
                }
            }
            User::find($userId)->update($input);
        }

        return $this->sendResponse(true, route('manageUser'), 'Institution User updated');
    }

    public function editInstitutionUserAjax($id) {
        $input = Input::all();

        if (!User::find($id)) {
            abort(401);
        } else {

            $input['granted'] = 1;
            $rolePermissions = RolePermission::select()->where(['Role_id' => User::INSTITUTION_USER, 'granted' => 1])->get();
            UserRolePermission::where(['User_id' => $id])->delete();
            foreach ($rolePermissions as $value) {
                if (isset($input["permission"][$value['Permission_id']]) && !empty($input["permission"][$value['Permission_id']])) {
                    $input['User_id'] = $id;
                    $input['RolePermission_id'] = $value['id'];
                    UserRolePermission::create($input);
                }
            }
            return $this->sendResponse(true, '', 'Institution User permission updated');
        }
    }

    public function institutionCourseSubdispline() {
        $get = Input::all();
        $FOSsearch = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        // print_r($FOSsearch);die;
        $Subdiscipline = Subdiscipline::whereIn('FieldofStudy_id', $FOSsearch)
                ->get();
        return $this->sendResponse(true, '', '', $Subdiscipline);
        //return $this->sendResponse(true, $route, "Course approval notification has been sent for approval");
    }

    //from admin controller
    public function manageCourses() {
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $id = $institutionDetail->id;

        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $SubdisplineSearch = isset($get['SubdisplineSearch']) && !empty($get['SubdisplineSearch']) ? $get['SubdisplineSearch'] : [];
        $branchSerach = isset($get['branchSerach']) && !empty($get['branchSerach']) ? $get['branchSerach'] : '';
        $LOSsearch = isset($get['LOSsearch']) && !empty($get['LOSsearch']) ? $get['LOSsearch'] : '';
        $FOSsearch = isset($get['FOSsearch']) && !empty($get['FOSsearch']) ? $get['FOSsearch'] : [];



        if ($id) {
            $allBranch = Branch::where(['Institution_id' => $id])->get();
        } else {
            $allBranch = Branch::get();
        }
        $allLOS = LevelOfStudy::get();
        $allFOS = FieldOfStudy::get();
        foreach ($allFOS as $key => $value) {

            $allFOS[$key]['Subdiscipline'] = Subdiscipline::select('id')
                    ->where(['FieldofStudy_id' => $value['id']])
                    ->get();
        }
        $userdata = Courses::where(function($q2) use($search) {
                    return $q2->where('name', 'like', '%' . $search . '%')
                            ->orWhere(function($q1) use ($search) {

                                return $q1->whereHas('getEditedBy', function($q3) use ($search) {
                                            return $q3->where('firstName', 'like', '%' . $search . '%');
                                        });
                            });
                })
                ->whereHas('getIntakes', function($q) use ($branchSerach, $id) {
            return $q->whereHas('getIntakeBranch', function($query) use ($branchSerach, $id) {
                        $query = $query->whereHas('getInstitution', function($iq) use ($id) {
                            if ($id) {
                                $iq = $iq->where(['id' => $id]);
                            }
                            return $iq;
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
        $userdata = $userdata->orderBy('name', 'asc')->paginate(Common::PAGINATION);

        if (\Request::ajax()) {
            return view('institution.dataManageCourses', compact('institutionDetail', 'userdata', 'allBranch', 'allLOS', 'allFOS'));
        }
        $subdisciplineIds = Subdiscipline::get();
        return view('institution.manageCourses', compact('institutionDetail', 'userdata', 'allBranch', 'allLOS', 'allFOS', 'subdisciplineIds'));
    }

    //

    public function approveCourseInstitution($id) {
        // $data = Input::all();
        // $data['visibility'] = ($data['approval']) ? $data['approval'] : $data['approval'];
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $data['dateSubmission'] = date('Y-m-d H:i:s');
        $Course = Courses::find($id)->update($data);
        ;
        $message = $institutionDetail->name . ' Institute wants approval of course ' . $Course['name'];
        $notyUrl = route('manageCoursesAdmin') . "/" . $institutionDetail->id;
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], Common::COURSE_FOR_APPROVAL, $message, $notyUrl);
        $route = route('institutionManageCourses');
        return $this->sendResponse(true, $route, "Course approval notification has been sent for approval");
    }

    public function hideCoursesInstitution($id) {
        $data = Input::all();
        $courses = Courses::find($id)->update($data);
        $route = route('institutionManageCourses');
        return $this->sendResponse(true, $route);
    }

    public function deleteCourse() {
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
            Intake::where(['Course_id' => $data['id']])->delete();
            $requirement_data = Requirement::where(['Course_id' => $data['id']])->get();
            foreach ($requirement_data as $key => $value) {
                $pathway_data = Pathway::where(['Requirement_id' => $value['id']])->first();
                $range_data = Range::where(['pathway_id' => $pathway_data['id']])->get();
                foreach ($range_data as $Rvalue) {
                    ConditionRange::where(['range_id' => $Rvalue['id']])->delete();
                }
                $range_data->delete();
                $pathway_data->delete();
                ReqSubdiscipline::where(['Requirement_id' => $value['id']])->delete();
                $value->delete();
            }
            $courseapp_data = CourseApplicationForm::where(['Course_id' => $data['id']])->get();
            foreach ($courseapp_data as $value) {
                File::where(['id' => $value['File_id']])->delete();
            }
            $courseapp_data->delete();
            Courses::where(['id' => $data['id']])->delete();
            $route = route('manageCourses');
            return $this->sendResponse(true, $route, 'Course has been deleted successfully.');
        }
    }

    public function getSubdiscipline() {
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

    public function getFieldOfStudy() {
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

    // add courses
    public function addCourses() {
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        if (!$institutionDetail->approval) {
            abort(401);
        }
        $course_id = isset(Input::all()['course_id']) ? Input::all()['course_id'] : '';
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        $Branch = Branch::where(['Institution_id' => $institutionDetail->id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        return view('institution.addCourses', compact('levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'course_id', 'TypeOfValue', 'Condition'));
    }

    public function editCoursesBasicDetailsView($id) {
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
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $Branch = Branch::where(['Institution_id' => $institutionDetail->id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        return view('institution.editCoursesStep1', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'course_tag'));
    }

    public function addCoursesBasicDetails(AddCourses $request) {
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
        $authUser = User::find(Auth::id());
        $institutionDetail_slug = $authUser->getInstitutionUser->getInstitution->slug;
        Courses::createCoursePage($course_data->id, $institutionDetail_slug);
        return $this->sendResponse(true, route('editRequirmentsPathwayView', $course_data->id));
    }

    public function editCoursesBasicDetails($id, AddCourses $request) {
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
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $Course = Courses::find($id);
        $message = $institutionDetail->name . ' Institute wants approval of course ' . $Course['name'];
        $notyUrl = route('manageCoursesAdmin') . "/" . $institutionDetail->id;
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], Common::COURSE_FOR_APPROVAL, $message, $notyUrl);

        return $this->sendResponse(true, route('editRequirmentsPathwayView', $id));
    }

    public function editRequirmentsPathwayView($id) {
        $course = Courses::find($id);
        if (!$course) {
            abort(404);
        }
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $Branch = Branch::where(['Institution_id' => $institutionDetail->id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        return view('institution.editCoursesStep2', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition'));
    }

    public function editRequirmentsPathway($id, AddRequirments $request) {
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

    public function deleteRequirement($id) {
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

    public function editCoursesIntakeView($id) {
        $course = Courses::find($id);
        if (!$course) {
            abort(404);
        }
        $levelofstudy = LevelOfStudy::get();
        $FieldOfStudy = FieldOfStudy::get();
        $Subdiscipline = Subdiscipline::get();
        $TypeOfValue = TypeOfValue::get();
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $Branch = Branch::where(['Institution_id' => $institutionDetail->id])->get();
        $Tag = Tag::get();
        $Condition = Condition::get();
        $month = Month::get();
        return view('institution.editCoursesStep3', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'month'));
    }

    public function editCoursesIntake($id, AddCoursesIntake $request) {
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
        return $this->sendResponse(true, route('editApplicationFormView', $id));
    }

    public function couserPreview($id) {
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

    // public function institutionPreview($id) {
    //     $user = Auth::user();

    //     $InstitutionData = Institution::where([
    //                 'id' => $id
    //             ])
    //             ->has('getInstitutionAdmin')
    //             ->first();

    //     if (!$InstitutionData) {
    //         abort(404);
    //     }

    //     $getCourses = Courses::where([
    //                         'visibility' => Courses::PUBLISHED,
    //                         'approval' => Courses::APPROVED
    //                     ])
    //                     ->whereHas('getIntakes', function($query) use ($id) {
    //                         return $query->whereHas('getIntakeBranch', function($intQuery) use($id) {
    //                                     return $intQuery->whereHas('getInstitution', function($instQuery) use($id) {
    //                                                 return $instQuery->where([
    //                                                             'id' => $id
    //                                                 ]);
    //                                             });
    //                                 });
    //                     })->get();

    //     $allFacility = Facility::get();
    //     $Facilities = [];
    //     foreach ($allFacility as $key => $value) {
    //         $Facilities[] = $value->name;
    //     }

    //     $InstitutionDetails = [];
    //     foreach ($InstitutionData->getBranch as $key => $branch) {
    //         $InstitutionDetails[$key]['Branch'] = $branch->name;
    //         $InstitutionDetails[$key]['Branch_id'] = $branch->id;
    //         foreach ($branch->getBranchFacilities as $branchFacilities) {
    //             $InstitutionFacility = Facility::where(['id' => $branchFacilities->Facility_id])->first();
    //             $InstitutionDetails[$key]['Facilities'][] = $InstitutionFacility->name;
    //         }
    //     }

    //     list($InstitutionQSRanking, $InstitutionTHERanking) = Institution::InstitutionRankingData($InstitutionData);
    //     list($Institution_listing, $Courses_Listing) = Courses::getCourseCompareData();
    //     //$slug = $slug." Detail page";
    //     return [$InstitutionData, $user, $Institution_listing, $Courses_Listing, $Facilities, $InstitutionDetails, $InstitutionQSRanking, $InstitutionTHERanking, $getCourses];
    // }

    public function editApplicationFormView($id) {
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
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $Branch = Branch::where(['Institution_id' => $institutionDetail->id])->get();
        $Tag = Tag::get();
        $month = Month::get();
        $Condition = Condition::get();
        $country = Country::get();
        $formFileConition = FormFilesCondition::get();
        $course_tag = CourseTags::where(['Course_id' => $id])->get(['Tag_id'])->toArray();
        $course_tag = array_column($course_tag, 'Tag_id');
        list($Institution, $user, $Facilities, $getCourses, $InstitutionDetails, $Institution_listing, $Courses_Listing, $InstitutionQSRanking, $InstitutionTHERanking, $countCourseScholarshipData) = $this->couserPreview($id);
        return view('institution.editCoursesStep4', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'month', 'country', 'formFileConition', 'course_tag', 'Institution', 'user', 'Facilities', 'getCourses', 'InstitutionDetails', 'Institution_listing', 'Courses_Listing', 'InstitutionQSRanking', 'InstitutionTHERanking', 'countCourseScholarshipData'));

        // return view('institution.editCoursesStep4', compact('course', 'levelofstudy', 'FieldOfStudy', 'Tag', 'Subdiscipline', 'Branch', 'TypeOfValue', 'Condition', 'month', 'country', 'formFileConition', 'course_tag'));
    }

    public function editApplicationForm($id, AddApplicationForm $request) {
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

//        if ($input['other_form'] !== 'update_other_form') {
//            $getOtherForm = CourseApplicationForm::where([
//                        'Course_id' => $id,
//                        'File_type' => CourseApplicationForm::OTHER_FORM
//                    ])
//                    ->first();
//            $fileUrl = url('/') . '/' . Courses::APPLICATION_FOLDER . '/' . $input['other_form'];
//            $from = $tempPath . $input['other_form'];
//            $filePath_to = $filePath_to . $input['other_form'];
//            $data['name'] = $input['other_form'];
//            $data['URL'] = $fileUrl;
//            copy($from, $filePath_to);
//            $file_data = File::create($data);
//            $file['File_id'] = $file_data['id'];
//            $file['Course_id'] = $id;
//            $file['File_type'] = CourseApplicationForm::OTHER_FORM;
//            $createOtherForm = CourseApplicationForm::create($file);
//            if ($createOtherForm) {
//                $getOtherFile = $getOtherForm ? $getOtherForm->getFile : '';
//                $getOtherFile ? $getOtherFile->delete() : '';
//                $getOtherForm ? $getOtherForm->delete() : '';
//            }
//        }

        return $this->sendResponse(true, '', '', route('institutionManageCourses'), ' submitCourseForm');
    }

    public function editApplicationOtherForm(CourseOtherForm $request, $id) {
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

    public function removeOtherForm($id) {
        $getFormFiles = FormFiles::find($id);
        if ($getFormFiles) {
            FormFilesCountry::where(['FormFiles_id' => $id])->delete();
            $getFileData = File::find($getFormFiles->File_id);
            $fileExistPath = Courses::APPLICATION_FOLDER;
            Common::removeFile($getFileData->name, $fileExistPath);
            $getFormFiles->delete();
            $getFileData->delete();
        } else {
            return $this->sendResponse(false, '', 'File Not Found');
        }
    }

    public function addTags() {
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

    public function manageEnquiries() {
        $get = Input::all();
        $user = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';

        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;

        $manageEnquiries_listData = Enquiry::whereHas('getStudentUser', function($query) use ($user) {
                    return $query->where(function($q2) use($user) {
                                return $q2->where('id', 'like', '%' . $user . '%')
                                        ->where(function($q1) {

                                            return $q1->whereHas('getUserDetails', function($q) {
                                                        return $q;
                                                    });
                                        });
                            });
                })
                ->where(['Institution_id' => $institutionDetail->id, 'ScholarshipProvider_id' => NULL]);

        $manageEnquiries_list = $manageEnquiries_listData->paginate(Common::PAGINATION);

        $outstanding_enquiry = Enquiry::whereHas('getStudentUser', function($query) use ($user) {
                    return $query->where(function($q2) use($user) {
                                return $q2->where('id', 'like', '%' . $user . '%')
                                        ->where(function($q1) {

                                            return $q1->whereHas('getUserDetails', function($q) {
                                                        return $q;
                                                    });
                                        });
                            });
                })
                ->where(['Institution_id' => $institutionDetail->id, 'ScholarshipProvider_id' => NULL])->where(['status' => 0])->get();
        //$outstanding_enquiry = $manageEnquiries_listData->where(['status' => 0])->get();

        if (\Request::ajax()) {
            return view('institution.dataManageEnquiries', compact('manageEnquiries_list', 'institutionDetail', 'outstanding_enquiry'));
        }
        return view('institution.manageEnquiries', compact('manageEnquiries_list', 'institutionDetail', 'outstanding_enquiry'));
    }

    public function manageEnquiriesView() {
        $data = Input::all();
        $enquiry_data = Enquiry::find($data['enquiryid']);
        echo view('institution.manageEnquiriesView', compact('enquiry_data'));
    }

    public function responseEnquirySubmit($id, RespondEnquiry $request) {
        $data = Input::all();
        $user_id = Auth::id();
        $data['responseDate'] = date('Y-m-d');
        $data['User_Responder'] = $user_id;
        $data['status'] = 1;
        Enquiry::find($id)->update($data);
        return $this->sendResponse(true, route('institutionManageEnquiries'), 'Enuiry has been Responded successfully.');
    }

    public function institutionsProfileView() {
        $authUser = User::find(Auth::id());
        $institutionDetail = $authUser->getInstitutionUser->getInstitution;
        $id = $institutionDetail->id;

        $institution = Institution::find($id);
        $branch = $institution->getBranch;
        return view('institution.institutionsProfileView', compact('institution', 'branch'));
    }

    public function addNewFacilities() {
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

    public function deleteGalleryImage() {
        $data = Input::all();
        $checkImageGallery = Gallery::where(['Image_id' => $data['Image_id']])->first();
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
//        return $this->sendResponse(true, route('manageProfile'), 'Image Deleted');
        return $this->sendResponse(true, '', 'Image Deleted');
    }

    // public function manageStudentDataInstitution() {
    //     $ApplicationPending = $this->getStudentApplicationApprove();
    //     return view('institution.manageStudentDataInstitution', compact('ApplicationPending'));
    // }

    public function studentApplicationApprove() {
        $ApplicationPending = $this->getStudentApplicationApprove();
        return view('admin.dataManageStudentApplicationPending', compact('ApplicationPending'));
    }

    public function getStudentApplicationApprove() {
        $get = Input::all();
        $pendingSearch = isset($get['pendingSearch']) && !empty($get['pendingSearch']) ? $get['pendingSearch'] : '';
        $search_date = isset($get['search_date']) && !empty($get['search_date']) ? $get['search_date'] : '';
        $ApplicationPendingData = ApplicationIntake::where(['step' => ApplicationIntake::APPLICATION_STEP6,]);
        if ($search_date) {
            $search_date = explode("from", $search_date);
            if (count($search_date) == 2) {
                $ApplicationPendingData = $ApplicationPendingData->whereBetween('created_at', [$search_date[0], $search_date[1]]);
            }
        }
        // $ApplicationPendingData = $ApplicationPendingData->whereHas('getUserData', function($query) {
        //     return $query->where(['Role_id' => User::INTERNAL_REVIEWER]);
        // });
        if ($pendingSearch) {
            $ApplicationPendingData->whereHas('getStudent', function($query) use ($pendingSearch) {
                return $query->whereHas('getUserDetails', function($q) use ($pendingSearch) {
                            return $q->where('firstName', 'LIKE', '%' . $pendingSearch . '%')
                                            ->orWhere('lastName', 'LIKE', '%' . $pendingSearch . '%')
                                            ->orWhere('email', 'LIKE', '%' . $pendingSearch . '%');
                        });
            })->orWhere('id', 'LIKE', '%' . $pendingSearch . '%');
        }

        return $ApplicationPending = $ApplicationPendingData->paginate(Common::PAGINATION, ['*'], 'pandingApplication');
    }

    public function studentViewApplicationInstitutionApprove($id) {
        $studentAppData = ApplicationIntake::find($id);
        $studentData = Student::where(['id' => $studentAppData->Student_id])->first();
        return view('institution.studentViewApplicationInstitutionApprove', compact('studentAppData', 'studentData'));
    }

    public function uploadDocumentForStudent(ManageCMSUser $request, $id) {
        // $getApplicationIntake = ApplicationIntake::find($id);
        // if (!$getApplicationIntake) {
        //     abort(404);
        // }
        // $input = Input::all();
        // if (!isset($input['file']) || (count($input['file']) != 3)) {
        //     return $this->sendResponse(false, '', 'Please Select All Files');
        // }
        // $this->uploadAllSupportiveFile($id, $input['file']['supportiveDocumentsFinancial'], ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_FINANCIAL);
        // $this->uploadAllSupportiveFile($id, $input['file']['supportiveDocumentsSurprise'], ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_SURPRISE);
        // $this->uploadAllSupportiveFile($id, $input['file']['supportiveDocumentsOfferLetter'], ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_OFFER_LETTER);
        // $getApplicationIntake->update(['step' => ApplicationIntake::APPLICATION_STEP9]);
        // return $this->sendResponse(true, route('applicationForm', $id));
    }

    public function callAction($method, $parameters) {
        // code that runs before any action
        $userRoleAction = [
            User::INSTITUTION_ADMIN_USER => [
                'contactInformation', 'contactInformationSubmit', 'addInstitutions',
                'manageCourses', 'manageEnquiries', 'manageProfile', 'manageProfileSubmit', 'manageUser',
                'addCourses', 'approveCourseInstitution',
                'deleteCourse', 'hideCoursesInstitution', 'addCoursesBasicDetails', 'addTags', 'deleteInstitutionUser',
                'editInstitutionUserView', 'editInstitutionUser', 'manageEnquiriesView', 'responseEnquirySubmit', 'getSubdiscipline',
                'editCoursesBasicDetails', 'editRequirmentsPathway', 'editCoursesIntake', 'editApplicationForm', 'getFieldOfStudy',
                'editRequirmentsPathwayView', 'editCoursesIntakeView', 'editApplicationFormView', 'editCoursesBasicDetailsView',
                'deleteRequirement', 'addNewFacilities', 'deleteGalleryImage', 'institutionsProfileView', 'studentViewApplicationInstitutionApprove',
                'studentApplicationApprove', 'studentViewApplicationPendingReview', 'editInstitutionUserAjax', 'makeAdminUser', 'editApplicationOtherForm', 'removeOtherForm', 'institutionCourseSubdispline'
            ],
            User::INSTITUTION_USER => [
                'contactInformation', 'contactInformationSubmit', 'addCourses', 'approveCourseInstitution',
                'manageProfile', 'manageCourses', 'manageProfileSubmit', 'manageEnquiries',
                'deleteCourse', 'hideCoursesInstitution', 'addCoursesBasicDetails', 'addTags', 'manageEnquiriesView', 'responseEnquirySubmit', 'getSubdiscipline',
                'editCoursesBasicDetails', 'editRequirmentsPathway', 'editCoursesIntake', 'editApplicationForm', 'getFieldOfStudy',
                'editRequirmentsPathwayView', 'editCoursesIntakeView', 'editApplicationFormView', 'editCoursesBasicDetailsView',
                'deleteRequirement', 'addNewFacilities', 'deleteGalleryImage', 'institutionsProfileView', 'editApplicationOtherForm', 'removeOtherForm', 'institutionCourseSubdispline'
            ]
        ];

        if ($method == 'notification') {
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
            Permission::INSTITUTION_CONTACT_INFORMATION => ['contactInformation', 'contactInformationSubmit'],
            Permission::INSTITUTION_MANAGE_PROFILE => ['manageProfile', 'manageProfileSubmit', 'addNewFacilities'],
            Permission::INSTITUTION_MANAGE_COURSES => [
                'manageCourses', 'approveCourseInstitution', 'hideCoursesInstitution', 'deleteCourse', 'addCourses',
                'addCoursesBasicDetails', 'addTags', 'getSubdiscipline',
                'editCoursesBasicDetails', 'editRequirmentsPathway', 'editCoursesIntake', 'editApplicationForm', 'getFieldOfStudy',
                'editRequirmentsPathwayView', 'editCoursesIntakeView', 'editApplicationFormView', 'editCoursesBasicDetailsView',
                'deleteRequirement', 'deleteGalleryImage', 'institutionsProfileView', 'editApplicationOtherForm', 'removeOtherForm', 'institutionCourseSubdispline'
            ],
            Permission::INSTITUTION_MANAGE_ENQUIRIES => ['manageEnquiries', 'manageEnquiriesView', 'responseEnquirySubmit']
        ];

//        'addInstitutions','manageUser','deleteInstitutionUser','editInstitutionUserView','editInstitutionUser'
        $permission = Auth::user()->Role_id;
        $user = User::find(Auth::id());
        if ($permission == User::INSTITUTION_ADMIN_USER) {
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

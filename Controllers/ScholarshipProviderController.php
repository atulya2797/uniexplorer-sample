<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use App\Model\User;
use App\Model\Image;
use App\Model\Branch;
use App\Helper\Common;
use App\Model\Enquiry;
use App\Model\Courses;
use App\Model\Country;
use App\Model\Criteria;
use App\Model\Condition;
use App\Model\Permission;
use App\Model\Scholarship;
use App\Model\Institution;
use App\Model\RolePermission;
use App\Model\ScholarshipType;
use App\Model\ScholarshipTypes;
use App\Model\ScholarshipIntake;
use App\Model\UserRolePermission;
use App\Model\ScholarshipProvider;
use App\Model\ScholarshipCriteria;
use App\Model\IntakeScholarshipIntake;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Http\Requests\ScholarshipLogo;
use App\Http\Requests\AddScholarship;
use App\Http\Requests\AddScholarshipProviderUser;
use App\Http\Requests\EditScholarshipProviderUser;
use App\Model\ScholarshipProviderUser;
use App\Http\Requests\RespondEnquiry;
use App\Model\Notification;

class ScholarshipProviderController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function notification() {
        $input = Input::all();
        Common::minimalNotification();
        $notification = Notification::where([
                    'role' => Auth::User()->Role_id,
                ])->orderBy('created_at', 'desc')->paginate(Common::PAGINATION);
        return view('scholarshipProvider.notification', compact('notification'));
    }

    public function readNotification() {
        $data = Input::all();
        Notification::where('id', $data['id'])->update(['is_read' => '1']);
        $response['status'] = true;
        return $response;
    }

    public function manageScholarship() {
        $get = Input::all();
        $user_id = Auth::id();
        $user = User::find(Auth::id());
        $scholarship_provider_id = $user->getScholarshipProviderUser->getScholarshipProvider->id;
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $scholarship_list = Scholarship::where('name', 'like', '%' . $search . '%')
                ->where('ScholarshipProvider_id', $scholarship_provider_id)
                ->where(['is_deleted' => Scholarship::ALIVE])
                ->orderBy('created_at', 'desc')
                ->paginate(Common::PAGINATION);
        $scholarship_provider_name = $user->getScholarshipProviderUser->getScholarshipProvider->name;
        $scholarship_provider_logo_id = $user->getScholarshipProviderUser->getScholarshipProvider->Logo_id;
        $logo_url = '';
        if ($scholarship_provider_logo_id > 0 || $scholarship_provider_logo_id != '') {
            $logo = Image::find($scholarship_provider_logo_id);
            $logo_url = $logo->URL;
        }
        if (\Request::ajax()) {
            return view('scholarshipProvider.dataManageScholarship', compact('scholarship_list', 'scholarship_provider_name', 'user_id', 'logo_url'));
        }
        return view('scholarshipProvider.manageScholarship', compact('scholarship_list', 'scholarship_provider_name', 'user_id', 'logo_url'));
    }

    public function manageScholarshipUpdateActionData($id) {
        $data = Input::all();
        Scholarship::find($id)->update($data);
        return $this->sendResponse(true, route('scholarshipProviderManageScholarship'), 'Scholarship update successfully');
    }

    public function scholarshipUpdatePendingAction($id) {
        $data = Input::all();
        Scholarship::find($id)->update($data);
        $scholarship = Scholarship::find($id);
        $message = 'Scholarship: ' . $scholarship->name . ' has submitted for approval.';
        $notyUrl = route('manageScholarship');
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], Common::SCHOLARSHIP_FOR_APPROVAL, $message, $notyUrl);
        return $this->sendResponse(true, route('scholarshipProviderManageScholarship'), 'Scholarship update successfully');
    }

    public function scholarshipProviderManageLogo() {
        $user_id = User::find(Auth::id())->id;
        $scholarshipProvider_id = ScholarshipProviderUser::where('user_id', $user_id)->first();
        $scholarship_provider_id = $scholarshipProvider_id->ScholarshipProvider_id;
        $scholarshipProvider_logo = ScholarshipProvider::find($scholarship_provider_id);

        $scholarship_provider_logo_id = $scholarshipProvider_logo->Logo_id;
        $logo_url = '';
        if ($scholarship_provider_logo_id > 0 || $scholarship_provider_logo_id != '') {
            $logo = Image::find($scholarship_provider_logo_id);
            $logo_url = $logo->URL;
        }
        if ($scholarship_provider_id < 0) {
            return abort(404);
        }
        return view('scholarshipProvider.scholarship-provider-upload-logo', compact('scholarship_provider_id', 'logo_url', 'scholarship_provider_logo_id'));
    }

    public function scholarshipProviderLogoSubmit(ScholarshipLogo $request) {
        $input = Input::all();
        $tempPath = public_path() . '/temp/';
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0777);
        }
        if (isset($input['logo']) && $input['logo']) {
            $imageData = [
                'name' => $input['logo'],
                'URL' => url('/') . '/scholarshipLogo/' . $input['logo'],
                'ImageTitle' => $input['logo'],
                'ImageDescription' => $input['logo'],
            ];
            if ($input['scholarship_provider_logo_id'] != '' || $input['scholarship_provider_logo_id'] > 0) {
                $image = Image::find($input['scholarship_provider_logo_id'])->update($imageData);
            } else {
                $image = Image::create($imageData);
                $input['Logo_id'] = $image->id;
                ScholarshipProvider::find($input['scholarship_provider_id'])->update($input);
            }
            $from = $tempPath . $input['logo'];
            $filePath_to = public_path() . '/' . ScholarshipProvider::LOGO_FOLDER . '/';
            if (!file_exists($filePath_to)) {
                mkdir($filePath_to, 0777, true);
            }
            $to = public_path() . '/' . ScholarshipProvider::LOGO_FOLDER . '/' . $input['logo'];
            rename($from, $to);
            return $this->sendResponse(true, route('scholarshipProviderManageLogo'));
        }
    }

    public function deleteScholarshipLogo() {
        $data = Input::all();
        ScholarshipProvider::where('id', $data['scholarshipprovider_id'])->update(['Logo_id' => NULL]);
        Image::where(['id' => $data['logo_id']])->delete();
        return $this->sendResponse(true, route('scholarshipProviderManageLogo'), 'Scholarship Logo has been removed successfully.');
    }

    public function scholarshipEdit($id) {
        $scholarship = Scholarship::find($id);
        $countries = Country::all();
        $institutions = Institution::where([
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->has('getInstitutionAdmin')
                ->get();
        $Scholarship_Criteria = Criteria::all();
        $Scholarship_type = ScholarshipType::all();
        $scholarshipintake_data = IntakeScholarshipIntake::whereHas('getScholarshipIntake', function($q) use ($id) {
                    return $q->where('Scholarship_id', $id);
                })->get();
        $scholarshipintake = array();
        $selcted_institute_arr = [];
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
        return view('scholarshipProvider.manageScholarshipEdit', compact('scholarship', 'countries', 'institutions', 'Scholarship_Criteria', 'Scholarship_type', 'scholarshipintake', 'selcted_institute_arr'));
    }

    public function scholarshipUpdate($id, AddScholarship $request) {
        $get = Input::all();
        if (!isset($get['Course_intake_id'])) {
            return $this->sendResponse(false, '', 'Please Select Course');
        }
        $get['Course_intake_id'] = array_values($get['Course_intake_id']);
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
        //return redirect()->route('managescholarship');
        return $this->sendResponse(true, route('scholarshipProviderManageScholarship'), 'Scholarship Edited successfully.');
    }

    public function deleteScholarship() {
        $data = Input::all();
        Scholarship::where('id', $data['id'])->update(['is_deleted' => Scholarship::DELETE]);
        return $this->sendResponse(true, route('scholarshipProviderManageScholarship'), 'Scholarship has been deleted');
    }

    public function manageEnquiries() {
        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $user_id = User::find(Auth::id())->id;
        $scholarship_provider_id = ScholarshipProviderUser::where('user_id', $user_id)->first();
        $scholarshipproviderid = $scholarship_provider_id->ScholarshipProvider_id;
        $manageEnquiries_listData = Enquiry::where('ScholarshipProvider_id', $scholarshipproviderid)
                ->whereHas('getStudentUser', function($query) use ($search) {
                    return $query->whereHas('getUserDetails', function($q) use ($search) {
                                return $q->Where('firstName', 'like', '%' . $search . '%')
                                        ->orWhere('lastName', 'like', '%' . $search . '%')
                                        ->orWhere('email', 'like', '%' . $search . '%');
                            });
                });
        $manageEnquiries_list = $manageEnquiries_listData->paginate(Common::PAGINATION);

        $outstanding_enquiry = Enquiry::where('ScholarshipProvider_id', $scholarshipproviderid)
                ->whereHas('getStudentUser', function($query) use ($search) {
                    return $query->whereHas('getUserDetails', function($q) use ($search) {
                                return $q->Where('firstName', 'like', '%' . $search . '%')
                                        ->orWhere('lastName', 'like', '%' . $search . '%')
                                        ->orWhere('email', 'like', '%' . $search . '%');
                            });
                })->where(['status'=>0])->get();
        //$outstanding_enquiry = $manageEnquiries_listData->where(['status'=>0])->get();
        if (\Request::ajax()) {
            return view('scholarshipProvider.dataManageEnquiries', compact('manageEnquiries_list','outstanding_enquiry'));
        }
        return view('scholarshipProvider.manageEnquiries', compact('manageEnquiries_list','outstanding_enquiry'));
    }

    public function manageEnquiriesView() {
        $data = Input::all();
        $enquiry_data = Enquiry::find($data['enquiryid']);
        echo view('scholarshipProvider.manageEnquiriesView', compact('enquiry_data'));
    }

    public function responseEnquirySubmit($id, RespondEnquiry $request) {
        $data = Input::all();
        $user_id = User::find(Auth::id())->id;
        $data['responseDate'] = date('Y-m-d');
        $data['User_Responder'] = $user_id;
        $data['status'] = 1;
        $institution = Enquiry::find($id)->update($data);
        //Common::sendMail($data['student_email'], 'Uniexplorers', $data, 'mail.enquiryMail');
        return $this->sendResponse(true, route('scholarshipProviderManageEnquiries'), 'Enuiry has been Responded successfully.');
    }

    public function manageUser() {
        $get = Input::all();
        $search = isset($get['search']) && !empty($get['search']) ? $get['search'] : '';
        $user = User::find(Auth::id());
        $scholarship_provider_id = $user->getScholarshipProviderUser->getScholarshipProvider->id;
        $scholarship_providerUsers = ScholarshipProviderUser::where('User_id', '!=', Auth::id())
                ->whereHas('getUser', function($q) use($search) {
                    return $q->where(function($query) use ($search) {
                                return $query->where('firstName', 'LIKE', '%' . $search . '%')
                                        ->orWhere('lastName', 'LIKE', '%' . $search . '%')
                                        ->orWhere('email', 'LIKE', '%' . $search . '%')
                                        ->orWhere(DB::raw('concat(firstName," ",lastName)') , 'like' , '%' . $search . '%');
                            });
                })
                ->where(['ScholarshipProvider_id' => $scholarship_provider_id])->orderBy('updated_at', 'desc')
                ->paginate(Common::PAGINATION);



        $permissions = RolePermission::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER, 'granted' => UserRolePermission::GRANTED])
                        ->whereHas('getPermission', function($q) {
                            return $q->where(['cms_type' => Permission::TYPE_SCHOOLARSHIP_PROVIERS]);
                        })->get();
        if (\Request::ajax()) {
            return view('scholarshipProvider.dataManageScholarshipProvider', compact('scholarship_providerUsers', 'permissions'));
        }

        return view('scholarshipProvider.manageUser', compact('scholarship_providerUsers', 'permissions'));
    }

    public function addScholarship() {
        $countries = Country::all();
        $institutions = Institution::where([
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->has('getInstitutionAdmin')
                ->get();
        $Scholarship_Criteria = Criteria::all();
        $Scholarship_type = ScholarshipType::all();
        return view('scholarshipProvider.addScholarship', compact('countries', 'institutions', 'Scholarship_type', 'Scholarship_Criteria'));
    }

    public function addScholarshipUser(AddScholarshipProviderUser $request) {
        $data = Input::all();
        $data['Role_id'] = User::SCHOLARSHIP_PROVIDER_USER;
        $uniquePassword = uniqid();
        $data['password'] = Hash::make($uniquePassword);
        $create_user = User::create($data);
        $user = User::find(Auth::id());
        $scholarship_provider_id = $user->getScholarshipProviderUser->getScholarshipProvider->id;
        $data['User_id'] = $create_user->id;
        $data['ScholarshipProvider_id'] = $scholarship_provider_id;
        $data['granted'] = 1;
        ScholarshipProviderUser::create($data);
        $data['granted'] = 1;
        $rolePermissions = RolePermission::select()->where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER, 'granted' => 1])->get();
        UserRolePermission::where(['User_id' => $create_user->id])->delete();
        foreach ($rolePermissions as $value) {
            if (isset($data["permission"][$value['Permission_id']]) && !empty($data["permission"][$value['Permission_id']])) {
                $data['User_id'] = $create_user->id;
                $data['RolePermission_id'] = $value['id'];
                UserRolePermission::create($data);
            }
        }
        $create_user['password'] = $uniquePassword;
        Common::sendMail($create_user->email, 'Uniexplorers', $create_user, 'mail.addScholarshipProviderUser');
        return $this->sendResponse(true, route('scholarshipProviderManageUser'), 'Scholarship Provider user has been added successfully.');
    }

    public function editScholarshipProviderUserView($userId) {
        $user = User::find(Auth::id());
        if (Auth::id() == $userId) {
            abort(401);
        }

        $scholarshipProvider = $user->getScholarshipProviderUser->getScholarshipProvider;
        $chckScholarshipProviderUser = ScholarshipProviderUser::where(['ScholarshipProvider_id' => $scholarshipProvider->id])
                ->where('User_id', '!=', Auth::id())
                ->whereHas('getUser', function($q) use ($userId) {
                    return $q->where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER])
                            ->where(['id' => $userId]);
                })
                ->first();

        if ($chckScholarshipProviderUser) {
            $scholarshipProviderUser = User::find($userId);
            $userRolePermission = UserRolePermission::where(['User_id' => $userId])->get();
            $permissions = RolePermission::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER, 'granted' => UserRolePermission::GRANTED])
                            ->whereHas('getPermission', function($q) {
                                return $q->where(['cms_type' => Permission::TYPE_SCHOOLARSHIP_PROVIERS]);
                            })->get();
            return view('scholarshipProvider.editManageUserForm', compact('scholarshipProviderUser', 'permissions', 'userRolePermission'));
        } else {
            abort(404);
        }
    }

    public function editScholarshipProviderUser(EditScholarshipProviderUser $request, $userId) {
        $input = Input::all();
        $user = User::find(Auth::id());
        if (Auth::id() == $userId) {
            abort(401);
        }

        $scholarshipProvider = $user->getScholarshipProviderUser->getScholarshipProvider;
        $chckScholarshipProviderUser = ScholarshipProviderUser::where(['ScholarshipProvider_id' => $scholarshipProvider->id])
                ->where('User_id', '!=', Auth::id())
                ->whereHas('getUser', function($q) use ($userId) {
                    return $q->where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER])
                            ->where(['id' => $userId]);
                })
                ->first();
        if ($chckScholarshipProviderUser) {
            $input['granted'] = 1;
            $rolePermissions = RolePermission::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER, 'granted' => 1])->get();
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
        return $this->sendResponse(true, route('scholarshipProviderManageUser'), 'Scholar Provider User updated');
    }

    public function editScholarshipProviderAjax($userId) {
        $input = Input::all();
        $input['granted'] = 1;
        $rolePermissions = RolePermission::where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER, 'granted' => 1])->get();
        UserRolePermission::where(['User_id' => $userId])->delete();
        foreach ($rolePermissions as $value) {
            if (isset($input["permission"][$value['Permission_id']]) && !empty($input["permission"][$value['Permission_id']])) {
                $input['User_id'] = $userId;
                $input['RolePermission_id'] = $value['id'];
                UserRolePermission::create($input);
            }
        }
        return $this->sendResponse(true, '', 'Scholar Provider User updated');
    }

    public function addScholarshipSubmit(AddScholarship $request) {
        $data = Input::all();

        if (!isset($data['Course_intake_id'])) {
            return $this->sendResponse(false, '', 'Please Select Course');
        }
        $data['Course_intake_id'] = array_values($data['Course_intake_id']);

        $user_id = User::find(Auth::id())->id;
        $scholarship_providerid = ScholarshipProviderUser::select('ScholarshipProvider_id')->where('user_id', $user_id)->get();

        foreach ($scholarship_providerid as $scholarshipprovider_id)
            $scholarship_provider_id = $scholarshipprovider_id->ScholarshipProvider_id;


        if ($data['type_name'] != '' && $data['Type_id'] == 'other') {
            $data['unique'] = 1;
            $addtype = ScholarshipType::create($data);
            $data['Type_id'] = $addtype->id;
        }
        if ($data['new_criteria_name'] != '' && $data['Criteria_id'] == 'other') {
            $data['criteria_name'] = $data['new_criteria_name'];
            $data['unique'] = 1;
            $addCriteria = Criteria::create($data);
            $data['Criteria_id'] = $addCriteria->id;
        }

        $data['ScholarshipProvider_id'] = $scholarship_provider_id;
        $data['slug'] = Scholarship::slug($data['name']);
        $added_Scholarship = Scholarship::create($data);
//        Scholarship::createScholarshipPage($added_Scholarship->id);

        $data['Scholarship_id'] = $added_Scholarship->id;
        $added_type = ScholarshipTypes::create($data);

        if (count($data['Course_intake_id']) > 0) {
            for ($j = 0; $j < count($data['application_start_date']); $j++) {
                $data['applicationStartDate'] = $data['application_start_date'][$j];
                $data['applicationDeadline'] = $data['application_deadline'][$j];
                $data['maxNumberRecipients'] = $data['maxNumber_recipients'][$j];
                $added_scholarshipintake = ScholarshipIntake::create($data);
                $data['ScholarshipIntake_id'] = $added_scholarshipintake->id;
                if (isset($data['Course_intake_id'][$j]) && count($data['Course_intake_id'][$j]) > 0) {
                    for ($i = 0; $i < count($data['Course_intake_id'][$j]); $i++) {
                        $data['Intake_id'] = $data['Course_intake_id'][$j][$i];
                        IntakeScholarshipIntake::create($data);
                    }
                }
            }
        }

        $added_Criteria = ScholarshipCriteria::create($data);
        return $this->sendResponse(true, route('scholarshipProviderManageScholarship'), 'Scholarship added successfully.');
    }

    public function deleteScholarshipProviderUser($userId) {
        User::find($userId)->update(['is_deleted' => User::DELETED]);
        return redirect()->route('scholarshipProviderManageUser', 'Scholar Provider User has been deleted');
    }

    public function getCourse() {
        $data = Input::all();
        $selected_course = $data['institution_id'];
        $institution = array_keys($data['institution_id']);

        $data = Branch::whereIn('Institution_id', $institution)->get();
        $main_arr = array();
        foreach ($data as $key => $value) {
            $course_list = array();
            $levelofstudy = array();
            $fieldofstudy = array();
            $check_arr2 = array();
            $main_course = $value->getintake;
            if (count($main_course)) {
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
        return view('scholarshipProvider.course_ajax', compact('main_arr', 'selected_course'));
    }

    public function getCourseListforLeveOfStudy() {
        $data = Input::all();
        $institution = $data['institution'];
        $level_of_study = $data['level_of_study'];
        $level_of_study_ids = array_keys($data['level_of_study']);
        $subdisciplineIds = (isset($data['subdiscipline_id']) && count($data['subdiscipline_id'])>0 ) ? $data['subdiscipline_id'] : [];
        $data = Courses::whereHas('getCourseSubdiscipline', function($q) use ($subdisciplineIds) {
                    return $q->whereIn('subdiscipline_id', $subdisciplineIds);
                })->whereIn('LevelofStudy_id', $level_of_study)->whereHas('getIntakes', function($query) use ($institution) {
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
        return view('scholarshipProvider.courseListforLeveOfStudy', compact('main_arr', 'institution'));
    }

    public function getCourseListforsubdiscipline() {
        $data = Input::all();
        $institution = $data['institution'];
        $subdisciplineIds = $data['subdiscipline_id'];
        $level_of_study = (isset($data['level_of_study']) && count($data['level_of_study'])>0 ) ? $data['level_of_study'] : [];
        if(count($level_of_study)){
            $level_of_study_ids = array_keys($data['level_of_study']);
        }
//        $level_of_study_ids = array_keys($data['subdiscipline_id']);
        $data = Courses::whereHas('getCourseSubdiscipline', function($q) use ($subdisciplineIds) {
                    return $q->whereIn('subdiscipline_id', $subdisciplineIds);
                })->whereIn('LevelofStudy_id', $level_of_study)->whereHas('getIntakes', function($query) use ($institution) {
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
        return view('scholarshipProvider.courseListforLeveOfStudy', compact('main_arr', 'institution'));
    }

    public function makeAdminUserScholarship() {
        $input = Input::all();
        $data['Role_id'] = User::SCHOLARSHIP_PROVIDER_ADMIN_USER;
        $permission_data = [];
        User::where('id', $input['id'])->update($data);
        if ($input['condition_name'] == Condition::TRANSFER_ADMIN_STATUS) {
            $data['Role_id'] = User::SCHOLARSHIP_PROVIDER_USER;
            User::where('id', Auth::id())->update($data);
            $rolePermissions = RolePermission::select()->where(['Role_id' => User::SCHOLARSHIP_PROVIDER_USER, 'granted' => 1])->get();
            $permission_data['granted'] = 1;
            foreach ($rolePermissions as $value) {
                $permission_data['User_id'] = Auth::id();
                $permission_data['RolePermission_id'] = $value['id'];
                UserRolePermission::create($permission_data);
            }
            return $this->sendResponse(true, route('notification'), 'User transfer successfully.');
        }
        return $this->sendResponse(true, route('scholarshipProviderManageUser'), 'User transfer successfully.');
    }

    public function callAction($method, $parameters) {
// code that runs before any action
        $userRoleAction = [
            User::SCHOLARSHIP_PROVIDER_ADMIN_USER => [
                'notification', 'readNotification', 'manageScholarship', 'deleteScholarship', 'manageEnquiries',
                'manageEnquiriesView', 'responseEnquirySubmit', 'manageUser', 'addScholarship',
                'addScholarshipUser', 'editScholarshipProviderUserView', 'editScholarshipProviderUser',
                'addScholarshipSubmit', 'deleteScholarshipProviderUser', 'getCourse',
                'getCourseListforLeveOfStudy', 'getCourseListforsubdiscipline', 'scholarshipEdit',
                'manageScholarshipUpdateActionData', 'scholarshipUpdate', 'scholarshipUpdateAction',
                'scholarshipUpdatePendingAction', 'scholarshipProviderManageLogo', 'scholarshipProviderLogoSubmit', 'deleteScholarshipLogo', 'editScholarshipProviderAjax', 'makeAdminUserScholarship'
            ],
            User::SCHOLARSHIP_PROVIDER_USER => [
                'notification', 'readNotification', 'manageScholarship', 'deleteScholarship', 'manageEnquiries',
                'manageEnquiriesView', 'responseEnquirySubmit', 'manageUser', 'addScholarship',
                'addScholarshipUser', 'editScholarshipProviderUserView', 'editScholarshipProviderUser',
                'addScholarshipSubmit', 'deleteScholarshipProviderUser', 'getCourse',
                'getCourseListforLeveOfStudy', 'getCourseListforsubdiscipline', 'scholarshipEdit',
                'manageScholarshipUpdateActionData', 'scholarshipUpdate', 'scholarshipUpdateAction', 'scholarshipUpdatePendingAction', 'scholarshipProviderManageLogo', 'scholarshipProviderLogoSubmit',
                'deleteScholarshipLogo', 'makeAdminUserScholarship'
            ]
        ];
        if ($method == 'notification' || $method == 'readNotification') {
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
            Permission::SCHOOLARSHIP_PROVIERS_MANAGE_SCHOOLARSHIP => [
                'manageScholarship', 'deleteScholarship', 'addScholarship', 'addScholarshipSubmit',
                'getCourse', 'getCourseListforLeveOfStudy', 'getCourseListforsubdiscipline', 'scholarshipEdit', 'manageScholarshipUpdateActionData', 'scholarshipUpdate', 'scholarshipUpdateAction', 'scholarshipUpdatePendingAction', 'scholarshipProviderManageLogo', 'scholarshipProviderLogoSubmit', 'deleteScholarshipLogo'
            ],
            Permission::SCHOOLARSHIP_PROVIERS_MANAGE_ENQUIRIES => ['manageEnquiries', 'manageEnquiriesView', 'responseEnquirySubmit']
        ];
        $permission = Auth::user()->Role_id;
        $user = User::find(Auth::id());
        if ($permission == User::SCHOLARSHIP_PROVIDER_ADMIN_USER) {
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

<?php

namespace App\Http\Controllers;

use Auth;
use Session;
use Validator;
use App\Model\Image;
use App\Model\User;
use App\Model\Year;
use App\Model\Month;
use App\Model\MonthYear;
use App\Model\Country;
use App\Helper\Common;
use App\Model\Institution;
use App\Model\InstitutionUser;
use App\Http\Requests\ScholarshipLogo;
use App\Http\Requests\Register;
use App\Http\Requests\StudentRegister;
use App\Http\Requests\StudentRequirements;
use App\Model\RegistrationMail;
use App\Model\ScholarshipProvider;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Model\ScholarshipProviderUser;
use App\Http\Requests\RegisterScholarshipProvider;
use Illuminate\Support\Str;
use App\Model\Student;
use App\Model\LevelOfStudy;
use App\Model\FieldOfStudy;
use App\Model\Subdiscipline;
use App\Model\DesiredSubdiscipline;
use App\Model\DesiredLevelOfStudy;
use App\Model\DesiredIntake;
use App\Model\ShortlistedCourses;
use App\Model\Courses;

class AuthController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function login() {
        if (Auth::user() && (Auth::user()->Role_id != User::STUDENT)) {
            return redirect()->route('notification');
        }
        if (\Request::ajax()) {
            $data = Input::all();
            unset($data['_token']);
            if (Auth::attempt($data)) {
                $checkUserDeleted = User::find(Auth::id());
                if ($checkUserDeleted->is_deleted == User::DELETED) {
                    Auth::logout();
                    return $this->sendResponse(false, route('login'), 'Invalid Details');
                }
                switch (Auth::user()->Role_id) {
                    case User::ADMIN;
                        return $this->sendResponse(true, route('notification'));
                    case User::INTERNAL_REVIEWER;
                        return $this->sendResponse(true, route('notification'));
                    case User::INSTITUTION_USER;
                        return $this->sendResponse(true, route('notification'));
                    case User::INSTITUTION_ADMIN_USER;
                        $user = User::find(Auth::id());
                        if (isset($user->getInstitutionUser) &&
                                ($user->getInstitutionUser->getInstitution->verification == Institution::VERIFICATION_PASS )) {
                            return $this->sendResponse(true, route('notification'));
                        } else {
                            Auth::logout();
                            return $this->sendResponse(false, route('login'), 'Account Not Verified');
                        }
                    case User::SCHOLARSHIP_PROVIDER_USER;
                        $user = User::find(Auth::id());
                        if (isset($user->getScholarshipProviderUser) && isset($user->getScholarshipProviderUser->getScholarshipProvider)) {
                            $scholarshipProvider = $user->getScholarshipProviderUser->getScholarshipProvider;
                            if ($scholarshipProvider && $scholarshipProvider->verified) {
                                return $this->sendResponse(true, route('notification'));
                            } else {
                                Auth::logout();
                                return $this->sendResponse(false, route('login'), 'Account Not Verified');
                            }
                        } else {
                            Auth::logout();
                            return $this->sendResponse(false, route('login'), 'Account Not Verified');
                        }
                    case User::SCHOLARSHIP_PROVIDER_ADMIN_USER;
                        $user = User::find(Auth::id());
                        if (isset($user->getScholarshipProviderUser) && isset($user->getScholarshipProviderUser->getScholarshipProvider)) {
                            $scholarshipProvider = $user->getScholarshipProviderUser->getScholarshipProvider;
                            if ($scholarshipProvider && $scholarshipProvider->verified) {
                                return $this->sendResponse(true, route('notification'));
                            } else {
                                Auth::logout();
                                return $this->sendResponse(false, route('login'), 'Account Not Verified');
                            }
                        } else {
                            Auth::logout();
                            return $this->sendResponse(false, route('login'), 'Account Not Verified');
                        }
                    case User::STUDENT;
                        Auth::logout();
                        return $this->sendResponse(false, '', 'Unauthorized');
                    default :
                        return $this->sendResponse(true, route('login'));
                }
            }
            return $this->sendResponse(false, route('login'), 'Invalid login credentials. Please try again.');
        }
        return view('auth.login');
    }

    public function logout() {
        Auth::logout();
        return redirect()->route('login');
    }

    public function studentLogout() {
        Auth::logout();
        return redirect()->route('loginStudent');
    }

    public function loginStudent() {
        if (Auth::user() && (Auth::user()->Role_id == User::STUDENT)) {
            return redirect()->route('myAccount');
        }
        if (\Request::ajax()) {
            $data = Input::all();
            $referrer = $data['referrer'];
            unset($data['_token']);
            unset($data['referrer']);
            if (Auth::attempt($data)) {
                if (Auth::user()->Role_id !== User::STUDENT) {
                    Auth::logout();
                    return $this->sendResponse(false, '', 'Unauthorized');
                }
                $studentDetail = User::find(Auth::id())->getStudent;
                if (!$studentDetail->verification) {
                    Auth::logout();
                    return $this->sendResponse(false, '', 'Account Not Verified');
                }
                if (empty($referrer)) {
                    return $this->sendResponse(true, route('home'));
                } else {
                    return $this->sendResponse(true, $referrer);
                }
            }
            return $this->sendResponse(false, route('loginStudent'), 'Invalid Details');
        }
        return view('auth.loginStudent');
    }

    public function register($token) {
        // if (!Common::isRegisterTokenValid(RegistrationMail::TYPE_INSTITUTION, $token)) {
        //     return abort(404);
        // }
        return view('auth.register');
    }

    public function registerSubmit(Register $request) {
        $data = Input::all();
        if (!Common::isRegisterTokenValid(RegistrationMail::TYPE_INSTITUTION, $data['mailToken'])) {
            return $this->sendResponse(false, '', 'Link expired.');
        }
        $data['verification'] = Institution::VERIFICATION_PENDING;

        $data['Role_id'] = User::INSTITUTION_ADMIN_USER;
        $data['password'] = Hash::make($data['password']);
        $create_user = User::create($data);
        $data['slug'] = Institution::slug($data['name']);
        $crete_institute = Institution::create($data);
        $data['User_id'] = $create_user->id;
        $data['Institution_id'] = $crete_institute->id;
        InstitutionUser::create($data);
        Institution::createInstitutionPage($crete_institute->id);

//create notification for created institute
        $message = 'Institute Provider: ' . $crete_institute->name . ' - ' . $create_user->firstName . ' ' . $create_user->lastName . ' - Email: ' . $create_user->email . ' - ' . $crete_institute->domain;
        $notyUrl = route('manageInstitutions');
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], Common::INSTITUTION_PENDING_ACCOUNT_VERIFICATION, $message, $notyUrl);
        RegistrationMail::where(['token' => $data['mailToken']])->delete();
//action after submit the form
        if (\Request::ajax()) {
            return $this->sendResponse(true, route('login'), 'Wait for admin to verify your profile');
        }
        if (\Request::post()) {
            return redirect()->route('login');
        }
        return view('auth.register');
    }

    public function registerScholarshipProvider($token) {
        // if (!Common::isRegisterTokenValid(RegistrationMail::TYPE_SCHOLARSHIP, $token)) {
        //     return abort(404);
        // }
        $countries = Country::all();
        return view('auth.register-scholarship-provider', compact('countries'));
    }

    public function registerScholarshipProviderSubmit(RegisterScholarshipProvider $request) {
        $data = Input::all();
        if (!Common::isRegisterTokenValid(RegistrationMail::TYPE_SCHOLARSHIP, $data['mailToken'])) {
            return $this->sendResponse(false, '', 'Link expired.');
        }

        $data['Role_id'] = User::SCHOLARSHIP_PROVIDER_ADMIN_USER;
        $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&');
        $data['password_to_email'] = substr($random, 0, 8);
        $data['password'] = Hash::make($data['password_to_email']);
        $data['token'] = Str::random(40);

        $create_user = User::create($data);

        $data['User_id'] = $create_user->id;
        $data['approval'] = ScholarshipProvider::NOT_APPROVAL;
        $data['visibility'] = ScholarshipProvider::HIDE_VISIBILITY;
        $crete_scholarship_provider = ScholarshipProvider::create($data);

        $data['ScholarshipProvider_id'] = $crete_scholarship_provider->id;
        ScholarshipProviderUser::create($data);

        $message = 'Scholarship provider: ' . $crete_scholarship_provider->name . ' - ' . $create_user->firstName . ' ' . $create_user->lastName . ' - Email: ' . $create_user->email;
        $notyUrl = route('manageScholarshipProvider');
        Common::saveNotification([User::ADMIN, User::INTERNAL_REVIEWER], Common::SCHOLARSHIP_PROVIDER_PENDING_ACCOUNT_VERIFICATION, $message, $notyUrl);
        /* Send an email to the Scholarship Provider_adminEmail1 with a password and a link to verify the account created. */
        Common::sendMail($data['email'], 'Uniexplorers', $data, 'mail.registerScholarshipProviderMail');
        RegistrationMail::where(['token' => $data['mailToken']])->delete();

//action after submit the form
        if (\Request::ajax()) {
            //return $this->sendResponse(true, route('login'));
            Session::put('scholarship_provider_id', $crete_scholarship_provider->id);
            return $this->sendResponse(true, route('ScholarshipProviderUploadLogo', $crete_scholarship_provider->id), 'Your Scholarship Provider account has been created successfully. We have sent you an email with a link to verify your account. Once verified, you can login to manage your scholarships.');
        }
        if (\Request::post()) {
            return redirect()->route('login');
        }
        return redirect()->route('registerScholarshipProvider');
    }

    public function ScholarshipProviderUploadLogo($id) {
        $scholarship_provider_id = $id;
        if ($scholarship_provider_id < 0 || $scholarship_provider_id == '') {
            return abort(404);
        }
        return view('auth.scholarship-provider-upload-logo', compact('scholarship_provider_id'));
    }

    public function uploadLogoSubmit(ScholarshipLogo $request) {
        $input = Input::all();
        $tempPath = public_path() . '/temp/';
        if (isset($input['logo']) && $input['logo']) {
            $imageData = [
                'name' => $input['logo'],
                'URL' => url('/') . '/' . ScholarshipProvider::LOGO_FOLDER . '/' . $input['logo'],
                'ImageTitle' => $input['logo'],
                'ImageDescription' => $input['logo'],
            ];
            $image = Image::create($imageData);
            $input['Logo_id'] = $image->id;
            ScholarshipProvider::find($input['scholarship_provider_id'])->update($input);
            $from = $tempPath . $input['logo'];
            $filePath_to = public_path() . '/' . ScholarshipProvider::LOGO_FOLDER . '/';
            if (!file_exists($filePath_to)) {
                mkdir($filePath_to, 0777, true);
            }
            $to = public_path() . '/' . ScholarshipProvider::LOGO_FOLDER . '/' . $input['logo'];
            rename($from, $to);
            Session::forget('scholarship_provider_id');
            return $this->sendResponse(true, route('login'), "Your Scholarship Provider logo has been uploaded successfully. Once verified, you can login to manage your scholarships.");
        } else {
            Session::forget('scholarship_provider_id');
            return $this->sendResponse(true, route('login'));
        }
    }

    public function verificationScholarshipProvider() {
        $token = Input::get('token');
        $user = User::where(['token' => $token])->first();
        if ($user && $user->getScholarshipProviderUser && $user->getScholarshipProviderUser->getScholarshipProvider) {
            $scholarshipProvider = $user->getScholarshipProviderUser->getScholarshipProvider;
            $scholarshipProvider->update(['verified' => ScholarshipProvider::VERIFIED]);
            $user->update(['token' => null]);
            Session::put('success', 'Account verify complete');
            return redirect()->route('login');
        } else {
            return abort(404);
        }
    }

    //student registration start
    public function studentRegisterView() {
        $LevelOfStudy = LevelOfStudy::all();
        $FieldOfStudy = FieldOfStudy::all();
        $Subdiscipline = Subdiscipline::all();
        $courses = Courses::all();
        $years = Year::all();
        $months = Month::all();
        list($currentYearDetail, $nextYearDetail) = $this->getIntakeFilterCourseSearch();
        return view('student.register', compact('LevelOfStudy', 'FieldOfStudy', 'courses', 'years', 'months', 'Subdiscipline', 'currentYearDetail', 'nextYearDetail'));
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

    public function registerStudent(StudentRegister $request) {
        $data = ['secondPageUrl' => route('saveRegisterStep2')];
        return $this->sendResponse(true, '', 'Student Registration First Step Complete', $data, 'studentRegister2Page');
    }

    public function saveRegisterStep2(StudentRegister $request, StudentRequirements $request2) {
        $input = Input::all();
        $input['Role_id'] = User::STUDENT;
        $input['phone'] = null;
        $input['password'] = bcrypt($input['password']);
        $input['token'] = bcrypt(uniqid());
        $create_user = User::create($input);

        $input['User_id'] = $create_user->id;
        $student = Student::create($input);

        $input['Student_id'] = $student->id;

        DesiredLevelOfStudy::create($input);
        DesiredSubdiscipline::create($input);

        $studentUpdate = $student->update($input);
        if (isset($input['courses'])) {
            $Courses = Courses::where(['name' => $input['courses']])->first();
            if (isset($Courses))
                $input['Course_id'] = $Courses->id;
            ShortlistedCourses::create($input);
        }


        if (isset($input['Year'])) {
            $Year = Year::where(['name' => $input['Year']])->first();
            if (!isset($Year)) {
                $Year_data['name'] = $input['Year'];
                $Year = Year::create($Year_data);
            }
            $input['Year_id'] = $Year->id;
        }

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
            if (!isset($MonthYear))
                $MonthYear = MonthYear::create($input);

            $input['MonthYear_id'] = $MonthYear->id;
            DesiredIntake::create($input);
        }

        Common::sendMail($create_user->email, 'Uniexplorers', $create_user, 'mail.registerStudentMail');
        return $this->sendResponse(true, '', 'Student Registration Complete, Please Conform Email', '', 'studentRegisterFinalPage');
    }

    //student registration end
    public function emailVerification() {
        $input = Input::all();
        if (!isset($input['token'])) {
            abort(404);
        }
        $getUnverifiedStudent = User::where(['token' => $input['token']])
                ->whereHas('getStudent', function($q) {
                    return $q->where(['verification' => false]);
                })
                ->first();
        if (!$getUnverifiedStudent) {
            abort(404);
        }
        User::find($getUnverifiedStudent->id)->update(['token' => null]);
        Student::where(['id' => $getUnverifiedStudent->getStudent->id])->update(['verification' => true]);
        return view('student.emailVerification');
    }

}

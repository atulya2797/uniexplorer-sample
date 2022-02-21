<?php

namespace App\Http\Controllers;

use Auth;
use App\Model\User;
use App\Model\Courses;
use App\Model\Institution;
use App\Model\Scholarship;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController {

    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests;

    public $student;

    /**
     * Send return response when Ajax call on form submit
     * 
     * @param boolean   $cstatus    This will be true or false.
     * @param string    $curl       This is the page Url where to redirect after form submit successfully.
     * @param string    $cmessage   This is to show message on error.
     * @param array     $cdata      Just in case if you want to send some data in return.
     * @param array     $function      This function will call in javascript like hgphpdev(param) (this is your custom function and param will be your data you send).
     * @return array    This will return all param detail with array.
     * 
     * */
    public function sendResponse($cstatus, $curl = '', $cmessage = '', $cdata = [], $function = '') {
        return [
            'status' => $cstatus,
            'url' => $curl,
            'message' => $cmessage,
            'data' => $cdata,
            'function' => $function
        ];
    }

    public function filterInstitution() {
        $institution = Institution::where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->where(['type' => Institution::TYPE_UNIVERSITY])
                ->has('getInstitutionAdmin');
        return $institution;
    }

    public function filterVet() {
        $vet = Institution::where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->where(['type' => Institution::TYPE_TRAINING])
                ->has('getInstitutionAdmin');
        return $vet;
    }
    public function filterAllInstitution() {
        $vet = Institution::where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])
                ->has('getInstitutionAdmin');
        return $vet;
    }

    public function filterCourse($institutionId = null) {
        $course = Courses::whereHas('getIntakes', function($query) use ($institutionId) {
                    return $query->whereHas('getIntakeBranch', function($intQuery) use ($institutionId) {
                                return $intQuery->whereHas('getInstitution', function($instQuery) use ($institutionId) {
                                            $instQuery = $instQuery->where([
                                                        'visibility' => Institution::VISIBILITY_PUBLISHED,
                                                        'approval' => Institution::APPROVAL,
                                                        'verification' => Institution::VERIFICATION_PASS
                                                    ])
                                                    ->has('getInstitutionAdmin');
                                            if ($institutionId) {
                                                $instQuery = $instQuery->where(['id' => $institutionId]);
                                            }
                                            return $instQuery;
                                        });
                            });
                })
                ->where([
            'visibility' => Courses::PUBLISHED,
            'approval' => Courses::APPROVED
        ]);
        return $course;
    }

    public function filterScholarship() {
        $scholarship = Scholarship::whereHas('getScholarshipProvider', function($q) {
                    return $q->whereHas('getScholarshipProviderUser', function($qu) {
                                return $qu->has('getUser');
                            });
                })
                ->where(['is_deleted' => Scholarship::ALIVE,'visibility'=>Scholarship::PUBLISHED,'approval'=>Scholarship::APPROVED]);
        return $scholarship;
    }

    public function callAction($method, $parameters) {
        if (Auth::id() && (Auth::User()->Role_id == User::STUDENT)) {
            $user = User::find(Auth::id());
            $this->student = $user;
        } else {
            $this->student = NULL;
        }
        return parent::callAction($method, $parameters);
    }

}

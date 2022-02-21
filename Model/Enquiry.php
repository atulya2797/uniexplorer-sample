<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * enquiryList
 *
 */
class Enquiry extends Model {

    protected $table = "enquiry";
    const PENDING_RESPONSE = 0;
    const RESPONDED = 1;

    //text
     const PENDING_RESPONSE_TEXT = 'Pending Response';
    const RESPONDED_TEXT = 'RESPONDED';
    const SOURCE_SCHOLARSHIP = 'scholarship';
    const SOURCE_COURSE = 'course';
    const SOURCE_INSTITUTION = 'institution';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Student_id', 'ScholarshipProvider_id', 'Institution_id', 'Intake_id', 'source', 'enquiry', 'response', 'enquiryDate', 'responseDate', 'User_Responder', 'status'
    ];

    public function getEnquiryUserResponder() {
        return $this->hasOne('App\Model\User', 'id', 'User_Responder')->where(['is_deleted'=>User::NOT_DELETED]);
    }

    public function getScholarshipProvider() {
        return $this->hasOne('App\Model\ScholarshipProvider', 'id', 'ScholarshipProvider_id');
    }

    public function getInstitution() {
        return $this->hasOne('App\Model\Institution', 'id', 'Institution_id');
    }

    public function getStudentUser() {
        return $this->hasOne('App\Model\Student', 'id', 'Student_id');
    }
    

}

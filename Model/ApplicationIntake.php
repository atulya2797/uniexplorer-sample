<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ApplicationIntake extends Model {

    protected $table = "applicationIntake";

    const APPLICATION_DRAFT = 0;
    const PENDING_APPLICATION = 1;
    const PENDING_APPLICATION_FEES = 3;
    const PENDING_TUITION_FEES = 6;
    const PENDING_COMMISSION_CLAIM = 7;
    const APPLICATION_STEP0 = 0;
    const APPLICATION_STEP0_MESSAGE = 'Application Draft';
    const APPLICATION_STEP1 = 1;
    const APPLICATION_STEP1_MESSAGE = 'Pending Internal Review';
    const APPLICATION_STEP2 = 2;
    const APPLICATION_STEP2_MESSAGE = 'Pending Application Fee';
    const APPLICATION_STEP3 = 3;
    const APPLICATION_STEP3_MESSAGE = 'Pending Institution Review';
    const APPLICATION_STEP4 = 4;
    const APPLICATION_STEP4_MESSAGE = 'Pending Form Signature/Stamp';
    const APPLICATION_STEP5 = 5;
    const APPLICATION_STEP5_MESSAGE = 'Pending Tuition Fees';
    const APPLICATION_STEP6 = 6;
    const APPLICATION_STEP6_MESSAGE = 'Pending Commission Claim';
    const APPLICATION_STEP7 = 7;
    const APPLICATION_STEP7_MESSAGE = 'Pending Application Fee Refund';
    const APPLICATION_STEP8 = 8;
    const APPLICATION_STEP8_MESSAGE = 'Application';
    const APPLICATION_STEP9 = 9;
    const APPLICATION_STEP9_MESSAGE = 'Application';
    const APPLICATION_STEP10 = 10;
    const APPLICATION_STEP10_MESSAGE = 'Application';
    const APPLICATION_STEP11 = 11;
    const APPLICATION_STEP11_MESSAGE = 'Application';
    const APPLICATION_STEP12 = 12;
    const APPLICATION_OVER_MESSAGE = 'Application Over';
    //


    const MISSED_DEADLINE = "Missed Application Deadline";
    const NOT_APPROVAL = 0;
    const APPROVAL = 1;
    const VISIBILITY = 1;
    const HIDE_VISIBILITY = 0;
    const COMMISSION_MISSED = 0;
    const COMMISSION_CLAIM = 1;
    const ELIGIBILITY = 1;
    const ERROR_IN_APPLICATION = 0;
    const DID_NOT_MEET_ACADEMIC_REQUIREMENTS = 1;
    const DID_NOT_MEET_ENGLISH_LANGUAGE_REQUIREMENTS = 2;
    const NOT_MEET_WORKING_EXPERIENCE = 3;
    const PROVIDED_FALSIFIED_DOCUMENT = 4;
    const INTERNAL_REVIEW_ELIGIBLE = 5;
    const SEND_APP_FEE_STATUS = 1;
    const INSTITUTION_ELIGIBILITY = 1;
    const APPLICATION_TERMINATED_MESSAGE = 'Application Terminated';
    //


    const ERROE_IN_APPLICATION_TEXT = "Error in Application";
    const DID_NOT_MEET_ACADEMIC_REQUIREMENTS_TEXT = "Did not meet Academic Requirements";
    const DID_NOT_MEET_ENGLISH_LANGUAGE_REQUIREMENTS_TEXT = "Did not meet English Language Requirements";
    const NOT_MEET_WORKING_EXPERIENCE_TEXT = "Default uncheckedDid not meet Working Experience Requirements";
    const PROVIDED_FALSIFIED_DOCUMENT_TEXT = "Provided Falsified Documents";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Form_id', 'Student_id', 'Intake_id', 'User_reviewedby', 'dateCreated', 'dateSubmission', 'dateReviewed', 'dateCommissionClaimed', 'Dateinstitutionreview', 'visaEmail', 'visaPassword', 'visibility', 'commissionStatus', 'visaStatus', 'refundStatus', 'step', 'notes', 'appFeeStatus', 'InternalReviewerEligibility', 'InstitutionEligibility', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getStudent() {
        return $this->hasOne('App\Model\Student', 'id', 'Student_id');
    }

    public function getIntakeData() {
        return $this->hasOne('App\Model\Intake', 'id', 'Intake_id');
    }

    public function getUserData() {
        return $this->hasOne('App\Model\User', 'id', 'User_reviewedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getScholardata() {
        return $this->hasOne('App\Model\Scholarship', 'ScholarshipProvider_id', 'ScholarshipProvider_id');
    }

    public function getIntakescholarshipintake() {
        return $this->hasMany('App\Model\IntakeScholarshipIntake', 'Intake_id', 'Intake_id');
    }

    public function getApplicationIntakeFileDraft() {
        return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::SUPPORTING_FILE]);
    }

    public function getApplicationIntakeOtherForms() {
        return $this->hasMany('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::APPLICATION_OTHER_FORM]);
    }

    public function getApplicationIntakeFiles() {
        return $this->hasMany('App\Model\ApplicationIntakeFile', 'Application_id', 'id');
    }

    public function getApplicationIntakeCommissionClaimed() {
        return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::COMMISSION_CLAIMED_PAYMENT_SLIP]);
    }

    public function getApplicationIntakeProffOfPayment() {
        return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::PROFF_OF_PAYMENT_TRANSFER]);
    }

    // public function getApplicationIntakeSurpiseFile() {
    //     return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_SURPRISE]);
    // }
    // public function getApplicationIntakeOfferLetter() {
    //     return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_OFFER_LETTER]);
    // }

    public function getApplicationIntakeTuitionFee() {
        return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::TUITION_FEE_PAYMENT_SLIP]);
    }

    public function getApplicationIntakeFileFeeSlip() {
        return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::APPLICATION_FEE_PAYMENT_SLIP]);
    }

    public function getApplicationFormFiles() {
        return $this->hasMany('App\Model\ApplicationFormFiles', 'Application_id', 'id');
    }

    public function getFormFilesComman() {
       return $this->hasMany('App\Model\FormFiles', 'Intake_id', 'Intake_id')->where(['ApplicationIntake_id'=> null]);
    }

    public function getFormFiles() {
       return $this->hasMany('App\Model\FormFiles', 'ApplicationIntake_id', 'id');
    }


    public function getApplicationAdminOfferLetter() {
        return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_OFFER_LETTER]);
    }

    public function getApplicationConfirmationOfEnrolment() {
        return $this->hasOne('App\Model\ApplicationIntakeFile', 'Application_id', 'id')->where(['type' => ApplicationIntakeFile::CONFIRMATION_OF_ENROLLMENT]);
    }

}

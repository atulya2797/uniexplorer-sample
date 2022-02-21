<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ApplicationIntakeFile extends Model {

    const SUPPORTING_FILE = 0;
    const APPLICATION_FEE_PAYMENT_SLIP = 1;
    const SUPPORTIVE_DOCUMENTS_FINANCIAL = 2;
    const SUPPORTIVE_DOCUMENTS_SURPRISE = 3;
    const SUPPORTIVE_DOCUMENTS_OFFER_LETTER = 4;
    const APPLICATION_OTHER_FORM = 5;
    const TUITION_FEE_PAYMENT_SLIP = 6;
    // const CONDITIONAL_OFFER_LETTER = 7;
    const CONFIRMATION_OF_ENROLLMENT = 8;
    const COMMISSION_CLAIMED_PAYMENT_SLIP = 9;
    const PROFF_OF_PAYMENT_TRANSFER = 10;

    /**
     * 
     * 
     */
    const FILE_FOLDER = 'studentApplication';

    protected $table = "applicationintakefiles";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Application_id', 'File_id', 'type', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getFile() {
        return $this->hasOne('App\Model\File', 'id', 'File_id');
    }

    public static function getFormType() {
        $formType = [
            self::getConstantData(ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_FINANCIAL) => ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_FINANCIAL,
            self::getConstantData(ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_SURPRISE) => ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_SURPRISE,
            self::getConstantData(ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_OFFER_LETTER) => ApplicationIntakeFile::SUPPORTIVE_DOCUMENTS_OFFER_LETTER,
            self::getConstantData(ApplicationIntakeFile::APPLICATION_OTHER_FORM) => ApplicationIntakeFile::APPLICATION_OTHER_FORM,
            self::getConstantData(ApplicationIntakeFile::CONFIRMATION_OF_ENROLLMENT) => ApplicationIntakeFile::CONFIRMATION_OF_ENROLLMENT,
        ];

        return $formType;

        // return $this->hasOne('App\Model\File', 'id', 'File_id');
    }

    public static function getConstantData($constandVal) {
        $array = [
            self::APPLICATION_FEE_PAYMENT_SLIP => 'Application Fee Payment Slip',
            self::SUPPORTIVE_DOCUMENTS_FINANCIAL => 'Financial supportive document',
            self::SUPPORTIVE_DOCUMENTS_SURPRISE => 'Surprise supportive document',
            self::SUPPORTIVE_DOCUMENTS_OFFER_LETTER => 'Offer letter supportive document',
            self::APPLICATION_OTHER_FORM => 'Other Form',
            self::TUITION_FEE_PAYMENT_SLIP => 'Tuition Fee payment slip',
            self::CONFIRMATION_OF_ENROLLMENT => 'Confirmation Of Enrollment',
            self::COMMISSION_CLAIMED_PAYMENT_SLIP => 'Commission Claimed Payment Slip',
            self::PROFF_OF_PAYMENT_TRANSFER => 'Proff of Payment Transfer'
        ];
        if (isset($array[$constandVal])) {
            return $array[$constandVal];
        }
        return '';
    }

    public function getApplicationIntake() {
        return $this->hasOne('App\Model\ApplicationIntake', 'id', 'Application_id');
    }

}

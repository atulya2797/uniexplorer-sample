<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * studentList
 *
 */
class Student extends Model {

    protected $table = "student";

    const STUDENT_APPLICATION_PATH = 'studentApplication';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'User_id', 'verification', 'dob', 'LevelofStudy_id', 'Subdiscipline_id', 'Country_id', 'CGPA', 'workingExperience', 'Subdiscipline_exp', 'maxTuitionFees', 'PreEntranceExams', 'PreEntranceExamsScore', 'EnglishLanguageTest', 'EnglishLanguageTestScore'
    ];

    public function getUserDetails() {
        return $this->hasOne('App\Model\User', 'id', 'User_id')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getCountry() {
        return $this->hasOne('App\Model\Country', 'id', 'Country_id');
    }

}

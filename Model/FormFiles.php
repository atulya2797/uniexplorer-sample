<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class FormFiles extends Model {

    protected $table = "formfiles";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'File_name', 'Course_id', 'Intake_id', 'File_id', 'type', 'ApplicationIntake_id', 'emailReviewer', 'forAllCourses', 'Condition_id', 'forAge'
    ];

    public function getFile() {
        return $this->hasOne('App\Model\File', 'id', 'File_id');
    }

    public function getFormFilesCountry() {
        return $this->hasMany('App\Model\FormFilesCountry', 'FormFiles_id', 'id');
    }

}

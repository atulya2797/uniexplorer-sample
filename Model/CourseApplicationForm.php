<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class CourseApplicationForm extends Model {

    const APPLICATION_FORM = 0;
    const OTHER_FORM = 1;
    const RESEARCH_PROPOSAL_FORM = 2;

    protected $table = "courseapplicationform";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'File_id', 'File_type', 'Course_id'
    ];

    public function getFile() {
        return $this->hasOne('App\Model\File', 'id', 'File_id');
    }

}

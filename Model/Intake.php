<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Intake extends Model {

    const FULL_TIME = 1;
    const PART_TIME = 0;

    protected $table = "intake";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Course_id', 'Branch_id', 'commencementDate', 'applicationStartDate', 'applicationDeadlineDate', 'TuitionfeeDate', 'tuitionDeadlineDate', 'mode', 'duration', 'commissionStartDate', 'commissionDeadlineDate', 'tuitionFeesEP', 'tuitionFeesPA', 'nonTuitionFee', 'tuitionFeeURL', 'applicationFeeURL', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getCourseData() {
        return $this->hasOne('App\Model\Courses', 'id', 'Course_id');
    }

    public function getCourseDataStudent()
    {
        return $this->hasOne('App\Model\Courses', 'id', 'Course_id')->where(['visibility'=>Courses::PUBLISHED,'approval'=>Courses::APPROVED]);
    }

    public function getIntakeBranch() {
        return $this->hasOne('App\Model\Branch', 'id', 'Branch_id');
    }

    public function getCityName() {
        return $this->hasOne('App\Model\City', 'id', 'City_id');
    }

    public function getIntakeScholarshipIntake() {
        return $this->hasMany('App\Model\IntakeScholarshipIntake', 'intake_id', 'id');
    }

    public function getCourseSubdiscipline() {
        return $this->hasMany('App\Model\CourseSubdiscipline', 'course_id', 'Course_id');
    }

    public function getApplicationIntake() {
        return $this->hasMany('App\Model\ApplicationIntake', 'intake_id', 'id');
    }

}

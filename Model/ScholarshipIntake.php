<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ScholarshipIntake extends Model {

    protected $table = "scholarshipintake";
    protected $fillable = [
        'id', 'Scholarship_id', 'maxNumberRecipients', 'applicationStartDate', 'applicationDeadline', 'created_at', 'updated_at'
    ];

    public function getScholarship() {
        return $this->hasOne('App\Model\Scholarship', 'id', 'Scholarship_id');
    }

    public function getScholarshipIntakeId() {
        return $this->hasMany('App\Model\IntakeScholarshipIntake', 'ScholarshipIntake_id', 'id');
    }

}

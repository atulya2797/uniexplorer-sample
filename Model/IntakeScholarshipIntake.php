<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class IntakeScholarshipIntake extends Model {

    protected $table = "intakescholarshipintake";
    protected $fillable = [
        'id', 'ScholarshipIntake_id', 'Intake_id', 'created_at', 'updated_at'
    ];

    public function getScholarshipIntake() {
        return $this->hasOne('App\Model\ScholarshipIntake', 'id', 'ScholarshipIntake_id');
    }

    public function getIntakeId() {
        return $this->hasOne('App\Model\Intake', 'id', 'Intake_id');
    }

}

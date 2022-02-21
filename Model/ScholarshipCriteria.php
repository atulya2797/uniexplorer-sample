<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * scholarshipcriteriaList
 *
 */
class ScholarshipCriteria extends Model {

    protected $table = "scholarshipcriteria";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Scholarship_id', 'Criteria_id'
    ];

    public function getCriteria() {
        return $this->hasOne('App\Model\Criteria', 'id', 'Criteria_id');
    }

}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Condition extends Model {

    const CGPA_EQUAL_TO_OR_GREATER_THAN = 8;
    const CGPA_LESS_THEN = 9;
    const WORKING_EXPERIENCE = 6;
    const SHARE_ADMIN_STATUS = 1;
    const TRANSFER_ADMIN_STATUS = 2;

    protected $table = "condition";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name'
    ];

    public function getConditionRange() {
        return $this->hasOne('App\Model\ConditionRange', 'Condition_id','id');
    }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class ConditionRange extends Model {

    protected $table = "conditionrange";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'range_id', 'Condition_id', 'value'
    ];

    public function getCondition() {
        return $this->hasMany('App\Model\Condition', 'id', 'Condition_id');
    }

    public function getRange() {
        return $this->hasMany('App\Model\Range', 'id', 'range_id');
    }

}

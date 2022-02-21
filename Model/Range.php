<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Range extends Model {

    protected $table = "range";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'pathway_id'
    ];

    public function getConditionRange() {
        return $this->hasMany('App\Model\ConditionRange', 'range_id', 'id');
    }

    public function getPathway() {
        return $this->hasMany('App\Model\Pathway', 'id', 'pathway_id');
    }

}

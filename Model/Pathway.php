<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Pathway extends Model {

    protected $table = "pathway";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Requirement_id'
    ];

    public function getRange() {
        return $this->hasMany('App\Model\Range', 'pathway_id', 'id');
    }

    public function getRequirement() {
        return $this->hasMany('App\Model\Requirement', 'id', 'Requirement_id');
    }

}

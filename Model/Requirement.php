<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Requirement extends Model {

    protected $table = "requirement";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Course_id', 'levelofStudy_id', 'TypeofValue_id'
    ];

    public function getPathway() {
        return $this->hasOne('App\Model\Pathway', 'Requirement_id', 'id');
    }
    public function getReqSubdiscipline() {
        return $this->hasMany('App\Model\ReqSubdiscipline', 'Requirement_id', 'id');
    }

}

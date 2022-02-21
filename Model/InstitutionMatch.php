<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * institutionmatchList
 *
 */ 
class InstitutionMatch extends Model {

    const ACTIVE = 0;
    const DELETED = 1;

    protected $table = "institutionmatch";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Match_id', 'Institution_id', 'previousInternational', 'currentInternational', 'previousNational', 'currentNational'
    ];

    public function getMatch() {
        return $this->hasOne('App\Model\Match', 'id', 'Match_id');
    }
    
    public function getInstitution(){
        return $this->hasOne('app\Model\Institution', 'id','Institution_id');
    }

}

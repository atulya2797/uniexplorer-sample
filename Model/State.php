<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class State extends Model {

    protected $table = "state";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Country_id', 'name'
    ];

    public function getCity() {
        return $this->hasMany('App\Model\City', 'State_id', 'id');
    }
    
    public function getCountry() {
        return $this->hasOne('App\Model\Country', 'id', 'Country_id');
    }

}

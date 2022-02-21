<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class City extends Model {

    protected $table = "city";
    const LIMIT_LIST = 50;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'State_id', 'name'
    ];

    public function getState() {
        return $this->hasOne('App\Model\State', 'id', 'State_id');
    }

    public function getbranch() {
        return $this->hasOne('App\Model\Branch', 'City_id', 'id');
    }

}

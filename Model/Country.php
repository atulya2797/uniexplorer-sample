<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Country extends Model {

    protected $table = "country";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'code', 'name', 'country_code'
    ];

    public function getState() {
        return $this->hasMany('App\Model\State', 'Country_id', 'id');
    }

}

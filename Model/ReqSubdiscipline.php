<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class ReqSubdiscipline extends Model {

    protected $table = "reqsubdiscipline";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Requirement_id', 'Subdiscipline_id'
    ];

    public function getSubdiscipline() {
        return $this->hasOne('App\Model\Subdiscipline', 'id', 'Subdiscipline_id');
    }

}

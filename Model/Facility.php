<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model {

    protected $table = "facility";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'unique', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getBranchFacilities() {
        return $this->hasMany('App\Model\BranchFacilities', 'Facility_id', 'id');
    }

}

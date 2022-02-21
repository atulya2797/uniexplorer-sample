<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BranchFacilities extends Model {

    protected $table = "branchfacilities";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Branch_id', 'Facility_id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getFacilities() {
        return $this->hasOne('App\Model\Facility', 'id', 'Facility_id');
    }

    public function getBranch() {
        return $this->hasOne('App\Model\Branch', 'id', 'Branch_id');
    }

}

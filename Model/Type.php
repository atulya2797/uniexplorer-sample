<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Type extends Model {

    protected $table = "type";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'type_name', 'unique'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getScholarshipTypeCount() {
        return $this->hasMany('App\Model\ScholarshipTypes', 'Type_id', 'id');
    }

}

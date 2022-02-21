<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * typeList
 *
 */
class ScholarshipType extends Model {

    protected $table = "type";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'type_name', 'unique'
    ];

    public function getScholarshipTypeCount() {
        return $this->hasMany('App\Model\ScholarshipTypes', 'Type_id', 'id');
    }

}

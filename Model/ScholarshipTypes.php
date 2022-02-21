<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * scholarshiptypesList
 *
 */
class ScholarshipTypes extends Model {

    protected $table = "scholarshiptypes";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Scholarship_id', 'Type_id'
    ];

    public function getType() {
        return $this->hasOne('App\Model\Type', 'id', 'Type_id');
    }

}

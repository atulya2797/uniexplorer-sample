<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * criteriaList
 *
 */
class Criteria extends Model {

    protected $table = "criteria";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'criteria_name', 'unique'
    ];

}

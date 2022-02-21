<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DesiredIntake extends Model {

    protected $table = "desiredintake";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Student_id','MonthYear_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

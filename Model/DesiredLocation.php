<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DesiredLocation extends Model {

    protected $table = "desiredlocation";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Student_id','City_id','state_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DesiredLevelOfStudy extends Model {

    protected $table = "desiredlevelofstudy";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Student_id','LevelofStudy_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

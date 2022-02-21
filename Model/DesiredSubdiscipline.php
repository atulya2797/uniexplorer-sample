<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DesiredSubdiscipline extends Model {

    protected $table = "desiredsubdiscipline";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Student_id','Fieldofstudy_id','Subdiscipline_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

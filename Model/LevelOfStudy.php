<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LevelOfStudy extends Model {

    protected $table = "levelofstudy";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getCourses() {
        return $this->hasMany('App\Model\Courses', 'LevelofStudy_id', 'id');
    }

}

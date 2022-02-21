<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ShortlistedCourses extends Model {

    protected $table = "shortlistedcourses";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Student_id','Course_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getCourses() {
        return $this->hasOne('App\Model\Courses', 'id', 'Course_id');
    }

}

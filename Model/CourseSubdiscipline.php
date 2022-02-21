<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CourseSubdiscipline extends Model {

    protected $table = "CourseSubdiscipline";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'course_id', 'subdiscipline_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getSubdiscipline() {
        return $this->hasOne('App\Model\Subdiscipline', 'id', 'subdiscipline_id');
    }

    public function getDesiredSubdiscipline() {
        return $this->hasOne('App\Model\DesiredSubdiscipline', 'Subdiscipline_id', 'subdiscipline_id');
    }

    public function getCourse() {
        return $this->hasOne('App\Model\Courses', 'id', 'course_id');
    }

}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Subdiscipline extends Model {

    protected $table = "subdiscipline";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'FieldofStudy_id', 'name', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getFieldofStudy() {
        return $this->hasOne('App\Model\FieldOfStudy', 'id', 'FieldofStudy_id');
    }

    public function getSubdisplineCourse() {
        return $this->hasMany('App\Model\CourseSubdiscipline', 'subdiscipline_id', 'id');
    }

}

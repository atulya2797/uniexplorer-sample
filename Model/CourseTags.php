<?php

namespace App\Model;

use App\Helper\Common;
use Illuminate\Database\Eloquent\Model;

class CourseTags extends Model {

    protected $table = "CourseTags";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Course_id', 'Tag_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
    public function getTags() {
        return $this->hasOne('App\Model\Tag', 'id', 'Tag_id');
    }
}

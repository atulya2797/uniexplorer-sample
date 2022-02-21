<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FieldOfStudy extends Model {

    protected $table = "fieldofstudy";

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

    public function getSubdisipline() {
        return $this->hasMany('App\Model\Subdiscipline', 'FieldofStudy_id', 'id');
    }
}

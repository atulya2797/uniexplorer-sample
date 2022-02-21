<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model {

    protected $table = "gallery";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Institution_id', 'Image_id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getImage() {
        return $this->hasOne('App\Model\Image', 'id', 'Image_id');
    }

    public function getInstitution() {
        return $this->hasMany('App\Model\Institution', 'id', 'Institution_id');
    }

}

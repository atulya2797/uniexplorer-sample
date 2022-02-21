<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Image extends Model {

    protected $table = "image";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'URL', 'ImageTitle', 'ImageDescription', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getGallery() {
        return $this->hasMany('App\Model\Gallery', 'Image_id', 'id');
    }

}

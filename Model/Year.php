<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Year extends Model {

    protected $table = "year";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

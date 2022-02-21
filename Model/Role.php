<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {

    protected $table = "role";

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

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Month extends Model {

    protected $table = "month";

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

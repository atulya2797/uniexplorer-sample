<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MonthYear extends Model {

    protected $table = "monthyear";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Month_id','Year_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

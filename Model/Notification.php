<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model {

    const NOT_READ = '0';
    const READ = '1';

    protected $table = "notification";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'role', 'is_read', 'message', 'title', 'url', 'user_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

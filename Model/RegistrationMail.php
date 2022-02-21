<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RegistrationMail extends Model {

    const TYPE_INSTITUTION = 0;
    const TYPE_SCHOLARSHIP = 1;

    protected $table = "registration_mail";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'type', 'token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

}

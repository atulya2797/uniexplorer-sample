<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ContactPerson extends Model {

    const INSTITUTION_ENQUIRIES = 1;
    const COMMISSION = 2;
    const APPLICATION_FEE = 3;
    const TUITION_FEE = 4;

    protected $table = "contactperson";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Branch_id', 'name', 'email', 'phone', 'type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

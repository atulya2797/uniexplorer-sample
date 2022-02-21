<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InstitutionUser extends Model {

    protected $table = "institutionusers";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'User_id', 'Institution_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getInstitution() {
        return $this->hasOne('App\Model\Institution', 'id', 'Institution_id');
    }

    public function getUser() {
        return $this->hasOne('App\Model\User', 'id', 'User_id')->where(['is_deleted'=>User::NOT_DELETED]);
    }

}

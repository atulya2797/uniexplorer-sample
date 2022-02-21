<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MustGetApproveUser extends Model {

    protected $table = "mustGetApproveUser";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'assignFromId', 'assignToId'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getAssignTo() {
        return $this->hasOne('App\Model\User', 'id', 'assignToId');
    }

}

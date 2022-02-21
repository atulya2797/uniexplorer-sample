<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserRolePermission extends Model {

    const GRANTED = 1;
    const NOT_GRANTED = 0;

    protected $table = "userrolepermission";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'User_id', 'RolePermission_id', 'granted'
    ];

    public function getRolePermission() {
        return $this->hasOne('App\Model\RolePermission', 'id', 'RolePermission_id');
    }

}

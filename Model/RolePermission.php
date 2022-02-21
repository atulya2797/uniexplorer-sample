<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model {

    const GRANTED_NO = 0;
    const GRANTED_YES = 1;

    protected $table = "rolepermission";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Role_id', 'Permission_id', 'granted'
    ];

    public function getPermission() {
        return $this->hasOne('App\Model\Permission', 'id', 'Permission_id');
    }

}

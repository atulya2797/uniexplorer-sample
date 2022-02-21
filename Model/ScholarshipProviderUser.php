<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ScholarshipProviderUser extends Model {

    protected $table = "scholarshipproviderusers";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'User_id', 'ScholarshipProvider_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getUser() {
        return $this->hasOne('App\Model\User', 'id', 'User_id')->where(['is_deleted' => User::NOT_DELETED]);
    }
    
    public function getScholarshipProvider() {
        return $this->hasOne('App\Model\ScholarshipProvider', 'id', 'ScholarshipProvider_id');
    }

}

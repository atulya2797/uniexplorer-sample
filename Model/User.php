<?php

namespace App\Model;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable {

    const ADMIN = 1;
    const INTERNAL_REVIEWER = 2;
    const INSTITUTION_USER = 3;
    const INSTITUTION_ADMIN_USER = 4;
    const SCHOLARSHIP_PROVIDER_USER = 5;
    const SCHOLARSHIP_PROVIDER_ADMIN_USER = 6;
    const STUDENT = 7;
    const CMSUSER = 8;
    const NOT_DELETED = 0;
    const DELETED = 1;
    //import tables
    const TABLE_INSTITUTION = 1;
    const TABLE_SCHOLARSHIP_PROVIDER = 2;
    const TABLE_SCHOLARSHIP = 3;
    const TABLE_BRANCHES = 4;
    const TABLE_COURSES = 5;

    use Notifiable;

    protected $table = "user";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Role_id', 'firstName', 'lastName', 'email', 'country_code', 'phone', 'password', 'token', 'is_deleted'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    public function getInstitutionUser() {
        return $this->hasOne('App\Model\InstitutionUser', 'User_id', 'id');
    }

    public function getUserRolePermission() {
        return $this->hasMany('App\Model\UserRolePermission', 'User_id', 'id');
    }

    public function getRole() {
        return $this->hasMany('App\Model\Role', 'Role_id', 'id');
    }

    public function getRoleFinal() {
        return $this->hasOne('App\Model\Role', 'id', 'Role_id');
    }

    public function getApplication() {
        return $this->hasMany('App\Model\ApplicationIntake', 'User_reviewedby', 'id');
    }

    public function getScholarshipProviderUser() {
        return $this->hasOne('App\Model\ScholarshipProviderUser', 'User_id', 'id');
    }

    public function getScholarshipProvider() {
        return $this->hasMany('App\Model\ScholarshipProvider', 'User_approvedby', 'id');
    }

    public function getScholarship() {
        return $this->hasMany('App\Model\Scholarship', 'User_approvedby', 'id');
    }

    public function getCountryFlag() {
        return $this->hasOne('App\Model\Country', 'id', 'country_code');
    }

    public function getStudent() {
        return $this->hasOne('App\Model\Student', 'User_id', 'id');
    }

    public function getApprovePublishingPages() {
        return $this->hasMany('App\Model\ApprovePublishingPages', 'user_id', 'id');
    }

    public function getMustGetApproved() {
        return $this->hasMany('App\Model\MustGetApproveUser', 'assignFromId', 'id');
    }

}

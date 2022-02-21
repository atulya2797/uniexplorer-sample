<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ScholarshipProvider extends Model {

    const NOT_APPROVAL = 0;
    const APPROVAL = 1;
    const HIDE_VISIBILITY = 0;
    const PUBLISHED_VISIBILITY = 1;
    const VERIFIED = 1;
    const VERIFIED_DENY = 0;
    const VERIFIED_PENDING = null;
    const LOGO_FOLDER = 'scholarshipLogo';

    protected $table = "scholarshipprovider";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Country_id', 'name', 'URL', 'Logo_id', 'visibility', 'approval', 'dateApproval', 'User_approvedby', 'dateSubmission', 'verified'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getTableColumns() {
        $columns = [
                ['required' => true, 'column' => 'firstName', 'label' => 'First Name'],
                ['required' => true, 'column' => 'lastName', 'label' => 'Last Name'],
                ['required' => true, 'column' => 'email', 'label' => 'User Email'],
                ['required' => true, 'column' => 'name', 'label' => 'Scholarship Provider Name'],
                ['required' => true, 'column' => 'URL', 'label' => 'Scholarship Provider URL'],
                ['required' => true, 'column' => 'phone', 'label' => 'Phone']
        ];
        return $columns;
    }

    public function getVerifiedUser() {
        return $this->hasOne('App\Model\User', 'id', 'User_approvedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getScholarshipProviderUser() {
        return $this->hasOne('App\Model\ScholarshipProviderUser', 'ScholarshipProvider_id', 'id');
    }

    public function getImage() {
        return $this->hasOne('App\Model\Image', 'id', 'Logo_id');
    }

    public function getScholarship() {
        return $this->hasMany('App\Model\Scholarship', 'ScholarshipProvider_id', 'id');
    }

}

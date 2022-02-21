<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model {

    const TYPE_OFFLINE = 0;
    const TYPE_ONLINE = 1;

    protected $table = "branch";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Institution_id', 'name', 'streetAddress', 'streetAddress2', 'City_id', 'zipcode', 'type', 'URLApplicationFee', 'URLTuitionFee'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getTableColumns() {
//        $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
//        $tableColumns = array_flip($columns);
//        unset($tableColumns['id']);
//        unset($tableColumns['slug']);
//        unset($tableColumns['created_at']);
//        unset($tableColumns['updated_at']);
//        return array_flip($tableColumns);
        $columns = [
                ['required' => true, 'column' => 'Institution_id', 'label' => 'Insittution Id',],
                ['required' => true, 'column' => 'name', 'label' => 'Branch Name',],
                ['required' => true, 'column' => 'streetAddress', 'label' => 'Street Address1',],
                ['required' => true, 'column' => 'streetAddress2', 'label' => 'Street Address2',],
                ['required' => true, 'column' => 'zipcode', 'label' => 'Zip Code',],
                ['required' => true, 'column' => 'type', 'label' => 'Type',],
                ['required' => true, 'column' => 'URLApplicationFee', 'label' => 'URL Application Fee',],
                ['required' => true, 'column' => 'URLTuitionFee', 'label' => 'URL Tuition Fee',],
                ['required' => true, 'column' => 'ContactPerson_name_payment', 'label' => 'Name of Staﬀ Responsible for Application Fee Payment',],
                ['required' => true, 'column' => 'ContactPerson_email_payment', 'label' => 'Email of Person Responsible for Application Fee Payment',],
                ['required' => true, 'column' => 'ContactPerson_phone_payment', 'label' => 'Phone Number of Person Responsible for Application Payment',],
                ['required' => true, 'column' => 'ContactPerson_name', 'label' => 'Name of Staﬀ Responsible for Commissions',],
                ['required' => true, 'column' => 'ContactPerson_email', 'label' => 'Email of Staﬀ Responsible for Commissions',],
                ['required' => true, 'column' => 'ContactPerson_phone', 'label' => 'Phone Number of Staﬀ Responsible for Commissions',],
                ['required' => true, 'column' => 'ContactPerson_name_tution', 'label' => 'Name of Staﬀ Responsible for Tuition Fee Payment',],
                ['required' => true, 'column' => 'ContactPerson_email_tution', 'label' => 'Email of Staﬀ Responsible for Tuition Fee Payment',],
                ['required' => true, 'column' => 'ContactPerson_phone_tution', 'label' => 'Phone Number of Staﬀ Responsible for Tuition Fee Payment',],
        ];
        return $columns;
    }

    public function getContactPerson() {
        return $this->hasMany('App\Model\ContactPerson', 'Branch_id', 'id');
    }

    public function getCity() {
        return $this->hasOne('App\Model\City', 'id', 'City_id');
    }

    public function getInstitution() {
        return $this->hasOne('App\Model\Institution', 'id', 'Institution_id');
    }

    public function getBranchFacilities() {
        return $this->hasMany('App\Model\BranchFacilities', 'Branch_id', 'id');
    }

    public function getintake() {
        return $this->hasMany('App\Model\Intake', 'Branch_id', 'id');
    }

}

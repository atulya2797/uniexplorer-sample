<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model {

    protected $table = "permission";

    const CMS_TYPE = 1;
    const NOT_CMS_TYPE = null;
    const TYPE_INTERNALREVIEWER = 2;
    const TYPE_INSTITUTION = 3;
    const TYPE_SCHOOLARSHIP_PROVIERS = 4;
    //Permission to access function internal reviewer
    const MANAGE_INSTITUTION = 1;
    const MANAGE_SCHOOLARSHIP_PROVIERS = 2;
    const MANAGE_CMS = 3;
    const MANAGE_STUDENT_APPLICATIONS = 4;
    const MANAGE_ENQUIRIES = 5;
    const SEND_REGISTER_REQUEST = 6;
    const MUST_GET_APPROVED = 7;
    const CAN_APPROVE_PUBLUSHING = 8;
    const PUBLISH_DIRECTLY = 15; 
    //Permission to access function institution
    const INSTITUTION_CONTACT_INFORMATION = 9;
    const INSTITUTION_MANAGE_PROFILE = 10;
    const INSTITUTION_MANAGE_COURSES = 11;
    const INSTITUTION_MANAGE_ENQUIRIES = 12;
    //Permission to access function institution
    const SCHOOLARSHIP_PROVIERS_MANAGE_SCHOOLARSHIP = 13;
    const SCHOOLARSHIP_PROVIERS_MANAGE_ENQUIRIES = 14;
    //only for import functionality
    const IMPORT_EXCEL_FILE_INTERNAL_REVIEWER = 15;
    const IMPORT_EXCEL_FILE_INSTITUTION = 16;
    const IMPORT_EXCEL_FILE_SCHOLARSHIP_PROVIDER = 17;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'cms_type'
    ];

}

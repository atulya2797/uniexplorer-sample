<?php

namespace App\Model;

use App\Helper\Common;
use Illuminate\Database\Eloquent\Model;

/**
 * scholarshipList
 *
 */
class Scholarship extends Model {

    protected $table = "scholarship";

    const DELETE = 1;
    const ALIVE = 0;
    const HIDDEN = 0;
    const PUBLISHED = 1;
    const DRAFT = null;
    const NOT_APPROVED = 0;
    const APPROVED = 1;
    const PENDING_APPROVAL = null;
    const TUITION_FEES = 0;
    const FIXED_FEES = 1;
    //visibility text
    const VISIBILITY_HIDE_TEXT = 'Hide';
    const VISIBILITY_PUBLISHED_TEXT = 'Published';
    const VISIBILITY_DRAFT_TEXT = 'Draft';
    //approval text
    const NOT_APPROVAL_TEXT = 'Un Approved  ';
    const APPROVAL_TEXT = 'Approved';
    const PENDING_APPROVAL_TEXT = 'Pending Approval';
    const SUBMIT_FOR_APPROVAL = 'SUBMIT APPROVAL';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'slug', 'ScholarshipProvider_id', 'User_editedby', 'country_id', 'name',
        'URL', 'scholarshiptype', 'scholarshipvalue', 'videoURL', 'details',
        'entryRequirements', 'CGPA', 'appFormURL', 'brochureURL', 'visibility',
        'approval', 'dateApproval', 'User_approvedby', 'dateSubmission', 'is_deleted'
    ];

    public function getTableColumns() {
        $columns = [
                ['required' => true, 'column' => 'ScholarshipProvider_id', 'label' => 'Scholarship Provider Id'],
                ['required' => true, 'column' => 'name', 'label' => 'Scholarship Name'],
                ['required' => true, 'column' => 'URL', 'label' => 'Scholarship Url'],
                ['required' => true, 'column' => 'Type_id', 'label' => 'Type of scholarship'],
                ['required' => true, 'column' => 'type_name', 'label' => 'Type of scholarship Text'],
                ['required' => true, 'column' => 'Criteria_id', 'label' => 'Criteria Specific Criteria'],
                ['required' => true, 'column' => 'criteria_name', 'label' => 'Criteria Specific Criteria Text'],
                ['required' => true, 'column' => 'details', 'label' => 'Detail'],
                ['required' => true, 'column' => 'entryRequirements', 'label' => 'Entry Requirements'],
                ['required' => true, 'column' => 'Intake_id', 'label' => 'Intake Id'],
                ['required' => false, 'column' => 'scholarshiptype', 'label' => 'Scholarshipp Fee Type'],
                ['required' => false, 'column' => 'scholarshipvalue', 'label' => 'Scholarshipp Fee Value'],
                ['required' => false, 'column' => 'videoURL', 'label' => 'Video URL'],
                ['required' => false, 'column' => 'CGPA', 'label' => 'Minimum Required CGPA'],
                ['required' => false, 'column' => 'appFormURL', 'label' => 'Application Form URL'],
                ['required' => false, 'column' => 'brochureURL', 'label' => 'Brochure URL'],
                ['required' => false, 'column' => 'applicationStartDate', 'label' => 'Application Start Date'],
                ['required' => false, 'column' => 'applicationDeadline', 'label' => 'Application Deadline Date'],
                ['required' => false, 'column' => 'maxNumberRecipients', 'label' => 'Max Number of Recipients'],
        ];
        return $columns;
    }

    public function getScholarshipEditByUser() {
        return $this->hasOne('App\Model\User', 'id', 'User_editedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getScholarshipApprovedByUser() {
        return $this->hasOne('App\Model\User', 'id', 'User_approvedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getScholarshipTypeId() {
        return $this->hasOne('App\Model\ScholarshipTypes', 'Scholarship_id', 'id');
    }

    public function getScholarshipCriteriaId() {
        return $this->hasOne('App\Model\ScholarshipCriteria', 'Scholarship_id', 'id');
    }

    public function getScholarshipProvider() {
        return $this->hasOne('App\Model\ScholarshipProvider', 'id', 'ScholarshipProvider_id');
    }

    public function getScholarshipIntake() {
        return $this->hasMany('App\Model\ScholarshipIntake', 'Scholarship_id', 'id');
    }

    public static function createScholarshipPage($id) {
        $scholarship = self::find($id);
        if ($scholarship) {
            $input = [
                'page_name' => $scholarship->name,
                'url' => url('/') . '/scholarship/' . $scholarship->slug,
                'title_tag' => $scholarship->name,
                'meta_description' => $scholarship->name,
                'H1' => $scholarship->name,
                'H2' => $scholarship->name,
                'hreflang' => $scholarship->name,
                'canonicaltag' => $scholarship->name,
                'publish_status' => 1,
                'editor_id' => null,
                'publish_by_id' => null,
                'is_deleted' => 0
            ];
            $data = Pages::create($input);
            $input['page_id'] = $data->id;
            Pages::find($data->id)->update($input);
        }
    }

    public static function slug($name) {
        $slug = Common::createSlug($name);
        $checkSlug = self::where(['slug' => $slug])->count();
        if ($checkSlug) {
            $slug = Common::updateSlug($slug);
            return self::slug($slug);
        }
        return $slug;
    }

}

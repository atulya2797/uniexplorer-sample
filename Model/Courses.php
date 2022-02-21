<?php

namespace App\Model;

use App\Helper\Common;
use Illuminate\Database\Eloquent\Model;

class Courses extends Model {

    protected $table = "course";

    const NOT_APPROVED = 0;
    const APPROVED = 1;
    const PENDING_APPROVAL = null;
    const HIDDEN = 0;
    const PUBLISHED = 1;
    const DRAFT = null;
    const APPLICATION_FOLDER = 'courseApplication';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name', 'slug', 'URL', 'LevelofStudy_id', 'Subdiscipline_id', 'tag_id', 'IELTS', 'TOEFL', 'TOEFL_IBT', 'PTE', 'CAE', 'videoURL', 'cricosCode', 'commissionType', 'commissionValue', 'applicationFee', 'interview', 'interviewDetails', 'overview', 'requirements', 'programmeStructure', 'researchProposal', 'researchProposalDetails', 'File_id', 'visibility', 'approval', 'dateApproval', 'User_approvedby', 'User_editedby', 'dateSubmission', 'created_at', 'updated_at'
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
                ['required' => true, 'column' => 'name', 'label' => 'Course Name'],
                ['required' => true, 'column' => 'URL', 'label' => 'Course Url'],
                ['required' => true, 'column' => 'cricosCode', 'label' => 'Cricos Code'],
                ['required' => true, 'column' => 'overview', 'label' => 'Overview'],
                ['required' => true, 'column' => 'requirements', 'label' => 'Requirements'],
                ['required' => true, 'column' => 'LevelofStudy_id', 'label' => 'Level of Study Id(Single Id)'],
                ['required' => true, 'column' => 'Subdiscipline_id', 'label' => 'Subdiscipline Ids(Multiple id With comma separated)'],
                ['required' => false, 'column' => 'videoURL', 'label' => 'Video URL'],
                ['required' => false, 'column' => 'IELTS', 'label' => 'IELTS'],
                ['required' => false, 'column' => 'TOEFL', 'label' => 'TOEFL PBT'],
                ['required' => false, 'column' => 'TOEFL_IBT', 'label' => 'TOEFL IBT'],
                ['required' => false, 'column' => 'PTE', 'label' => 'PTE'],
                ['required' => false, 'column' => 'CAE', 'label' => 'CAE'],
                ['required' => false, 'column' => 'commissionType', 'label' => 'Commission Type'],
                ['required' => false, 'column' => 'commissionValue', 'label' => 'Commission Value'],
                ['required' => false, 'column' => 'applicationFee', 'label' => 'Application Fee'],
                ['required' => false, 'column' => 'programmeStructure', 'label' => 'Programme Structure'],
            //this is for intake data
            ['required' => true, 'column' => 'Branch_id', 'label' => 'Branch Id'],
                ['required' => true, 'column' => 'mode', 'label' => 'Mode'],
                ['required' => false, 'column' => 'commencementDate', 'label' => 'Commencement Date'],
                ['required' => false, 'column' => 'applicationStartDate', 'label' => 'Application Start Date'],
                ['required' => false, 'column' => 'applicationDeadlineDate', 'label' => 'Application Deadline Date'],
                ['required' => false, 'column' => 'TuitionfeeDate', 'label' => 'Tuition Fee Date'],
                ['required' => false, 'column' => 'tuitionDeadlineDate', 'label' => 'Tuition Deadline Date'],
                ['required' => false, 'column' => 'duration', 'label' => 'Duration'],
                ['required' => false, 'column' => 'commissionStartDate', 'label' => 'Commission Start Date'],
                ['required' => false, 'column' => 'commissionDeadlineDate', 'label' => 'Commission Deadline Date'],
                ['required' => false, 'column' => 'tuitionFeesEP', 'label' => 'Tuition Fees EP'],
                ['required' => false, 'column' => 'tuitionFeesPA', 'label' => 'Tuition Fees PA'],
                ['required' => false, 'column' => 'nonTuitionFee', 'label' => 'Non Tuition Fee'],
                ['required' => false, 'column' => 'tuitionFeeURL', 'label' => 'Tuition Fee URL'],
                ['required' => false, 'column' => 'applicationFeeURL', 'label' => 'Application Fee URL'],
//            next intake
            ['required' => true, 'column' => 'Branch_id2', 'label' => 'Branch Id (Intake 2)'],
                ['required' => true, 'column' => 'mode2', 'label' => 'Mode (Intake 2)'],
                ['required' => false, 'column' => 'commencementDate2', 'label' => 'Commencement Date (Intake 2)'],
                ['required' => false, 'column' => 'applicationStartDate2', 'label' => 'Application Start Date (Intake 2)'],
                ['required' => false, 'column' => 'applicationDeadlineDate2', 'label' => 'Application Deadline Date (Intake 2)'],
                ['required' => false, 'column' => 'TuitionfeeDate2', 'label' => 'Tuition Fee Date (Intake 2)'],
                ['required' => false, 'column' => 'tuitionDeadlineDate2', 'label' => 'Tuition Deadline Date (Intake 2)'],
                ['required' => false, 'column' => 'duration2', 'label' => 'Duration (Intake 2)'],
                ['required' => false, 'column' => 'commissionStartDate2', 'label' => 'Commission Start Date (Intake 2)'],
                ['required' => false, 'column' => 'commissionDeadlineDate2', 'label' => 'Commission Deadline Date (Intake 2)'],
                ['required' => false, 'column' => 'tuitionFeesEP2', 'label' => 'Tuition Fees EP (Intake 2)'],
                ['required' => false, 'column' => 'tuitionFeesPA2', 'label' => 'Tuition Fees PA (Intake 2)'],
                ['required' => false, 'column' => 'nonTuitionFee2', 'label' => 'Non Tuition Fee (Intake 2)'],
                ['required' => false, 'column' => 'tuitionFeeURL2', 'label' => 'Tuition Fee URL (Intake 2)'],
                ['required' => false, 'column' => 'applicationFeeURL2', 'label' => 'Application Fee URL (Intake 2)'],
            //            next intake
            ['required' => true, 'column' => 'Branch_id3', 'label' => 'Branch Id (Intake 3)'],
                ['required' => true, 'column' => 'mode3', 'label' => 'Mode (Intake 3)'],
                ['required' => false, 'column' => 'commencementDate3', 'label' => 'Commencement Date (Intake 3)'],
                ['required' => false, 'column' => 'applicationStartDate3', 'label' => 'Application Start Date (Intake 3)'],
                ['required' => false, 'column' => 'applicationDeadlineDate3', 'label' => 'Application Deadline Date (Intake 3)'],
                ['required' => false, 'column' => 'TuitionfeeDate3', 'label' => 'Tuition Fee Date (Intake 3)'],
                ['required' => false, 'column' => 'tuitionDeadlineDate3', 'label' => 'Tuition Deadline Date (Intake 3)'],
                ['required' => false, 'column' => 'duration3', 'label' => 'Duration (Intake 3)'],
                ['required' => false, 'column' => 'commissionStartDate3', 'label' => 'Commission Start Date (Intake 3)'],
                ['required' => false, 'column' => 'commissionDeadlineDate3', 'label' => 'Commission Deadline Date (Intake 3)'],
                ['required' => false, 'column' => 'tuitionFeesEP3', 'label' => 'Tuition Fees EP (Intake 3)'],
                ['required' => false, 'column' => 'tuitionFeesPA3', 'label' => 'Tuition Fees PA (Intake 3)'],
                ['required' => false, 'column' => 'nonTuitionFee3', 'label' => 'Non Tuition Fee (Intake 3)'],
                ['required' => false, 'column' => 'tuitionFeeURL3', 'label' => 'Tuition Fee URL (Intake 3)'],
                ['required' => false, 'column' => 'applicationFeeURL3', 'label' => 'Application Fee URL (Intake 3)'],
        ];
        return $columns;
    }

    public function getApprovedBy() {
        return $this->hasOne('App\Model\User', 'id', 'User_approvedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getEditedBy() {
        return $this->hasOne('App\Model\User', 'id', 'User_editedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getLevelofStudyId() {
        return $this->hasOne('App\Model\LevelOfStudy', 'id', 'LevelofStudy_id');
    }

    public function getCourseSubdiscipline() {
        return $this->hasMany('App\Model\CourseSubdiscipline', 'course_id', 'id');
    }

    public function getIntakes() {
        return $this->hasMany('App\Model\Intake', 'Course_id', 'id');
    }

    public function getRequirement() {
        return $this->hasMany('App\Model\Requirement', 'Course_id', 'id');
    }

    public function getCourseApplicationForm() {
        return $this->hasOne('App\Model\CourseApplicationForm', 'Course_id', 'id')->where(['File_type' => CourseApplicationForm::APPLICATION_FORM]);
    }

    public function getResearchProposalForm() {
        return $this->hasOne('App\Model\CourseApplicationForm', 'Course_id', 'id')->where(['File_type' => CourseApplicationForm::RESEARCH_PROPOSAL_FORM]);
    }

    public function getCourseOtherForm() {
        return $this->hasOne('App\Model\CourseApplicationForm', 'Course_id', 'id')->where(['File_type' => CourseApplicationForm::OTHER_FORM]);
    }

    public function getShortlistedCourses() {
        return $this->hasMany('App\Model\ShortlistedCourses', 'Course_id', 'id');
    }

    public function getDesiredLevelOfStudy() {
        return $this->hasOne('App\Model\DesiredLevelOfStudy', 'LevelofStudy_id', 'LevelofStudy_id');
    }

    public function getFormFile() {
        return $this->hasMany('App\Model\FormFiles', 'Course_id', 'id');
    }

    public static function createCoursePage($id, $institution_slug) {
        $course = self::find($id);
        if ($course) {
            $input = [
                'page_name' => $course->name,
                'url' => url('/') . '/course/' . $institution_slug . '/' . $course->slug,
                'title_tag' => $course->name,
                'meta_description' => $course->overview,
                'H1' => $course->name,
                'H2' => $course->name,
                'hreflang' => $course->name,
                'canonicaltag' => $course->name,
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
    public function getCourseTags() {
        return $this->hasMany('App\Model\CourseTags', 'Course_id', 'id');
    }

    public static function getCourseCompareData() {

        $Institution_listing = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->get();
        $CompareCourseInstitution = Institution::has('getInstitutionAdmin')->where([
                    'visibility' => Institution::VISIBILITY_PUBLISHED,
                    'approval' => Institution::APPROVAL,
                    'verification' => Institution::VERIFICATION_PASS
                ])->first();
        $inst_id = isset($CompareCourseInstitution->id) ? $CompareCourseInstitution->id : '';

        $institute_data = [];

        if (isset($CompareCourseInstitution)) {
            for ($i = 0; $i < 3; $i++) {
                $Institution_data[] = $CompareCourseInstitution->id;

                $compareCourses_Listing = Courses::where([
                        'visibility' => Courses::PUBLISHED,
                        'approval' => Courses::APPROVED
                    ])
                    ->whereHas('getIntakes', function($query) use ($Institution_data, $i) {
                            return $query->whereHas('getIntakeBranch', function($instQuery) use($Institution_data, $i) {
                                    return $instQuery->whereHas('getInstitution', function($instQuery) use($Institution_data, $i) {
                                            return $instQuery->where([
                                                    'id' => $Institution_data[$i],
                                                    'approval' => Institution::APPROVAL,
                                                    'verification' => Institution::VERIFICATION_PASS,
                                                    'visibility' => Institution::VISIBILITY_PUBLISHED
                                           ]);
                                    });
                            });
                    })
                    ->first();

                if (isset($compareCourses_Listing))
                    $course_data[] = $compareCourses_Listing->id;
            }
        }

        $Courses_Listing = Courses::where([
                'visibility' => Courses::PUBLISHED,
                'approval' => Courses::APPROVED
            ])
            ->whereHas('getIntakes', function($query) use ($inst_id) {
                    return $query->whereHas('getIntakeBranch', function($intQuery) use($inst_id) {
                            return $intQuery->whereHas('getInstitution', function($instQuery) use($inst_id) {
                                    return $instQuery->where([
                                            'id' => $inst_id,
                                            'approval' => Institution::APPROVAL,
                                            'verification' => Institution::VERIFICATION_PASS,
                                            'visibility' => Institution::VISIBILITY_PUBLISHED
                                   ]);
                            });
                    });
            })
            ->get();

        return [$Institution_listing, $Courses_Listing];
    }

}

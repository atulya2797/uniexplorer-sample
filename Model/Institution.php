<?php

namespace App\Model;

use App\Helper\Common;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model {

    //type
    const TYPE_UNIVERSITY = 0;
    const TYPE_TRAINING = 1;
    //VERIFICATION
    const VERIFICATION_DENY = 0;
    const VERIFICATION_DENY_TEXT = 'Access Denied';
    const VERIFICATION_PASS = 1;
    const VERIFICATION_PASS_TEXT = 'Verified';
    const VERIFICATION_PENDING = null;
    const VERIFICATION_PENDING_TEXT = 'Pending Verification';
    //visibility
    const VISIBILITY_HIDE = 0;
    const VISIBILITY_HIDE_TEXT = 'Hide';
    const VISIBILITY_PUBLISHED = 1;
    const VISIBILITY_PUBLISHED_TEXT = 'Published';
    const VISIBILITY_DRAFT = null;
    const VISIBILITY_DRAFT_TEXT = 'Draft';
    //approval
    const NOT_APPROVAL = 0;
    const NOT_APPROVAL_TEXT = 'Un Approved  ';
    const APPROVAL = 1;
    const APPROVAL_TEXT = 'Approved';
    const PENDING_APPROVAL = null;
    const PENDING_APPROVAL_TEXT = 'Pending Approval';
    //rankingStatus
    const UNAVAILABLE = 0;
    const AVAILABLE = 1;
    const IMAGE_FOLDER = 'institutionImage';
    const NATIONAL_RANKING = 0;
    const INTER_NATIONAL_RANKING = 1;
    const THE_RANKING = 1;
    const QS_RANKING = 0;

    protected $table = "institution";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'slug', 'domain', 'type', 'cricosCode', 'Image_coverphoto', 'Image_logo',
        'brochureURL', 'overview', 'videoURL', 'visibility', 'approval', 'dateApproval',
        'User_approvedby', 'dateSubmission', 'verification', 'User_verifiedby',
        'User_editedby', 'dateEditing', 'rankingStatus', 'QSCurrentNational',
        'QSPreviousNational', 'QSCurrentInternational', 'QSPreviousInternational',
        'THECurrentNational', 'THEPreviousNational', 'THECurrentInternational',
        'THEPreviousInternational'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getTableColumns() {
        /* $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
          $tableColumns = array_flip($columns);
          unset($tableColumns['id']);
          unset($tableColumns['slug']);
          unset($tableColumns['created_at']);
          unset($tableColumns['updated_at']);
          $columns = array_flip($tableColumns); */


        $columns = [
                ['required' => true, 'column' => 'firstName', 'label' => 'First Name'],
                ['required' => true, 'column' => 'lastName', 'label' => 'Last Name'],
                ['required' => true, 'column' => 'email', 'label' => 'User Email'],
                ['required' => true, 'column' => 'phone', 'label' => 'Phone'],
                ['required' => true, 'column' => 'name', 'label' => 'Institution Name'],
                ['required' => true, 'column' => 'cricosCode', 'label' => 'Institution Cricos Code'],
                ['required' => true, 'column' => 'domain', 'label' => 'Institutoin Domain'],
                ['required' => true, 'column' => 'type', 'label' => 'Institution Type'],
                ['required' => false, 'column' => 'brochureURL', 'label' => 'Institution Brochure Url'],
                ['required' => false, 'column' => 'overview', 'label' => 'Institution Overview'],
                ['required' => false, 'column' => 'videoURL', 'label' => 'Video Url'],
                ['required' => false, 'column' => 'QSCurrentNational', 'label' => 'QSCurrentNational'],
                ['required' => false, 'column' => 'QSPreviousNational', 'label' => 'QSPreviousNational',],
                ['required' => false, 'column' => 'QSCurrentInternational', 'label' => 'QSCurrentInternational',],
                ['required' => false, 'column' => 'QSPreviousInternational', 'label' => 'QSPreviousInternational',],
                ['required' => false, 'column' => 'THECurrentNational', 'label' => 'THECurrentNational',],
                ['required' => false, 'column' => 'THEPreviousNational', 'label' => 'THEPreviousNational',],
                ['required' => false, 'column' => 'THECurrentInternational', 'label' => 'THECurrentInternational',],
                ['required' => false, 'column' => 'THEPreviousInternational', 'label' => 'THEPreviousInternational']
        ];

        return $columns;
    }

    public function getInstitutionUser() {
        return $this->hasMany('App\Model\InstitutionUser', 'Institution_id', 'id');
    }

    public function getInstitutionAdmin() {
        return $this->hasOne('App\Model\InstitutionUser', 'Institution_id', 'id')->whereHas('getUser', function($q) {
                    return $q->where(['Role_id' => User::INSTITUTION_ADMIN_USER])
                                    ->where(['is_deleted' => User::NOT_DELETED]);
                });
    }

    public function getBranch() {
        return $this->hasMany('App\Model\Branch', 'Institution_id', 'id');
    }

    public function getUserVerifiedBy() {
        return $this->hasOne('App\Model\User', 'id', 'User_verifiedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getUserApprovedBy() {
        return $this->hasOne('App\Model\User', 'id', 'User_approvedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getUserEditedBy() {
        return $this->hasOne('App\Model\User', 'id', 'User_editedby')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getGallery() {
        return $this->hasMany('App\Model\Gallery', 'Institution_id', 'id');
    }

    public function getCoverImage() {
        return $this->hasOne('App\Model\Image', 'id', 'Image_coverphoto');
    }

    public function getLogo() {
        return $this->hasOne('App\Model\Image', 'id', 'Image_logo');
    }

    public function getIntakeData() {
        return $this->hasMany('App\Model\Intake', 'Branch_id', 'id');
    }

    public function getInstitutionMatch() {
        return $this->hasMany('App\Model\InstitutionMatch', 'Institution_id', 'id');
    }

    public static function createInstitutionPage($id) {
        $institution = self::find($id);
        if ($institution) {
            $input = [
                'page_name' => $institution->name,
                'url' => url('/') . '/institution/' . $institution->slug,
                'title_tag' => $institution->name,
                'meta_description' => $institution->overview,
                'H1' => $institution->name,
                'H2' => $institution->name,
                'hreflang' => $institution->name,
                'canonicaltag' => $institution->name,
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

    public static function InstitutionRankingData($Institution) {

        $InstitutionQSRanking = InstitutionMatch::wherehas('getMatch', function($query) use ($Institution) {
            return $query->where(['type' => 0]);
        })->where(['Institution_id' => $Institution->id])->get();

        $InstitutionTHERanking = InstitutionMatch::wherehas('getMatch', function($query) use ($Institution) {
                return $query->where(['type' => 1]);
        })->where(['Institution_id' => $Institution->id])->get();

         return [$InstitutionQSRanking, $InstitutionTHERanking];
    }

}

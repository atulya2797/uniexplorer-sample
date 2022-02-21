<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * matchList
 *
 */
class Match extends Model {

    const TYPE_QS = 0;
    const TYPE_THE = 1;
    const MATCH_BY_COURSE = 0;
    const MATCH_BY_SUBDISCIPLINE = 1;
    const VALUELOGIN1_INCLUDE = 0;
    const VALUELOGIN1_EXCLUDE = 1;
    const VALUELOGIN2_INCLUDE = 0;
    const VALUELOGIN2_EXCLUDE = 1;
    const LOGIC_AND = 0;
    const LOGIC_OR = 1;
    const ACTIVE = 0;
    const DELETED = 1;
    //text
    const MATCH_BY_COURSE_TEXT = 'Course';
    const MATCH_BY_SUBDISCIPLINE_TEXT = 'Subdiscipline';
    const VALUELOGIN1_INCLUDE_TEXT = 'Include';
    const VALUELOGIN1_EXCLUDE_TEXT = 'Exclude';
    const VALUELOGIN2_INCLUDE_TEXT = 'Include';
    const VALUELOGIN2_EXCLUDE_TEXT = 'Exclude';
    const LOGIC_AND_TEXT = 'and';
    const LOGIC_OR_TEXT = 'or';

    protected $table = "match";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'type', 'matchby', 'User_id', 'dateEditing', ' is_deleted'
    ];

    public function getAddedByUser() {
        return $this->hasOne('App\Model\User', 'id', 'User_id')->where(['is_deleted' => User::NOT_DELETED]);
    }

    public function getInstitutionMatch() {
        return $this->hasMany('App\Model\InstitutionMatch', 'Match_id', 'id')->where('is_deleted', Match::ACTIVE);
    }

    public function getMatchLogic() {
        return $this->hasMany('App\Model\MatchLogics', 'match_id', 'id');
    }

    // public function getInstitutionMatch() {
    //     return $this->hasMany('App\Model\InstitutionMatch', 'Match_id', 'id');
    // }

}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * matchList
 *
 */
class MatchLogics extends Model {

    const VALUELOGIN_INCLUDE = 0;
    const VALUELOGIN_EXCLUDE = 1;
    const LOGIC_AND = 0;
    const LOGIC_OR = 1;

    protected $table = "matchLogics";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'match_id', 'valueLogic', 'value', 'logic'
    ];

}

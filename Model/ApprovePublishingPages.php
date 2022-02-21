<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ApprovePublishingPages extends Model {

    const PUBLISH_DIRECTLY = 1;
    const CAN_APPROVE_PUBLISHING = 0;

    protected $table = "approvePublishingPages";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'page_id', 'user_id', 'publish_directly'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getPage() {
        return $this->hasOne('App\Model\Pages', 'id', 'page_id');
    }

}

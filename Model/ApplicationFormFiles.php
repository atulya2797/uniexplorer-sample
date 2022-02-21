<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ApplicationFormFiles extends Model {

    /**
     * 
     * 
     */
    protected $table = "applicationformfiles";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'Application_id', 'FormFiles_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getFormFiles() {
        return $this->hasOne('App\Model\FormFiles', 'id', 'FormFiles_id');
    }

}

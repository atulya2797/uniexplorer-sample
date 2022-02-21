<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PageSection extends Model {

    protected $table = "page_section";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'page_id', 'name', 'content','section_type', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}

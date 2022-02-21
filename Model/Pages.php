<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Pages extends Model {

    protected $table = "pages";

    const CREATED = 1;
    const PUBLISH_PENDING = 2;
    const EDITED = 3;
    const PUBLISHED = 4;
    const DISAVOWED = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'page_id', 'page_name', 'keyword', 'css', 'js', 'url', 'title_tag', 'meta_description', 'html', 'H1', 'H2', 'hreflang', 'canonicaltag', 'MetaRobots', 'publish_status', 'editor_id', 'edited_on', 'publish_note', 'publish_by_id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function getPageSection() {
        return $this->hasMany('App\Model\PageSection', 'page_id', 'page_id');
    }

    public function getEditor() {
        return $this->hasOne('App\Model\User', 'id', 'editor_id');
    }

    public function getPublisher() {
        return $this->hasOne('App\Model\User', 'id', 'publish_by_id');
    }

}

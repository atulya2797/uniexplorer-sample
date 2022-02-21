<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class FormFilesCountry extends Model {

    protected $table = "FormFilesCountry";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'FormFiles_id', 'Country_id'
    ];

}

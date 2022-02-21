<?php

namespace App\Http\Controllers;

use Auth;
use App\Model\User;
use App\Http\Controllers\Controller;

class InternalReviewerController extends Controller {

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index() {
        return view('internalReviewer.index');
    }

    public function callAction($method, $parameters) {
        // code that runs before any action
        $userRoleAction = [
            User::INTERNAL_REVIEWER => [
                'index'
            ]
        ];

        if (!isset($userRoleAction[Auth::user()->Role_id]) || !in_array($method, $userRoleAction[Auth::user()->Role_id])) {
            return abort(404);
        }
        return parent::callAction($method, $parameters);
    }

}

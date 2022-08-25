<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function fetch()
    {
        $user = CentralUser::select('id','name')->get();
        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_all_success');
        $this->response["data"] = $user;
        return response()->json($this->response);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Hash;
use Carbon\Carbon;

use App\Models\Tenant;
use App\Models\CentralOrganization;
use App\Models\User;

class ChooseOrganizationController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_url' => 'required|alpha_num|max:32|exists:App\Models\Tenant,id',
        ]);
        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $centralOrganization = CentralOrganization::where(['tenant_id' => $request->input('organization_url'), 'status' => 'active'])->first();
        if($centralOrganization){

            $this->response["status"] = true;
            $this->response["message"] = __('strings.get_one_success');
            $this->response["data"] = [
                'tenant_id' => $centralOrganization->tenant_id,
            ];
        } else {
            $this->response["message"] = __('strings.get_one_failed');
            return response()->json($this->response, 401);
        }
        return response()->json($this->response);
    }
}

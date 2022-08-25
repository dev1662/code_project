<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;
use Hash;
use Carbon\Carbon;

use App\Models\Tenant;
use App\Models\CentralUser;
use App\Models\User;

use App\Http\Resources\TenantResource;
use App\Http\Resources\OrganizationResource;

class SwitchOrganizationController extends Controller
{
    /**
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     tags={"auth"},
     *     path="/switch",
     *     operationId="postSwitch",
     *     summary="Switch",
     *     description="Switch",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="tenant_id", type="string", example="XXXXX"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Switched successfully"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Something went wrong!")
     *          )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthorized Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized access!")
     *          )
     *     ),
     * )
     */

    public function index(Request $request){ 

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:App\Models\Tenant,id',
        ]);

        if($validator->fails()) {
            $this->response["message"] = $validator->errors()->first();
            return response()->json($this->response, 422);
        }

        // Check is the new Tenants related to this User
        $centralUser = tenancy()->central(function ($tenant) use($user) {
            return CentralUser::where('email', $user->email)->first();
        });
        
        $newTenant = $centralUser->tenants()->with('organization')->find($request->tenant_id);
        if(!$newTenant) {
            $this->response["message"] = 'Invalid switch to the Tenant!';
            return response()->json($this->response, 422);
        }
        
        // tenancy()->initialize($newTenant);
        $newUser = $newTenant->run(function ($tenant) use($user) {
            return User::where("email", $user->email)->first();
        });
        $newUserToken = $newTenant->run(function ($tenant) use($newUser) {
            return $newUser->createToken("Tenant: " . $newUser->name . " (" . $newUser->email . ")")->accessToken;
        });

        $newUser = User::where("email", $user->email)->first();
        if(!$newUser) {
            $this->response["message"] = 'User not exists in the Tenant!';
            return response()->json($this->response, 422);
        }

        $result = array(
            'token' => $newUserToken,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'current_tenant' => new TenantResource($newTenant),
            'all_tenants' => TenantResource::collection($centralUser->tenants()->with('organization')->get()),
        );

        // Revoke Old Tenant Token
        $user->token()->revoke();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_one_success');
        $this->response["data"] = $result;
        return response()->json($this->response);
    }
}

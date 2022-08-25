<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use App\Models\Tenant;
use App\Models\CentralOrganization;
use App\Models\CentralUser;
use App\Models\User;

use App\Http\Resources\TenantResource;
use App\Http\Resources\OrganizationResource;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/login",
     *     operationId="postUserLogin",
     *     summary="Login",
     *     description="Login",
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com"),
     *              @OA\Property(property="password", type="string", example="password"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Loggedin successfully"),
     *              @OA\Property(
     *                  property="data", 
     *                  type="object",
     *                  @OA\Property(property="token", type="string", example="1|uDm2cTp9fiRYTEopir0X9mdy9CEC20JDPsaAGiiV"),
     *                  @OA\Property(property="name", type="string", example="Naveen"),
     *                  @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com"),
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Invalid email or password")
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
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:App\Models\User,email',
            'password' => 'required',
        ]);

        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        // $credentials = $request->only('email', 'password');
        // $credentials = array_merge($credentials, ['status' => 'active']);

        $centralUser = CentralUser::where("email", $request->email)->first();

        $tenant = $centralUser->tenants()->with('organization')->first();
            
        tenancy()->initialize($tenant);

        
        $user = User::where("email", $request->email)->first();
        if($user && Hash::check($request->password, $user->password)) {

            $result = array(
                'token' => $user->createToken("Tenant: " . $user->name . " (" . $user->email . ")")->accessToken,
                'name' => $user->name,
                'email' => $user->email,
                'current_tenant' => new TenantResource($tenant),
                'all_tenants' => TenantResource::collection($centralUser->tenants()->with('organization')->get()),
            );
            // Session::put('key', 'value');
            // session()->save();
            // $request->session()->put('key', 'value');
            // save();

            // $request->session()->put('current_tenant' , new TenantResource($tenant));
            // session('current_tenant' , new TenantResource($tenant));
            $this->response["status"] = true;
            $this->response["message"] = __('strings.login_success');
            $this->response["data"] = $result;
        } else { 
            $this->response["message"] = __('strings.login_failed');
            return response()->json($this->response, 200);
        } 
        return response()->json($this->response);
    }
}

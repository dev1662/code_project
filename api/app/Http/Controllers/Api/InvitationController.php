<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;
use Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

use App\Models\CentralUser;
use App\Models\User;

class InvitationController extends Controller
{
    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/invitation/check",
     *     operationId="postInvitationCheck",
     *     summary="Check Invitation Status",
     *     description="Check Invitation Status",
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string", example="XXXXXXXXXXXXXXXXXXXXXXXXXX"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Checked successfully"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Something went wrong!"),
     *              @OA\Property(property="code", type="string", example="INVALID"),
     *              @OA\Property(
     *                  property="errors", 
     *                  type="object",
     *                      @OA\Property(
     *                  property="token", 
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected token is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */

    public function check(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        
        try {
            $tokenData = json_decode(Crypt::decryptString($request->token));
        } catch (DecryptException $e) {
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response);
        }

        $centralUser = CentralUser::where('email', $tokenData->email)->first();
        if($centralUser){

            $tenant = $centralUser->tenants()->with('organization')->find($tokenData->tenant_id);
            if($tenant) {
                
                $user = $tenant->run(function ($tenant) use($centralUser) {
                    return User::where("email", $centralUser->email)->first();
                });
                if($user && $user->status == User::STATUS_PENDING) {
                    
                    $this->response["status"] = true;
                    $this->response["message"] = __('strings.invitation_check_success');
                    $this->response["data"] = [
                        'new_account' => $centralUser->status == CentralUser::STATUS_PENDING ? true : false
                    ];
                    return response()->json($this->response);
                }
            }
        }

        $this->response["message"] = __('strings.invitation_check_failed');
        return response()->json($this->response);
    }

    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/invitation/accept",
     *     operationId="postInvitationAccept",
     *     summary="Accept Invitation",
     *     description="Accept Invitation",
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string", example="XXXXXXXXXXXXXXXXXXXXXXXXXX"),
     *              @OA\Property(property="password", type="string", example=""),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Invitation accepted"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Something went wrong!"),
     *              @OA\Property(property="code", type="string", example="INVALID"),
     *              @OA\Property(
     *                  property="errors", 
     *                  type="object",
     *                      @OA\Property(
     *                  property="token", 
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected token is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */

    public function accept(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'nullable|string|min:6|max:15',
        ]);

        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        
        try {
            $tokenData = json_decode(Crypt::decryptString($request->token));
        } catch (DecryptException $e) {
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }

        $centralUser = CentralUser::where('email', $tokenData->email)->first();
        if($centralUser){

            if($centralUser->status == CentralUser::STATUS_PENDING && !$request->password){
                $this->response["message"] = __('strings.something_wrong');
                return response()->json($this->response, 401);
            }

            $tenant = $centralUser->tenants()->find($tokenData->tenant_id);
            if($tenant) {
                
                $user = $tenant->run(function ($tenant) use($centralUser) {
                    return User::where("email", $centralUser->email)->first();
                });
                if($user && $user->status == User::STATUS_PENDING) {

                    if($request->password) $centralUser->password = Hash::make($request->password);
                    $centralUser->status = CentralUser::STATUS_ACTIVE;
                    $centralUser->update();

                    $tenant->run(function ($tenant) use($centralUser) {
                        $user = User::where("email", $centralUser->email)->first();
                        $user->status = User::STATUS_ACTIVE;
                        $user->update();
                    });

                    if(!$centralUser->hasVerifiedEmail()) $centralUser->markEmailAsVerified();

                    $this->response["status"] = true;
                    $this->response["message"] = __('strings.invitation_accept_success');
                    return response()->json($this->response);
                }
            }
        }

        $this->response["message"] = __('strings.invitation_accept_failed');
        return response()->json($this->response);
    }

    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/invitation/decline",
     *     operationId="postInvitationDecline",
     *     summary="Decline Invitation",
     *     description="Decline Invitation",
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string", example="XXXXXXXXXXXXXXXXXXXXXXXXXX"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Invitation declined"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Something went wrong!"),
     *              @OA\Property(property="code", type="string", example="INVALID"),
     *              @OA\Property(
     *                  property="errors", 
     *                  type="object",
     *                      @OA\Property(
     *                  property="token", 
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected token is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */

    public function decline(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        
        try {
            $tokenData = json_decode(Crypt::decryptString($request->token));
        } catch (DecryptException $e) {
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }

        $centralUser = CentralUser::where('email', $tokenData->email)->first();
        if($centralUser){

            $tenant = $centralUser->tenants()->find($tokenData->tenant_id);
            if($tenant) {
                
                $user = $tenant->run(function ($tenant) use($centralUser) {
                    return User::where("email", $centralUser->email)->first();
                });
                if($user && $user->status == User::STATUS_PENDING) {

                    $tenant->run(function ($tenant) use($centralUser) {
                        $user = User::where("email", $centralUser->email)->first();
                        $user->status = User::STATUS_DECLINED;
                        $user->update();
                    });

                    $this->response["status"] = true;
                    $this->response["message"] = __('strings.invitation_decline_success');
                    return response()->json($this->response);
                }
            }
        }

        $this->response["message"] = __('strings.invitation_decline_failed');
        return response()->json($this->response);
    }
}

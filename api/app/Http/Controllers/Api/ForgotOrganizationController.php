<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

use App\Models\Tenant;
use App\Models\CentralUser;
use App\Models\User;

use App\Mail\ForgotOrganization as ForgotOrganizationMail;

class ForgotOrganizationController extends Controller
{
    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/forgot-organization",
     *     operationId="postForgotOrganization",
     *     summary="Forgot Organization",
     *     description="Forgot Organization",
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Updated successfully"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Something went wrong, please try again!")
     *          )
     *     ),
     * )
     */

    public function index(Request $request){ 
        
        // return $this->response;
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:App\Models\CentralUser,email',
        ]);
        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $centralUser = CentralUser::where("email", $request->email)->first();
        $tenants = $centralUser->tenants()->with('organization')->get();
        
        Mail::to($centralUser->email)->send(new ForgotOrganizationMail($centralUser, $tenants));
        
        $this->response["status"] = true;
        $this->response["message"] = __('strings.forgot_organization');
        // return "hee";
        return response()->json($this->response);
    }
}

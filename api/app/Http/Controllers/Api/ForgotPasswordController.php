<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use App\Models\Tenant;
use App\Models\CentralUser;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/forgot-password",
     *     operationId="postForgotPassword",
     *     summary="Forgot Password",
     *     description="Forgot Password",
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
     *              @OA\Property(property="message", type="string", example="Reset link  sent successfully"),
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
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:App\Models\CentralUser,email',
        ]);
        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        
        $status = Password::broker('central_users')->sendResetLink(
            $request->only('email')
        );
        // return response()->json($status);
        // $status = Password::sendResetLink(
        //     $request->only('email')
        // );
        
        // return "heelo";
        if ($status === Password::RESET_LINK_SENT) {
            $this->response["status"] = true;
            $this->response["message"] = __('strings.forgot_password');
            return response()->json($this->response);
        }else{

            $this->response["message"] = __('strings.sending_email_failed');
            return response()->json($this->response);
            
        }
        return response()->json($this->response);
    }
}

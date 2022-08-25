<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Mail;

use App\Models\Tenant;
use App\Models\CentralOnboarding;
use App\Models\CentralOrganization;
use App\Models\CentralUser;
use App\Models\User;

use App\Mail\SingUpOTP as SingUpOTPMail;
use App\Notifications\SignupEmailNotification;
use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification as FacadesNotification;
use Illuminate\Support\Facades\DB;


class SignUpController extends Controller
{
    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/signup/send-email",
     *     operationId="postSignupSendEmail",
     *     summary="Signup Send Email",
     *     description="Signup Send Email",
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
     *              @OA\Property(property="message", type="string", example="Otp sent successfully"),
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

    public function sendEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:64',
        ]);

        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        // Random OTP Generation
        $randomOTP = rand(100000, 999999);

        $centralOnboarding = new CentralOnboarding($request->all());
        $centralOnboarding->otp = $randomOTP;
        $centralOnboarding->status = 'pending';
        if($centralOnboarding->save()){


                //  Mail::to($centralOnboarding->email)->send(new SingUpOTPMail($randomOTP));


            $this->response["status"] = true;
            $this->response["message"] = __('strings.otp_sent_success');
            $this->response["data"] = [
                'email' => $centralOnboarding->email,
                'signup_token' => Crypt::encryptString($centralOnboarding->id),
            ];

            // return response()->json($this->response);
            // return $this->response;
            // return $this->response;
        } else {
            $this->response["message"] = __('strings.otp_sending_failed');
            return response()->json($this->response, 401);
        }
        return response()->json($this->response);
    }

    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/signup/verify-email",
     *     operationId="postSignupVerifyEmail",
     *     summary="Signup Verify Email",
     *     description="Signup Verify Email",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="signup_token", type="string", example="XXXXXXXXXXXXXXXXX"),
     *              @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com"),
     *              @OA\Property(property="otp", type="string", example="123456"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Email verified successfully"),
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

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'signup_token' => 'required',
            'email' => 'required|email|max:64|exists:App\Models\CentralOnboarding,email',
            'otp' => 'required|digits:6',
        ]);
        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        try {
            $centralOnboardingID = Crypt::decryptString($request->input('signup_token'));
        } catch (DecryptException $e) {
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }

        $centralOnboarding = CentralOnboarding::where(['id' => $centralOnboardingID, 'email' => $request->input('email'), 'status' => CentralOnboarding::STATUS_PENDING])->whereNull('email_verified_at')->first();
        if(!$centralOnboarding){
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }

        if($centralOnboarding->otp != $request->input('otp')){
            $this->response["message"] = __('strings.invalid_otp');
            return response()->json($this->response, 401);
        }

        $centralOnboarding->email_verified_at = Carbon::now();
        if($centralOnboarding->update()){

            $this->response["status"] = true;
            $this->response["message"] = __('strings.email_verified_success');
            $this->response["data"] = [
                'email' => $centralOnboarding->email,
                'signup_token' => Crypt::encryptString($centralOnboarding->id),
            ];
        } else {
            $this->response["message"] = __('strings.email_verification_failed');
            return response()->json($this->response, 401);
        }
        return response()->json($this->response);
    }

    /**
     * @OA\Put(
     *     tags={"auth"},
     *     path="/signup/organization",
     *     operationId="putSignupOrganization",
     *     summary="Signup Organization",
     *     description="Signup Organization",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="signup_token", type="string", example="XXXXXXXXXXXXXXXXX"),
     *              @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com"),
     *              @OA\Property(property="organization_name", type="string", example="Oas36ty"),
     *              @OA\Property(property="organization_url", type="string", example="oas36ty"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Registered successfully"),
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

    public function organization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'signup_token' => 'required',
            'email' => 'required|email|max:64|exists:App\Models\CentralOnboarding,email',
            'organization_name' => 'required|max:255',
            'organization_url' => 'required|alpha_num|max:32|unique:App\Models\Tenant,id',
        ]);
        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        try {
            $centralOnboardingID = Crypt::decryptString($request->input('signup_token'));
        } catch (DecryptException $e) {
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }

        $centralOnboarding = CentralOnboarding::where(['id' => $centralOnboardingID, 'email' => $request->input('email'), 'status' => CentralOnboarding::STATUS_PENDING])->whereNotNull('email_verified_at')->first();
        if(!$centralOnboarding){
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }
        // $db = DB::connection();
        // $dbs = $db->select('show databases');
        // $original = [
        //     'data' => '',
        // ];
        // foreach($dbs as $d){

        //    $us = $d->Database;
        //     if($us === config('tenancy.database.prefix'). $request->input('organization_url')){
        //         $original['data'] = $us;
        //     }
        // }
        // if(empty($original['data'])){


            $centralOnboarding->organization_name = $request->input('organization_name');
            $centralOnboarding->subdomain = $request->input('organization_url');
            $user= CentralOnboarding::where(['email'=> $request->email])->whereNotNull('organization_name') ;
                if($user->count() > 0){
                    $this->response['passwordNotRequired'] = true;
                    // return response()->json($this->response['passwordNotRequired']);
                }
            if($centralOnboarding->update()){


                $this->response["status"] = true;
                $this->response["message"] = __('strings.register_organization_success');
                $this->response["data"] = [
                    'email' => $centralOnboarding->email,
                    'signup_token' => Crypt::encryptString($centralOnboarding->id),
                ];
            } else {
                $this->response["message"] = __('strings.register_organization_failed');
                return response()->json($this->response, 401);
            }
        // }else{
        //     $this->response["message"] = __('strings.register_organization_failed');
        //     return response()->json($this->response, 401);
        // }
        return response()->json($this->response);
    }

    /**
     * @OA\Post(
     *     tags={"auth"},
     *     path="/signup/complete",
     *     operationId="postSignupComplete",
     *     summary="Signup Complete",
     *     description="Signup Complete",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="signup_token", type="string", example="XXXXXXXXXXXXXXXXX"),
     *              @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com"),
     *              @OA\Property(property="name", type="string", example="Naveen"),
     *              @OA\Property(property="password", type="string", example="password"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Registered successfully"),
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

     public function complete(Request $request)
    {
        // return $request->all();
        if($request->password){

            $validator = Validator::make($request->all(), [
                'signup_token' => 'required',
                'email' => 'required|email|max:64|exists:App\Models\CentralOnboarding,email',
                'name' => 'required|max:32',
                'password' => 'required|string|min:6|max:15',
            ]);
        }else{

            $validator = Validator::make($request->all(), [
                'signup_token' => 'required',
                'email' => 'required|email|max:64|exists:App\Models\CentralOnboarding,email',
                'name' => 'required|max:32',
                // 'password' => 'required|string|min:6|max:15',
            ]);

        }

        if($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        try {
            $centralOnboardingID = Crypt::decryptString($request->input('signup_token'));
        } catch (DecryptException $e) {
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }

        $centralOnboarding = CentralOnboarding::where(['id' => $centralOnboardingID, 'email' => $request->input('email'), 'status' => CentralOnboarding::STATUS_PENDING])->whereNotNull('email_verified_at')->whereNotNull('subdomain')->first();
        if(!$centralOnboarding){
            $this->response["message"] = __('strings.something_wrong');
            return response()->json($this->response, 401);
        }
        // return $centralOnboarding;
        $tenant = Tenant::create(['id' => $centralOnboarding->subdomain]);
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id]
        ]);
        // $tenant->domains()->create(['domain' => 'foo.localhost']);
        // $tenant = $centralOrganization->tenant()->create(['id' => $centralOrganization->subdomain]);

        if($tenant){
            // if($user === null){
                $centralOrganization = new CentralOrganization([
                    'name' => $centralOnboarding->organization_name,
                    'subdomain' => $centralOnboarding->subdomain,
                    'status' => CentralOrganization::STATUS_ACTIVE
                ]);

                if($request->password){
                    if($tenant->organization()->save($centralOrganization)){
                    // return $request->all();

                        $centralUser = CentralUser::firstOrCreate(
                            [
                                'email' => $centralOnboarding->email
                            ],
                            [
                                'name' => $request->input('name'),
                                'password' => Hash::make($request->input('password')),
                                'status' => CentralUser::STATUS_ACTIVE,
                                ]
                            );
                            if(!$centralUser->hasVerifiedEmail()) $centralUser->markEmailAsVerified();

                            $centralUser->tenants()->attach($tenant);

                            $centralOnboarding->status = CentralOnboarding::STATUS_COMPLETED;
                            $centralOnboarding->update();

                            $this->response["status"] = true;
                            $this->response["message"] = __('strings.register_success');

                        } else {
                            $this->response["message"] = __('strings.register_failed');
                            return response()->json($this->response, 401);
                        }
                    }else{

                        if($tenant->organization()->save($centralOrganization)){

                            $centralUser = CentralUser::firstOrCreate(
                                [
                                    'email' => $centralOnboarding->email
                                ],
                                [
                                    'name' => $request->input('name'),
                                    // 'password' => Hash::make($request->input('password')),
                                    'status' => CentralUser::STATUS_ACTIVE,
                                    ]
                                );
                                if(!$centralUser->hasVerifiedEmail()) $centralUser->markEmailAsVerified();

                                $centralUser->tenants()->attach($tenant);

                                $centralOnboarding->status = CentralOnboarding::STATUS_COMPLETED;
                                $centralOnboarding->update();

                                $this->response["status"] = true;
                                $this->response["message"] = __('strings.register_success');

                            } else {
                                $this->response["message"] = __('strings.register_failed');
                                return response()->json($this->response, 401);
                            }
                    }
        } else {
            $this->response["message"] = __('strings.register_failed');
            return response()->json($this->response, 401);
        }
        return response()->json($this->response);
    }
}

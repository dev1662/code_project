<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Mail;

use App\Models\CentralUser;
use App\Models\User;

use App\Mail\JoiningInvitation as JoiningInvitationMail;

class UserController extends Controller
{

    /**
     *
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     tags={"users"},
     *     path="/users",
     *     operationId="getUsers",
     *     summary="Users",
     *     description="Users",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="search", in="query", required=false, description="Search"),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Fetched all records successfully"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example="1"
     *                      ),
     *                      @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Naveen"
     *                      ),
     *                      @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         example="naveen.w3master@gmail.com"
     *                      ),
     *                      @OA\Property(
     *                         property="status",
     *                         type="string",
     *                         example="active"
     *                      ),
     *                  ),
     *              ),
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

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $search = $request->search;

        $users = User::select('id', 'name', 'email', 'status')->where(function($q) use($search){
            if($search) $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
        })->latest()->get();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_all_success');
        $this->response["data"] = $users;
        return response()->json($this->response);
    }

    /**
     *
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     tags={"users"},
     *     path="/users",
     *     operationId="postUsers",
     *     summary="Create User",
     *     description="Create User",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Naveen", description=""),
     *             @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com", description=""),
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Created new record successfully"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(property="id", type="string", example="1"),
     *                  @OA\Property(property="name", type="string", example="Hyderabad Contacts", description=""),
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthorized Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized access!")
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
     *                  property="email",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected email is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:64',
            'email' => 'required|email|max:64|unique:App\Models\User,email',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $centralUser = tenancy()->central(function ($tenant) use($request) {
            $centralUser = CentralUser::firstOrCreate(
                [
                    'email' => $request->email
                ],
                [
                    'name' => $request->name,
                    'status' => CentralUser::STATUS_PENDING,
                ]
            );
            $centralUser->tenants()->attach($tenant);
            return $centralUser;
        });

        $user = User::where('email', $centralUser->email)->first();
        if($request->name != $user->name) $user->display_name = $request->name;
        $user->status = User::STATUS_PENDING;
        $user->update();

        // Joining Invitation Mail from Organization. -> Join / Decline
        tenancy()->central(function ($tenant) use($centralUser) {
            $organization = $tenant->organization()->first();
            $token = [
                'tenant_id' => $organization->tenant_id,
                'email' => $centralUser->email,
            ];
            $url = config('app.url').'/invitation?token='.Crypt::encryptString(json_encode($token));
            Mail::to($centralUser->email)->send(new JoiningInvitationMail($centralUser, $organization, $url));
        });

        $result = User::select('id', 'name', 'email', 'status')->find($user->id);

        $this->response["status"] = true;
        $this->response["message"] = __('strings.store_success');
        $this->response["data"] = $result;
        return response()->json($this->response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     *
     * @OA\Put(
     *     security={{"bearerAuth":{}}},
     *     tags={"users"},
     *     path="/users/{userID}",
     *     operationId="putUsers",
     *     summary="Update User",
     *     description="Update User",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="userID", in="path", required=true, description="User ID"),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="naveen.w3master@gmail.com", description=""),
     *         )
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
     *          response=401,
     *          description="Unauthorized Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized access!")
     *          )
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Forbidden Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Forbidden!")
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
     *                  property="user_id",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected user_id is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make(['user_id' => $id] + $request->all(), [
            'user_id' => 'required|exists:App\Models\User,id',
            'email' => 'required|email|max:64|unique:App\Models\User,email',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $member = User::find($id);
        if($member->status != User::STATUS_PENDING){
            $this->response["message"] = __('strings.update_failed');
            return response()->json($this->response, Response::HTTP_FORBIDDEN);
        }

        $oldCentralUser = tenancy()->central(function ($tenant) use($member) {
            return CentralUser::where(['email' => $member->email])->first();
        });
        $newCentralUser = tenancy()->central(function ($tenant) use($request) {
            return CentralUser::where(['email' => $request->email])->first();
        });
        $oldCentralUserTenantsCount = tenancy()->central(function ($tenant) use($oldCentralUser) {
            return $oldCentralUser->tenants()->count();
        });

        if($oldCentralUserTenantsCount == 1 && !$newCentralUser){
            $member->email = $request->email;
            $member->update();
        } else {
            if(!$newCentralUser){
                $newCentralUser = tenancy()->central(function ($tenant) use($request, $member) {
                    return CentralUser::create([
                        'name' => $member->name,
                        'email' => $request->email,
                        'status' => CentralUser::STATUS_PENDING,
                    ]);
                });
            }

            tenancy()->central(function ($tenant) use($oldCentralUser, $newCentralUser) {
                $tenant->users()->detach($oldCentralUser->global_id);
            });

            $member->global_id = $newCentralUser->global_id;
            $member->email = $request->email;
            $member->update();

            tenancy()->central(function ($tenant) use($oldCentralUser, $newCentralUser) {
                $tenant->users()->syncWithoutDetaching([$newCentralUser->global_id]);
            });

            if($oldCentralUserTenantsCount == 1){
                tenancy()->central(function ($tenant) use($oldCentralUser) {
                    $oldCentralUser->delete();
                });
            }
        }

        $this->response["status"] = true;
        $this->response["message"] = __('strings.update_success');
        return response()->json($this->response);
    }


    /**
     *
     * @OA\Delete(
     *     security={{"bearerAuth":{}}},
     *     tags={"users"},
     *     path="/users/{userID}",
     *     operationId="deleteUsers",
     *     summary="Delete User",
     *     description="Delete User",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="userID", in="path", required=true, description="User ID"),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Record deleted successfully"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthorized Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized access!")
     *          )
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Forbidden Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Forbidden!")
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
     *                  property="user_id",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected user_id is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make(['user_id' => $id], [
            'user_id' => 'required|exists:App\Models\User,id',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $member = User::find($id);
        if($member->status != User::STATUS_PENDING && $member->status != User::STATUS_DECLINED){
            $this->response["message"] = __('strings.destroy_failed');
            return response()->json($this->response, Response::HTTP_FORBIDDEN);
        }

        if ($member->forceDelete()) {
            $this->response["status"] = true;
            $this->response["message"] = __('strings.destroy_success');
            return response()->json($this->response);
        }

        $this->response["message"] = __('strings.destroy_failed');
        return response()->json($this->response);
    }


    /**
     *
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     tags={"users"},
     *     path="/users/{userID}/deactivate",
     *     operationId="deactivateUser",
     *     summary="Deactivate User",
     *     description="Deactivate User",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="userID", in="path", required=true, description="User ID"),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Deactivated successfully"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthorized Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized access!")
     *          )
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Forbidden Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Forbidden!")
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
     *                  property="user_id",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected user_id is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function deactivate(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make(['user_id' => $id], [
            'user_id' => 'required|exists:App\Models\User,id',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $member = User::find($id);
        if($member->status != User::STATUS_ACTIVE){
            $this->response["message"] = __('strings.store_failed');
            return response()->json($this->response, Response::HTTP_FORBIDDEN);
        }

        $member->status = User::STATUS_INACTIVE;
        if ($member->update()) {
            $this->response["status"] = true;
            $this->response["message"] = __('strings.store_success');
            return response()->json($this->response);
        }

        $this->response["message"] = __('strings.store_failed');
        return response()->json($this->response);
    }
}

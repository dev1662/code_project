<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use App\Models\ContactPerson;
use App\Models\ContactPersonEmail;
use App\Models\ContactPersonPhone;
use Illuminate\Support\Facades\Config;
use PDO;

class ContactPersonController extends Controller
{
    public function switchingDB($dbName)
    {
        Config::set("database.connections.mysql", [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $dbName,
            'username' => env('DB_USERNAME','root'),
            'password' => env('DB_PASSWORD',''),
            'unix_socket' => env('DB_SOCKET',''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ]);
    }
    public function getDataForLeads(Request $request)
    {
        $dbname = json_decode($request->header('currrent'))->tenant->organization->name;
        $dbname = config('tenancy.database.prefix').strtolower($dbname);
        // return   $dbname;
        $this->switchingDB($dbname);
        // $result = ContactPerson::select('id','name','type')->get();
        $result = ContactPerson::select('id','name','type')->get();
        // $result = array();

            $this->response["status"] = true;
            $this->response["message"] = __('strings.get_all_success');
            $this->response["data"] = $result;
            return response()->json($this->response);

    }
    /**
     *
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPeople"},
     *     path="/contact-people",
     *     operationId="getContactPeople",
     *     summary="Contact People",
     *     description="Contact People",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Fetched all data successfully"),
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
     *                         example="Contact Person Name"
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
        // $dbname = json_decode($request->header('currrent'))->tenant->organization->name;
        // $dbname = config('tenancy.database.prefix').strtolower($dbname);
        // // return   $dbname;
        // $this->switchingDB($dbname);
        $this->switchingDB('oas36ty_org_devTesting');

        // $result = ContactPerson::select('id','name','type')->get();
        $id = ContactPerson::select('id','name','type')->get();
        // $result = array();
                  foreach($id as $key => $val){

            $email = ContactPersonEmail::where(['contact_person_id' => $val->id])->select('email')->get();
            $phone = ContactPersonPhone::where(['contact_person_id' => $val->id])->select('phone')->get();


            $result[$key]=[
                 "data" => $val,
                'email'=>$email ?? [],
                'phone'=>$phone ?? []
            ];


        }
            $this->response["status"] = true;
            $this->response["message"] = __('strings.get_all_success');
            $this->response["data"] = $result;
            return response()->json($this->response);

    }

    /**
     *
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPeople"},
     *     path="/contact-people",
     *     operationId="postContactPerson",
     *     summary="Create Contact Person",
     *     description="Create Contact Person",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Contact Person", description=""),
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Created successfully"),
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
     *                  property="name",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected name is invalid."
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
        $this->switchingDB('oas36ty_org_devTesting23');

        // $user = $request->user();
        // $request->validation($request,$this->rules);
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:64|unique:App\Models\ContactPerson,name',
            // 'email' => 'required|array|max:64',
            // 'phone' => 'required|digits:10|max:64',

        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        // $data =array();
        // $phoness = array();
        // for($i=0;$i<count($request->email);$i++){
        //     $data[] = [
        //         "email" => $request->email[$i],
        //     ];
        // }
        // for($i=0;$i<count($request->phone);$i++){
        //     $phoness[] = [
        //         "phone" => $request->phone[$i],
        //     ];
        // }

        // return $d = [$data,$phoness];
        // return $request->all();
        $contactPerson = new ContactPerson();
        $contactPerson->name = $request->name;
        $contactPerson->status = ContactPerson::STATUS_ACTIVE;
        $contactPerson->save();
        // $id = DB::getPdo()->lastInsertId();;
        $id = $contactPerson->id;

        if(count($request->email) > 1){

        for($i=0;$i<count($request->email);$i++){
        $contactPersonEmail = new ContactPersonEmail();

            $contactPersonEmail->contact_person_id = $id;
            $contactPersonEmail->email = $request->email[$i];
            $contactPersonEmail->status = ContactPersonEmail::STATUS_ACTIVE;
            // return $contactPersonEmail
            $contactPersonEmail->save();
            }
        }else{
        $contactPersonEmail = new ContactPersonEmail();

            $contactPersonEmail->contact_person_id = $id;
            $contactPersonEmail->email = $request->email;
            $contactPersonEmail->status = ContactPersonEmail::STATUS_ACTIVE;
            $contactPersonEmail->save();
        }
        if(count($request->phone) > 1){
            for($i=0;$i<count($request->phone);$i++){
        $contactPersonPhone = new ContactPersonPhone();

                $contactPersonPhone->contact_person_id = $id;
                $contactPersonPhone->phone = $request->phone[$i];
                $contactPersonPhone->status = ContactPersonPhone::STATUS_ACTIVE;
                $contactPersonPhone->save();
            }
        }else{
        $contactPersonPhone = new ContactPersonPhone();

            $contactPersonPhone->contact_person_id = $id;
                $contactPersonPhone->phone = $request->phone;
                $contactPersonPhone->status = ContactPersonPhone::STATUS_ACTIVE;
                $contactPersonPhone->save();
        }
        $this->response["status"] = true;
        $this->response["message"] = __('strings.store_success');
        return response()->json($this->response);
    }

    /**
     *
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPeople"},
     *     path="/contact-people/{contactPersonID}",
     *     operationId="getContactPerson",
     *     summary="Show Contact Person",
     *     description="Show Contact Person",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Fethced data successfully!"),
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
     *                         example="Contact Person Name"
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
    public function show($contactPersonID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID], [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $contactPerson = ContactPerson::select('id', 'name')->find($contactPersonID);

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_one_success');
        $this->response["data"] = $contactPerson;
        return response()->json($this->response);
    }

    /**
     *
     * @OA\Put(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPeople"},
     *     path="/contact-people/{contactPersonID}",
     *     operationId="putContactPerson",
     *     summary="Update Contact Person",
     *     description="Update Contact Person",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Contact Person name", description=""),
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
     *                  property="contact_person_id",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected contact_person_id is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function update(Request $request, $contactPersonID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID] + $request->all(), [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'name' => 'required|max:64',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $contactPerson = ContactPerson::select('id', 'name')->find($contactPersonID);

        if(!$contactPerson){
            $this->response["message"] = __('strings.update_failed');
            return response()->json($this->response, 422);
        }

        $contactPerson->fill($request->only(['name']));
        $contactPerson->update();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.update_success');
        return response()->json($this->response);
    }

    /**
     *
     * @OA\Delete(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPeople"},
     *     path="/contact-people/{contactPersonID}",
     *     operationId="deleteContactPerson",
     *     summary="Delete Contact Person",
     *     description="Delete Contact Person",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Deleted successfully"),
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
     *                  property="contact_person_id",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected contact_person_id is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function destroy($contactPersonID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID], [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $contactPerson = ContactPerson::select('id', 'name')->find($contactPersonID);

        if(!$contactPerson){
            $this->response["message"] = __('strings.destroy_failed');
            return response()->json($this->response, 422);
        }

        if ($contactPerson->delete()) {
            $this->response["status"] = true;
            $this->response["message"] = __('strings.destroy_success');
            return response()->json($this->response);
        }

        $this->response["message"] = __('strings.destroy_failed');
        return response()->json($this->response, 422);
    }
}

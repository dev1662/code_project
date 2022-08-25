<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use App\Models\ContactPerson;
use App\Models\ContactPersonPhone;
use Illuminate\Support\Facades\Config;
use PDO;

class ContactPersonPhoneController extends Controller
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
    /**
     * 
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonPhones"},
     *     path="/contact-people/{contactPersonID}/phones",
     *     operationId="getContactPersonPhones",
     *     summary="Contact Person Phones",
     *     description="Contact Person Phones",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Fetched data successfully"),
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
     *                         property="phone",
     *                         type="string",
     *                         example="Contact Person Phone"
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
    public function index(Request $request,$contactPersonID)
    {
        $dbname = json_decode($request->header('currrent'))->tenant->organization->name;
        $dbname = config('tenancy.database.prefix').strtolower($dbname);
        // return   $dbname;
        $this->switchingDB($dbname);
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
            $this->response["message"] = __('strings.get_all_failed');
            return response()->json($this->response);
        }

        $result = $contactPerson->phones()->select('id', 'phone')->get();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_all_success');
        $this->response["data"] = $result;
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonPhones"},
     *     path="/contact-people/{contactPersonID}/phones",
     *     operationId="postContactPersonPhone",
     *     summary="Create Contact Person Phone",
     *     description="Create Contact Person Phone",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="phone", type="string", example="Contact Person Phone", description=""),
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
     *                  property="phone", 
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected phone is invalid."
     *                  ),
     *              ),
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function store(Request $request, $contactPersonID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID] + $request->all(), [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'phone' => 'required|digits:10|unique:App\Models\ContactPersonPhone,phone',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        
        $contactPerson = ContactPerson::select('id', 'name')->find($contactPersonID);

        if(!$contactPerson){
            $this->response["message"] = __('strings.store_failed');
            return response()->json($this->response);
        }

        $contactPersonPhone = new ContactPersonPhone($request->all());
        $contactPersonPhone->status = ContactPersonPhone::STATUS_ACTIVE;
        $contactPerson->phones()->save($contactPersonPhone);
        
        $this->response["status"] = true;
        $this->response["message"] = __('strings.store_success');
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonPhones"},
     *     path="/contact-people/{contactPersonID}/phones/{contactPersonPhoneID}",
     *     operationId="getContactPersonPhone",
     *     summary="Show Contact Person Phone",
     *     description="Show Contact Person Phone",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Parameter(name="contactPersonPhoneID", in="path", required=true, description="Contact Person Phone ID"),
     *     @OA\Response(
     *          response=200, 
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Fetched data successfully"),
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
     *                         property="phone",
     *                         type="string",
     *                         example="Contact Person Phone"
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
    public function show($contactPersonID, $contactPersonPhoneID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID, 'contact_person_phone_id' => $contactPersonPhoneID], [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'contact_person_phone_id' => 'required|exists:App\Models\ContactPersonPhone,id',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }
        
        $contactPerson = ContactPerson::select('id', 'name')->find($contactPersonID);

        if(!$contactPerson){
            $this->response["message"] = __('strings.get_one_failed');
            return response()->json($this->response);
        }

        $contactPersonPhone = $contactPerson->phones()->select('id', 'phone')->find($contactPersonPhoneID);

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_one_success');
        $this->response["data"] = $contactPersonPhone;
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Put(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonPhones"},
     *     path="/contact-people/{contactPersonID}/phones/{contactPersonPhoneID}",
     *     operationId="putContactPersonPhone",
     *     summary="Update Contact Person Phone",
     *     description="Update Contact Person Phone",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Parameter(name="contactPersonPhoneID", in="path", required=true, description="Contact Person Phone ID"),
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="phone", type="string", example="Contact Person Phone", description=""),
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
    public function update(Request $request, $contactPersonID, $contactPersonPhoneID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID, 'contact_person_phone_id' => $contactPersonPhoneID] + $request->all(), [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'contact_person_phone_id' => 'required|exists:App\Models\ContactPersonPhone,id',
            'phone' => 'required|digits:10|unique:App\Models\ContactPersonPhone,phone,'.$contactPersonPhoneID.',id',
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
            return response()->json($this->response);
        }

        $contactPersonPhone = $contactPerson->phones()->select('id', 'phone')->find($contactPersonPhoneID);

        if(!$contactPersonPhone){
            $this->response["message"] = __('strings.update_failed');
            return response()->json($this->response, 422);
        }

        $contactPersonPhone->fill($request->only(['phone']));
        $contactPersonPhone->update();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.update_success');
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Delete(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonPhones"},
     *     path="/contact-people/{contactPersonID}/phones/{contactPersonPhoneID}",
     *     operationId="deleteContactPersonPhone",
     *     summary="Delete Contact Person Phone",
     *     description="Delete Contact Person Phone",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Parameter(name="contactPersonPhoneID", in="path", required=true, description="Contact Person Phone ID"),
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
    public function destroy($clientID, $contactPersonID, $contactPersonPhoneID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID, 'contact_person_phone_id' => $contactPersonPhoneID], [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'contact_person_phone_id' => 'required|exists:App\Models\ContactPersonPhone,id',
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
            return response()->json($this->response);
        }

        $contactPersonPhone = $contactPerson->phones()->select('id', 'phone')->find($contactPersonPhoneID);

        if(!$contactPersonPhone){
            $this->response["message"] = __('strings.destroy_failed');
            return response()->json($this->response, 422);
        }
        
        if ($contactPersonPhone->delete()) {
            $this->response["status"] = true;
            $this->response["message"] = __('strings.destroy_success');
            return response()->json($this->response);
        }

        $this->response["message"] = __('strings.destroy_failed');
        return response()->json($this->response, 422);
    }
}

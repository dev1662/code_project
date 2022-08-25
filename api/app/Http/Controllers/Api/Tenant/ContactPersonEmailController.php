<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use App\Models\ContactPerson;
use App\Models\ContactPersonEmail;
use Illuminate\Support\Facades\Config;
use PDO;

class ContactPersonEmailController extends Controller
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
     *     tags={"contactPersonEmails"},
     *     path="/contact-people/{contactPersonID}/emails",
     *     operationId="getContactPersonEmails",
     *     summary="Contact Person Emails",
     *     description="Contact Person Emails",
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
     *                         property="email",
     *                         type="string",
     *                         example="Contact Person Email"
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

        $result = $contactPerson->emails()->select('id', 'email')->get();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_all_success');
        $this->response["data"] = $result;
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonEmails"},
     *     path="/contact-people/{contactPersonID}/emails",
     *     operationId="postContactPersonEmail",
     *     summary="Create Contact Person Email",
     *     description="Create Contact Person Email",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="Contact Person Email", description=""),
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
    public function store(Request $request, $contactPersonID)
    {
        // return $contactPersonID;
        $validator = Validator::make(['contact_person_id' => $contactPersonID] + $request->all(), [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'email' => 'required|email|max:64|unique:App\Models\ContactPersonEmail,email',
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

        $contactPersonEmail = new ContactPersonEmail($request->all());
        $contactPersonEmail->status = ContactPersonEmail::STATUS_ACTIVE;
        $contactPerson->emails()->save($contactPersonEmail);
        
        $this->response["status"] = true;
        $this->response["message"] = __('strings.store_success');
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonEmails"},
     *     path="/contact-people/{contactPersonID}/emails/{contactPersonEmailID}",
     *     operationId="getContactPersonEmail",
     *     summary="Show Contact Person Email",
     *     description="Show Contact Person Email",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Parameter(name="contactPersonEmailID", in="path", required=true, description="Contact Person Email ID"),
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
     *                         property="email",
     *                         type="string",
     *                         example="Contact Person Email"
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
    public function show($contactPersonID, $contactPersonEmailID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID, 'contact_person_email_id' => $contactPersonEmailID], [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'contact_person_email_id' => 'required|exists:App\Models\ContactPersonEmail,id',
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

        $contactPersonEmail = $contactPerson->emails()->select('id', 'email')->find($contactPersonEmailID);

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_one_success');
        $this->response["data"] = $contactPersonEmail;
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Put(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonEmails"},
     *     path="/contact-people/{contactPersonID}/emails/{contactPersonEmailID}",
     *     operationId="putContactPersonEmail",
     *     summary="Update Contact Person Email",
     *     description="Update Contact Person Email",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Parameter(name="contactPersonEmailID", in="path", required=true, description="Contact Person Email ID"),
     *     @OA\RequestBody(
     *          required=true, 
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="Contact Person Email", description=""),
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
    public function update(Request $request, $contactPersonID, $contactPersonEmailID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID, 'contact_person_email_id' => $contactPersonEmailID] + $request->all(), [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'contact_person_email_id' => 'required|exists:App\Models\ContactPersonEmail,id',
            'email' => 'required|email|max:64|unique:App\Models\ContactPersonEmail,email,'.$contactPersonEmailID.',id',
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

        $contactPersonEmail = $contactPerson->emails()->select('id', 'email')->find($contactPersonEmailID);

        if(!$contactPersonEmail){
            $this->response["message"] = __('strings.update_failed');
            return response()->json($this->response, 422);
        }

        $contactPersonEmail->fill($request->only(['email']));
        $contactPersonEmail->update();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.update_success');
        return response()->json($this->response);
    }

    /**
     * 
     * @OA\Delete(
     *     security={{"bearerAuth":{}}},
     *     tags={"contactPersonEmails"},
     *     path="/contact-people/{contactPersonID}/emails/{contactPersonEmailID}",
     *     operationId="deleteContactPersonEmail",
     *     summary="Delete Contact Person Email",
     *     description="Delete Contact Person Email",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="contactPersonID", in="path", required=true, description="Contact Person ID"),
     *     @OA\Parameter(name="contactPersonEmailID", in="path", required=true, description="Contact Person Email ID"),
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
    public function destroy($contactPersonID, $contactPersonEmailID)
    {
        $validator = Validator::make(['contact_person_id' => $contactPersonID, 'contact_person_email_id' => $contactPersonEmailID], [
            'contact_person_id' => 'required|exists:App\Models\ContactPerson,id',
            'contact_person_email_id' => 'required|exists:App\Models\ContactPersonEmail,id',
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

        $contactPersonEmail = $contactPerson->emails()->select('id', 'email')->find($contactPersonEmailID);

        if(!$contactPersonEmail){
            $this->response["message"] = __('strings.destroy_failed');
            return response()->json($this->response, 422);
        }
        
        if ($contactPersonEmail->delete()) {
            $this->response["status"] = true;
            $this->response["message"] = __('strings.destroy_success');
            return response()->json($this->response);
        }

        $this->response["message"] = __('strings.destroy_failed');
        return response()->json($this->response, 422);
    }
}

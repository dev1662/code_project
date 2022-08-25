<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use PDO;
use App\Models\ToDo;


class ToDoController extends Controller
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
     *     tags={"toDos"},
     *     path="/to-dos",
     *     operationId="getToDos",
     *     summary="ToDos",
     *     description="ToDos",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
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
     *                         property="to_do",
     *                         type="string",
     *                         example="A To-Do"
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
        $user = $request->user();

        $toDos = $user->toDos()->select('id', 'task_id', 'to_do', 'status')->with([
            'task' => function($q){
                $q->select('id', 'type', 'subject', 'description');
            },
            'mentionUsers' => function($q){
                $q->select('users.id', 'name', 'email');
            },
        ])->latest()->get();

        $this->response["status"] = true;
        $this->response["message"] = __('strings.get_all_success');
        $this->response["data"] = $toDos;
        return response()->json($this->response);
    }

    /**
     *
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     tags={"toDos"},
     *     path="/to-dos",
     *     operationId="postToDos",
     *     summary="Create ToDo",
     *     description="Create ToDo",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="to_do", type="string", example="A ToDo", description=""),
     *             @OA\Property(property="task_id", type="integer", example="", description=""),
     *             @OA\Property(
     *                  property="user_ids",
     *                  type="array",
     *                  @OA\Items(
     *                         type="integer",
     *                         example="1"
     *                  ),
     *              ),
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful Response",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Created new record successfully"),
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
     *                  property="to_do",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected to_do is invalid."
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
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'to_do' => 'required|max:255',
            'task_id' => 'nullable|exists:App\Models\Task,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'required|exists:App\Models\User,id',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $toDo = new ToDo($request->all());
        $toDo->status = ToDo::STATUS_NOT_DONE;
        $user->toDos()->save($toDo);

        $toDo->mentionUsers()->sync($request->user_ids);

        $this->response["status"] = true;
        $this->response["message"] = __('strings.store_success');
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
     *     tags={"toDos"},
     *     path="/to-dos/{toDoID}",
     *     operationId="putToDo",
     *     summary="Update ToDo",
     *     description="Update ToDo",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="toDoID", in="path", required=true, description="To Do ID"),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="to_do", type="string", example="A ToDo", description=""),
     *             @OA\Property(property="task_id", type="integer", example="", description=""),
     *             @OA\Property(
     *                  property="user_ids",
     *                  type="array",
     *                  @OA\Items(
     *                         type="integer",
     *                         example="1"
     *                  ),
     *              ),
     *             @OA\Property(property="status", type="string", example="done", description=""),
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
     *                  property="to_do_id",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected to_do_id is invalid."
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

        $validator = Validator::make(['to_do_id' => $id] + $request->all(), [
            'to_do_id' => 'required|exists:App\Models\ToDo,id',
            'to_do' => 'required|max:255',
            'task_id' => 'nullable|exists:App\Models\Task,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'required|exists:App\Models\User,id',
            'status' => 'required|in:not-done,done',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $toDo = $user->toDos()->find($id);
        if(!$toDo){
            $this->response["message"] = __('strings.update_failed');
            return response()->json($this->response, 422);
        }

        $toDo->fill($request->only(['to_do', 'task_id', 'status']));
        $toDo->update();

        $toDo->mentionUsers()->sync($request->user_ids);

        $this->response["status"] = true;
        $this->response["message"] = __('strings.update_success');
        return response()->json($this->response);
    }

    /**
     *
     * @OA\Delete(
     *     security={{"bearerAuth":{}}},
     *     tags={"toDos"},
     *     path="/to-dos/{toDoID}",
     *     operationId="deleteToDo",
     *     summary="Delete ToDo",
     *     description="Delete ToDO",
     *     @OA\Parameter(ref="#/components/parameters/tenant--header"),
     *     @OA\Parameter(name="toDoID", in="path", required=true, description="To Do ID"),
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
     *                  property="to_do_id",
     *                  type="array",
     *                  @OA\Items(
     *                         type="string",
     *                         example="The selected to_do_id is invalid."
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

        $validator = Validator::make(['to_do_id' => $id], [
            'to_do_id' => 'required|exists:App\Models\ToDo,id',
        ]);
        if ($validator->fails()) {
            $this->response["code"] = "INVALID";
            $this->response["message"] = $validator->errors()->first();
            $this->response["errors"] = $validator->errors();
            return response()->json($this->response, 422);
        }

        $toDo = $user->toDos()->find($id);
        if(!$toDo){
            $this->response["message"] = __('strings.destroy_failed');
            return response()->json($this->response, 422);
        }

        $toDo->mentions()->delete();

        if ($toDo->delete()) {
            $this->response["status"] = true;
            $this->response["message"] = __('strings.destroy_success');
            return response()->json($this->response);
        }

        $this->response["message"] = __('strings.destroy_failed');
        return response()->json($this->response, 422);
    }
}

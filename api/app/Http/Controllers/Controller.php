<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Server(
 *  url="https://api-office36ty.protracked.in/v1",
 *  description="Office36ty"
 * )
 * @OA\Server(
 *  url="http://192.168.1.10:8000/v1",
 *  description="Localhost"
 * )
 * @OA\Info(
 *    title="API Documentation",
 *    version="1.0.0",
 * )
 * @OA\SecurityScheme(
 *  description="Execute Login or Signup APIs to get the authentication token",
 *  name="Token Based Authentication",
 *  securityScheme="bearerAuth",
 *  type="http",
 *  scheme="bearer"
 * )
 * @OA\Parameter(
 *      parameter="tenant--header",
 *      in="header",
 *      name="X-Tenant",
 *      description="Tenant ID",
 *      @OA\Schema(
 *          type="string",
 *          default="oas36ty",
 *      )
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $response = array("status" => false, "message" => "Something went wrong!");
}

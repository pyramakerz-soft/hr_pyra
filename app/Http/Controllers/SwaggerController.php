<?php

namespace App\Http\Controllers;
/**
 * @OA\OpenApi(
 *    security={{"bearerAuth": {}}}
 * )
 * 
 * @OA\Info(
 *     title="API Documentation",
 *     version="1.0.0"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Enter JWT token prefixed with 'Bearer '"
 * )
 */
class SwaggerController extends Controller
{
    //
}
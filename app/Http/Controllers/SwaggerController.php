<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Diet Club API",
 *     description="API documentation for your project"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization"
 * )
 */
class SwaggerController extends Controller
{
    // این کنترلر لازم نیست متد داشته باشه
}

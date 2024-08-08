<?php

namespace App\Exceptions;

use App\Traits\ResponseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ResponseTrait;
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */

    public function render($request, Throwable $exception)
    {

        if ($exception instanceof ModelNotFoundException) {
            return $this->returnError('Not Found');
        }
        if ($exception instanceof NotFoundHttpException) {
            return $this->returnError('Link Not Found');

        }
        //unauthorized
        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            return $this->returnError('This action is unauthorized', Response::HTTP_FORBIDDEN);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->returnError('Method Not Allowed', Response::HTTP_METHOD_NOT_ALLOWED);

        }
        // Unauthenticated
        if ($exception instanceof AuthenticationException || $exception instanceof RouteNotFoundException) {
            return $this->returnError('You Are unauthenticated', Response::HTTP_UNAUTHORIZED);

        }
        if ($exception instanceof ValidationException) {
            // Get the validation errors
            $errors = $exception->validator->errors();

            // Get the first error message
            $firstErrorMessage = $errors->first();

            // Convert errors to associative array with field names
            $errorArray = $errors->toArray();

            return response()->json([
                'message' => $firstErrorMessage,
                'errors' => $errorArray,
            ], Response::HTTP_BAD_REQUEST);
        }

        return parent::render($request, $exception);
    }

    /**
     * Get details about the duplicate entry error.
     *
     * @param \Illuminate\Database\QueryException $exception
     * @return array
     */

}

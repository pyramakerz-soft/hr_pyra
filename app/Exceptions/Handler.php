<?php

namespace App\Exceptions;

use App\Traits\ResponseTrait;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PDOException;
use Psy\Readline\Hoa\FileException;
use RuntimeException;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Exceptions\UnauthorizedException;
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
            $errors = $exception->validator->errors();

            $firstErrorMessage = $errors->first();

            $errorArray = $errors->toArray();

            return response()->json([
                'message' => $firstErrorMessage,
                'errors' => $errorArray,
            ], Response::HTTP_BAD_REQUEST);
        }
        if ($exception instanceof RoleDoesNotExist) {
            return $this->returnError('There is no role by this name', Response::HTTP_BAD_REQUEST);

        }
        if ($exception instanceof AuthorizationException || $exception instanceof UnauthorizedException) {
            return $this->returnError('You are not authorized', Response::HTTP_FORBIDDEN);

        }
        if ($exception instanceof ServerException) {
            return $this->returnError('Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);

        }
        // Guzzle ClientException (handles 4xx errors)
        if ($exception instanceof ClientException) {
            return $this->returnError('Client Error', Response::HTTP_BAD_REQUEST);
        }

        // Guzzle RequestException (handles request issues like invalid requests)
        if ($exception instanceof RequestException) {
            return $this->returnError('Request Error', Response::HTTP_BAD_REQUEST);
        }

        // Network Issues (handles connection problems)
        if ($exception instanceof ConnectException) {
            return $this->returnError('Network Connection Error', Response::HTTP_BAD_GATEWAY);
        }

        // Database Query Exception
        if ($exception instanceof QueryException) {
            Log::error('Database Query Error: ' . $exception->getMessage());

            return $this->returnError('Database Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // PDO Exception (database connection issues)
        if ($exception instanceof PDOException) {
            return $this->returnError('Database Connection Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Runtime Exceptions (generic runtime errors)
        if ($exception instanceof RuntimeException) {
            return $this->returnError('Runtime Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Filesystem Exception (handles file-related errors)
        if ($exception instanceof FileException) {
            return $this->returnError('File System Error', Response::HTTP_INTERNAL_SERVER_ERROR);
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

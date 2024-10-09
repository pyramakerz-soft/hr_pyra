<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait ResponseTrait
{
    private function cleanDuplicateEntryError($message)
    {
        if (strpos($message, 'Duplicate entry') !== false) {
            $matches = [];
            preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $message, $matches);
            return isset($matches[1], $matches[2]) ? "Duplicate entry '{$matches[1]}' for field '{$matches[2]}'" : $message;
        }
        return $message;
    }
    public function returnSuccessMessage($msg = "")
    {
        return response()->json([
            "result" => "true",
            "message" => $msg,
            "data" => (object) [],
        ], Response::HTTP_OK);
    }
    private function returnSuccess($message, $statusCode)
    {
        return response()->json([
            'result' => 'true',
            'status' => $statusCode,
            'message' => $message,
            'data' => [],
        ], $statusCode);
    }
    public function returnError($msg, $status = Response::HTTP_NOT_FOUND)
    {
        return response()->json([
            'result' => "false",
            'status' => $status,
            'message' => $msg,
            'data' => (object) [],
        ], $status);
    }
    public function returnErrorMessage($msg = "")
    {
        return response()->json([
            'message' => $msg,
        ], Response::HTTP_NOT_FOUND);

    }
    public function returnData($key, $value, $msg = "")
    {
        return response()->json([
            'result' => "true",
            'message' => $msg,
            $key => $value,
        ], Response::HTTP_OK);
    }
    public function returnValidationError($code = 'E0001', $validator)
    {
        return $this->returnError($code, $validator->errors()->first());
    }
    public function getErrorCode($input)
    {
        if ($input == "name") {
            return 'E001';
        } else if ($input == "email") {
            return 'E002';
        } else if ($input == "password") {
            return 'E003';
        } else if ($input == "phone") {
            return 'E004';
        } else if ($input == "contact_phone") {
            return 'E005';
        } else if ($input == "gender") {
            return 'E006';
        }
    }
    public function returnCodeAccordingToInput($validator)
    {
        $inputs = array_keys($validator->errors()->toArray());
        $code = $this->getErrorCode($inputs[0]);
        return $code;
    }

}

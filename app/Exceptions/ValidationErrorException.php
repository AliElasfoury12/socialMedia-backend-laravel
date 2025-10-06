<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ValidationErrorException extends Exception
{
    protected $errors;

    public function __construct($errors, int $status = 422)
    {
        parent::__construct('Validation Error',$status);
        $this->errors = is_array($errors) ? $errors : $errors->toArray();
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $this->errors,
        ], 422);
    }
}
<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationErrorException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    use AuthorizesRequests;

    protected function isValid (Request $request, array $rules): array 
    {
        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if($validator->fails()) 
            throw new ValidationErrorException($validator->errors());
        
        return $data;
    }
}

<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationErrorException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
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

    protected function response (array $response) 
    {
        $request = request();
        $new_token = $request->new_token;

        if($new_token) $response['new_token'] = $new_token;
        
        return response()->json($response);
    }
}

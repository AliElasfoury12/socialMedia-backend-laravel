<?php 

namespace App\JWT_Token;

use Exception;

class JWT_Token {

    public static function CreatToken (object $payload, string $validation_time): string 
    {
        $validation_time = self::timeToSeconds($validation_time);
        $b64Header = self::header_encode();
        $b64Payload = self::payload_encode($payload, $validation_time);

        $secretKey = $_ENV['JWT_SECRET'];
        $b64Signature = self::signature("$b64Header.$b64Payload",$secretKey);

        return "$b64Header.$b64Payload.$b64Signature";
    }

    public static function CheckToken (string $jwt_token, bool $is_updating = false) 
    {        
        $parts = explode('.', $jwt_token);
        if(count($parts) !== 3) 
            throw new Exception('Invalid Token'); 

        self::check_header($parts[0]);

        $secretKey = $_ENV['JWT_SECRET'];
        self::check_signature($parts, $secretKey);

        return self::check_payload($parts[1],$is_updating);
    }

    public static function UpdateToken (string $old_jwt_token, string $validation_time): string  
    {
        $payload = self::CheckToken($old_jwt_token,true);
        return self::CreatToken($payload,$validation_time);
    }

    private static function timeToSeconds (string $time): int 
    {
        if(str_contains($time,'day')){
            $time = str_replace(' day', '', $time);
            return $time * 24 * 60 * 60;
        }

        if(str_contains($time,'hour')){
            $time = str_replace(' hour', '', $time);
            return $time * 60 * 60;
        }

        if(str_contains($time,'min')){
            $time = str_replace(' min', '', $time);
            return $time * 60;
        }

        if(str_contains($time,'mon')){
            $time = str_replace(' mon', '', $time);
            return $time * 30 * 24 * 60 * 60;
        }

        throw new Exception('Invalid Time Format');
    }

    private static function check_header (string $b64Header): void 
    {
        $headerJson = self::base64url_decode($b64Header);
        $header = json_decode($headerJson, true);
        if (!isset($header['algo']) || $header['algo'] !== 'HS256') 
            throw new Exception('Invalid Token'); 
    }

    private static function check_payload (string $b64Payload,bool $is_updating) 
    {
        $payloadJson = self::base64url_decode($b64Payload);
        $payload = json_decode($payloadJson);
        
        if (!$payload?->expires_at) 
            throw new Exception('Invalid Token'); 

        if ($payload?->expires_at && time() >= $payload->expires_at && !$is_updating) 
            throw new Exception('Token Expired');

        return $payload;
    }

    private static function check_signature (array $parts, string $secretKey) 
    {
        [$b64Header, $b64Payload, $b64Signature] = $parts;

        $signature = self::base64url_decode($b64Signature);

        $expectedSig = hash_hmac('sha256', "$b64Header.$b64Payload", $secretKey, true);

        if (!hash_equals($expectedSig, $signature)) 
            throw new Exception('Invalid Token'); 
    }

    private static function header_encode (): string 
    {
        $header = ['algo' => 'HS256', 'type' => 'JWT'];
        return  self::base64url_encode(json_encode($header));
    }

    private static function payload_encode (object $payload, int $validationTime): string 
    {
        $now = time();
        $payload->created_at = $now;
        $payload->expires_at = $now + $validationTime;
        return self::base64url_encode(json_encode($payload));
    }

    private static function signature (string $data, string $secretKey) 
    {
        $signature = hash_hmac('sha256', $data, $secretKey, true);
        return self::base64url_encode($signature);
    }

    private static function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode(string $data): string 
    {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
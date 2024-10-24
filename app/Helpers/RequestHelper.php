<?php
namespace App\Helpers;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class RequestHelper {

    //function to send a get request to an endpoint
    public static function getRequest($url, $token = ''): PromiseInterface|string|Response
    {
        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$token}",
            "cache-control" => "no-cache"
        );
        $response = Http::withHeaders($headers)->get($url);
        if ($response->serverError()) {
            return [
                "status" => false,
                "message" => 'Request failed' 
            ];
        } else {
            return $response;
        }
    }

    //function to send a post request to an endpoint
    public static function postRequest($token,$url,$payload): PromiseInterface|string|Response
    {
        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$token}",
            "cache-control" => "no-cache"
        );
        $response = Http::withHeaders($headers)->post($url, $payload);
        
        if ($response->serverError()) {
            return [
                "status" => false,
                "message" => 'Request failed' 
            ];
        } else {
            return $response;
        }
    }

}

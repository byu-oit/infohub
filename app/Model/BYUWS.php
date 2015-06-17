<?php

App::uses('Model', 'Model');

class BYUWS extends Model {
    public $useTable = false;
    private $code;
    private $info;
    private $error;
    private $requestTries = 0;
    
    private $settings = array(
        // DEV SERVER
        'url'       =>  'https://byu-dev.collibra.com/rest/latest/',
        'username'  => 'Admin',
        'password'  => 'ey6Rourpkwxwe5G'
        // NON-DEV SERVER
        //'url'       =>  'https://byu.collibra.com/rest/latest/',
        //'username'  => '***REMOVED***', 
        //'password'  => '***REMOVED***'
    );
    
    private static function cmp($a, $b){
        return strcmp($a->name, $b->name);
    }

    public function personalSummary($netid){
        //$netid = "***REMOVED***";
        $apiKey = "***REMOVED***";
        $sharedSecret = "***REMOVED***";
        $url = "https://ws.byu.edu:443/rest/v2.0/identity/person/PRO/personSummary.cgi/".$netid;
        $nonceURL = 'https://ws.byu.edu/authentication/services/rest/v1/hmac/nonce/'.$apiKey.'/'.$netid;

        // create a nonce with apikey
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $nonceURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        $response = curl_exec($ch);
        curl_close($ch);
        
        // decode response to JSON
        $nonce = json_decode($response);
        
        //# Use openssl to get an HMAC value and base64 encode it
        // get HMAC value and base64 encode it
        $hmac = base64_encode(hash_hmac('sha512', $nonce->nonceValue, $sharedSecret, true));
        
        //# Call the Service
        $chWS = curl_init();
        curl_setopt($chWS, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chWS, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chWS, CURLOPT_URL, $url);
        curl_setopt($chWS, CURLOPT_HTTPGET, true);
        curl_setopt($chWS, CURLOPT_HTTPHEADER, array(
            'Authorization: Nonce-Encoded-API-Key '.$apiKey.','.$nonce->nonceKey.','.$hmac,
                "Accept: application/json"
        ));
        $response = curl_exec($chWS);
        curl_close($chWS);
        $response = json_decode($response);
        
        //print_r($response->PersonSummaryService->response);
        //exit;
        return $response->PersonSummaryService->response;
    }
}
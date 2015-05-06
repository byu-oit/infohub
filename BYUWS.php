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

    public function request($options=array()){
        $APIKEY="***REMOVED***";
        $SHAREDSECRET="***REMOVED***";
        $URL="https://ws.byu.edu:443/rest/v2.0/identity/person/PRO/personSummary.cgi/$1";

        // create a nonce with apikey
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ws.byu.edu/authentication/services/rest/v1/hmac/nonce/'.$APIKEY);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // decode response to JSON
        $nonce = json_decode($response);
        echo $nonce->nonceKey.'<hr>';
        
        //# Use openssl to get an HMAC value and base64 encode it
        //HMAC="$(echo -n ${NONCEVALUE} | openssl dgst -sha512 -hmac ${SHAREDSECRET} -binary | base64)"
        // get HMAC value and base64 encode it
        $hmac = base64_encode(hash_hmac('sha256', $nonce->nonceValue, $SHAREDSECRET, true));
        echo $hmac.'<hr>';

        //# Call the Service
        //curl -i -H "Authorization: Nonce-Encoded-API-Key ${APIKEY},${NONCEKEY},${HMAC}" $URL
        $chWS = curl_init();
        curl_setopt($chWS, CURLOPT_URL, 'https://ws.byu.edu:443/rest/v2.0/identity/person/PRO/personSummary.cgi/');
        curl_setopt($chWS, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chWS, CURLOPT_HEADER, 0);
        curl_setopt($chWS, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chWS, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($chWS, CURLOPT_TIMEOUT, 30);
        curl_setopt($chWS, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($chWS, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($chWS, CURLOPT_POST, 1);
        curl_setopt($chWS, CURLOPT_HTTPHEADER, array(
            'Authorization: Nonce-Encoded-API-Key '.$APIKEY.','.$nonce->nonceKey.','.$hmac
        ));
        $response = curl_exec($chWS);
        curl_close($chWS);
        
        print_r($response);
        exit;
    }
}
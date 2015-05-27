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
        /*$api_key = "***REMOVED***"; #tsg
        $shared_secret = "***REMOVED***"; #tsg
        $user_netid = "***REMOVED***";
        $url = 'https://ws.byu.edu:443/rest/v2.0/identity/person/PRO/personSummary.cgi/' . $user_netid;

        // 1. Get the NONCE
        $ch = curl_init();
        $get_nonce_url = 'https://ws.byu.edu/authentication/services/rest/v1/hmac/nonce/' . $api_key . "/" . $user_netid;
        curl_setopt($ch, CURLOPT_URL, $get_nonce_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_POST,1);

        $nonce = curl_exec($ch);
        curl_close ($ch);
        $nonce = json_decode($nonce, TRUE);
        $nonceValue = $nonce['nonceValue'];
        $nonceKey = $nonce['nonceKey'];
        $hash = base64_encode(hash_hmac('sha512', $nonceValue, $shared_secret, true));
        
        echo $nonceKey.'*<hr>';
        echo 'hmac: '.$hash.'<br>';
        
        // 2. Make web service call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                "Authorization: Nonce-Encoded-API-Key {$api_key},{$nonceKey},{$hash}",
                "Accept: application/json"
            )
        );
        $response = curl_exec($ch);
        curl_close ($ch);
        
        print_r($response);
         echo '<hr><hr>';
        //exit;*/

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
        //echo $nonce->nonceKey.'*<hr>';
        
        //# Use openssl to get an HMAC value and base64 encode it
        //HMAC="$(echo -n ${NONCEVALUE} | openssl dgst -sha512 -hmac ${SHAREDSECRET} -binary | base64)"
        // get HMAC value and base64 encode it
        $hmac = base64_encode(hash_hmac('sha512', $nonce->nonceValue, $sharedSecret, true));
        //echo 'hmac: '.$hmac.'<br>';

        //# Call the Service
        //curl -i -H "Authorization: Nonce-Encoded-API-Key ${APIKEY},${NONCEKEY},${HMAC}" $URL
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
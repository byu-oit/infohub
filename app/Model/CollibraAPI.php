<?php

App::uses('Model', 'Model');

class CollibraAPI extends Model {
    public $useTable = false;
    private $code;
    private $info;
    private $error;
    private $requestTries = 0;
    
    private $settings = array(
        'url'       =>  'https://byu-dev.collibra.com/rest/latest/',
        'username'  => 'Admin', //***REMOVED***:***REMOVED***
        'password'  => 'ey6Rourpkwxwe5G'
    );
    
    private static function cmp($a, $b){
        return strcmp($a->name, $b->name);
    }

    public function request($options=array()){
        $ch = curl_init();
        $url = $this->settings['url'].$options['url'];
        $params = isset($options['params'])?$options['params']:'';
        
        if(isset($options['post'])){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }else{
            if($params!='') $url .= '?'.$params;
        }
        
        if(isset($options['json'])){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($params))
            );
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->settings['username'].":".$this->settings['password']);
        $response = curl_exec($ch);
        
        $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->info = curl_getinfo($ch);
        $this->error = curl_error($ch);
        
        curl_close($ch);
        
        if($this->code != '200' && $this->code != '201'){
            echo 'cURL ERROR:<br>'.
                'code: '. $this->code.'<br>'.
                'info: '. print_r($this->info).'<br>'.
                'error: '. $this->error.'<br>';
            exit;
        }
        
        // if given a 401 error, retry submitting a max of 3 times
        if($this->code != '200' && $this->code != '201'){//$this->code == '401'){
            echo $this->code;
            $this->requestTries++;
            if($this->requestTries<4){
                sleep(2);
                //$this->request($options);
            }
        }
        /*
        echo $response;
        exit;
        
        $arrResponse = json_decode($response);
        //usort($arrResponse->communityReference, 'self::cmp');
        //print_r($arrResponse->communityReference[0]->name);
        echo "\r\n--------------------------\r\n";
        print_r($arrResponse);
                
        exit;
        */
        return $response;
    }
}
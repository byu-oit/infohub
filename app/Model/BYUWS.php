<?php

App::uses('Model', 'Model');

class BYUWS extends Model {
    private $apiKey = '***REMOVED***';
    private $sharedSecret = "***REMOVED***";
    private $pwsURL = 'https://ws.byu.edu:443/rest/v2.0/identity/person/PRO/personSummary.cgi/';
    private $supervisorURL = 'https://api.byu.edu/rest/v1/apikey/PeopleSoft_HR_REST_Get/Y_REST.v1?sn=EMPLOYEE_SUPERVISOR_LOOKUP&oprid=';
    private $nonceURL = 'https://ws.byu.edu/authentication/services/rest/v1/hmac/nonce/';


    public function personalSummary($netid){
        $nonce = $this->getNonce($netid);
        $nonceValue = $nonce->nonceValue;
        $nonceKey = $nonce->nonceKey;
        $hash = base64_encode(hash_hmac('sha512', $nonceValue, $this->sharedSecret, true));
        
        //# Call the Service
        $chWS = curl_init();
        curl_setopt($chWS, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chWS, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chWS, CURLOPT_URL, $this->pwsURL.$netid);
        curl_setopt($chWS, CURLOPT_HTTPGET, true);
        curl_setopt($chWS, CURLOPT_HTTPHEADER, array(
            "Authorization: Nonce-Encoded-API-Key {$this->apiKey},{$nonceKey},{$hash}",
            "Accept: application/json"
        ));
        $userResponse = curl_exec($chWS);
        curl_close($chWS);
        $userResponse = json_decode($userResponse);

        return $userResponse->PersonSummaryService->response;
    }

    public function supervisorLookup($netid){
        //$netid = "***REMOVED***";
        $nonce = $this->getNonce($netid);
        $nonceValue = $nonce->nonceValue;
        $nonceKey = $nonce->nonceKey;
        $hash = base64_encode(hash_hmac('sha512', $nonceValue, $this->sharedSecret, true));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->supervisorURL.$netid);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                "Authorization: Nonce-Encoded-API-Key {$this->apiKey},{$nonceKey},{$hash}",
                "Accept: application/json"
            )
        );
        $supervisorResponse = curl_exec($ch);
        curl_close ($ch);
        $supervisorResponse = json_decode($supervisorResponse);
        
        // set empty object properties if user is not an employee
        if(!empty($supervisorResponse->MESSAGE)){
            $supervisorResponse->EMPLOYEE->EmployeeName = '';
            $supervisorResponse->EMPLOYEE->EmployeeID = '';
            $supervisorResponse->EMPLOYEE->EmployeeNetID = '';
            $supervisorResponse->EMPLOYEE->JOB = array();
            $supervisorResponse->EMPLOYEE->JOB[0]->JobRecordNumber = '';
            $supervisorResponse->EMPLOYEE->JOB[0]->JobDescription = '';
            $supervisorResponse->EMPLOYEE->JOB[0]->SupervisorName = '';
            $supervisorResponse->EMPLOYEE->JOB[0]->SupervisorJobTitle = '';
            $supervisorResponse->EMPLOYEE->JOB[0]->SupervisorDepartment = '';
            $supervisorResponse->EMPLOYEE->JOB[0]->SupervisorOrganization = '';
            $supervisorResponse->EMPLOYEE->JOB[0]->SupervisorEmail = '';
            $supervisorResponse->EMPLOYEE->JOB[0]->SupervisorPhoneNumber = '';
        }
        if(is_array($supervisorResponse->EMPLOYEE->JOB)){
            $supervisorResponse->EMPLOYEE->JOB = $supervisorResponse->EMPLOYEE->JOB[0];
        }
        return $supervisorResponse->EMPLOYEE;
    }

    private function getNonce($netID){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->nonceURL.$this->apiKey.'/'.$netID);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }
}
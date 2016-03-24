<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');

class BYUWS extends Model {
    private $pwsURL = 'https://ws.byu.edu/rest/v2.0/identity/person/PRO/personSummary.cgi/';
    private $supervisorURL = 'https://api.byu.edu/rest/v1/apikey/PeopleSoft_HR_REST_Get/Y_REST.v1?sn=EMPLOYEE_SUPERVISOR_LOOKUP&oprid=';

    public $useTable = false;
    public $useDbConfig = 'byuApi';


    public function personalSummary($netidRaw){
        $netid = urlencode(trim($netidRaw));
        $config = $this->getDataSource()->config;
        $config['net_id'] = $netidRaw;
        $http = new HttpSocket();
        $http->configAuth('ByuApi', $config);
        $response = $http->get($this->pwsURL . $netid);
        $data = json_decode($response->body());

        return $data->PersonSummaryService->response;
    }

    public function supervisorLookup($netidRaw){
        $netid = urlencode(trim($netidRaw));
        $config = $this->getDataSource()->config;
        $config['net_id'] = $netidRaw;
        $http = new HttpSocket();
        $http->configAuth('ByuApi', $config);

        $response = $http->request(['uri' => $this->supervisorURL . $netid, 'header' => ['Accept' => 'application/json']]);
        $supervisorResponse = json_decode($response->body());
        
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
            $supervisorResponse->EMPLOYEE->JOB->SupervisorPhoneNumber = str_replace('/', '-', $supervisorResponse->EMPLOYEE->JOB->SupervisorPhoneNumber);
        }
        return $supervisorResponse->EMPLOYEE;
    }
}
<?php

App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');

class CollibraAPI extends Model {
    public $useTable = false;
    public $useDbConfig = 'collibra';
    private $code;
    private $info;
    private $error;
    private $requestTries = 0;
    
    private $settings;

    private static function cmp($a, $b){
        return strcmp($a->name, $b->name);
    }

    public function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->settings = $this->getDataSource()->config;
    }

    /**
     *
     * @return HttpSocket
     */
    protected function client() {
        if (empty($this->_client)) {
            $this->_client = new HttpSocket();
            $this->_client->configAuth('Basic', $this->settings['username'], $this->settings['password']);
        }
        return $this->_client;
    }

    public function get($url, $options = []) {
        $response = $this->client()->get($this->settings['url'] . $url);
        return empty($options['raw']) ? $response->body() : $response;
    }

    public function dataTable($config) {
        $response = $this->client()->post(
                $this->settings['url'] . "output/data_table",
                json_encode($config),
                ['header' => [
                    'Content-Type' => "application/json"]]);
        if (!($response && $response->isOk())) {
            return null;
        }
        return @json_decode($response->body());
    }

    protected function buildTableConfig($config) {
        $output = [];
        foreach((array)$config as $resourceName => $resource) {
            $filters = [];
            foreach($resource as $field => $filter) {
                if (is_int($field)) {
                    $field = $filter;
                    $filter = null;
                }
                $fieldName = "{$resourceName}{$field}";
                $output['Columns'][] = ['Column' => ['fieldName' => $fieldName]];
                $output['Resources'][$resourceName][$field]['name'] = $fieldName;
                if ($filter) {
                    $filters[] = ['Field' => ['name' => $fieldName, 'operator' => 'EQUALS', 'value' => $filter]];
                }
            }
            if (!empty($filters)) {
                $output['Resources'][$resourceName]['Filter']['AND'] = $filters;
            }
        }
        return ['TableViewConfig' => $output];
    }

    public function userResourceFromUsername($username) {
        $tableConfig = $this->buildTableConfig(['User' => ['Id', 'UserName' => $username]]);
        $data = $this->dataTable($tableConfig);
        if (empty($data->iTotalRecords) || $data->iTotalRecords != 1) {
            return null;
        }
        return empty($data->aaData[0]->UserId) ? null : $data->aaData[0]->UserId;
    }

    public function userList($limit = 20, $offset = 0) {
        $config = $this->buildTableConfig(['User' => ['Id', 'UserName', 'FirstName', 'LastName', 'Emailaddress']]);
        $config['TableViewConfig']['Resources']['User']['Order'][] = [
            'Field' => [
                'name' => 'UserFirstName',
                'order' => 'ASC']];
        $config['TableViewConfig']['displayStart'] = $offset;
        $config['TableViewConfig']['displayLength'] = $limit;
        return $this->dataTable($config);
    }
    public function photo($userResourceId, $update = null) {
        if (!empty($update)) {
            $type = explode(';', $update['type'])[0];
            $typeSplit = explode('/', $type);
            $extension = (count($typeSplit) > 1) ? $typeSplit[1] : $type;
            $fileId = $this->uploadFile($update['body'], "newphoto.{$extension}");
            if (empty($fileId)) {
                return null;
            }
            $response = $this->client()->post($this->settings['url'] . "user/{$userResourceId}/avatar", ['file' => $fileId]);
            return ($response && $response->isOk());
        }
        $photo = $this->get("user/{$userResourceId}/avatar", ['raw' => true]);
        if (!($photo && $photo->isOk())) {
            return null;
        }
        return [
            'type' => $photo->getHeader('Content-Type'),
            'body' => $photo->body];
    }

    public function uploadFile($rawData, $filename = null) {
        $boundary = 'CakePHPBoundary' . str_replace('-', '', String::uuid());
        if (empty($filename)) {
            $filename = 'uploadfile';
        }
        $body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$filename}\"; filename=\"{$filename}\"\r\n\r\n{$rawData}\r\n--{$boundary}--";
        /* @var $response HttpSocketResponse */
        $response = $this->client()->post(
                $this->settings['url'] . "file",
                $body,
                ['header' => [
                    'Content-Type' => "multipart/form-data; boundary={$boundary}"]]);
        if (!($response && $response->isOk())) {
            return null;
        }
        $files = @json_decode($response->body());
        if (empty($files->file[0])) {
            echo "NOPE";
            return null;
        }
        return $files->file[0];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->settings['username'].":".$this->settings['password']);
        $response = curl_exec($ch);
        
        $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->info = curl_getinfo($ch);
        $this->error = curl_error($ch);
        
        curl_close($ch);
        
        /*if($this->code != '200' && $this->code != '201'){
            echo 'cURL ERROR:<br>'.
                'code: '. $this->code.'<br>'.
                'info: '. print_r($this->info).'<br>'.
                'error: '. $this->error.'<br>';
            //exit;
            echo $url.'<br>';
        }*/
        return $response;
    }
}
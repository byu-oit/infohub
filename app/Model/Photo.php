<?php
App::uses('HttpSocket', 'Network/Http');

class Photo extends AppModel{
    public $useTable = false;
    public $useDbConfig = 'byuApi';

    public function get($netIdRaw) {
        $netId = urlencode($netIdRaw);
        $config = $this->getDataSource()->config;
        $http = new HttpSocket();
        $http->configAuth('ByuApi', $config['api_key'], $config['shared_secret']);
        /* @var $response HttpSocketResponse */
        $response = $http->get("https://{$config['host']}/rest/v1/apikey/identity/person/idphoto/photo?n={$netId}");
        if (!$response->isOk()) {
            return null;
        }
        return [
            'type' => $response->getHeader('Content-Type'),
            'body' => $response->body];
    }
}
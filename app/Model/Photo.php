<?php
App::uses('HttpSocket', 'Network/Http');

class Photo extends AppModel{
    public $useTable = false;
    public $useDbConfig = 'apiStore';

    public function get($netIdRaw) {
        $netId = urlencode($netIdRaw);
        $config = $this->getDataSource()->config;
        $http = new HttpSocket();
        $http->configAuth('ByuApiStore', $config);
        $response = $http->get("https://{$config['host']}/domains/legacy/identity/person/idphoto/v1?N={$netId}");
        if (!$response->isOk()) {
            return null;
        }
        return [
            'type' => $response->getHeader('Content-Type'),
            'body' => $response->body];
    }
}
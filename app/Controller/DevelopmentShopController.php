<?php

class DevelopmentShopController extends AppController {
	public $uses = ['CollibraAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function search($query) {
		$this->autoRender = false;

		$devShops = $this->CollibraAPI->getDevelopmentShopDetails($query, false);
		return json_encode($devShops);
	}

    public function getDetails($developmentShopName) {
        $this->autoRender = false;

        $devShop = $this->CollibraAPI->getDevelopmentShopDetails($developmentShopName);
        return json_encode($devShop);
    }
}

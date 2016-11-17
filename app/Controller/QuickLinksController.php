<?php

class QuickLinksController extends AppController {
	public function add(){
		$this->autoRender = false;
		if ($this->request->is('post')) {
			$ql = $this->request->data['ql'];
			$id = $this->request->data['id'];

			$arrQl = (array)$this->Cookie->read('QL');
			if (array_key_exists($id, $arrQl)) {
				echo '0';
				return;
			}

			$arrQl[$id] = array($ql, $id);
			$this->Cookie->write('QL', $arrQl, true, '90 days');
			echo '1';
		}
	}

	public function remove(){
		$this->autoRender = false;
		if ($this->request->is('post')) {
			$id = $this->request->data['id'];

			$arrQl = $this->Cookie->read('QL');
			if(array_key_exists($id, $arrQl)) {
				unset($arrQl[$id]);
				$this->Cookie->write('QL', $arrQl, true, '90 days');
			}
		}
	}
}

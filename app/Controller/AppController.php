<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
	public $components = ['Session', 'Cookie', 'Auth' => ['authorize' => 'Controller'], 'Flash'];

	public function initBeforeFilter(){
		$byuUsername = '';
		$byuUserDepartment = '';

		if ($authUser = $this->Session->read('Auth.User')) {
			$this->set('casAuthenticated', true);
			$netID = $authUser['username'];
			$this->set('netID', $netID);

			// get username from BYU web service to display in to navigation
			if(empty($_SESSION["byuUsername"])){
				$this->loadModel('BYUAPI');
				$byuUser = $this->BYUAPI->personalSummary($netID);
				if(isset($byuUser->names->preferred_name)){
					$byuUsername = $byuUser->names->preferred_name;
					$_SESSION["byuUsername"] = $byuUsername;
				}
				if(isset($byuUser->employee_information->department)){
					$byuUserDepartment = $byuUser->employee_information->department;
					$_SESSION["byuUserDepartment"] = $byuUserDepartment;
				}
			}else{
				$byuUsername = $_SESSION["byuUsername"];
				$byuUserDepartment = $_SESSION["byuUserDepartment"];
			}

			$cartEmpty = true;
			if ($this->Session->check('queue')) {
				$arrQueue = $this->Session->read('queue');
				// Temporary for transition to new queue structure
				if (!isset($arrQueue['samlFields'])) {
					$arrQueue['samlFields'] = [];
				}
				$cartEmpty = empty($arrQueue['apiFields']) &&
							 empty($arrQueue['dbColumns']) &&
 							 empty($arrQueue['virtualColumns']) &&
							 empty($arrQueue['samlFields']) &&
							 empty($arrQueue['businessTerms']) &&
							 empty($arrQueue['emptyApis']);
			}
			if (!$this->Session->check('cartLoaded') && $cartEmpty) {
				$this->loadCart();
				$this->Session->write('cartLoaded', true);
			}
		}else{
			$this->set('casAuthenticated', false);
			$_SESSION["byuUsername"] = '';
			$_SESSION["byuUserDepartment"] = '';
		}

		if (!$this->Session->check('queue')) {
			$arrQueue = [];
			$arrQueue['apiFields'] = [];
			$arrQueue['dbColumns'] = [];
			$arrQueue['virtualColumns'] = [];
			$arrQueue['samlFields'] = [];
			$arrQueue['businessTerms'] = [];
			$arrQueue['emptyApis'] = [];
			$this->Session->write('queue', $arrQueue);
		}

		$arrQueue = $this->Session->read('queue');
		// Temporary for transition to new queue structure
		if (!isset($arrQueue['samlFields'])) {
			$arrQueue['samlFields'] = [];
		}
		$requestedTermCount = count($arrQueue['apiFields']) +
							  count($arrQueue['dbColumns']) +
							  count($arrQueue['virtualColumns']) +
							  count($arrQueue['samlFields']) +
							  count($arrQueue['businessTerms']) +
							  count($arrQueue['emptyApis']);

		$this->set('byuUsername', $byuUsername);
		$this->set('byuUserDepartment', $byuUserDepartment);
		$this->set('requestedTermCount', $requestedTermCount);
		$this->set('controllerName', $controllerName = $this->request->params['controller']);
		$this->set('isAdmin', $this->Auth->user('infohubUserId'));
	}

	public function beforeFilter() {
		parent::beforeFilter();

		$this->Cookie->name = 'Infohub';
		$this->Auth->authenticate = ['Cas' => ['hostname' => 'cas.byu.edu', 'uri' => 'cas']];
		$this->Auth->allow();
		if($this->name != 'CakeError'){
			$this->initBeforeFilter();
		} else {
			$this->set('isAdmin', $this->Auth->user('infohubUserId'));
		}
	}

	public function isAuthorized($user) {
		return true;
	}

	public function isAdmin($user) {
		return !empty($user['infohubUserId']);
	}

	public function loadCart() {
		$netId = $this->Auth->user('username');
		$this->loadModel('CollibraAPI');
		$draftId = $this->CollibraAPI->checkForDSRDraft($netId);

		if (empty($draftId)) {
			return;
		}

		$draft = $this->CollibraAPI->getRequestDetails($draftId[0]->id);
		if (!empty($draft->attributes['Draft User Cart'])) {
			$arrQueue = json_decode(html_entity_decode($draft->attributes['Draft User Cart']->attrValue), true);
			$this->Session->write('queue', $arrQueue);
		}

		return;
	}

	public function implementedEvents() {
		$implementedEvents = parent::implementedEvents();
		$implementedEvents['CAS.authenticated'] = 'afterCASAuthentication';
		//Re-run initBeforeFilter immediately after fresh Authentication
		$implementedEvents['Auth.afterIdentify'] = 'initBeforeFilter';
		return $implementedEvents;
	}

	public function afterCASAuthentication($event) {
		if (!array_key_exists('infohubUserId', $event->data)) {
			$this->loadModel('CmsUsers');
			$cmsUser = $this->CmsUsers->find('first', [
				'conditions' => ['username' => $event->data['username'], 'active' => '1']]);
			if (empty($cmsUser)) {
				$event->data['infohubUserId'] = null;
			} else {
				$event->data['infohubUserId'] = $cmsUser['CmsUsers']['id'];
			}
		}
		return $event;
	}
}

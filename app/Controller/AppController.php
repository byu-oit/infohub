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
	private $quickLinks;
	public $components = array('Session', 'Auth' => array('authorize' => 'Controller'));

	public function initBeforeFilter(){
		$byuUsername = '';

		if ($authUser = $this->Session->read('Auth.User')) {
			$this->set('casAuthenticated', true);

			// get username from BYU web service to display in to navigation
			if(empty($_SESSION["byuUsername"])){
				$netID = $authUser['username'];
				$this->loadModel('BYUWS');
				$byuUser = $this->BYUWS->personalSummary($netID);
				if(isset($byuUser->names->preferred_name)){
					$byuUsername = $byuUser->names->preferred_name;
					$_SESSION["byuUsername"] = $byuUsername;
				}
			}else{
				$byuUsername = $_SESSION["byuUsername"];
			}
		}else{
			$this->set('casAuthenticated', false);
			$_SESSION["byuUsername"] = '';
		}

		//$this->disableCache();

		App::import('Controller', 'QuickLinks');
		$objQuickLinks = new QuickLinksController;
		$quickLinks = $objQuickLinks->load();

		$requestedTermCount = 0;
		if(isset($_COOKIE['queue'])) {
			$arrQueue = unserialize($_COOKIE['queue']);
			$requestedTermCount = sizeof($arrQueue);
		}

		$this->set('byuUsername', $byuUsername);
		$this->set('quickLinks', $quickLinks);
		$this->set('requestedTermCount', $requestedTermCount);
		$this->set('controllerName', $controllerName = $this->request->params['controller']);
	}

	public function beforeFilter() {
		parent::beforeFilter();

		$this->Auth->authenticate = array('Cas' => array('hostname' => 'cas.byu.edu', 'uri' => 'cas'));
		$this->Auth->allow();
		if($this->name != 'CakeError'){
			$this->initBeforeFilter();
		}

		$isAdmin = false;
		if($this->Session->read('Auth.User.infohubUserId') != ''){
			$isAdmin = true;
		}
		$this->set('isAdmin', $isAdmin);
	}

	public function isAuthorized($user) {
		return true;
	}

	public function isAdmin($user) {
		if (!array_key_exists('infohubUserId', $user)) {
			$this->loadModel('CmsUsers');
			$cmsUser = $this->CmsUsers->find('first', array(
				'conditions' => array('username' => $user['username'], 'active' => '1')));
			if (empty($cmsUser)) {
				$user['infohubUserId'] = null;
			} else {
				$user['infohubUserId'] = $cmsUser['CmsUsers']['id'];
			}
			$this->Session->write('Auth.User.infohubUserId', $user['infohubUserId']);
		}
		return !empty($user['infohubUserId']);
	}
}
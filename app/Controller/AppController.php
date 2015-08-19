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
	
	public function initBeforeFilter(){
		require_once $_SERVER['DOCUMENT_ROOT'].'/CAS-1.3.3/config.php';
		require_once $_SERVER['DOCUMENT_ROOT'].'/CAS-1.3.3/CAS.php';
		phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
		// phpCAS::setCasServerCACert($cas_server_ca_cert_path);
		phpCAS::setNoCasServerValidation();
		$byuUsername = '';

		if(phpCAS::isAuthenticated()){
			$this->set('casAuthenticated', true);

			// get username from BYU web service to display in to navigation
			/*$netID = phpCAS::getUser();
			$this->loadModel('BYUWS');
			$objBYUWS = new BYUWS();
			$byuUser = $objBYUWS->personalSummary($netID);
			if(isset($byuUser->names->preferred_name)){
	            $byuUsername = $byuUser->names->preferred_name;
	        }*/
		}else{
			$this->set('casAuthenticated', false);
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
		
		if($this->name != 'CakeError'){
			$this->initBeforeFilter();
		}
		
		$isAdmin = false;
		if($this->Session->read('userID') != '' && $this->Session->read('userIP')==$_SERVER["REMOTE_ADDR"]){
			$isAdmin = true;
		}
		$this->set('isAdmin', $isAdmin);
	}
}
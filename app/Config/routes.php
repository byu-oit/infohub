<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
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
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */
	//Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
	//Router::connect('/*', array('controller' => 'pages', 'action' => 'display'));
/**
 * ...and connect the rest of 'Pages' controller's URLs.
 */
	//Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

	Router::connect('/resources/*', array('controller' => 'cmsPages', 'action' => 'index'));

	Router::connect('/login', array('controller' => 'myaccount', 'action' => 'login'));

	Router::connect('/apis/:requestid/:hostname/*', ['controller' => 'apis', 'action' => 'viewRequested'], ['pass' => ['requestid', 'hostname'], 'requestid' => '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}']);
	Router::connect('/apis/deep_links/:hostname/*', ['controller' => 'apis', 'action' => 'deep_links'], ['pass' => ['hostname']]);
	Router::connect('/apis/:hostname', ['controller' => 'apis', 'action' => 'host'], ['pass' => ['hostname']]);
	Router::connect('/apis/:hostname/*', ['controller' => 'apis', 'action' => 'view'], ['pass' => ['hostname']]);

	Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));


/**
 * Load all plugin routes. See the CakePlugin documentation on
 * how to customize the loading of plugin routes.
 */
	CakePlugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';

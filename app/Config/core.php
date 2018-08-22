<?php
/**
 * This is core configuration file.
 *
 * Use it to configure core behavior of Cake.
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

if (!function_exists('denv')) {
	function denv($key, $default = null) {
		$val = env($key);
		if ($val === null) {
			return $default;
		}
		return $val;
	}
}

	Configure::write('Collibra', [
		'community' => [
			'api' => 'd9f1c1bb-17cf-4fcb-9046-49d35f839623',
			'byu' => 'c690b823-4341-4125-8a81-22d592c23773',
			'academicRecords' => 'e467b5c3-c497-4fb6-a0d0-cf48815e9bec',
			'dataWarehouse' => 'ad297c25-ddde-42f3-a534-bd73818cbef7',
			'saml' => 'ab07c21b-c27f-470f-942f-102686753057'
		],
		'vocabulary' => [
			'dataSharingRequests' => '00000000-0000-0000-0000-000000006016',
			'newBusinessTerms' => '00000000-0000-0000-0000-000000006013',
			'infoGovPolicies' => '2a1eaea2-62b0-45c0-b499-177f3ddf0551',
			'developmentShops' => '2ffdd301-35e9-44f5-aef4-c4f7881e0e8d',
			'businessGroups' => 'a806940e-c094-4ab6-95af-dde2b664e9f1',
			'applicationsAndProjects' => '51cff8e0-ea5b-4b29-abb1-1cbcf1d26899'
		],
		'workflow' => [
			'intakeDSR' => '8bfbed57-c387-4717-8f5d-c3cfebb485de',
			'newBusinessTerms' => '27028f8b-3d2b-4286-a567-b9658418be51',
			'updateDataWarehouse' => 'c5063f9b-47f4-4c59-92fd-947878d7b5d2',
			'changeDSRRelations' => '987d74a5-1968-4177-90ce-41d9ebac3548',
			'changeAttributes' => '7ee55a00-a355-4066-a65c-f4df27184646',
			'createDSRDraft' => 'ecdb22aa-f091-42b8-9b63-4303e82ee7cf',
			'createRelationsAsync' => 'c1934dda-c1c8-4ad2-8e53-18303fb06545'
		],
		'requiredTermsString' => 'requiredTerms',
		'additionalTermsString' => 'additionalTerms',
		'requiredElementsString' => 'requiredElements',
		'additionalElementsString' => 'additionalElements',
		'dataAssetDomainTypeId' => '00000000-0000-0000-0000-000000030011',
		'glossaryTypeId' => '00000000-0000-0000-0000-000000010001',
		'techAssetDomainTypeId' => '00000000-0000-0000-0000-000000030004',
		'businessTermTypeId' => '00000000-0000-0000-0000-000000011001',
		'standardFieldNameTypeId' => 'ea92faa8-685d-4cb0-8665-14808de08dd2',
		'relationship' => [
			'termToSynonym' => '00000000-0000-0000-0000-000000007001',
			'DSRtoRequestedTerm' => 'edc9e81b-98dc-4bde-8191-1ae75161ab67',
			'DSRtoAdditionallyIncludedTerm' => '71be0418-dbb7-4ee0-ad9e-ef9d5eca13ab',
			'DSRtoRequestedDataAsset' => 'e3e7f185-7d37-4475-949b-a16ff04e73bc',
			'DSRtoAdditionallyIncludedDataAsset' => 'eb29734c-dd12-4046-9a61-3ad83b0f1f80',
			'termToDataAsset' => '00000000-0000-0000-0000-000000007038',
			'DSAtoDSR' => '00000000-0000-0000-0000-000000007055',
			'DSAtoPolicy' => 'd22051bf-af3e-4771-862c-c2b3f10bce05',
			'DSRtoPolicy' => '0cd17138-7610-48fc-8d11-616a376d44c5',
			'termToPolicy' => '0b8404c5-b0f2-4109-89ab-374c5f775c14',
			'DSRtoNecessaryAPI' => '5f4d65ab-ab89-4ed2-a2e7-a628ee7ba4ba',
			'DSRtoNecessaryTable' => 'ec73bc13-c215-42f2-911b-76a345ec9ed5',
			'DSRtoNecessarySAML' => 'dca012a1-e752-4299-bf61-925358efe83c',
			'applicationOrProjectToDSR' => '7ff3a292-a33a-47fd-b549-4b9b5a66da3b',
			'applicationOrProjectToDSA' => '029c9294-3bdc-4ceb-a922-e2186fb5df30',
			'schemaToTable' => '00000000-0000-0000-0000-000000007043',
			'columnToTable' => '00000000-0000-0000-0000-000000007042',
			'fieldToSaml' => 'cee630bb-e1c0-471b-91c2-727397f10d0b',
			'developmentShopToApplicationOrProject' => '3e4e1b35-4cc2-43b3-a624-d753f14347c2',
			'businessGroupToApplicationOrProject' => '4e041daf-1a20-4962-bc43-134649c7d3b0'
		],
		'type' => [
			'dataSharingRequest' => '00000000-0000-0000-0000-000000031231',
			'dataSharingRequestDraft' => '6bee7c91-3f51-4db6-b2d1-f1a3411fad49',
			'synonym' => '2c2d6491-a41c-4e9e-af52-759b43948951',
			'term' => '00000000-0000-0000-0000-000000011001',
			'api' => '43efdd9e-8173-4f42-8055-9ae73d6134b1',
			'field' => '00000000-0000-0000-0001-000400000008',
			'fieldSet' => '73bae222-220d-4342-94ba-6a8369797eac',
			'samlResponse' => 'fbe6f14c-38a0-4926-aa0c-42f2c3b04018',
			'developmentShop' => 'f8d30393-9f7f-4236-8e8d-828805a569d9',
			'applicationOrProject' => '4d9178d0-ff3d-4e7e-8dba-4dca9e0f8343',
			'businessGroup' => '00b946c2-9bf1-449b-a528-20f9a0700e95',
			'glossary' => ''
		],
		'attribute' => [
			'definition' => '00000000-0000-0000-0000-000000000202',
			'description' => '00000000-0000-0000-0000-000000003114',
			'descriptiveExample' => '00000000-0000-0000-0000-000000003115',
			'standardFieldName' => 'ea92faa8-685d-4cb0-8665-14808de08dd2',
			'classification' => '80cae8d4-856f-4b9a-971a-164514e79744',
			'concept' => '00fd4187-ffbd-4419-acd0-74fd9f16598a',
			'requesterNetId' => 'a368134f-ab44-4ce6-ac1d-1b69bfa37d6e',
			'stewardPhone' => '9a18e247-c090-40c3-896e-ab97335ae759',
			'stewardEmail' => '0cbbfd32-fc97-47ce-bef1-a89ae4e77ee8',
			'stewardName' => '4331ec09-88a2-48e6-b096-9ece6648aff3',
			'notes' => '00000000-0000-0000-0000-000000003116',
			'inclusionScenario' => '00000000-0000-0000-0001-000500000026',
			'applicationIdentity' => '0a607e95-4e85-4b86-beac-e2b74cd9e89a'
		],
		'status' => [
			'testing' => '4eb2eaf1-faa5-4e89-9129-3203fc5eafa4',
			'preProduction' => '744e30a0-5152-406c-98a6-7db92d3b8522',
			'production' => 'cb7feadd-bfe8-4c01-bcb1-7ad955bc58a1',
			'deprecated' => 'de263699-db33-4762-9330-943049eeec55',
			'retired' => '982bf7ce-25b4-4903-b932-36091434514c',
			'deleted' => '5ed07772-862c-4898-aa31-9c781ab13a1e'
		],
		'term' => [
			'custodian' => '6f666b9e-be07-4521-95fa-36fecfe2ff71',
			'steward' => 'c43795f0-5450-41ec-90fb-55d1d84a0efe'
		],
		'role' => [
			'requestCoordinator' => 'b79020f5-ff56-400f-8882-35f968e1712d',
			'steward' => '00000000-0000-0000-0000-000000005016',
			'custodian' => '00000000-0000-0000-0000-000000005041',
			'trustee' => 'f55b47bc-973a-462d-9a37-35395f20e52d'
		],
		'formFields' => [
			'requesterNetId' => 'a368134f-ab44-4ce6-ac1d-1b69bfa37d6e',
			'name' => 'f7ebce1f-c0b5-48c8-bc62-bddd976994fc',
			'phone' => 'c3252e20-c566-405d-8bfe-dd69377042c2',
			'role' => '688e8cf3-0db8-4ad1-b577-215f09b8e613',
			'email' => '52404e00-7448-456a-8eac-bad31aea7e61',
			'sponsorName' => '81e2e950-e9f7-4340-9d39-7faa7e88733a',
			'sponsorPhone' => 'd16d9253-e1fc-4d3b-a5a7-39d2ffb92560',
			'sponsorRole' => '2791b03b-464a-4bc9-b97d-2b70d4274a1e',
			'sponsorEmail' => '51e3cd5e-9aa9-4953-8a29-31cfd0492d0c',
			'requestingOrganization' => '97a41292-5627-49cc-bc36-efad1b996d37',
			'developmentShop' => '064fe8d8-bcd8-42b6-8b85-af984c14714d',
			'developmentShopId' => '72747431-c83d-4f3f-9ba5-016ae47ef36b',
			'applicationOrProjectName' => 'c70f71fb-a92b-4034-b8b6-9f042c0a39ea',
			'applicationOrProjectId' => 'e36f6ecc-3a16-4bfd-95bb-ac9e42166aed',
			'descriptionOfInformation' => '6b41a821-2a26-4fe8-8202-23442c1e54bb',
			'descriptionOfApplicationOrProject' => '459f3f5c-2904-47f7-b91d-005a02ad2fce',
			'necessityOfData' => '12d99132-6845-48f9-89b6-e4c74e1ab978',
			'scopeAndControl' => 'dc4f0171-a69e-4168-87e6-35e38547e96c',
			'readWriteAccess' => '275fd60d-4b40-487e-b425-db2d208886a3',
			'dataStewardResponse' => 'e35fa2ba-e89a-4cd5-9744-cfe216427396',
			'requestedInformationMap' => '26e7c03e-9a6a-4ce3-baa7-5d31ad5505c2',
			'technologyType' => '5ab6d7fb-811b-418c-8273-08d28bc4e1d1',
			'draftUserCart' => '9368b76d-6fa8-4402-8bf4-a45051d40752',
			'descriptionOfIntendedUse' => 'cfdc107a-c949-465e-810e-13296a5759dc',
			'accessRights' => '8d12adef-0584-4bb6-97b5-5f9e1e3ec4ad',
			'accessMethod' => '42fc793b-70e1-4860-9846-8c67c1c49d50',
			'impactOnSystem' => '3931e9bf-b904-4232-930e-af70b0895611'
		],
		'policy' => [
			'standardDataUsagePolicies' => '76c92ba2-6930-4946-809b-e559bb247587',
			'trustedPartnerSecurityStandards' => '712bd956-0c58-4724-bc12-69b1ee12bddf'
		]
	]);

	Configure::write('allowUnapprovedTerms', true);
	Configure::write('allowUnrequestableTerms', false);

	Configure::write('Datasources', [
		'default' => [
			'datasource' => 'Database/Mysql',
			'persistent' => false,
			'host' => denv('CAKE_DEFAULT_DB_HOST', 'salix.byu.edu'),
			'login' => denv('CAKE_DEFAULT_DB_USERNAME', '***REQUIRED***'),
			'password' => denv('CAKE_DEFAULT_DB_PASSWORD', '***REQUIRED***'),
			'database' => denv('CAKE_DEFAULT_DB_DATABASE', 'infohub'),
			'prefix' => ''],
		'collibra' => [
			'datasource' => 'DataSource',
			'url'       =>  denv('CAKE_COLLIBRA_HOST', 'https://byu.collibra.com/rest/latest/'),
			'username'  => denv('CAKE_COLLIBRA_USERNAME', '***REQUIRED***'),
			'password'  => denv('CAKE_COLLIBRA_PASSWORD', '***REQUIRED***')],
		'byuApi' => [
			'datasource' => 'DataSource',
			'host' => denv('CAKE_BYU_API_HOST', 'api.byu.edu'),
			'key' => denv('CAKE_BYU_API_KEY', '***REQUIRED***'),
			'secret' => denv('CAKE_BYU_API_SECRET', '***REQUIRED***')]]);
/**
 * CakePHP Debug Level:
 *
 * Production Mode:
 * 	0: No error messages, errors, or warnings shown. Flash messages redirect.
 *
 * Development Mode:
 * 	1: Errors and warnings shown, model caches refreshed, flash messages halted.
 * 	2: As in 1, but also with full debug messages and SQL output.
 *
 * In production mode, flash messages redirect after a time interval.
 * In development mode, you need to click the flash message to continue.
 */
	Configure::write('debug', denv('CAKE_DEBUG', 0));

/**
 * Configure the Error handler used to handle errors for your application. By default
 * ErrorHandler::handleError() is used. It will display errors using Debugger, when debug > 0
 * and log errors with CakeLog when debug = 0.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle errors. You can set this to any callable type,
 *   including anonymous functions.
 *   Make sure you add App::uses('MyHandler', 'Error'); when using a custom handler class
 * - `level` - integer - The level of errors you are interested in capturing.
 * - `trace` - boolean - Include stack traces for errors in log files.
 *
 * @see ErrorHandler for more information on error handling and configuration.
 */
	Configure::write('Error', [
		'handler' => 'ErrorHandler::handleError',
		'level' => E_ALL & ~E_DEPRECATED,
		'trace' => true
	]);

/**
 * Configure the Exception handler used for uncaught exceptions. By default,
 * ErrorHandler::handleException() is used. It will display a HTML page for the exception, and
 * while debug > 0, framework errors like Missing Controller will be displayed. When debug = 0,
 * framework errors will be coerced into generic HTTP errors.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle exceptions. You can set this to any callback type,
 *   including anonymous functions.
 *   Make sure you add App::uses('MyHandler', 'Error'); when using a custom handler class
 * - `renderer` - string - The class responsible for rendering uncaught exceptions. If you choose a custom class you
 *   should place the file for that class in app/Lib/Error. This class needs to implement a render method.
 * - `log` - boolean - Should Exceptions be logged?
 * - `skipLog` - array - list of exceptions to skip for logging. Exceptions that
 *   extend one of the listed exceptions will also be skipped for logging.
 *   Example: `'skipLog' => array('NotFoundException', 'UnauthorizedException')`
 *
 * @see ErrorHandler for more information on exception handling and configuration.
 */
	Configure::write('Exception', [
		'handler' => 'ErrorHandler::handleException',
		'renderer' => 'ExceptionRenderer',
		'log' => true
	]);

/**
 * Application wide charset encoding
 */
	Configure::write('App.encoding', 'UTF-8');

/**
 * To configure CakePHP *not* to use mod_rewrite and to
 * use CakePHP pretty URLs, remove these .htaccess
 * files:
 *
 * /.htaccess
 * /app/.htaccess
 * /app/webroot/.htaccess
 *
 * And uncomment the App.baseUrl below. But keep in mind
 * that plugin assets such as images, CSS and JavaScript files
 * will not work without URL rewriting!
 * To work around this issue you should either symlink or copy
 * the plugin assets into you app's webroot directory. This is
 * recommended even when you are using mod_rewrite. Handling static
 * assets through the Dispatcher is incredibly inefficient and
 * included primarily as a development convenience - and
 * thus not recommended for production applications.
 */
	//Configure::write('App.baseUrl', env('SCRIPT_NAME'));

/**
 * To configure CakePHP to use a particular domain URL
 * for any URL generation inside the application, set the following
 * configuration variable to the http(s) address to your domain. This
 * will override the automatic detection of full base URL and can be
 * useful when generating links from the CLI (e.g. sending emails)
 */
	Configure::write('App.fullBaseUrl', denv('CAKE_FULL_BASE_URL', 'https://infohub.byu.edu'));

/**
 * Web path to the public images directory under webroot.
 * If not set defaults to 'img/'
 */
	//Configure::write('App.imageBaseUrl', 'img/');

/**
 * Web path to the CSS files directory under webroot.
 * If not set defaults to 'css/'
 */
	//Configure::write('App.cssBaseUrl', 'css/');

/**
 * Web path to the js files directory under webroot.
 * If not set defaults to 'js/'
 */
	//Configure::write('App.jsBaseUrl', 'js/');

/**
 * Uncomment the define below to use CakePHP prefix routes.
 *
 * The value of the define determines the names of the routes
 * and their associated controller actions:
 *
 * Set to an array of prefixes you want to use in your application. Use for
 * admin or other prefixed routes.
 *
 * 	Routing.prefixes = array('admin', 'manager');
 *
 * Enables:
 *	`admin_index()` and `/admin/controller/index`
 *	`manager_index()` and `/manager/controller/index`
 *
 */
	//Configure::write('Routing.prefixes', array('admin'));

/**
 * Turn off all caching application-wide.
 *
 */
//	Configure::write('Cache.disable', true);

/**
 * Enable cache checking.
 *
 * If set to true, for view caching you must still use the controller
 * public $cacheAction inside your controllers to define caching settings.
 * You can either set it controller-wide by setting public $cacheAction = true,
 * or in each action using $this->cacheAction = true.
 *
 */
	//Configure::write('Cache.check', true);

/**
 * Enable cache view prefixes.
 *
 * If set it will be prepended to the cache name for view file caching. This is
 * helpful if you deploy the same application via multiple subdomains and languages,
 * for instance. Each version can then have its own view cache namespace.
 * Note: The final cache file name will then be `prefix_cachefilename`.
 */
	//Configure::write('Cache.viewPrefix', 'prefix');

/**
 * Session configuration.
 *
 * Contains an array of settings to use for session configuration. The defaults key is
 * used to define a default preset to use for sessions, any settings declared here will override
 * the settings of the default config.
 *
 * ## Options
 *
 * - `Session.cookie` - The name of the cookie to use. Defaults to 'CAKEPHP'
 * - `Session.timeout` - The number of minutes you want sessions to live for. This timeout is handled by CakePHP
 * - `Session.cookieTimeout` - The number of minutes you want session cookies to live for.
 * - `Session.checkAgent` - Do you want the user agent to be checked when starting sessions? You might want to set the
 *    value to false, when dealing with older versions of IE, Chrome Frame or certain web-browsing devices and AJAX
 * - `Session.defaults` - The default configuration set to use as a basis for your session.
 *    There are four builtins: php, cake, cache, database.
 * - `Session.handler` - Can be used to enable a custom session handler. Expects an array of callables,
 *    that can be used with `session_save_handler`. Using this option will automatically add `session.save_handler`
 *    to the ini array.
 * - `Session.autoRegenerate` - Enabling this setting, turns on automatic renewal of sessions, and
 *    sessionids that change frequently. See CakeSession::$requestCountdown.
 * - `Session.ini` - An associative array of additional ini values to set.
 *
 * The built in defaults are:
 *
 * - 'php' - Uses settings defined in your php.ini.
 * - 'cake' - Saves session files in CakePHP's /tmp directory.
 * - 'database' - Uses CakePHP's database sessions.
 * - 'cache' - Use the Cache class to save sessions.
 *
 * To define a custom session handler, save it at /app/Model/Datasource/Session/<name>.php.
 * Make sure the class implements `CakeSessionHandlerInterface` and set Session.handler to <name>
 *
 * To use database sessions, run the app/Config/Schema/sessions.php schema using
 * the cake shell command: cake schema create Sessions
 *
 */
	Configure::write('Session', [
		'defaults' => 'php'
	]);

/**
 * A random string used in security hashing methods.
 */
	Configure::write('Security.salt', denv('CAKE_SECURITY_SALT', 'DYhG2db0qyJO3xfs2guGoUubWwvniR2G0FgaC9mi'));

/**
 * A random numeric string (digits only) used to encrypt/decrypt strings.
 */
	Configure::write('Security.cipherSeed', denv('CAKE_SECURITY_CIPHER_SEED', '26859309377459542446745783645'));

/**
 * Apply timestamps with the last modified time to static assets (js, css, images).
 * Will append a query string parameter containing the time the file was modified. This is
 * useful for invalidating browser caches.
 *
 * Set to `true` to apply timestamps when debug > 0. Set to 'force' to always enable
 * timestamping regardless of debug value.
 */
	Configure::write('Asset.timestamp', 'force');

/**
 * Compress CSS output by removing comments, whitespace, repeating tags, etc.
 * This requires a/var/cache directory to be writable by the web server for caching.
 * and /vendors/csspp/csspp.php
 *
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use HtmlHelper::css().
 */
	//Configure::write('Asset.filter.css', 'css.php');

/**
 * Plug in your own custom JavaScript compressor by dropping a script in your webroot to handle the
 * output, and setting the config below to the name of the script.
 *
 * To use, prefix your JavaScript link URLs with '/cjs/' instead of '/js/' or use JsHelper::link().
 */
	//Configure::write('Asset.filter.js', 'custom_javascript_output_filter.php');

/**
 * The class name and database used in CakePHP's
 * access control lists.
 */
	Configure::write('Acl.classname', 'DbAcl');
	Configure::write('Acl.database', 'default');

/**
 * Uncomment this line and correct your server timezone to fix
 * any date & time related errors.
 */
	date_default_timezone_set('UTC');

/**
 * `Config.timezone` is available in which you can set users' timezone string.
 * If a method of CakeTime class is called with $timezone parameter as null and `Config.timezone` is set,
 * then the value of `Config.timezone` will be used. This feature allows you to set users' timezone just
 * once instead of passing it each time in function calls.
 */
	//Configure::write('Config.timezone', 'Europe/Paris');

/**
 * Cache Engine Configuration
 * Default settings provided below
 *
 * File storage engine.
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'File', //[required]
 *		'duration' => 3600, //[optional]
 *		'probability' => 100, //[optional]
 * 		'path' => CACHE, //[optional] use system tmp directory - remember to use absolute path
 * 		'prefix' => 'cake_', //[optional]  prefix every cache file with this string
 * 		'lock' => false, //[optional]  use file locking
 * 		'serialize' => true, //[optional]
 * 		'mask' => 0664, //[optional]
 *	));
 *
 * APC (http://pecl.php.net/package/APC)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Apc', //[required]
 *		'duration' => 3600, //[optional]
 *		'probability' => 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *	));
 *
 * Xcache (http://xcache.lighttpd.net/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Xcache', //[required]
 *		'duration' => 3600, //[optional]
 *		'probability' => 100, //[optional]
 *		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional] prefix every cache file with this string
 *		'user' => 'user', //user from xcache.admin.user settings
 *		'password' => 'password', //plaintext password (xcache.admin.pass)
 *	));
 *
 * Memcached (http://www.danga.com/memcached/)
 *
 * Uses the memcached extension. See http://php.net/memcached
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Memcached', //[required]
 *		'duration' => 3600, //[optional]
 *		'probability' => 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 * 		'servers' => array(
 * 			'127.0.0.1:11211' // localhost, default port 11211
 * 		), //[optional]
 * 		'persistent' => 'my_connection', // [optional] The name of the persistent connection.
 * 		'compress' => false, // [optional] compress data in Memcached (slower, but uses less memory)
 *	));
 *
 *  Wincache (http://php.net/wincache)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Wincache', //[required]
 *		'duration' => 3600, //[optional]
 *		'probability' => 100, //[optional]
 *		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *	));
 */

/**
 * Configure the cache handlers that CakePHP will use for internal
 * metadata like class maps, and model schema.
 *
 * By default File is used, but for improved performance you should use APC.
 *
 * Note: 'default' and other application caches should be configured in app/Config/bootstrap.php.
 *       Please check the comments in bootstrap.php for more info on the cache engines available
 *       and their settings.
 */
$engine = 'File';

// In development mode, caches should expire quickly.
$duration = '+999 days';
if (Configure::read('debug') > 0) {
	$duration = '+10 seconds';
}

// Prefix each application on the same server with a different string, to avoid Memcache and APC conflicts.
$prefix = 'myapp_';

/**
 * Configure the cache used for general framework caching. Path information,
 * object listings, and translation cache files are stored with this configuration.
 */
Cache::config('_cake_core_', array(
	'engine' => $engine,
	'prefix' => $prefix . 'cake_core_',
	'path' => CACHE . 'persistent' . DS,
	'serialize' => ($engine === 'File'),
	'duration' => $duration
));

/**
 * Configure the cache for model and datasource caches. This cache configuration
 * is used to store schema descriptions, and table listings in connections.
 */
Cache::config('_cake_model_', array(
	'engine' => $engine,
	'prefix' => $prefix . 'cake_model_',
	'path' => CACHE . 'models' . DS,
	'serialize' => ($engine === 'File'),
	'duration' => $duration
));


if (is_readable(dirname(__FILE__) . DS . 'core-local.php')) {
	Configure::load('core-local');
}

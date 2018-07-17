<?php

/*
 * Mimicking Cake 3 database configuration: now all config is in core.php
 * (with potential overrides in core-local.php)
 */
class DATABASE_CONFIG {
	public $default = null;
	public function __construct() {
		$configs = Configure::read('Datasources');
		if (!is_array($configs)) {
			throw new Exception('No Datasources connections defined in core.php');
		}

		foreach ($configs as $key => $config) {
			$this->{$key} = $config;
		}

		if (!property_exists($this, 'default') || !is_array($this->default)) {
			throw new Exception('No Datasources.default connection defined in core.php');
		}
	}
}

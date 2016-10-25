<?php
use Rs\Json\Pointer;

class Swagger extends AppModel {

	public $useTable = false;

	/** @var Pointer $swag */
	private $swag;
	private $elements;
	public $parseErrors = [];

	public function parse($json) {
		try {
			$this->swag = new Pointer($json);
		} catch (Exception $e) {
			$this->parseErrors[] = "Unable to parse Swagger doc";
			return false;
		}

		try {
			$paths = $this->swag->get('/paths');
		} catch (Exception $e) {
			$this->parseErrors[] = "Unable to find /paths in Swagger";
			return false;
		}

		$this->elements = [];
		foreach ($paths as $pathName => $path) {
			if (empty($path['get']['responses'][200]['schema'])) {
				continue;
			}
			$schema = $path['get']['responses'][200]['schema'];
			if (!empty($schema['$ref'])) {
				list($name, $schema) = $this->_getRef($schema['$ref']);
				$schema = [$name => $schema];
			}
			$this->_addElements([], $schema);
		}

		$host = $this->_getRef('/host');
		$basePath = $this->_getRef('/basePath');

		if (empty($this->elements)) {
			$this->parseErrors[] = "No fields found in Swagger";
			return false;
		}

		return [
			'host' => $host[1],
			'basePath' => $basePath[1],
			'elements' => array_values($this->elements)
		];
	}

	protected function _getRef($ref) {
		if (strpos($ref, '#') !== false) {
			$ref = substr($ref, strpos($ref, '#') + 1);
		}
		$refBaseName = array_slice(explode('/', $ref), -1)[0];
		try {
			return [$refBaseName, $this->swag->get($ref)];
		} catch (Exception $e) {
			return [null, null];
		}
	}

	protected function _addElements($mainParents, $properties) {
		foreach ($properties as $propertyName => $property) {
			if ($propertyName == 'links' || $propertyName == 'metadata') {
				continue;
			}
			if (empty($property['type']) && !empty($property['$ref'])) {
				if (preg_match('#/api_([^/]*)$#', $property['$ref'], $matches)) {
					$property['type'] = $matches[1];
				} else {
					list($refName, $property) = $this->_getRef($property['$ref']);
					if ($refName != 'response') {
						$propertyName = $refName;
					}
				}
			}
			if (empty($property['type'])) {
				if (!empty($property['properties'])) {
					$property['type'] = 'object';
				} else {
					continue;
				}
			}

			$parents = $mainParents;
			if (substr($propertyName, -5) != 'basic' && $propertyName != 'identity') {
				//conditionalize this
				$parents[] = $propertyName;
			}

			switch ($property['type']) {
				case 'object':
					if (empty($property['properties'])) {
						break;
					}
					if (!empty($property['properties']['api_type'])) {
						$this->_addElement($parents, $property);
						break;
					}
					if (count($property['properties']) == 2 && !empty($property['properties']['request']) && !empty($property['properties']['response'])) {
						$this->_addElements($mainParents, [$propertyName => $property['properties']['response']]);
						break;
					}
					$this->_addElements($parents, $property['properties']);
					break;
				case 'array':
					if (empty($property['items'])) {
						break;
					}
					$parents = $mainParents;
					if ($propertyName == 'values') {
						$propertyName = array_pop($parents);
					}
					$this->_addElements($parents, [$propertyName => $property['items']]);
					break;
				default:
					$this->_addElement($parents, $property);
			}
		}
	}

	protected function _addElement($parents, $property, $type = 'field') {
		if (count($parents) > 1) {
			$this->_addElement(array_slice($parents, 0, -1), [], 'fieldset');
		}
		$key = implode('.', $parents);
		if (!array_key_exists($key, $this->elements)) {
			$this->elements[$key] = ['name' => $key, 'type' => $type];
		}
		if (!empty($property['description']) && empty($this->elements[$key]['description'])) {
			$this->elements[$key]['description'] = $property['description'];
		}
	}
}
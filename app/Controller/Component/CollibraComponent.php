<?php

App::uses('Component', 'Controller');

class CollibraComponent extends Component {
    public function cleanEdits(&$asset, $parent) {
        $this->CollibraAPI = ClassRegistry::init('CollibraAPI');

        // Making edits in Collibra inserts weird html into the attributes; if an
        // edit was made in Collibra, we replace their html with some more cooperative tags
		$this->arrChangedAttrIds = [];
		$this->arrChangedAttrValues = [];
		foreach($asset->attributes as $label => $attr) {
			if ($label == 'Request Date') continue;
			if (preg_match('/<div>/', $attr->attrValue)) {
                $attr->attrValue = $this->replaceTags($attr);
			}
		}

		if ($parent) {
			for ($i = 0; $i < sizeof($asset->dsas); $i++) {
                foreach($asset->dsas[$i]->attributes as $attr) {
                    if (preg_match('/<div>/', $attr->attrValue)) {
                        $attr->attrValue = $this->replaceTags($attr);
                    }
                }
			}
		}

		if (!empty($this->arrChangedAttrIds)) {
			$resp = $this->CollibraAPI->post(
				'workflow/'.Configure::read('Collibra.workflow.changeAttributes').'/start',
				$this->preparePostData(['attributes' => $this->arrChangedAttrIds, 'values' => $this->arrChangedAttrValues]));
		}
    }

    private function replaceTags($attr) {
        array_push($this->arrChangedAttrIds, $attr->attrResourceId);
        $newValue = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->attrValue);
        array_push($this->arrChangedAttrValues, $newValue.'  ');

        // After updating the value in Collibra, just replace the value for current page load
        return $newValue;
    }

    public function preparePostData($postData, $match='/%5B[0-9]*%5D/', $replacement='') {
    	// Collibra doesn't like array keys in post data, e.g.
		// foo[0]=bar&foo[1]=duh&foo[2]=gronk
		//
		// Instead it simply expects the same key repeated, e.g.
		// foo=bar&foo=duh&foo=gronk
		//
		// But we don't want to corrupt the urlencoded values, for instance pre-encoded JSON,
		// so we use http_build_query, split everything apart, fix the keys, and then glue it all back together
        $postString = http_build_query($postData);
        $pieces = explode('&', $postString);
        $cleanKeys = [];
        foreach($pieces as $keyVal) {
            list($key, $val) = explode('=', $keyVal);
            $key = preg_replace($match, $replacement, $key);
            $cleanKeys[] = "{$key}={$val}";
		}

        return implode('&', $cleanKeys);
    }
}

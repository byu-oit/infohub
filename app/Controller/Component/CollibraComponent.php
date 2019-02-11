<?php

App::uses('Component', 'Controller');

class CollibraComponent extends Component {
    public $components = ['Post'];

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
				$this->Post->preparePostData(['attributes' => $this->arrChangedAttrIds, 'values' => $this->arrChangedAttrValues]));
		}
    }

    private function replaceTags($attr) {
        array_push($this->arrChangedAttrIds, $attr->attrResourceId);
        $newValue = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->attrValue);
        array_push($this->arrChangedAttrValues, $newValue.'  ');

        // After updating the value in Collibra, just replace the value for current page load
        return $newValue;
    }
}

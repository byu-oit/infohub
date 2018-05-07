<?php

class DirectoryController extends AppController {
	public $uses = ['BYUAPI'];

    public function lookup() {
        $this->autoRender = false;

		if (empty($this->request->query['term'])) {
			$this->redirect(['controller' => 'search', 'action' => 'index']);
		}

		$arrPeople = $this->BYUAPI->directorySearch($this->request->query['term'], 5);

		$arrReturn = [];
		foreach ($arrPeople as $person) {
			$html = '<div class="collaborators-search-result" person-id="'.$person->person_id.'">'.ucwords(strtolower($person->sort_name));
			if (!empty($person->department)) $html .= ' - '.$person->department;
			$html .= "</div>";
			array_push($arrReturn, $html);
		}

		return json_encode($arrReturn);
    }
}

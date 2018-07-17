<?php

class DirectoryController extends AppController {
	public $uses = ['BYUAPI'];

    public function collaboratorSearch() {
        $this->autoRender = false;

		if (empty($this->request->query['term'])) {
			return json_encode([]);
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

	public function requesterAndSupervisorLookup() {
		$this->autoRender = false;

		if (empty($this->request->query['netId'])) {
			return json_encode(['success' => 0, 'message' => 'A Net ID to look up is required.']);
		}

		$netId = $this->request->query['netId'];
		$requester = $this->BYUAPI->personalSummary($netId);
		if (empty($requester)) {
			return json_encode(['success' => 0, 'message' => 'We can\'t find a user with that Net ID.']);
		}

		$arrReturn = [
			'requester' => [
				'name' => '',
				'phone' => '',
				'role' => '',
				'email' => '',
				'department' => ''
			],
			'supervisor' => [
				'name' => '',
				'phone' => '',
				'role' => '',
				'email' => ''
			]
		];

		if (isset($requester->names->preferred_name)) $arrReturn['requester']['name'] = $requester->names->preferred_name;
		if (isset($requester->contact_information->work_phone)) $arrReturn['requester']['phone'] = $requester->contact_information->work_phone;
		if (isset($requester->employee_information->job_title)) $arrReturn['requester']['role'] = $requester->employee_information->job_title;
		if (isset($requester->contact_information->work_email_address)) {
			$arrReturn['requester']['email'] = $requester->contact_information->work_email_address;
		} else if (isset($requester->contact_information->email_address)) {
			$arrReturn['requester']['email'] = $requester->contact_information->email_address;
		}
		if (isset($requester->employee_information->department)) $arrReturn['requester']['department'] = $requester->employee_information->department;

		$supervisor = $this->BYUAPI->supervisorLookup($netId);
		if (empty($supervisor)) {
			$arrReturn['message'] = 'Supervisor information not found.';
		}
		if (isset($supervisor->name)) $arrReturn['supervisor']['name'] = $supervisor->name;
		if (isset($supervisor->phone)) $arrReturn['supervisor']['phone'] = $supervisor->phone;
		if (isset($supervisor->job_title)) $arrReturn['supervisor']['role'] = $supervisor->job_title;
		if (isset($supervisor->email)) $arrReturn['supervisor']['email'] = $supervisor->email;

		$arrReturn['success'] = 1;
		return json_encode($arrReturn);
	}
}

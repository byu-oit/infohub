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
			//Using netId to work better with personalSummary function when they are added to the list
			$html = '<div class="collaborators-search-result" person-id="'.$person->basic->net_id->value.'">'.ucwords(strtolower($person->basic->name_lnf->value));
			if (!empty($person->employee_summary->department->value)) $html .= ' - '.$person->employee_summary->department->value;
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
				'email' => '',
				'department' => ''
			]
		];

		if (isset($requester->basic->preferred_name->value)) $arrReturn['requester']['name'] = $requester->basic->preferred_name->value;
		for($i = 0; $i < sizeof($requester->phones->values); $i++) {
			if($requester->phones->values[$i]->work_flag->value) {
				$arrReturn['requester']['phone'] = $requester->phones->values[$i]->phone_number->value;
				break;
			}
		}
		for($i = 0; $i < sizeof($requester->email_addresses->values); $i++) {
			if($requester->email_addresses->values[$i]->email_address_type->value == "PERSONAL") {
				$psEmailPersonal = $requester->email_addresses->values[$i]->email_address->value;
			} else if($requester->email_addresses->values[$i]->email_address_type->value == "WORK") {
				$psWorkEmail = $requester->email_addresses->values[$i]->email_address->value;
			}
		}
		if (isset($requester->employee_summary->job_code->description)) $arrReturn['requester']['role'] = $requester->employee_summary->job_code->description;
		if (isset($psWorkEmail)) {
			$arrReturn['requester']['email'] = $psWorkEmail;
		} else if (isset($psEmailPersonal)) {
			$arrReturn['requester']['email'] = $psEmailPersonal;
		}
		if (isset($requester->employee_summary->department->value)) $arrReturn['requester']['department'] = $requester->employee_summary->department->value;

		$supervisor = $this->BYUAPI->supervisorLookup($netId);
		if (empty($supervisor)) {
			$arrReturn['message'] = 'Supervisor information not found.';
		}
		if (isset($supervisor->name)) $arrReturn['supervisor']['name'] = $supervisor->name;
		if (isset($supervisor->phone)) $arrReturn['supervisor']['phone'] = $supervisor->phone;
		if (isset($supervisor->job_title)) $arrReturn['supervisor']['role'] = $supervisor->job_title;
		if (isset($supervisor->email)) $arrReturn['supervisor']['email'] = $supervisor->email;
		if (isset($supervisor->department)) $arrReturn['supervisor']['department'] = $supervisor->department;

		$arrReturn['success'] = 1;
		return json_encode($arrReturn);
	}
}

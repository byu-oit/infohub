<?php

class PeopleController extends AppController {
	private static function sortCommunities($a, $b){
		return strcmp($a->name, $b->name);
	}

	private static function sortUsers($a, $b){
		return strcmp($a->userlastname, $b->userlastname);
	}

	private function getParentCommunities($communityData, $parentID, $objCommunity, $level=0){
		if($parentID != Configure::read('Collibra.community.byu')){
			if($level==0) $objCommunity->parents = array();
			foreach($communityData[0]->Subcommunities as $co){
				if($parentID == $co->subcommunityid){
					array_unshift($objCommunity->parents, $co);
					$this->getParentCommunities($communityData, $co->parentCommunityId, $objCommunity, $level+1);
					break;
				}
			}
		}
	}

	private function getSubCommunities($json, $cid, $arrData=null, $level=0){
		// returns array with first item being the sub community
		// and the second item being an array with sub-sub communities
		if(!$arrData) $arrData = array();
		foreach($json[0]->Subcommunities as $sc){
			if($sc->parentCommunityId === $cid){
				if($sc->hasNonMetaChildren){
					array_push($arrData, array($sc, array()));
					$arr = $arrData[sizeof($arrData)-1];
					$arr = $this->getSubCommunities($json, $sc->subcommunityid, null, $level+1);
					if(sizeof($arr)>0){
						$arrData[sizeof($arrData)-1][$level+1] = $arr;
					}else{
						array_pop($arrData[sizeof($arrData)-1]);
					}
				}
			}
		}
		return $arrData;
	}

	public function index(){
		// get all parent communities for left nav
		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->get('community/'.Configure::read('Collibra.community.byu').'/sub-communities');
		$parentCommunities = json_decode($resp);
		usort($parentCommunities->communityReference, 'self::sortCommunities');

		$users = $this->CollibraAPI->getUserData();
		usort($users, 'self::sortUsers');
		$communities = $this->CollibraAPI->getAllCommunities();

		$arrUserData = array();
		$letterGroup = '0';
		foreach($users as $r){
			$group = substr($r->userlastname,0,1);
			if(!isset($arrUserData[$group])){
				$arrUserData[$group] = array();
			}

			if(!isset($arrUserData[$group][$r->userrid])){
				$arrUserData[$group][$r->userrid]['id'] = $r->userrid;
				$arrUserData[$group][$r->userrid]['fname'] = $r->userfirstname;
				$arrUserData[$group][$r->userrid]['lname'] = $r->userlastname;
				$arrUserData[$group][$r->userrid]['email'] = $r->emailemailaddress;
				$arrUserData[$group][$r->userrid]['phone'] = '&nbsp;';
				if(sizeof($r->phonenumber)>0){
					$arrUserData[$group][$r->userrid]['phone'] = $r->phonenumber[0]->phonephonenumber;
				}

				// add communities in which user has a role
				$arrUserData[$group][$r->userrid]['stewardRoles'] = array();
				$arrUserData[$group][$r->userrid]['custodianRoles'] = array();
				foreach($communities[0]->Subcommunities as $c){
					foreach($c->Role8a0a6c89106c4adb9936f09f29b747ac as $role){
						if($role->userRole8a0a6c89106c4adb9936f09f29b747acrid == $r->userrid){
							$this->getParentCommunities($communities, $c->parentCommunityId, $c);
							array_push($arrUserData[$group][$r->userrid]['stewardRoles'], $c);
						}
					}
					foreach($c->Rolef86d1d3abc2e4beeb17fe0e9985d5afb as $role){
						if($role->userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid == $r->userrid){
							$this->getParentCommunities($communities, $c->parentCommunityId, $c);
							array_push($arrUserData[$group][$r->userrid]['custodianRoles'], $c);
						}
					}
				}

				//get vocabularyid for steward and custodian roles
				foreach($arrUserData[$group][$r->userrid]['stewardRoles'] as $roleKey => $role){
					if($role->hasNonMetaChildren == 'true'){
						foreach($communities[1]->Vocabularies as $vocab){
							if($role->subcommunityid == $vocab->vocabularyParentCommunityId){
								$role->vocabulary = $vocab->vocabulary;
								$role->vocabularyid = $vocab->vocabularyid;
								break;
							}
						}
						if(!isset($role->vocabularyid)){	//if $role->hasNonMetaChildren but isn't a vocab it's a nested subcommunity, which we need to get rid of for the view
							unset($arrUserData[$group][$r->userrid]['stewardRoles'][$roleKey]);
						}
					}
				}
				foreach($arrUserData[$group][$r->userrid]['custodianRoles'] as $roleKey => $role){
					if($role->hasNonMetaChildren == 'true'){
						foreach($communities[1]->Vocabularies as $vocab){
							if($role->subcommunityid == $vocab->vocabularyParentCommunityId){
								$role->vocabulary = $vocab->vocabulary;
								$role->vocabularyid = $vocab->vocabularyid;
								break;
							}
						}
						if(!isset($role->vocabularyid)){
							unset($arrUserData[$group][$r->userrid]['custodianRoles'][$roleKey]);
						}
					}
				}
			}
		}

		$stewardDefinition = $this->CollibraAPI->getTermDefinition(Configure::read('Collibra.term.steward'));
		$custodianDefinition = $this->CollibraAPI->getTermDefinition(Configure::read('Collibra.term.custodian'));

		$this->set('stewardDef', $stewardDefinition);
		$this->set('custodianDef', $custodianDefinition);

		$this->set('communities', $parentCommunities);
		$this->set('userData', $arrUserData);
	}

	public function lookup(){
		// get all parent communities for left nav
		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->get('community/'.Configure::read('Collibra.community.byu'));
		$parentCommunities = json_decode($resp);
		usort($parentCommunities->subCommunityReferences->communityReference, 'self::sortCommunities');

		$query = 'glossary';
		if ($this->request->is('post')) {
			// search in all communities, vocabularies and users
			//=================================================================
			if(isset($this->request->data['query'])){
				$query = $this->request->data['query'];
				$query = preg_replace('/[^ \w]+/', '', $query);
			}

			$request = '{"query":"'.$query.'*", "filter": { "community": ["'.Configure::read('Collibra.community.byu').'"], "category":["CO", "VC", "UR"], "vocabulary":[], "type":{"asset":[]},';
			if(!Configure::read('allowUnapprovedTerms')){
				$request .= '"status": ["00000000-0000-0000-0000-000000005009"], ';
			}
			$request .= '"includeMeta":false}, "fields":["name","attributes"], "order":{"by":"score","sort": "desc"}, "limit":500, "offset":0, "highlight":false}';

			$searchResp = $this->CollibraAPI->postJSON('search', $request);
			$searchResp = json_decode($searchResp);
			//=================================================================

			// build array to hold users and communities found
			$arrUserResults = array();
			$arrCommunityResults = array();
			foreach($searchResp->results as $r){
				if($r->name->type == 'UR'){
					array_push($arrUserResults, $r->name->id);
				}
				if($r->name->type == 'CO'){
					array_push($arrCommunityResults, $r->name->id);
				}
				if($r->name->type == 'VC'){
					// for vocabularies, add parent community to arrCommunityResults
					array_push($arrCommunityResults, $r->context->id);
				}
			}

			$communities = $this->CollibraAPI->getAllCommunities();
			$users = $this->CollibraAPI->getUserData();

			$arrUserData = array();
			foreach($users as $r){
				if(in_array($r->userrid, $arrUserResults)){
					$group = substr($r->userlastname,0,1);
					if(!isset($arrUserData[$group])){
						$arrUserData[$group] = array();
					}

					if(!isset($arrUserData[$group][$r->userrid])){
						$arrUserData[$group][$r->userrid]['id'] = $r->userrid;
						$arrUserData[$group][$r->userrid]['fname'] = $r->userfirstname;
						$arrUserData[$group][$r->userrid]['lname'] = $r->userlastname;
						$arrUserData[$group][$r->userrid]['email'] = $r->emailemailaddress;
						$arrUserData[$group][$r->userrid]['phone'] = '&nbsp;';
						if(sizeof($r->phonenumber)>0){
							$arrUserData[$group][$r->userrid]['phone'] = $r->phonenumber[0]->phonephonenumber;
						}

						// add communities in which user has a role
						$arrUserData[$group][$r->userrid]['stewardRoles'] = array();
						$arrUserData[$group][$r->userrid]['custodianRoles'] = array();
						foreach($communities[0]->Subcommunities as $c){
							foreach($c->Role8a0a6c89106c4adb9936f09f29b747ac as $role){
								if($role->userRole8a0a6c89106c4adb9936f09f29b747acrid == $r->userrid){
									$this->getParentCommunities($communities, $c->parentCommunityId, $c);
									array_push($arrUserData[$group][$r->userrid]['stewardRoles'], $c);
								}
							}
							foreach($c->Rolef86d1d3abc2e4beeb17fe0e9985d5afb as $role){
								if($role->userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid == $r->userrid){
									$this->getParentCommunities($communities, $c->parentCommunityId, $c);
									array_push($arrUserData[$group][$r->userrid]['custodianRoles'], $c);
								}
							}
						}

						//get vocabularyid for steward and custodian roles
						foreach($arrUserData[$group][$r->userrid]['stewardRoles'] as $roleKey => $role){
							if($role->hasNonMetaChildren == 'true'){
								foreach($communities[1]->Vocabularies as $vocab){
									if($role->subcommunityid == $vocab->vocabularyParentCommunityId){
										$role->vocabulary = $vocab->vocabulary;
										$role->vocabularyid = $vocab->vocabularyid;
										break;
									}
								}
								if(!isset($role->vocabularyid)){	//if $role->hasNonMetaChildren but isn't a vocab it's a nested subcommunity, which we need to get rid of for the view
									unset($arrUserData[$group][$r->userrid]['stewardRoles'][$roleKey]);
								}
							}
						}
						foreach($arrUserData[$group][$r->userrid]['custodianRoles'] as $roleKey => $role){
							if($role->hasNonMetaChildren == 'true'){
								foreach($communities[1]->Vocabularies as $vocab){
									if($role->subcommunityid == $vocab->vocabularyParentCommunityId){
										$role->vocabulary = $vocab->vocabulary;
										$role->vocabularyid = $vocab->vocabularyid;
										break;
									}
								}
								if(!isset($role->vocabularyid)){
									unset($arrUserData[$group][$r->userrid]['custodianRoles'][$roleKey]);
								}
							}
						}
					}
				}
			}

			// loop through all communities from above and filter out those not found
			// in our first search results
			//=================================================================
			$tmpCommunities = array();
			foreach($communities[0]->Subcommunities as $c){
				$include = false;
				if($c->parentCommunityId != Configure::read('Collibra.community.byu')){
					// add community based on previous search resulta
					if(in_array($c->subcommunityid, $arrCommunityResults)){
						$include = true;
					}
					// add community based on custodian
					if(count($c->Rolef86d1d3abc2e4beeb17fe0e9985d5afb)>0){
						if(in_array($c->Rolef86d1d3abc2e4beeb17fe0e9985d5afb[0]->userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid, $arrUserResults)){
							//$include = true;
						}
					}
					// add community based on steward
					if(count($c->Role8a0a6c89106c4adb9936f09f29b747ac)>0){
						if(in_array($c->Role8a0a6c89106c4adb9936f09f29b747ac[0]->userRole8a0a6c89106c4adb9936f09f29b747acrid, $arrUserResults)){
							//$include = true;
						}
					}

					// look for user in the $users array and insert their details
					foreach($users as $u){
						if(count($c->Rolef86d1d3abc2e4beeb17fe0e9985d5afb)>0){
							$custodianUserID = $c->Rolef86d1d3abc2e4beeb17fe0e9985d5afb[0]->userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid;
							if($u->userrid == $custodianUserID){
								$c->custodian = $u;
							}
						}

						if(count($c->Role8a0a6c89106c4adb9936f09f29b747ac)>0){
							$stewardUserID = $c->Role8a0a6c89106c4adb9936f09f29b747ac[0]->userRole8a0a6c89106c4adb9936f09f29b747acrid;
							if($u->userrid == $stewardUserID){
								$c->steward = $u;
							}
						}
					}
				}

				if($include) array_push($tmpCommunities, $c);
				// add parent communities
				$this->getParentCommunities($communities, $c->parentCommunityId, $c);
			}
			$communities[0]->Subcommunities = $tmpCommunities;

			//print_r($communities);
			//exit;
			//=================================================================
		}

		// get definitions for steward and custodian terms
		$stewardDefinition = $this->CollibraAPI->getTermDefinition(Configure::read('Collibra.term.steward'));
		$custodianDefinition = $this->CollibraAPI->getTermDefinition(Configure::read('Collibra.term.custodian'));

		$this->set('stewardDef', $stewardDefinition);
		$this->set('custodianDef', $custodianDefinition);
		$this->set('communities', $communities);
		$this->set('parentCommunities', $parentCommunities);
		$this->set('userData', $arrUserData);
		$this->set('query', $query);
	}

	public function dept() {
		// get all parent communities for left nav
		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->get('community/'.Configure::read('Collibra.community.byu').'/sub-communities');
		$parentCommunities = json_decode($resp);
		usort($parentCommunities->communityReference, 'self::sortCommunities');

		// get community from querystring or set as first community from array above
		if(isset($this->request->query['c']) && $this->request->query['c'] != ''){
			$community = htmlspecialchars($this->request->query['c']);
		}else{
			$community = $parentCommunities->communityReference[0]->resourceId;
		}

		$communities = $this->CollibraAPI->getAllCommunities();

		// run through recursive function to return array with only the sub communities for the selected community
		$arrCommunities = $this->getSubCommunities($communities, $community);

		// build data array for navigation
		$arrNavDomainData = array();
		for($i=0; $i<sizeof($arrCommunities); $i++){
			$communityName = $arrCommunities[$i][0]->subcommunity;
			$arr = array();
			$arr['name'] = $communityName;
			$arr['id'] = $arrCommunities[$i][0]->subcommunityid;

			if(sizeof($arrCommunities[$i])>1){
				$arr['subdomains']  = $arrCommunities[$i][1];
			}

			array_push($arrNavDomainData, $arr);
		}

		// build data array for directory/role listing
		$arrDomainData = array(array());
		for($i=0; $i<sizeof($arrCommunities); $i++){
			$communityName = $arrCommunities[$i][0]->subcommunity;
			if(sizeof($arrCommunities[$i])>1){
				for($j=1; $j<sizeof($arrCommunities[$i]); $j++){
					$sc = $arrCommunities[$i][$j];
					for($k=0; $k<sizeof($sc); $k++){
						$arr = array();
						$arr['name'] = $communityName.' <span class="arrow-separator">&gt;</span> '.$ssc = $arrCommunities[$i][$j][$k][0]->subcommunity;
						$arr['id'] = $arrCommunities[$i][$j][$k][0]->subcommunityid;
						$arr['description'] = $arrCommunities[$i][$j][$k][0]->description;
						//$arr['trustee'] = $arrCommunities[$i][$j][$k][0]->Rolefb97305e00c0459a84cbb3f5ea62d411g; // Trustee
						$arr['steward'] = $arrCommunities[$i][$j][$k][0]->Role8a0a6c89106c4adb9936f09f29b747ac;  // Steward
						$arr['custodian'] = $arrCommunities[$i][$j][$k][0]->Rolef86d1d3abc2e4beeb17fe0e9985d5afb; // Custodian
						array_push($arrDomainData, $arr);
					}
				}
			}else{
				$arr = array();
				$arr['name'] = $communityName;
				$arr['id'] = $arrCommunities[$i][0]->subcommunityid;
				$arr['description'] = $arrCommunities[$i][0]->description;
				//$arr['trustee'] = $arrCommunities[$i][0]->Rolefb97305e00c0459a84cbb3f5ea62d411g; // Trustee
				$arr['steward'] = $arrCommunities[$i][0]->Role8a0a6c89106c4adb9936f09f29b747ac;  // Steward
				$arr['custodian'] = $arrCommunities[$i][0]->Rolef86d1d3abc2e4beeb17fe0e9985d5afb; // Custodian
				array_push($arrDomainData, $arr);
			}
		}

		$users = $this->CollibraAPI->getUserData();

		// load additional information for users
		$arrUserDetails = array();
		array_shift($arrDomainData);
		for($i=0; $i<sizeof($arrDomainData); $i++){
			foreach($users as $u){
				if(count($arrDomainData[$i]['steward']) > 0){
					$userrid = $arrDomainData[$i]['steward'][0]->userRole8a0a6c89106c4adb9936f09f29b747acrid;
					if($u->userrid == $userrid){
						$arrDomainData[$i]['stewardData'] = $u;
					}
				}

				if(count($arrDomainData[$i]['custodian']) > 0){
					$userrid = $arrDomainData[$i]['custodian'][0]->userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid;
					if($u->userrid == $userrid){
						$arrDomainData[$i]['custodianData'] = $u;
					}
				}
			}
			foreach($communities[1]->Vocabularies as $vocab){
				if($arrDomainData[$i]['id'] == $vocab->vocabularyParentCommunityId && strpos($vocab->vocabulary, 'Glossary') !== false){
					$arrDomainData[$i]['vocabulary'] = $vocab->vocabulary;
					$arrDomainData[$i]['vocabularyid'] = $vocab->vocabularyid;
					break;
				}
			}
		}

		//////////////////////////////
		//////////////////////////////

		$stewardDefinition = $this->CollibraAPI->getTermDefinition(Configure::read('Collibra.term.steward'));
		$custodianDefinition = $this->CollibraAPI->getTermDefinition(Configure::read('Collibra.term.custodian'));

		$this->set('communities', $parentCommunities);
		$this->set('navDomains', $arrNavDomainData);
		$this->set('domains', $arrDomainData);
		$this->set('community', $community);
		$this->set('stewardDef', $stewardDefinition);
		$this->set('custodianDef', $custodianDefinition);
	}
}

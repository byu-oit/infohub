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
			foreach($communityData->aaData[0]->Subcommunities as $co){
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
		foreach($json->aaData[0]->Subcommunities as $sc){
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

		// get user data for specified user groups including their phone and email
		$resp = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Group":{"name":"groupname","Columns":[{"Column":{"fieldName":"groupgroupname"}},{"Column":{"fieldName":"grouprid"}}]}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Group":{"Groupname":{"name":"groupgroupname"},"Id":{"name":"grouprid"}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"}],"Filter":{"AND":[{"OR":[{"Field":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid","operator":"NOT_NULL"}},{"Field":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid","operator":"NOT_NULL"}},]},{"AND":[{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}]}]}}},"Order":[{"Field":{"name":"userlasttname","order":"ASC"}}],"displayStart":0,"displayLength":1000}}'
		);
		$resp = json_decode($resp);
		usort($resp->aaData, 'self::sortUsers');

		// get all communities in the system with their steward and custodian
		$communities = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":500}}'
		);
		$communities = json_decode($communities);

		$arrUserData = array();
		$letterGroup = '0';
		foreach($resp->aaData as $r){
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
				foreach($communities->aaData[0]->Subcommunities as $c){
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
						foreach($communities->aaData[1]->Vocabularies as $vocab){
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
						foreach($communities->aaData[1]->Vocabularies as $vocab){
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
		usort($parentCommunities->communityReference, 'self::sortCommunities');

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

			// get all communities in the system along with their custodian and steward info
			$communities = $this->CollibraAPI->postJSON(
					'output/data_table',
					'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":500}}'
			);
			$communities = json_decode($communities);

			// get all users including their contact info
			$users = $this->CollibraAPI->postJSON(
					'output/data_table',
					'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Group":{"Groupname":{"name":"groupgroupname"},"Id":{"name":"grouprid"},"Filter":{"AND":[{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000001"}},{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000002"}}]}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"}],"Filter":{"AND":[{"OR":[{"Field":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid","operator":"NOT_NULL"}},{"Field":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid","operator":"NOT_NULL"}},]},{"AND":[{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}]}]}}},"Order":[{"Field":{"name":"userlasttname","order":"ASC"}}],"displayStart":0,"displayLength":1000}}'
			);
			$users = json_decode($users);

			$arrUserData = array();
			foreach($users->aaData as $r){
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
						foreach($communities->aaData[0]->Subcommunities as $c){
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
								foreach($communities->aaData[1]->Vocabularies as $vocab){
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
								foreach($communities->aaData[1]->Vocabularies as $vocab){
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
			foreach($communities->aaData[0]->Subcommunities as $c){
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
					foreach($users->aaData as $u){
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
			$communities->aaData[0]->Subcommunities = $tmpCommunities;

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

		// get all communities in the system
		$resp = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Column":{"fieldName":"description"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"Description":{"name":"description"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":500}}'
		);
		$resp = json_decode($resp);

		// run through recursive function to return array with only the sub communities for the selected community
		$arrCommunities = $this->getSubCommunities($resp, $community);

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

		// get all users including their contact info
		$users = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Filter":{"AND":[{"AND":[{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}]}]}}},"Order":[{"Field":{"name":"userlasttname","order":"ASC"}}],"displayStart":0,"displayLength":1000}}'
		);
		$users = json_decode($users);


		// load additional information for users
		$arrUserDetails = array();
		array_shift($arrDomainData);
		for($i=0; $i<sizeof($arrDomainData); $i++){
			foreach($users->aaData as $u){
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
			foreach($resp->aaData[1]->Vocabularies as $vocab){
				if($arrDomainData[$i]['id'] == $vocab->vocabularyParentCommunityId && $vocab->domainType == 'Glossary'){
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

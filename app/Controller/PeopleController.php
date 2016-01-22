<?php

class PeopleController extends AppController {
	private static function sortCommunities($a, $b){
		return strcmp($a->name, $b->name);
	}
	
	private static function sortUsers($a, $b){
		return strcmp($a->userlastname, $b->userlastname);
	}

	private function getParentCommunities($communityData, $parentID, $objCommunity, $level=0){
		if($parentID != Configure::read('byuCommunity')){
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
		$objCollibra = new CollibraAPI();
		$resp = $objCollibra->request(
			array('url'=>'community/'.Configure::read('byuCommunity').'/sub-communities')
		);
		$parentCommunities = json_decode($resp);
		usort($parentCommunities->communityReference, 'self::sortCommunities');
		
		// get user data for specified user groups including their phone and email
		$objCollibra = new CollibraAPI();
		$resp = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				'post'=>true,
				'json'=>true,
				'params'=>'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Group":{"name":"groupname","Columns":[{"Column":{"fieldName":"groupgroupname"}},{"Column":{"fieldName":"grouprid"}}]}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Group":{"Groupname":{"name":"groupgroupname"},"Id":{"name":"grouprid"},"Filter":{"AND":[{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000001"}},{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000002"}}]}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"}],"Filter":{"AND":[{"OR":[{"Field":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid","operator":"NOT_NULL"}},{"Field":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid","operator":"NOT_NULL"}},]},{"AND":[{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}]}]}}},"Order":[{"Field":{"name":"userlasttname","order":"ASC"}}],"displayStart":0,"displayLength":1000}}'
			)
		);
		$resp = json_decode($resp);
		usort($resp->aaData, 'self::sortUsers');

		// get all communities in the system with their steward and custodian
		$communities = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				'post'=>true,
				'json'=>true,
				'params'=>'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"1b16f543-db2b-49c4-b576-663138286efe"}}'
			)
		);
		$communities = json_decode($communities);
		//print_r($communities);
		//exit;

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
			}
		}

		// get definitions for steward and custodian terms
		$resp = $objCollibra->request(array('url'=>'term/f6b8b3cd-373c-43d7-8cbd-d8f03ff9048b'));
		$stewardDefinition = json_decode($resp);
		$stewardDefinition = strip_tags($stewardDefinition->attributeReferences->attributeReference[3]->value);
		$resp = $objCollibra->request(array('url'=>'term/8ed41506-3eaf-47e1-b076-76bb49022059'));
		$custodianDefinition = json_decode($resp);
		$custodianDefinition = strip_tags($custodianDefinition->attributeReferences->attributeReference[2]->value);
		
		$this->set('stewardDef', $stewardDefinition);
		$this->set('custodianDef', $custodianDefinition);

		$this->set('communities', $parentCommunities);
		$this->set('userData', $arrUserData);
	}

	public function lookup(){
		// get all parent communities for left nav
		$this->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();
		$resp = $objCollibra->request(
			array('url'=>'community/'.Configure::read('byuCommunity').'/sub-communities')
		);
		$parentCommunities = json_decode($resp);
		usort($parentCommunities->communityReference, 'self::sortCommunities');
		
		//$query = 'glossary';
		if ($this->request->is('post')) {
			// search in all communities, vocabularies and users
			//=================================================================
			if(isset($this->request->data['query'])){
				$query = $this->request->data['query'];
				$query = preg_replace('/[^ \w]+/', '', $query);
			}
			
			$request = '{"query":"'.$query.'*", "filter": { "community": ["'.Configure::read('byuCommunity').'"], "category":["CO", "VC", "UR"], "vocabulary":[], "type":{"asset":[]},';
			if(!Configure::read('allowUnapprovedeTerms')){
				$request .= '"status": ["00000000-0000-0000-0000-000000005009"], ';
			}
			$request .= '"includeMeta":false}, "fields":["name","attributes"], "order":{"by":"score","sort": "desc"}, "limit":500, "offset":0, "highlight":false}';
			
			$searchResp = $objCollibra->request(
				array('url'=>'search', 'post'=>true, 'json'=>true, 'params'=>$request)
			);
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
			$communities = $objCollibra->request(
				array(
					'url'=>'output/data_table',
					'post'=>true,
					'json'=>true,
					'params'=>'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"parentCommunity"}},{"Column":{"fieldName":"description"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"},"Name":{"name":"parentCommunity"}},"Description":{"name":"description"},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"parentCommunity","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"1b16f543-db2b-49c4-b576-663138286efe"}}'
					//'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"1b16f543-db2b-49c4-b576-663138286efe"}}'
				)
			);
			$communities = json_decode($communities);

			// get all users including their contact info
			$users = $objCollibra->request(
				array(
					'url'=>'output/data_table',
					'post'=>true,
					'json'=>true,
					'params'=>'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Group":{"Groupname":{"name":"groupgroupname"},"Id":{"name":"grouprid"},"Filter":{"AND":[{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000001"}},{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000002"}}]}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"}],"Filter":{"AND":[{"OR":[{"Field":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid","operator":"NOT_NULL"}},{"Field":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid","operator":"NOT_NULL"}},]},{"AND":[{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}]}]}}},"Order":[{"Field":{"name":"userlasttname","order":"ASC"}}],"displayStart":0,"displayLength":1000}}'
				)
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
					}
				}
			}
			
			// loop through all communities from above and filter out those not found
			// in our first search results
			//=================================================================
			$tmpCommunities = array();
			foreach($communities->aaData[0]->Subcommunities as $c){
				$include = false;
				if($c->parentCommunityId != Configure::read('byuCommunity')){
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
		$resp = $objCollibra->request(array('url'=>'term/f6b8b3cd-373c-43d7-8cbd-d8f03ff9048b'));
		$stewardDefinition = json_decode($resp);
		$stewardDefinition = strip_tags($stewardDefinition->attributeReferences->attributeReference[3]->value);
		$resp = $objCollibra->request(array('url'=>'term/8ed41506-3eaf-47e1-b076-76bb49022059'));
		$custodianDefinition = json_decode($resp);
		$custodianDefinition = strip_tags($custodianDefinition->attributeReferences->attributeReference[2]->value);
		
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
		$objCollibra = new CollibraAPI();
		$resp = $objCollibra->request(
			array('url'=>'community/'.Configure::read('byuCommunity').'/sub-communities')
		);
		$parentCommunities = json_decode($resp);
		usort($parentCommunities->communityReference, 'self::sortCommunities');
		
		// get community from querystring or set as first community from array above
		if(isset($this->request->query['c']) && $this->request->query['c'] != ''){
			$community = htmlspecialchars($this->request->query['c']);
		}else{
			$community = $parentCommunities->communityReference[0]->resourceId;
		}
		//$community = '4e756e1e-11ee-4d1e-bfaa-fb0ada974fc5';
		
		// get all communities in the system
		$resp = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				'post'=>true,
				'json'=>true,
				'params'=>'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Column":{"fieldName":"description"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"Description":{"name":"description"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"1b16f543-db2b-49c4-b576-663138286efe"}}'
				//'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"8a0a6c89-106c-4adb-9936-f09f29b747ac","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldId":"fb97305e-00c0-459a-84cb-b3f5ea62d411","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"f86d1d3a-bc2e-4bee-b17f-e0e9985d5afb"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"8a0a6c89-106c-4adb-9936-f09f29b747ac"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"fb97305e-00c0-459a-84cb-b3f5ea62d411"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"1b16f543-db2b-49c4-b576-663138286efe"}}'
			)
		);
		$resp = json_decode($resp);
		
		// run through recursive function to return array with only the sub communities for the selected community
		$arrCommunities = $this->getSubCommunities($resp, $community);
		//print_r($arrCommunities);
		//exit;
		
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
		$users = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				'post'=>true,
				'json'=>true,
				'params'=>'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Filter":{"AND":[{"AND":[{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}]}]}}},"Order":[{"Field":{"name":"userlasttname","order":"ASC"}}],"displayStart":0,"displayLength":1000}}'
			)
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
		}
		//print_r($arrDomainData);
		//exit;
		
		//////////////////////////////
		//////////////////////////////
		
		// get definitions for steward and custodian terms

		$jsonResp1 = $objCollibra->request(array('url'=>'term/f6b8b3cd-373c-43d7-8cbd-d8f03ff9048b'));
		$stewardDefinition = json_decode($jsonResp1);
		$stewardDefinition = strip_tags($stewardDefinition->attributeReferences->attributeReference[3]->value);

		$jsonResp2 = $objCollibra->request(array('url'=>'term/8ed41506-3eaf-47e1-b076-76bb49022059'));
		$custodianDefinition = json_decode($jsonResp2);
		$custodianDefinition = strip_tags($custodianDefinition->attributeReferences->attributeReference[2]->value);
		
		$this->set('communities', $parentCommunities);
		$this->set('navDomains', $arrNavDomainData);
		$this->set('domains', $arrDomainData);
		$this->set('community', $community);
		$this->set('stewardDef', $stewardDefinition);
		$this->set('custodianDef', $custodianDefinition);
	}
}
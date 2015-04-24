<?php

class PeopleController extends AppController {
    private static function sortCommunities($a, $b){
        return strcmp($a->name, $b->name);
    }
    
    private static function sortUsers($a, $b){
        return strcmp($a->userlastname, $b->userlastname);
    }
    
    public function lookup(){
        // get all parent communities for left nav
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(
            array('url'=>'community/99582048-38e3-4149-a301-c6d54d8151c8/sub-communities')
        );
        $parentCommunities = json_decode($resp);
        usort($parentCommunities->communityReference, 'self::sortCommunities');
        
        $arrUserData = array();
        $fname = '';
        $lname = '';
        
        if ($this->request->is('post')) {
            if(isset($this->request->data['fname'])){
                $fname = $this->request->data['fname'];
            }
            if(isset($this->request->data['lname'])){
                $lname = $this->request->data['lname'];
            }
            
            // get user data for specified user groups
            $tableData = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Group":{"name":"groupname","Columns":[{"Column":{"fieldName":"groupgroupname"}},{"Column":{"fieldName":"grouprid"}}]}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Group":{"Groupname":{"name":"groupgroupname"},"Id":{"name":"grouprid"},"Filter":{"AND":[{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000001"}},{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000002"}}]}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Filter":{"AND":['.
                '{"OR":['.
                '{"Field":{"name":"grouprid","operator":"EQUALS","value":"6c4fd1f0-aee0-4635-8d8c-9047be385370"}},'.    //Info Trustees group
                '{"Field":{"name":"grouprid","operator":"EQUALS","value":"e39aea6e-f25c-4917-acba-dfa9270555f1"}},'.    //Info Custodians group
                '{"Field":{"name":"grouprid","operator":"EQUALS","value":"f1323abe-c668-465b-8677-8ff881119028"}}'.    //Info Stewards group
                ']},{"OR":[';
            if($fname != ''){
                $tableData .= '{"Field":{"name":"userfirstname","operator":"INCLUDES","value":"'.$fname.'"}}';
            }
            if($lname != ''){
                if($fname != '') $tableData .= ',';
                $tableData .= '{"Field":{"name":"userlastname","operator":"INCLUDES","value":"'.$lname.'"}}';
            }
            $tableData .= ']},'.
                '{"AND":['.
                    '{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}'. // make sure user is active
                ']}'.
                ']}}},"Order":[{"Field":{"name":"userlasttname","order":"DESC"}}],"displayStart":0,"displayLength":50}}';
                
            $resp = $objCollibra->request(
                array(
                    'url'=>'output/data_table',
                    'post'=>true,
                    'json'=>true,
                    'params'=>$tableData
                )
            );
            
            $resp = json_decode($resp);
            //print_r($resp);
            //exit;
            if(isset($resp->aaData)){
                usort($resp->aaData, 'self::sortUsers');

                $group = 'Search Results';
                $arrUserData[$group] = array();
                foreach($resp->aaData as $r){
                    if(!isset($arrUserData[$group][$r->userrid])){
                        $arrUserData[$group][$r->userrid]['id'] = $r->userrid;
                        $arrUserData[$group][$r->userrid]['fname'] = $r->userfirstname;
                        $arrUserData[$group][$r->userrid]['lname'] = $r->userlastname;
                        $arrUserData[$group][$r->userrid]['email'] = $r->emailemailaddress;
                        $arrUserData[$group][$r->userrid]['phone'] = '&nbsp;';
                        if(sizeof($r->phonenumber)>0){
                            $arrUserData[$group][$r->userrid]['phone'] = $r->phonenumber[0]->phonephonenumber;
                        }
                    }
                }
            }
        }
        
        $this->set('communities', $parentCommunities);
        $this->set('userData', $arrUserData);
        $this->set('fname', $fname);
        $this->set('lname', $lname);
        //print_r($arrUserData);
        //exit;
    }
    
    public function index(){
        // get all parent communities for left nav
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(
            array('url'=>'community/99582048-38e3-4149-a301-c6d54d8151c8/sub-communities')
        );
        $parentCommunities = json_decode($resp);
        usort($parentCommunities->communityReference, 'self::sortCommunities');
        
        // get user data for specified user groups
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(
            array(
                'url'=>'output/data_table',
                'post'=>true,
                'json'=>true,
                'params'=>'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"userrid"}},{"Column":{"fieldName":"userenabled"}},{"Column":{"fieldName":"userfirstname"}},{"Group":{"name":"groupname","Columns":[{"Column":{"fieldName":"groupgroupname"}},{"Column":{"fieldName":"grouprid"}}]}},{"Column":{"fieldName":"userlastname"}},{"Column":{"fieldName":"emailemailaddress"}},{"Group":{"name":"phonenumber","Columns":[{"Column":{"fieldName":"phonephonenumber"}},{"Column":{"fieldName":"phonerid"}}]}},{"Column":{"fieldName":"useractivated"}},{"Column":{"fieldName":"isuserldap"}}],"Resources":{"User":{"Enabled":{"name":"userenabled"},"UserName":{"name":"userusername"},"FirstName":{"name":"userfirstname"},"LastName":{"name":"userlastname"},"Emailaddress":{"name":"emailemailaddress"},"Phone":{"Phonenumber":{"name":"phonephonenumber"},"Id":{"name":"phonerid"}},"Group":{"Groupname":{"name":"groupgroupname"},"Id":{"name":"grouprid"},"Filter":{"AND":[{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000001"}},{"FIELD":{"name":"grouprid","operator":"NOT_EQUALS","value":"00000000-0000-0000-0000-000001000002"}}]}},"Activated":{"name":"useractivated"},"LDAPUser":{"name":"isuserldap"},"Id":{"name":"userrid"},"Filter":{"AND":['.
                    '{"OR":['.
                        '{"Field":{"name":"grouprid","operator":"EQUALS","value":"6c4fd1f0-aee0-4635-8d8c-9047be385370"}},'.    //Info Trustees group
                        '{"Field":{"name":"grouprid","operator":"EQUALS","value":"e39aea6e-f25c-4917-acba-dfa9270555f1"}},'.    //Info Custodians group
                        '{"Field":{"name":"grouprid","operator":"EQUALS","value":"f1323abe-c668-465b-8677-8ff881119028"}},'.    //Info Stewards group
                        //'{"Field":{"name":"grouprid","operator":"EQUALS","value":"44b9f82a-043b-433f-82f1-7192e5b17f27"}}'.
                    ']},'.
                    '{"AND":['.
                        '{"Field":{"name":"userenabled","operator":"EQUALS","value":"true"}}'. // make sure user is active
                    ']}'.
                    ']}}},"Order":[{"Field":{"name":"userlasttname","order":"DESC"}}],"displayStart":0,"displayLength":50}}'
            )
        );
        $resp = json_decode($resp);
        usort($resp->aaData, 'self::sortUsers');
        
        //print_r($resp);
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
            }
        }
        
        $this->set('communities', $parentCommunities);
        $this->set('userData', $arrUserData);
        //print_r($arrUserData);
        //exit;
    }
    
    public function dept() {
        // get all parent communities for left nav
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(
            array('url'=>'community/99582048-38e3-4149-a301-c6d54d8151c8/sub-communities')
        );
        $parentCommunities = json_decode($resp);
        usort($parentCommunities->communityReference, 'self::sortCommunities');
        
        // get community from querystring or set as first community from array above
        if(isset($this->request->query['c']) && $this->request->query['c'] != ''){
            $community = $this->request->query['c'];
        }else{
            $community = $parentCommunities->communityReference[0]->resourceId;
        }
        
        // load all domains for parent community
        $resp = $objCollibra->request(
            array(
                'url'=>'output/data_table',
                'post'=>true,
                'json'=>true,
                'params'=>'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldId":"00000000-0000-0000-0000-000000005015","fieldName":"userRole00000000000000000000000000005015rid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015gender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015fn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015ln"}}],"name":"Role00000000000000000000000000005015"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015grid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015ggn"}}],"name":"Role00000000000000000000000000005015g"}},{"Group":{"Columns":[{"Column":{"label":"Information Custodian User ID","fieldId":"7865d2c3-405a-459d-b11e-df061adec84b","fieldName":"userRole7865d2c3405a459db11edf061adec84brid"}},{"Column":{"label":"Information Custodian Gender","fieldName":"userRole7865d2c3405a459db11edf061adec84bgender"}},{"Column":{"label":"Information Custodian First Name","fieldName":"userRole7865d2c3405a459db11edf061adec84bfn"}},{"Column":{"label":"Information Custodian Last Name","fieldName":"userRole7865d2c3405a459db11edf061adec84bln"}}],"name":"Role7865d2c3405a459db11edf061adec84b"}},{"Group":{"Columns":[{"Column":{"label":"Information Custodian Group ID","fieldName":"groupRole7865d2c3405a459db11edf061adec84bgrid"}},{"Column":{"label":"Information Custodian Group Name","fieldName":"groupRole7865d2c3405a459db11edf061adec84bggn"}}],"name":"Role7865d2c3405a459db11edf061adec84bg"}},{"Group":{"Columns":[{"Column":{"label":"Information Security Officer User ID","fieldId":"6fe7db77-92bb-4321-988a-884614c1601c","fieldName":"userRole6fe7db7792bb4321988a884614c1601crid"}},{"Column":{"label":"Information Security Officer Gender","fieldName":"userRole6fe7db7792bb4321988a884614c1601cgender"}},{"Column":{"label":"Information Security Officer First Name","fieldName":"userRole6fe7db7792bb4321988a884614c1601cfn"}},{"Column":{"label":"Information Security Officer Last Name","fieldName":"userRole6fe7db7792bb4321988a884614c1601cln"}}],"name":"Role6fe7db7792bb4321988a884614c1601c"}},{"Group":{"Columns":[{"Column":{"label":"Information Security Officer Group ID","fieldName":"groupRole6fe7db7792bb4321988a884614c1601cgrid"}},{"Column":{"label":"Information Security Officer Group Name","fieldName":"groupRole6fe7db7792bb4321988a884614c1601cggn"}}],"name":"Role6fe7db7792bb4321988a884614c1601cg"}},{"Group":{"Columns":[{"Column":{"label":"Information Steward User ID","fieldId":"61582918-6613-486a-9d82-61f7d1c0c014","fieldName":"userRole615829186613486a9d8261f7d1c0c014rid"}},{"Column":{"label":"Information Steward Gender","fieldName":"userRole615829186613486a9d8261f7d1c0c014gender"}},{"Column":{"label":"Information Steward First Name","fieldName":"userRole615829186613486a9d8261f7d1c0c014fn"}},{"Column":{"label":"Information Steward Last Name","fieldName":"userRole615829186613486a9d8261f7d1c0c014ln"}}],"name":"Role615829186613486a9d8261f7d1c0c014"}},{"Group":{"Columns":[{"Column":{"label":"Information Steward Group ID","fieldName":"groupRole615829186613486a9d8261f7d1c0c014grid"}},{"Column":{"label":"Information Steward Group Name","fieldName":"groupRole615829186613486a9d8261f7d1c0c014ggn"}}],"name":"Role615829186613486a9d8261f7d1c0c014g"}},{"Group":{"Columns":[{"Column":{"label":"Information Trustee User ID","fieldId":"734327ec-9eaa-4132-9fea-1a4f2b9568dd","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddrid"}},{"Column":{"label":"Information Trustee Gender","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddgender"}},{"Column":{"label":"Information Trustee First Name","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddfn"}},{"Column":{"label":"Information Trustee Last Name","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddln"}}],"name":"Role734327ec9eaa41329fea1a4f2b9568dd"}},{"Group":{"Columns":[{"Column":{"label":"Information Trustee Group ID","fieldName":"groupRole734327ec9eaa41329fea1a4f2b9568ddgrid"}},{"Column":{"label":"Information Trustee Group Name","fieldName":"groupRole734327ec9eaa41329fea1a4f2b9568ddggn"}}],"name":"Role734327ec9eaa41329fea1a4f2b9568ddg"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldId":"00000000-0000-0000-0000-000000005018","fieldName":"userRole00000000000000000000000000005018rid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018gender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018fn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018ln"}}],"name":"Role00000000000000000000000000005018"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018grid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018ggn"}}],"name":"Role00000000000000000000000000005018g"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldId":"00000000-0000-0000-0000-000000005015","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXrid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXgender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXfn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005015VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005015gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Custodian User ID","fieldId":"7865d2c3-405a-459d-b11e-df061adec84b","fieldName":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXrid"}},{"Column":{"label":"Information Custodian Gender","fieldName":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXgender"}},{"Column":{"label":"Information Custodian First Name","fieldName":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXfn"}},{"Column":{"label":"Information Custodian Last Name","fieldName":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXln"}}],"name":"Role7865d2c3405a459db11edf061adec84bVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Custodian Group ID","fieldName":"groupRole7865d2c3405a459db11edf061adec84bgVOCSUFFIXrid"}},{"Column":{"label":"Information Custodian Group Name","fieldName":"groupRole7865d2c3405a459db11edf061adec84bgVOCSUFFIXgn"}}],"name":"Role7865d2c3405a459db11edf061adec84bgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Security Officer User ID","fieldId":"6fe7db77-92bb-4321-988a-884614c1601c","fieldName":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXrid"}},{"Column":{"label":"Information Security Officer Gender","fieldName":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXgender"}},{"Column":{"label":"Information Security Officer First Name","fieldName":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXfn"}},{"Column":{"label":"Information Security Officer Last Name","fieldName":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXln"}}],"name":"Role6fe7db7792bb4321988a884614c1601cVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Security Officer Group ID","fieldName":"groupRole6fe7db7792bb4321988a884614c1601cgVOCSUFFIXrid"}},{"Column":{"label":"Information Security Officer Group Name","fieldName":"groupRole6fe7db7792bb4321988a884614c1601cgVOCSUFFIXgn"}}],"name":"Role6fe7db7792bb4321988a884614c1601cgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Steward User ID","fieldId":"61582918-6613-486a-9d82-61f7d1c0c014","fieldName":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXrid"}},{"Column":{"label":"Information Steward Gender","fieldName":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXgender"}},{"Column":{"label":"Information Steward First Name","fieldName":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXfn"}},{"Column":{"label":"Information Steward Last Name","fieldName":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXln"}}],"name":"Role615829186613486a9d8261f7d1c0c014VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Steward Group ID","fieldName":"groupRole615829186613486a9d8261f7d1c0c014gVOCSUFFIXrid"}},{"Column":{"label":"Information Steward Group Name","fieldName":"groupRole615829186613486a9d8261f7d1c0c014gVOCSUFFIXgn"}}],"name":"Role615829186613486a9d8261f7d1c0c014gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Trustee User ID","fieldId":"734327ec-9eaa-4132-9fea-1a4f2b9568dd","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXrid"}},{"Column":{"label":"Information Trustee Gender","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXgender"}},{"Column":{"label":"Information Trustee First Name","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXfn"}},{"Column":{"label":"Information Trustee Last Name","fieldName":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXln"}}],"name":"Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Information Trustee Group ID","fieldName":"groupRole734327ec9eaa41329fea1a4f2b9568ddgVOCSUFFIXrid"}},{"Column":{"label":"Information Trustee Group Name","fieldName":"groupRole734327ec9eaa41329fea1a4f2b9568ddgVOCSUFFIXgn"}}],"name":"Role734327ec9eaa41329fea1a4f2b9568ddgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldId":"00000000-0000-0000-0000-000000005018","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXgender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXfn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005018VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005018gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXln"}}'.
                    //',{"Column":{"label":"Steward Email","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXpn"}}'.
                    '],"name":"Role00000000000000000000000000005016VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005016gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015gender"},"FirstName":{"name":"userRole00000000000000000000000000005015fn"},"Id":{"name":"userRole00000000000000000000000000005015rid"},"LastName":{"name":"userRole00000000000000000000000000005015ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015sig"},"name":"Role00000000000000000000000000005015","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015rid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015ggn"},"Id":{"name":"groupRole00000000000000000000000000005015grid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole7865d2c3405a459db11edf061adec84bgender"},"FirstName":{"name":"userRole7865d2c3405a459db11edf061adec84bfn"},"Id":{"name":"userRole7865d2c3405a459db11edf061adec84brid"},"LastName":{"name":"userRole7865d2c3405a459db11edf061adec84bln"}},"Role":{"Signifier":{"hidden":"true","name":"Role7865d2c3405a459db11edf061adec84bsig"},"name":"Role7865d2c3405a459db11edf061adec84b","Id":{"hidden":"true","name":"roleRole7865d2c3405a459db11edf061adec84brid"}},"roleId":"7865d2c3-405a-459d-b11e-df061adec84b"},{"Role":{"Signifier":{"hidden":"true","name":"Role7865d2c3405a459db11edf061adec84bg"},"Id":{"hidden":"true","name":"roleRole7865d2c3405a459db11edf061adec84bgrid"}},"Group":{"GroupName":{"name":"groupRole7865d2c3405a459db11edf061adec84bggn"},"Id":{"name":"groupRole7865d2c3405a459db11edf061adec84bgrid"}},"roleId":"7865d2c3-405a-459d-b11e-df061adec84b"},{"User":{"Gender":{"name":"userRole6fe7db7792bb4321988a884614c1601cgender"},"FirstName":{"name":"userRole6fe7db7792bb4321988a884614c1601cfn"},"Id":{"name":"userRole6fe7db7792bb4321988a884614c1601crid"},"LastName":{"name":"userRole6fe7db7792bb4321988a884614c1601cln"}},"Role":{"Signifier":{"hidden":"true","name":"Role6fe7db7792bb4321988a884614c1601csig"},"name":"Role6fe7db7792bb4321988a884614c1601c","Id":{"hidden":"true","name":"roleRole6fe7db7792bb4321988a884614c1601crid"}},"roleId":"6fe7db77-92bb-4321-988a-884614c1601c"},{"Role":{"Signifier":{"hidden":"true","name":"Role6fe7db7792bb4321988a884614c1601cg"},"Id":{"hidden":"true","name":"roleRole6fe7db7792bb4321988a884614c1601cgrid"}},"Group":{"GroupName":{"name":"groupRole6fe7db7792bb4321988a884614c1601cggn"},"Id":{"name":"groupRole6fe7db7792bb4321988a884614c1601cgrid"}},"roleId":"6fe7db77-92bb-4321-988a-884614c1601c"},{"User":{"Gender":{"name":"userRole615829186613486a9d8261f7d1c0c014gender"},"FirstName":{"name":"userRole615829186613486a9d8261f7d1c0c014fn"},"Id":{"name":"userRole615829186613486a9d8261f7d1c0c014rid"},"LastName":{"name":"userRole615829186613486a9d8261f7d1c0c014ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role615829186613486a9d8261f7d1c0c014sig"},"name":"Role615829186613486a9d8261f7d1c0c014","Id":{"hidden":"true","name":"roleRole615829186613486a9d8261f7d1c0c014rid"}},"roleId":"61582918-6613-486a-9d82-61f7d1c0c014"},{"Role":{"Signifier":{"hidden":"true","name":"Role615829186613486a9d8261f7d1c0c014g"},"Id":{"hidden":"true","name":"roleRole615829186613486a9d8261f7d1c0c014grid"}},"Group":{"GroupName":{"name":"groupRole615829186613486a9d8261f7d1c0c014ggn"},"Id":{"name":"groupRole615829186613486a9d8261f7d1c0c014grid"}},"roleId":"61582918-6613-486a-9d82-61f7d1c0c014"},{"User":{"Gender":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddgender"},"FirstName":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddfn"},"Id":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddrid"},"LastName":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddln"}},"Role":{"Signifier":{"hidden":"true","name":"Role734327ec9eaa41329fea1a4f2b9568ddsig"},"name":"Role734327ec9eaa41329fea1a4f2b9568dd","Id":{"hidden":"true","name":"roleRole734327ec9eaa41329fea1a4f2b9568ddrid"}},"roleId":"734327ec-9eaa-4132-9fea-1a4f2b9568dd"},{"Role":{"Signifier":{"hidden":"true","name":"Role734327ec9eaa41329fea1a4f2b9568ddg"},"Id":{"hidden":"true","name":"roleRole734327ec9eaa41329fea1a4f2b9568ddgrid"}},"Group":{"GroupName":{"name":"groupRole734327ec9eaa41329fea1a4f2b9568ddggn"},"Id":{"name":"groupRole734327ec9eaa41329fea1a4f2b9568ddgrid"}},"roleId":"734327ec-9eaa-4132-9fea-1a4f2b9568dd"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018gender"},"FirstName":{"name":"userRole00000000000000000000000000005018fn"},"Id":{"name":"userRole00000000000000000000000000005018rid"},"LastName":{"name":"userRole00000000000000000000000000005018ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018sig"},"name":"Role00000000000000000000000000005018","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018rid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018ggn"},"Id":{"name":"groupRole00000000000000000000000000005018grid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005015VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015VOCSUFFIXsig"},"name":"Role00000000000000000000000000005015VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXgender"},"FirstName":{"name":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXfn"},"Id":{"name":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXrid"},"LastName":{"name":"userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role7865d2c3405a459db11edf061adec84bVOCSUFFIXsig"},"name":"Role7865d2c3405a459db11edf061adec84bVOCSUFFIX","Id":{"hidden":"true","name":"roleRole7865d2c3405a459db11edf061adec84bVOCSUFFIXrid"}},"roleId":"7865d2c3-405a-459d-b11e-df061adec84b"},{"Role":{"Signifier":{"hidden":"true","name":"Role7865d2c3405a459db11edf061adec84bgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole7865d2c3405a459db11edf061adec84bgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole7865d2c3405a459db11edf061adec84bgVOCSUFFIXgn"},"Id":{"name":"groupRole7865d2c3405a459db11edf061adec84bgVOCSUFFIXrid"}},"roleId":"7865d2c3-405a-459d-b11e-df061adec84b"},{"User":{"Gender":{"name":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXgender"},"FirstName":{"name":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXfn"},"Id":{"name":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXrid"},"LastName":{"name":"userRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role6fe7db7792bb4321988a884614c1601cVOCSUFFIXsig"},"name":"Role6fe7db7792bb4321988a884614c1601cVOCSUFFIX","Id":{"hidden":"true","name":"roleRole6fe7db7792bb4321988a884614c1601cVOCSUFFIXrid"}},"roleId":"6fe7db77-92bb-4321-988a-884614c1601c"},{"Role":{"Signifier":{"hidden":"true","name":"Role6fe7db7792bb4321988a884614c1601cgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole6fe7db7792bb4321988a884614c1601cgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole6fe7db7792bb4321988a884614c1601cgVOCSUFFIXgn"},"Id":{"name":"groupRole6fe7db7792bb4321988a884614c1601cgVOCSUFFIXrid"}},"roleId":"6fe7db77-92bb-4321-988a-884614c1601c"},{"User":{"Gender":{"name":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXgender"},"FirstName":{"name":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXfn"},"Id":{"name":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXrid"},"LastName":{"name":"userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role615829186613486a9d8261f7d1c0c014VOCSUFFIXsig"},"name":"Role615829186613486a9d8261f7d1c0c014VOCSUFFIX","Id":{"hidden":"true","name":"roleRole615829186613486a9d8261f7d1c0c014VOCSUFFIXrid"}},"roleId":"61582918-6613-486a-9d82-61f7d1c0c014"},{"Role":{"Signifier":{"hidden":"true","name":"Role615829186613486a9d8261f7d1c0c014gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole615829186613486a9d8261f7d1c0c014gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole615829186613486a9d8261f7d1c0c014gVOCSUFFIXgn"},"Id":{"name":"groupRole615829186613486a9d8261f7d1c0c014gVOCSUFFIXrid"}},"roleId":"61582918-6613-486a-9d82-61f7d1c0c014"},{"User":{"Gender":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXgender"},"FirstName":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXfn"},"Id":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXrid"},"LastName":{"name":"userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXsig"},"name":"Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX","Id":{"hidden":"true","name":"roleRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXrid"}},"roleId":"734327ec-9eaa-4132-9fea-1a4f2b9568dd"},{"Role":{"Signifier":{"hidden":"true","name":"Role734327ec9eaa41329fea1a4f2b9568ddgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole734327ec9eaa41329fea1a4f2b9568ddgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole734327ec9eaa41329fea1a4f2b9568ddgVOCSUFFIXgn"},"Id":{"name":"groupRole734327ec9eaa41329fea1a4f2b9568ddgVOCSUFFIXrid"}},"roleId":"734327ec-9eaa-4132-9fea-1a4f2b9568dd"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005018VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018VOCSUFFIXsig"},"name":"Role00000000000000000000000000005018VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005016VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016VOCSUFFIXsig"},"name":"Role00000000000000000000000000005016VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":10,"generalConceptId":"78067114-8ad0-48af-9a46-f50859b280f2"}}'
            )
        );
        $domains = json_decode($resp);
        // Trustee                  Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX
        // Information Steward      Role615829186613486a9d8261f7d1c0c014VOCSUFFIX
        // Information Custodian    Role7865d2c3405a459db11edf061adec84bVOCSUFFIX
        
        // load additional information for users
        $arrUserDetails = array();
        if(sizeof($domains->aaData)>0){
            for($i=0; $i<sizeof($domains->aaData[1]->Vocabularies); $i++){
                $v = $domains->aaData[1]->Vocabularies[$i];
                // Trustee user
                if(sizeof($v->Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX)>0){
                    $userrid = $v->Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX[0]->userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXrid;
                    if(!isset($arrUserDetails[$userrid])){
                        $userResp = $objCollibra->request(array('url'=>'user/'.$userrid));
                        $userData = json_decode($userResp);
                        $arrUserDetails[$userrid] = array();
                        $arrUserDetails[$userrid]['name'] = $userData->firstName.' '.$userData->lastName;
                        $arrUserDetails[$userrid]['email'] = $userData->emailAddress;
                        $arrUserDetails[$userrid]['phone'] = '&nbsp;';
                        if(isset($userData->phoneNumbers->phone)){
                            $arrUserDetails[$userrid]['phone'] = $userData->phoneNumbers->phone[0]->number;
                        }
                    }
                }
                // Steward user
                if(sizeof($v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX)>0){
                    $userrid = $v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX[0]->userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXrid;
                    if(!isset($arrUserDetails[$userrid])){
                        $userResp = $objCollibra->request(array('url'=>'user/'.$userrid));
                        $userData = json_decode($userResp);
                        $arrUserDetails[$userrid] = array();
                        $arrUserDetails[$userrid]['name'] = $userData->firstName.' '.$userData->lastName;
                        $arrUserDetails[$userrid]['email'] = $userData->emailAddress;
                        $arrUserDetails[$userrid]['phone'] = '&nbsp;';
                        if(isset($userData->phoneNumbers->phone)){
                            $arrUserDetails[$userrid]['phone'] = $userData->phoneNumbers->phone[0]->number;
                        }
                    }
                }
                // Custodian user
                if(sizeof($v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX)>0){
                    $userrid = $v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX[0]->userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXrid;
                    if(!isset($arrUserDetails[$userrid])){
                        $userResp = $objCollibra->request(array('url'=>'user/'.$userrid));
                        $userData = json_decode($userResp);
                        $arrUserDetails[$userrid] = array();
                        $arrUserDetails[$userrid]['name'] = $userData->firstName.' '.$userData->lastName;
                        $arrUserDetails[$userrid]['email'] = $userData->emailAddress;
                        $arrUserDetails[$userrid]['phone'] = '&nbsp;';
                        if(isset($userData->phoneNumbers->phone)){
                            $arrUserDetails[$userrid]['phone'] = $userData->phoneNumbers->phone[0]->number;
                        }
                    }
                }
            }
        }
        
        /*print_r($arrUserDetails);
        exit;
        
        print_r($domains);
        exit;*/
        
        $this->set('communities', $parentCommunities);
        $this->set('domains', $domains);
        $this->set('users', $arrUserDetails);
        $this->set('community', $community);
    }
    public function success() {
        
    }
    
    public function peopleByCommunity() {
        
    }
}
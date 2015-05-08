<?php

class CmsPage extends AppModel {
    public $listPageHtml = '';
    
    public $validate = array(
        'title' => array(
            'rule' => 'notEmpty'
        ),
        'slug' => array(
            'rule' => 'notEmpty'
        ),
        'body' => array(
            'rule' => 'notEmpty'
        )
    );
    
    public function loadPage($pageID=0){        
        App::uses('Helpers', 'Model');
        $pageID = Helpers::getInt($pageID);
        $cmsPage = '';
        if($pageID==0){
            $cmsPage = $this->find('first', array(
                'conditions'=>array('parentID'=>'0'),
                'order'=>array('rank'=>'DESC')
            ));
        }else{
            $cmsPage = $this->find('first', array(
                'conditions'=>array('id'=>$pageID),
                'order'=>array('rank'=>'DESC')
            ));
        }
        return $cmsPage;
    }
    
    public function listPages($pageID=0, $parentID=0, $level=0, $html=''){
        App::uses('Helpers', 'Model');
        $parentID = Helpers::getInt($parentID);
        $level = Helpers::getInt($level);
        $pageID = Helpers::getInt($pageID);
        $hasSubpages = false;
        
        $activeFilter = "";
        App::uses('CakeSession', 'Model/Datasource');
        if(CakeSession::read('userID') == null){
            $activeFilter = "AND active=1";
        }
        
        $results = $this->query("SELECT P.*, (SELECT COUNT(*) FROM cms_pages WHERE parentID=P.id) AS ChildPages FROM cms_pages P WHERE P.parentID=".$parentID." ".$activeFilter." ORDER BY P.rank");
		foreach($results as $result){
            $page = $result['P'];
            $hasSubpages = $result[0]["ChildPages"];
			
			$cssClass = "";
			if($hasSubpages){
				$cssClass = "hasChildren";
			}
            
            $levelCss = "";
            switch($level){
                case 1:
                    $levelCss = "catalogChild";
                    break;
                case 2:
                    $levelCss = "grandChild";
                    break;
            }
            
            if($result['P']["redirectURL"]){
                $link = $result['P']["redirectURL"].'" target="_blank"';
            }else{
                $link = '/resources/'.$page["slug"];
            }
            
            $this->listPageHtml .= '<li class="catalogItem"><a class="'.$cssClass.'" href="'.$link.'">'.$page['title'].'</a>';
			if($hasSubpages){
                $this->listPageHtml .=  "<ul class='subList ".$levelCss."'>\r\n";
                $this->listPages($pageID, $page['id'], $level+1, $html);
                $this->listPageHtml .=  "</ul>\r\n";
            }
			$this->listPageHtml .= "</li>\r\n";
        }
        if($level == 0){
            return $this->listPageHtml;
        }
    }
    
    public function listAdminPages($pageID=0, $parentID=0, $level=0, $html=''){
        App::uses('Helpers', 'Model');
        $parentID = Helpers::getInt($parentID);
        $level = Helpers::getInt($level);
        $pageID = Helpers::getInt($pageID);
        
        $hasSubpages = false;
        $results = $this->query("SELECT P.*, (SELECT COUNT(*) FROM cms_pages WHERE parentID=P.id) AS ChildPages FROM cms_pages P WHERE P.parentID=".$parentID." ORDER BY P.rank");
		foreach($results as $result){
            $page = $result['P'];
            $hasSubpages = $result[0]["ChildPages"];
			$pageTitle = str_replace("-", " ", $page['title']);
			
			$cssClass = "";
			if($page['id'] == 1){
				//$cssClass = "no-nest disabled";
                $cssClass = "disabled";
			}
			if($pageID == $page['id']){
				$cssClass = $cssClass . " active";
			}
            
            $this->listPageHtml .= '<li class="'.$cssClass.'" id="'.$page['id'].'_'.$page['parentID'].'"><a href="/admin/editpage/'.$page["id"].'#pg'.$page["id"].'" name="pg'.$page["id"].'">'.$pageTitle.'</a>';
			if($hasSubpages){
                if($page['id']==1){
                    $this->listPageHtml .= '<a class="add-record" href="/admin/addpage/1">+ Add Page</a>';
                }
                $this->listPageHtml .=  "<ul class='left-subnav'>\r\n";
                //$this->listPageHtml = $html.$this->listPages($pageID, $page['id'], $level+1, $html);
                $this->listAdminPages($pageID, $page['id'], $level+1, $html);
                $this->listPageHtml .=  "</ul>\r\n";
            }
			$this->listPageHtml .= "</li>\r\n";
        }
        if($level == 0){
            return $this->listPageHtml;
        }
    }
    
    public function loadCmsBody($pageID, $body, $isAdmin){
        $pageBody = stripslashes($body);
        if($isAdmin){
return <<<HTML
<script type="text/javascript" src="/js/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
    tinyMCE.init({
		// General options
		media_strict: false,
		convert_urls : false,
		mode : "textareas",
		theme : "advanced",
		editor_selector : "mcePGBody",
		extended_valid_elements : "iframe[src|width|height|name|align]",
		plugins : "spellchecker,fileLibrary,video,media,table,inlinepopups,save,paste,contextmenu,autosave,advimage",
		theme_advanced_blockformats : "h2,h3",
		theme_advanced_buttons1 : "bold,italic,underline,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,|,link,unlink,|,undo,redo,|,save",
		theme_advanced_buttons2 : "pastetext,pasteword,spellchecker,|,formatselect,fontsizeselect,|,video,fileLibrary,table,|,code",
		theme_advanced_buttons3 : "",
		paste_auto_cleanup_on_paste : true,
		theme_advanced_toolbar_location : "top",
		pageID:$pageID,
        file_browser_callback: RoxyFileBrowser
	});
    
    function RoxyFileBrowser(field_name, url, type, win) {
		//type = '';
		
        var roxyFileman = '/fileman/index.html';
        if (roxyFileman.indexOf("?") < 0) {     
            roxyFileman += "?type=" + type;   
        }
        else {
            roxyFileman += "&type=" + type;
        }
        roxyFileman += '&input=' + field_name + '&value=' + win.document.getElementById(field_name).value;
        if(tinyMCE.activeEditor.settings.language){
            roxyFileman += '&langCode=' + tinyMCE.activeEditor.settings.language;
        }
        tinyMCE.activeEditor.windowManager.open({
            file: roxyFileman,
            title: 'Roxy Fileman',
            width: 850, 
            height: 650,
            resizable: "yes",
            plugins: "media",
            inline: "yes",
            close_previous: "no"  
        }, {     window: win,     input: field_name    });
        return false; 
    }
	
	function togglePreview(){
		//tinyMCE.execCommand('mceFocus',false,'pgBody');
		$("#cms-preview-container").html(tinyMCE.editors["mcePGBody"].getContent());
		if($("#cms-edit-container").css("display") == "block"){
			$("#cms-edit-container").hide();
			$("#cms-preview-container").show();
		}else{
			$("#cms-edit-container").show();
			$("#cms-preview-container").hide();
		}
		
		if($(".cms-links").css("display") == "block"){
			$(".cms-links").hide();
		} else {
			$(".cms-links").show();
		}
        
        $(".preview-hidden").toggle();
		
		if($("#cms-edit-container2").css("display") == "block"){		
			$("#cms-edit-container2").hide();
			$("#cms-preview-container2").show();
		}else{
			$("#cms-edit-container2").show();
			$("#cms-preview-container2").hide();
		}
	}
</script>
<div id="cms-edit-container">
	<form action="/cmspages/updatepage" method="post">
		<textarea style="width: 100%; height: 500px" id="mcePGBody" class="mcePGBody" name="pgBody">$pageBody</textarea>
		<input type="hidden" name="pgID" value="$pageID" />
	</form>
</div>

<div id="cms-preview-container">
	$pageBody
</div>

HTML;
        }else{
            return $pageBody;
        }
    }
}
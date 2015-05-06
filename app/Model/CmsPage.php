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
                    $this->listPageHtml .= '<a class="add-page" href="/admin/addpage/1">+ Add Page</a>';
                }
                $this->listPageHtml .=  "<ul class='left-subnav'>\r\n";
                //$this->listPageHtml = $html.$this->listPages($pageID, $page['id'], $level+1, $html);
                $this->listPages($pageID, $page['id'], $level+1, $html);
                $this->listPageHtml .=  "</ul>\r\n";
            }
			$this->listPageHtml .= "</li>\r\n";
        }
        if($level == 0){
            return $this->listPageHtml;
        }
    }

}
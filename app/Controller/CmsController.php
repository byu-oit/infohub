<?php

class CmsController extends AppController {
    public $helpers = array('Html', 'Form');

    public function listPages($parentID=0, $level=0) {
        $results = $this->query("SELECT * FROM locations;");
        print_r($results);
        exit;
        /*$arrParentIDs = array();
        if(getInt($_GET['pgID'])!=0){
            $pageID = getInt($_GET['pgID']);
            $lastParent = getInt($_GET['pgID']);
            $loops = 0;
            do{
                $result = sqlQuery("SELECT ID,ParentID FROM CMS_Pages P WHERE ID=".$lastParent." AND P.ShowInAdmin=1 ORDER BY P.Rank");
                while ($row = mysql_fetch_array($result)) {
                    $lastParent  = $row['ParentID'];
                    array_push($arrParentIDs, $row['ID']);
                }
                $loops++;
            }while($lastParent!=0 && $loops<20);
            //print_r($arrParentIDs);
        }
        
		$result = sqlQuery("SELECT P.*, (SELECT COUNT(*) FROM CMS_Pages WHERE ParentID=P.ID) AS ChildPages FROM CMS_Pages P WHERE P.ParentID=".$parentID." AND P.ShowInAdmin=1 ORDER BY P.Rank");
		$hasSubpages = false;
        while ($row = mysql_fetch_array($result)) {
			if(getInt($_GET["pgID"]) == getInt($row["ID"])){
				global $pgTitle;
				global $pgSubTitle;
				global $pgDisplayTitle;
				global $pgUrl;
				global $parentID;
				global $pgLevel;
				global $pgTemplateID;
				global $hiddenChecked;
				global $showOnNavChecked;
				global $arrUserGroups;
				global $pgNavImage;
				global $pgMetaKeywords;
				global $pgMetaDesc;
				global $pgMetaTitle;
				global $noDelete;
				//$pgTitle = str_replace("-", " ", $row["Title"]);
				$pgTitle = $row["Title"];
				$pgSubTitle =  mysql_real_escape_string($row["SubTitle"]);
				$pgDisplayTitle = $row["DisplayTitle"];
				$pgMetaKeywords = $row["MetaKeywords"];
				$pgMetaDesc = $row["MetaDescription"];
				$pgMetaTitle = $row["MetaTitle"];
				$pgNavImage = $row["NavImage"];
				$pgUrl = $row["PageUrl"];
				$parentID = $row["ParentID"];
				$pgTemplateID = $row["TemplateID"];
				$noDelete = getInt($row["NoDelete"]);
				$pgLevel = $level;
				if($row["Active"] == "0"){
					$hiddenChecked = "checked";
				}
				if($row["ShowOnNav"] == "1"){
					$showOnNavChecked = "checked";
				}
				
				// load page's user groups
				$arrUserGroups = getPageUserGroups($row["ID"]);
			}
            $hasSubpages = $row["ChildPages"];
			$pageTitle = str_replace("-", " ", $row["Title"]);
			
			$cssClass = "";
			if($row["ID"] == 1){
				$cssClass = "no-nest disabled";
			}
			if($_GET["pgID"] == $row["ID"]){
				$cssClass = $cssClass . " active";
			}
            
            $indent = 0;
			for($i=0; $i<$level; $i++){
				$indent += 12;
			}
			
			echo '<li class="'.$cssClass.'" id="'.$row["ID"].'_'.$row["ParentID"].'">';

            
            $activeLinkClass = '';
            if($pageID == $row['ID']){
                $activeLinkClass = 'active';
            }            
            echo '<a class="page-link '.$activeLinkClass.'" style="padding-left:'.$indent.'px" href="pageManager.php?a=2&amp;pgID='.$row["ID"].'#pg'.$row["ID"].'" name="pg'.$row["ID"].'">';
            
            if($hasSubpages){
                if(in_array($row['ID'], $arrParentIDs)){
                    echo '<div class="close"></div>';
                }else{
                    echo '<div class="expand"></div>';
                }
            }else{
                echo '<div class="expand-empty"></div>';
            }
            echo $pageTitle;
            if(!$row["Active"]){
                echo '<div class="lock" title="hidden"></div>';
            }
            if($row["PageUrl"] != ''){
                echo '<div class="link" title="linked"></div>';
            }
            echo '</a>';
            
            if($hasSubpages){
            //if(in_array($row['ID'], $arrParentIDs)){
                $hasSubpages = false;
                if(in_array($row['ID'], $arrParentIDs)){
                    echo "<ul class='left-subnav' style='display:block'>";
                }else{
                    echo "<ul class='left-subnav'>";
                }
                listPages($row["ID"], $level+1);
                echo "</ul>";
            }
			echo "</li>";
		}*/
    }
}
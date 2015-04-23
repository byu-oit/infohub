<?php

class QuickLinksController extends AppController {
    public function load(){
        $this->autoRender = false;
        
        if(isset($_COOKIE['QL'])) {
            return unserialize($_COOKIE['QL']);
        }else{
            return '';   
        }
    }
    
    public function add(){
        $this->autoRender = false;
        if ($this->request->is('post')) {
            $ql = $this->request->data['ql'];
            $id = $this->request->data['id'];

            $arrQl = array();
            if(isset($_COOKIE['QL'])) {
                $arrQl = unserialize($_COOKIE['QL']);
            }

            $qlExists = false;
            for($i=0; $i<sizeof($arrQl); $i++){
                if($arrQl[$i][1] == $id){
                    $qlExists = true;
                    break;
                }
            }

            if(!$qlExists){
                array_push($arrQl, array($ql, $id));
                echo '1';
            }else{
                echo '0';
            }

            setcookie('QL', serialize($arrQl), time() + (60*60*24*90), "/"); // 90 days
        }
    }
    
    public function remove(){
        $this->autoRender = false;
        if ($this->request->is('post') || 1==1) {
            $id = $this->request->data['id'];

            if(isset($_COOKIE['QL'])) {
                $arrQl = unserialize($_COOKIE['QL']);
                for($i=0; $i<sizeof($arrQl); $i++){
                    if($arrQl[$i][1] == $id){
                        //unset($arrQl[$i]);
                        array_splice($arrQl, $i, 1);
                        break;
                    }
                }

                setcookie('QL', serialize($arrQl), time() + (60*60*24*90), "/"); // 90 days
            }
        }
    }
}

?>


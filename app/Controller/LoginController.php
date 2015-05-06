<?php

class LoginController extends AppController {
    function beforeFilter() {
    }
    
    public function index() {
        require_once $_SERVER['DOCUMENT_ROOT'].'/CAS-1.3.3/config.php';
        require_once $phpcas_path.'/CAS.php';
        phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
        
        // phpCAS::setCasServerCACert($cas_server_ca_cert_path);
        phpCAS::setNoCasServerValidation();
        
        if(phpCAS::isAuthenticated()){
            $redirect = '/';
            if(isset($_SESSION['referer'])){
                $redirect = $_SESSION['referer'];
                $_SESSION['referer'] = null;
            }
            
            header('Location: '.$redirect);
            exit;
        }else{
            $referer = $_SERVER['HTTP_REFERER'];
            $host = $_SERVER['HTTP_HOST'];
            if(strpos($referer, $host) !== false){
                $_SESSION['referer'] = $referer;
            }
            phpCAS::forceAuthentication();
        }
        //echo 'user: '.phpCAS::getUser();
    }
    public function logout() {
        require_once $_SERVER['DOCUMENT_ROOT'].'/CAS-1.3.3/config.php';
        require_once $phpcas_path.'/CAS.php';
        phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
        phpCAS::logout();
        exit;
    }
}
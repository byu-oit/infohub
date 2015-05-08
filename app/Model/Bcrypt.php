<?php

App::uses('Model', 'Model');

class Bcrypt extends Model {
	public static function hash($password, $salt='', $work_factor = '12'){
    	if($salt == '') $salt = Bcrypt::generateSalt();
        if (version_compare(PHP_VERSION, '5.3') < 0){
        	return $salt.md5(md5($password));
        }else{
        	 // using blowfish
        	$salt = '$2a$'.$work_factor.'$'.$salt;
        	return crypt($password, $salt);
    	}
    }

    public static function check($password, $hash){
    	if (version_compare(PHP_VERSION, '5.3') < 0){
    		$startChar = 0;
    	}else{
    		$startChar = 7; // using blowfish
    	}
    	$salt = substr($hash, $startChar, 21);
    	return $hash == Bcrypt::hash($password, $salt);
    }

    public static function generateSalt(){
    	return substr(md5(uniqid('',true)), 0, 21);
    }
}
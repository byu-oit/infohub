<?php

App::uses('Model', 'Model');

class Helpers extends Model {
    public $useTable = false;
    
    public static function encryptSha($str){
        return sha1($str);
    }

    public static function getInt($num){
        $num = (int)$num;
        if(is_int($num)){
            return $num;
        }else{
            return 0;
        }
    }

    public static function getDevice() {
        $arrDevices = array('ipod', 'iphone', 'ipad', 'android', 'webos', 'silk');
        $device = 'pc';
        for($i=0; $i<sizeof($arrDevices); $i++){
            if(stristr($_SERVER['HTTP_USER_AGENT'], $arrDevices[$i])){
                $device = $arrDevices[$i];
                break;
            }
        }

        if($device == 'ipod') $device = 'iphone';

        return $device;
    }

    public static function dateToMySQL($date) {
        $date = strtotime($date);
        return date("Y-m-d H:i:s", $date);
    }

    public static function dateFromMySQL($date, $format="m/d/Y") {
        return date($format, strtotime($date));
    }

    public static function limitCopy($body, $maxLength, $addEnd=false){
        $body = strip_tags($body);
        $bodyArr = explode(" ", $body);

        $totalLen = 0;
        for($i=0; $i<sizeOf($bodyArr); $i++){
            $loopLen = 1;
            $loopLen += strlen($bodyArr[$i]);

            if($loopLen + $totalLen > $maxLength){
                break;
            }
            $totalLen += $loopLen;
        }
        $newBody = substr($body, 0, $totalLen);
        if($addEnd) $newBody .= '...';
        return $newBody;
    }	

    protected static function showError($str){
        if($str != ""){
            echo '<div class="err">';
            echo $str;
            echo '</div>';
        }	
    }

    public static function showStatus($str){
        if($str != ""){
            echo '<div class="status">';
            echo $str;
            echo '</div>';
        }
    }

    public static function isValidFileType($file, $validFileTypes){
        $file = strToLower($file);
        $fileExt = substr(strrchr($file, '.'), 1, 3);
        foreach($validFileTypes as $ext){
            if($fileExt == $ext){
                return true;
            }
        }
        return false;
    }

    public static function trackError($str){
        $message = date('m/d/Y h:i:s a', time())." - An error occured on page ".$_SERVER["REQUEST_URI"]." \r\n".
            "Error: ".$str."\r\n\r\n";

        $file = DOC_ROOT."/errors.txt";
        $fh = fopen($file, 'a');
        fwrite($fh, $message);
        fclose($fh);

        @mail('anava@summitslc.com', 'site error', $message);
    }

    public static function addLog($str){
        $file = DOC_ROOT."/log.txt";
        $fh = fopen($file, 'a');
        fwrite($fh, $str);
        fclose($fh);
    }

    public static function simpleCrypt($str){
        $ky = "hJdn2iuDb788h2kjdkjs34CPlahbd2XVdkiBf8hwFYeb3kjdBudFh8ei";
        if($ky=='')return $str;
        $ky=str_replace(chr(32),'',$ky);
        if(strlen($ky)<8)exit('key error');
        $kl=strlen($ky)<32?strlen($ky):32;
        $k=array();for($i=0;$i<$kl;$i++){
        $k[$i]=ord($ky{$i})&0x1F;}
        $j=0;for($i=0;$i<strlen($str);$i++){
        $e=ord($str{$i});
        $str{$i}=$e&0xE0?chr($e^$k[$j]):chr($e);
        $j++;$j=$j==$kl?0:$j;}
        return $str;
    }

    public static function has_swear_words($str){
        $bad_words = array("anus","arse","arsehole","assbag","assbandit","assbanger","assbite","assclown","asscock","asscracker","asses","assface","assfuck","assfucker","assgoblin","asshat","asshead","asshole","asshopper","assjacker","asslick","asslicker","assmunch","assmuncher","asspirate","assshole","asswipe","bampot","bastard","beaner","bitch","bitchass","bitchtits","bitchy","blow","blowjob","bollocks","bollox","boner","brotherfucker","bullshit","bumblefuck","butt","butt-pirate","buttfucka","buttfucker","camel","carpetmuncher","chinc","chink","choad","chode","clit","clitface","clitfuck","cock","cockbite","cockface","cockfucker","cockknoker","cockmaster","cockmongler","cockmongruel","cockmonkey","cockmuncher","cocknugget","cockshit","cocksmith","cocksmoker","cocksucker","coochie","coochy","coon","cooter","cracker","cum","cumbubble","cumjockey","cumtart","cunnilingus","cunt","cunthole","damn","deggo","dick","dickbag","dickbeaters","dickface","dickfuck","dickhead","dickhole","dickmonger","dicks","dickweasel","dickweed","dickwod","dildo","dipshit","dookie","douche","douche-fag","douchebag","douchewaffle","dumass","dumb","dumbass","dumbfuck","dumbshit","dumshit","dyke","fag","fagbag","fagfucker","faggit","faggot","fagtard","fatass","fellatio","feltch","flamer","fuck","fuckass","fuckbrain","fuckbutt","fucked","fucker","fuckface","fuckhead","fuckhole","fuckin","fucking","fucknut","fucks","fuckstick","fucktard","fuckup","fuckwad","fuckwit","fuckwitt","fudgepacker","gay","gaybob","gaydo","gayfuck","gayfuckist","gaytard","gaywad","goddamn","goddamnit","gooch","gook","gringo","guido","handjob","hard","heeb","hell","ho","homo","homodumbshit","honkey","humping","jackass","jap","jerk","jigaboo","jizz","jungle","junglebunny","kike","kooch","kootch","kyke","lesbian","lesbo","lezzie","mcfagget","mick","minge","mothafucka","motherfucker","motherfucking","muff","muffdiver","munging","negro","nigga","nigger","niglet","nut","nutsack","paki","panooch","pecker","peckerhead","penis","penisfucker","piss","pissed","pissed","pissflaps","pollock","poon","poonani","poonany","porch","porchmonkey","prick","punanny","punta","pussies","pussy","pussylicking","puto","queef","ueer","queerbait","queerhole","renob","rimjob","ruski","sand","sandnigger","schlong","scrote","shit","shitbagger","shitcunt","shitdick","shitface","shitfaced","shithead","shithole","shithouse","shitspitter","shitstain","shitter","shittiest","shitting","shitty","shiz","shiznit","skank","skeet","skullfuck","slut","slutbag","snatch","spic","spick","splooge","tard","testicle","thundercunt","tit","titfuck","tits","tittyfuck","twat","twatlips","twats","twatwaffle","va-j-j","vag","vagina","vjayjay","wank","wetback","whore","whorebag","whoreface","wop");
        foreach ($bad_words as $word){
            if(strpos($str, $word) !== FALSE){
                return true;
                break;
            }
        }
        return false;
	}
}
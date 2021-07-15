<?php
/* code:
*0:没有安装
*1:正常返回
*2:URL请求出错或违规
*3:发生了什么情况导致出错,比如没有权限访问文件夹
*4:需要登录的地方却没有登录
*/



function isinstall(){
    if(is_dir(config)&&file_exists(config.'/install.lock')){
        return true;
    }
    return false;
}

//array到josn
function json($arr){
    return json_encode($arr);
}

//json到array
function dejson($json){
    return json_decode($json);
}

//判断目录是否有读写权限
function check_dir_iswritable($dir_path){
$dir_path=str_replace('\\','/',$dir_path);
$is_writale=1;
if(!is_dir($dir_path)){
$is_writale=0;
return $is_writale;
}else{
$file_hd=@fopen($dir_path.'/test.txt','w');
if(!$file_hd){
@fclose($file_hd);
@unlink($dir_path.'/test.txt');
$is_writale=0;
return $is_writale;
}
$dir_hd=opendir($dir_path);
while(false!==($file=readdir($dir_hd))){
if ($file != "." && $file != "..") {
if(is_file($dir_path.'/'.$file)){
//文件不可写，直接返回
if(!is_writable($dir_path.'/'.$file)){
return 0;
}
}else{
$file_hd2=@fopen($dir_path.'/'.$file.'/test.txt','w');
if(!$file_hd2){
@fclose($file_hd2);
@unlink($dir_path.'/'.$file.'/test.txt');
$is_writale=0;
return $is_writale;
}
//递归
$is_writale=check_dir_iswritable($dir_path.'/'.$file);
}
}
}
}
return $is_writale;
}

//写Setup
function writesetup($key,$value){
    if(is_dir(config)){
        if(!file_exists(config."/setup.php")){
            file_put_contents(config."/setup.php","<?php\n");
        }
        file_put_contents(config."/setup.php",'define("'.$key.'","'.$value.'");'."\n",FILE_APPEND);
    }else{
        mkdir(config,0777,true);
        writesetup($key,$value);
    }
}

function getdirfile(){
    $redis = linkRedis();
    $temp=scandir(SAVEPATH);
    
    $arrok = array();               //已下载完成文件集合
    $arrno = array();               //正在下载中的文件集合
    $_json = array();
    
    
    foreach($temp as $_v){
        if(!is_dir($_v)){
            //取文件所在目录：dirname($v)
            $v = SAVEPATH.$_v;
            
            
            // if(file_exists(config."/".$_v.".json")){
            //     $_json = json_decode(file_get_contents(config."/".$_v.".json"));
            //     if($_json->downing){
            //         array_push($arrno,array(
            //             "value"=>$_json,
            //             "json"=>config."/".$_v.".json"
            //             ));
            //         continue;
            //     }
            // }
            
            $k = md5($_v);
            if($redis->sismember("task",$k)){  //判断是否是集合中的成员 如果是
                $val = $redis->get($k);
                if($val && dejson($val)->downing){
                    array_push($arrno,array(
                        "value"=>$val,
                        "key"=>$k
                    ));
                    continue;
                }
            }
            
            array_push($arrok,array(
                "file"=>$v,
                "filename"=>$_v,
                "filesize"=>filesize($v),
                "time"=>array(
                    filectime($v),  //创建时间
                    filemtime($v),  //修改时间
                    fileatime($v)   //访问时间
                    ),
                "downinfo"=> $_json
                )
            );
        }
    }
    return array("downok"=>$arrok,"downing"=>$arrno);
}


function gethost(){
    $host = $_SERVER['HTTP_HOST'];
    if(strpos($host,":")!==false){
        return substr($host,0,stripos($host,":"));
    }else{
        return $host;
    }
}

//将服务器文件下载到客户端
function downtoweb($_name,$_isstream = false){
    $name = SAVEPATH.$_name;
    $info = pathinfo($name);
    $ext = $info['extension'];
    $size = filesize($name);
    
    if(file_exists($name)&&is_file($name)){
        @set_time_limit(3600*2);
        header_remove();
        ob_clean();
        $file=fopen($name,"rb");
        
        header("Accept-Ranges: bytes");
        if(in_array(strtolower($ext),array("mp4","rmvb","flv","amr","webm"))){
            header("Content-type:video/$ext");
        }else if(in_array(strtolower($ext),array("mp3","ogg","wav"))){      //如果是图片
            header('Content-type:audio/'.$ext);
            header('x-upyun-content-type:audio/'.$ext);
        }else if(in_array(strtolower($ext),array("png","jpg","jpeg","gif","webp"))){      //如果是图片
            header('Content-type:image/'.$ext);
            header_remove("Accept-Ranges");
        }else if(strtolower($ext) == "m3u8"){
            header_remove("Accept-Ranges");
            header('content-type:application/vnd.apple.mpegURL');
        }
        
        //如果前端要求Range
        if(isset($_SERVER['HTTP_RANGE'])){
            header("HTTP/1.1 206 Partial Content");
            list($name, $range) = explode("=", $_SERVER['HTTP_RANGE']);
            list($begin, $end) =explode("-", $range);
            if($end == 0){
                $end = $size - 1;
            }
            fseek($file, $begin);
            header("Content-Range: bytes ".$begin."-".$end."/".$size);
        }else {
            $begin = 0; $end = $size - 1;
        }
        header("Content-Length: " . ($end - $begin + 1));
        

        //如果前端要求下载而不是在线打开
        if(!$_isstream){
            header("Content-Disposition: attachment; filename=$_name");
            header('Content-Type: application/octet-stream');
        }else{
            header("Content-Disposition: inline; filename=\"$_name\"; filename*=utf-8''$_name");
        }
        header("X-OutFileName: $_name");

        
        //flush();// 刷新内容
        while (!feof($file)){
          $p = min(1024*1024, ($end - $begin + 1));
          $begin = $begin + $p;
          echo fread($file,$p);// 发送当前部分文件给浏览者
          ob_flush();
          flush();// flush 内容输出到浏览器端
          if($p <= 0){
              break;
          }
        } 
        fclose($file);// 关闭文件流
    }else{
        exit(json(array("code"=>2,"msg"=>"No Found File:".$name)));
    }
}

//取文本右边
function GetRstr($str,$findstr,$pos=0){
	$onepos=strpos($str,$findstr,$pos);
	return substr($str,$onepos+strlen($findstr));
}

//文本过长 则取后60位
function basename2($_link){
    $headers = get_headers($_link,true);
    $web = geturlname($_link,false,$headers);
    $web = str_replace("/","_",str_replace("\\","_",$web));
    if($web){
        return unescape($web); 
    }else{
        $name = basename($_link);
        $sp = strrpos($name,"?");
        if($sp > 0){
            $name = substr($name,0,$sp);
        }
        if(strlen($name)>100){
            $name = substr( $name, -60 );
        }
        $name=str_replace('&',"",$name);
        if(!haspointfilename($name)){    //如果不是*.*格式
            $Ctype = $headers['Content-Type'];
            if($Ctype){
                $ext = GetRstr($Ctype,"/");
                if($ext=="vnd.apple.mpegURL"){
                    $ext="m3u8";
                }
                if($ext){
                    return unescape($name).".".$ext;
                }
            }
        }
        return unescape($name);
    }
}

//文件名字符串中是否是*.*格式
function haspointfilename($name){
    $pos = strpos($name,".");
    if($pos!==false&&$pos!=0&&$pos!=strlen($name)-1){    //有点
        return true;
    }else {
        return false;
    }
}

function geturlname($_url,$from302=false,$header=null){
    $headers = $header || get_headers($_url,true);
    //$load = $headers['Location'];
    //exit(json(array("code"=>3,"msg"=>$load)));
    if(!empty($headers['Location'])){//如果有302跳转
        return geturlname($headers['Location'],true);
    }else{
        $reheader = $headers['Content-Disposition'];
        if(isset($headers['Content-Disposition'])){//  [^;=\n]*=((['"]).*?\2|[^;\n]*)
            $reDispo = '/.*filename=(([\'\"]).*?\2|[^;\n]*)/m';
            if (preg_match($reDispo, $reheader, $mDispo))
            {
                $filename = trim($mDispo[1],' ";'); //移除字符串中所含有的这些字符
                if(!haspointfilename($filename)){    //如果不是*.*格式
                    $Ctype = $headers['Content-Type'];
                    if($Ctype){
                        $ext = GetRstr($Ctype,"/");
                        if($ext=="vnd.apple.mpegURL"){
                            $ext="m3u8";
                        }
                        if($ext){
                            return $filename.".".$ext;
                        }
                    }
                }
                return $filename;
            }
        }else if($from302){                    //看是不是被302跳转过
            if(is_array($_url)){
                $_url = $_url[0];
            }
            $name = basename($_url);
            $sp = strrpos($name,"?");
            if($sp > 0){
                $name = substr($name,0,$sp);
            }
            if(strlen($name)>60){
                $name = substr( $name, -60 );
            }
            $name=str_replace('&',"",$name);
            if(!haspointfilename($name)){    //如果不是*.*格式
                $Ctype = $headers['Content-Type'];
                if($Ctype){
                    $ext = GetRstr($Ctype,"/");
                    if($ext=="vnd.apple.mpegURL"){
                        $ext="m3u8";
                    }
                    if($ext){
                        return $name.".".$ext;
                    }
                }
            }
            return $name;
        }else{
            return false;
        }
    }

}

function linkRedis(){
    $redis = new Redis();
    try {
        $redis->connect(REDIS_IP,REDIS_PORT);
        $redis->setOption(Redis::OPT_PREFIX,'wget_');//设置表前缀
        return $redis;
    } catch (Exception $e) {
        header('Content-type: application/json; charset=utf-8');
        exit(json(array("code"=>3,"msg"=>"Redis连接出错:".$e)));
    }
}

//总容量max,剩余容量last
function getsize(){
    return array("max"=>disk_total_space(SAVEPATH),"last"=>disk_free_space(SAVEPATH));
    //return array("max"=>disk_total_space(SAVEPATH),"last"=>1024*1024*1024*1024);
}

function getmaxsize(){
    return disk_total_space(SAVEPATH);
}

function getfreesize(){
    return disk_free_space(SAVEPATH);
}

function rewrite_m3u8($path,$durl){
    //file_put_contents("/U/Download/1.txt","1");
    //重写m3u8
    $m3u8 = explode("\n",file_get_contents($path));
    $m3u8_data = "";
    foreach ($m3u8 as $i => $line) {
        //print($i . '.' . $line . PHP_EOL);
        if(substr($line, 0, strlen("http")) === "http" || substr($line, 0 , 1) == "#" || trim($line) == ""){    //http or https
            if(substr($line, 0, strlen("#EXT-X-KEY:")) === "#EXT-X-KEY:"){//KEY
                $pos = strpos($line,'URI="');
                if($pos === false){                //不是 URL="
                    $pos = strpos($line,'URI=');
                    if($pos === false){            //也不是 URL=
                        continue;
                    }else{
                        $pos += 4;
                    }
                }
                else{
                    $pos += 5;
                }
                $lstr = substr($line,0,$pos);
                $rstr = substr($line, $pos , strlen($line) - $pos);
                if(substr($rstr, 0 , 1) == "/"){    //左斜杠开头
                    $rs = parse_url($durl);
                    $m3u8_data .= $lstr . ($rs["scheme"]?($rs["scheme"]."://"):("http://")) . $rs["host"] . $rstr . "\n";
                }else if(substr($line, 0, strlen("http")) === "http"){
                    $m3u8_data .= $line . "\n";
                }else{
                    $m3u8_data .= $lstr . substr($durl,0,strrpos($durl,"/")+1) . $rstr . "\n";
                }
            }else{
                $m3u8_data .= $line . "\n";
            }
        }else if(substr($line, 0 , 1) == "/"){    // 左斜杠开头
            $rs = parse_url($durl);
            $m3u8_data .= ($rs["scheme"]?($rs["scheme"]."://"):("http://")) . $rs["host"] . $line . "\n";
        }else{
            $m3u8_data .= substr($durl,0,strrpos($durl,"/")+1) . $line . "\n";
        }
    }
    file_put_contents($path,$m3u8_data);
}

function unescape($str) { //这个是解密用的
         $str = rawurldecode($str); 
         preg_match_all("/%u.{4}|&#x.{4};|&#d+;|.+/U",$str,$r); 
         $ar = $r[0]; 
         foreach($ar as $k=>$v) { 
                  if(substr($v,0,2) == "%u") 
                           $ar[$k] = iconv("UCS-2","GBK",pack("H4",substr($v,-4))); 
                  elseif(substr($v,0,3) == "&#x") 
                           $ar[$k] = iconv("UCS-2","GBK",pack("H4",substr($v,3,-1))); 
                  elseif(substr($v,0,2) == "&#") { 
                           $ar[$k] = iconv("UCS-2","GBK",pack("n",substr($v,2,-1))); 
                  } 
         } 
         return join("",$ar); 
}
 
function phpescape($str){//这个是加密用的
    preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/",$str,$newstr);
    $ar = $newstr[0];
    foreach($ar as $k=>$v){
        if(ord($ar[$k])>=127){
            $tmpString=bin2hex(iconv("GBK","ucs-2",$v));
            if (!eregi("WIN",PHP_OS)){
                $tmpString = substr($tmpString,2,2).substr($tmpString,0,2);
            }
            $reString.="%u".$tmpString;
        } else {
            $reString.= rawurlencode($v);
        }
    }
    return $reString;
}
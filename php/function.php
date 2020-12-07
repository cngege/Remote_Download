<?php
/* code:
*0:没有安装
*1:正常返回
*2:URL请求出错或违规
*3:发生了什么情况导致出错,比如没有权限访问文件夹
*4:需要登录的地方却没有登录
*/



function isinstall(){
    if(is_dir('user')&&file_exists('user/install.lock')){
        return true;
    }
    return false;
}

//array到josn
function json($arr){
    return json_encode($arr);
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
    if(is_dir('user')){
        if(!file_exists("user/setup.php")){
            file_put_contents("user/setup.php","<?php\n");
        }
        file_put_contents("user/setup.php",'define("'.$key.'","'.$value.'");'."\n",FILE_APPEND);
    }else{
        mkdir('user',0777,true);
        writesetup($key,$value);
    }
}

function getdirfile(){
    $temp=scandir(SAVEPATH);
    
    $arrok = array();
    $arrno = array();
    $_json = array();
    foreach($temp as $_v){
        if(!is_dir($_v)){
            //取文件所在目录：dirname($v)
            $v = SAVEPATH.$_v;
            if(file_exists("user/".$_v.".json")){
                $_json = json_decode(file_get_contents("user/".$_v.".json"));
                if($_json->downing){
                    array_push($arrno,array(
                        "value"=>$_json,
                        "json"=>"user/".$_v.".json"
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
        if(in_array(strtolower($ext),array("mp4","rmvb","flv","amr","ogg","mp3"))){
            header("Content-type:video/$ext");
        }else if(in_array(strtolower($ext),array("png","jpg","jpeg","gif","webp"))){      //如果是图片
            header('Content-type:image/'.$ext);
            header_remove("Accept-Ranges");
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



//文本过长 则取后60位
function basename2($_link){
    $web = geturlname($_link);
    if($web){
        return $web; 
    }else{
        $name = basename($_link);
        $sp = strrpos($name,"?");
        if($sp > 0){
            $name = substr($name,0,$sp);
        }
        if(strlen($name)>100){
            $name = substr( $name, -60 );
        }
        return str_replace('&',"",$name);
    }
}

function geturlname($_url,$from302=false){
    $headers = get_headers($_url,true);
    //$load = $headers['Location'];
    //exit(json(array("code"=>3,"msg"=>$load)));
    if(!empty($headers['Location'])){//如果有302跳转
        return geturlname($headers['Location'],true);
    }else{
        $reheader = $headers['Content-Disposition'];
        if($reheader){//  [^;=\n]*=((['"]).*?\2|[^;\n]*)
            $reDispo = '/.*filename=(([\'\"]).*?\2|[^;\n]*)/m';
            if (preg_match($reDispo, $reheader, $mDispo))
            {
                $filename = trim($mDispo[1],' ";'); //移除字符串中所含有的这些字符
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
            return str_replace('&',"",$name);
        }else{
            return false;
        }
    }

}
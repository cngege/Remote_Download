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
                    )
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


// function downtoweb($_name){
//     $name = SAVEPATH.$_name;
//     if(file_exists($name)&&is_file($name)){
//         @set_time_limit(3600*2);
//         ob_clean();
//         header('Pragma: public');
//         header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
//         header('Cache-Control: no-store, max-age=0, no-cache, must-revalidate'); // HTTP/1.1
//         header('Cache-Control: post-check=0, pre-check=0', false);
//         header('Cache-control: private');// 发送 headers 
//         header('Content-Length: '.filesize($_name)); 
//         header('Content-Disposition: attachment; filename='.$_name);
//         header('Content-Type: application/octet-stream; name="'.$_name.'"');
//         flush();// 刷新内容
//         $file=fopen($name,"r");
//         while (!feof($file)){ 
//           echo fread($file,1024*1024);// 发送当前部分文件给浏览者 
//           flush();// flush 内容输出到浏览器端
//         } 
//         fclose($file);// 关闭文件流
//     }else{
//         exit(json(array("code"=>2,"msg"=>"No Found File:".$name)));
//     }
// }

function downtoweb($_name){
    $name = SAVEPATH.$_name;
    if(file_exists($name)&&is_file($name)){
        @set_time_limit(3600*2);
        ob_clean();
        $file_size = filesize($name);
        $ranges = getRange($file_size);
        $file=fopen($name,"r");
        header('Pragma: public');
        header('Content-Type: application/octet-stream; name="'.$_name.'"');
        header('Content-Disposition: attachment; filename='.$_name);
        if($reload && $ranges!=null){//断点续传
            header('HTTP/1.1 206 Partial Content');
            header('Accept-Ranges:bytes');
            header(sprintf('content-length:%u',$ranges['end']-$ranges['start']));
            header(sprintf('content-range:bytes %s-%s/%s', $ranges['start'], $ranges['end'], $file_size));
            fseek($file, sprintf('%u', $ranges['start']));
        }else{
            header('HTTP/1.1 200 OK');
            header('content-length:'.$file_size);
        }
        
        
        flush();// 刷新内容
        while (!feof($file)){ 
          echo fread($file,1024*1024);// 发送当前部分文件给浏览者 
          flush();// flush 内容输出到浏览器端
        } 
        fclose($file);// 关闭文件流
    }else{
        exit(json(array("code"=>2,"msg"=>"No Found File:".$name)));
    }
}


function getRange($file_size){ 
    if(isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])){ 
      $range = $_SERVER['HTTP_RANGE']; 
      $range = preg_replace('/[\s|,].*/', '', $range); 
      $range = explode('-', substr($range, 6)); 
      if(count($range)<2){ 
        $range[1] = $file_size; 
      } 
      $range = array_combine(array('start','end'), $range); 
      if(empty($range['start'])){ 
        $range['start'] = 0; 
      } 
      if(empty($range['end'])){ 
        $range['end'] = $file_size; 
      } 
      return $range; 
    } 
    return null; 
} 

//文本过长 则取后40位
function basename2($_link){
    $web = geturlname($_link);
    if($web){
        return $web; 
    }else{
        $name = basename($_link);
        if(strlen($name)>40){
            $name = substr( $name, -40 );
        }
        return trim($name,'/&= ');
    }
}

function geturlname($_url){
    $headers = get_headers($_url,true);
    $load = $headers['Location'];
    if(!empty($load)){//如果有302跳转
        return geturlname($load);
    }else{
        $reheader = $headers['Content-Disposition'];
        if($reheader){//  [^;=\n]*=((['"]).*?\2|[^;\n]*)
            $reDispo = '/.*filename=(([\'\"]).*?\2|[^;\n]*)/m';
            if (preg_match($reDispo, $reheader, $mDispo))
            {
                $filename = trim($mDispo[1],' ";'); //移除字符串中所含有的这些字符
                return $filename;
            }
        }
        return false;
    }

}
<?php
ini_set("session.cookie_httponly", 1); 
header('Access-Control-Allow-Origin:*');

require_once("php/function.php");
if(file_exists("user/setup.php")){
    require_once("user/setup.php");
}
require_once("php/login.php");
require_once("php/curl.php");
header('Content-type: application/json; charset=utf-8');

$type = @$_GET['type'];
$isins = isinstall();           //变量 此次请求的时候服务器是否已经安装过了

if(!isset($_GET['type'])){              //任何时候的请求都要带上type标记
    exit(json(array("code"=>2,"msg"=>"请求错误,没有参数type")));//请求错误 没有type
}
if(!$isins){                            //如果没有安装
    if($type == "install"){             //如果type标记是安装
        if(isset($_GET['circuit'])){
            if($_GET['circuit'] == "hascurl"){//流程是判断 是否有curl这个php扩展
                exit(json(array("code"=>1,"value"=>function_exists("curl_init"))));
            }else if($_GET['circuit'] == "setsavepath"){    //流程是设置下载文件的保存目录
                if(isset($_GET['path'])){
                    //$path = iconv("UTF-8", "GBK",$_GET['path']);
                    $path = $_GET['path'];
                    if(is_dir($path)){
                        if(check_dir_iswritable($path)){
                            writesetup("SAVEPATH",$path);
                            exit(json(array("code"=>1,"value"=>true)));
                        }else{
                            exit(json(array("code"=>1,"value"=>false,"msg"=>"该目录没有读写权限")));
                        }
                    }else{
                        mkdir ($path,0777,true);
                        if(check_dir_iswritable($path)){
                            writesetup("SAVEPATH",$path);
                            exit(json(array("code"=>1,"value"=>true)));
                        }else{
                            exit(json(array("code"=>1,"value"=>false,"msg"=>"失败,可能没有权限在该目录中操作")));
                        }
                    }
                }else{
                    exit(json(array("code"=>2,"msg"=>"请求错误,没有必须的参数")));
                }
            }else if($_GET['circuit'] == "setpasswd"){              //创建访问密码
                if(isset($_POST['key'])){
                    writesetup("PASSWD",md5(md5($_POST['key'])));
                    if(file_exists("user/setup.php")){
                        file_put_contents("user/install.lock","install ok");
                    }
                    exit(json(array("code"=>1,"value"=>true)));
                }
            }
        }
    }
    exit(json(array("code"=>0)));
}


if($type == 'login'){
    if(isset($_POST['key'])){
        if(login($_POST['key'])){
            exit(json(array("code"=>1,"value"=>true)));     //密码正确
        }else{
            exit(json(array("code"=>1,"value"=>false)));    //密码不正确
        }
    }else{
        exit(json(array("code"=>2)));               //URL出错
    }
}else if($type == "getfilelist"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录    
    exit(json(array("code"=>1,"value"=>getdirfile())));
}else if($type == "getdownloadlist"){               //获取下载列表
    
}else if($type == "delfile"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['file']) && file_exists(SAVEPATH.$_GET['file'])){
        chmod(SAVEPATH.$_GET['file'],0777);
        @unlink(SAVEPATH.$_GET['file']);
        //顺便将下载进度文件一起删除
        if(file_exists("user/".$_GET['file'].".json")){
            @unlink("user/".$_GET['file'].".json");
        }
        exit(json(array("code"=>1,"value"=>!file_exists(SAVEPATH.$_GET['file']))));
    }
}else if($type == "download"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['file'])){
        chmod(SAVEPATH.$_GET['file'],0777);
        downtoweb($_GET['file']);
    }
}else if($type == "curl"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['url'])){
        $fcurl = new curl($_GET['url']);
        $fcurl->start();
    }
}else if($type == "getdowninfo_one"){                //获取刚才下载的文件的进度文件
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['url'])){
        //$arr = array();
        
        $lock = file_get_contents("user/install.lock");
        $i = 0;
        do{
            if($i!=0){sleep(1);}
            $_json = json_decode(file_get_contents($lock));
            $i = $i+1;
        }while(empty($_json)&&$i<5);
        
        if(!empty($_json->filename) || $_json->filename == basename2($_GET['url'])){
            if(file_exists(SAVEPATH.$_json->filename)){
                exit(json(array("code"=>1,"value"=>$_json,"json"=>$lock)));
            }else{
                @unlink("user/".$_json->filename.".json");
            }
        }
        exit(json(array("code"=>1,"value"=>false,"debug"=>$_json)));
    }
}












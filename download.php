<?php
ini_set("session.cookie_httponly", 1); 
header('Access-Control-Allow-Origin:*');

define("config","user");                //定义配置文件所在目录

require_once("php/function.php");
if(file_exists(config."/setup.php")){
    require_once(config."/setup.php");
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
            if($_GET['circuit'] == "has_rely"){//流程是判断 是否有curl这个php扩展 改为判断各扩展的安装情况
                exit(json(array(
                    "code"=>1,
                    "value"=>array("curl"=>function_exists("curl_init"),"redis"=>class_exists("Redis"))
                    )));
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
            }else if($_GET['circuit'] == "setredis"){
                if(!isset($_GET['address']) || empty($_GET['address']) || !isset($_GET['port']) || empty($_GET['port'])){
                    exit(json(array("code"=>2,"msg"=>"请求错误,参数皆不能为空")));
                }else{
                    $redis = new Redis();
                    try {
                        $redis->connect($_GET['address'], $_GET['port']);
                        if($redis->ping() == "+PONG"){
                            writesetup("REDIS_IP",$_GET['address']);
                            writesetup("REDIS_PORT",$_GET['port']);
                            exit(json(array("code"=>1,"value"=>true)));
                        }else{
                            exit(json(array("code"=>3,"msg"=>"连接异常,请检查Redis服务是否启动")));
                        }
                    } catch (Exception $e) {
                        exit(json(array("code"=>1,"value"=>false,"msg"=>$e->getMessage())));
                    }
                    
                }
            }else if($_GET['circuit'] == "setpasswd"){              //创建访问密码
                if(isset($_POST['key'])){
                    writesetup("PASSWD",md5(md5($_POST['key'])));
                    if(file_exists(config."/setup.php")){
                        file_put_contents(config."/install.lock","install ok");
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
}else if($type == 'logout'){                        //退出登录
    logout();
    exit(json(array("code"=>1,"value"=>true)));
}else if($type == "getfilelist"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录    
    exit(json(array("code"=>1,"value"=>getdirfile())));
}else if($type == "getdownloadlist"){               //获取下载列表【前端没有】
    if(!islogin()){exit(json(array("code"=>4)));}
    
    
}else if($type == "delfile"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['file']) && file_exists(SAVEPATH.$_GET['file'])){
        chmod(SAVEPATH.$_GET['file'],0777);
        @unlink(SAVEPATH.$_GET['file']);
        $redis = linkRedis();
        //顺便将数据库文件下载信息一起删除
        $k = md5($_GET['file']);
        if($redis->srem("task",$k)){
            $redis->delete($k);
        }
        $redis->close();
        exit(json(array("code"=>1,"value"=>!file_exists(SAVEPATH.$_GET['file']))));
    }
}else if($type == "download"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['file'])){
        chmod(SAVEPATH.$_GET['file'],0777);
        downtoweb($_GET['file']);
    }
}else if($type == "openfile"){
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['file'])){
        chmod(SAVEPATH.$_GET['file'],0777);
        downtoweb($_GET['file'],true);
    }
}else if($type == "curl"){                          //下载文件到服务器
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['url'])){
        $fcurl = new curl($_GET['url']);
        $fcurl->start();
    }
}else if($type == "getdowninfo_one"){                //获取刚才下载的文件的进度文件[应该暂被废弃]
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['url'])){
        //$arr = array();
        
        $lock = file_get_contents(config."/install.lock");
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
                @unlink(config."/".$_json->filename.".json");
            }
        }
        exit(json(array("code"=>1,"value"=>false,"debug"=>$_json)));
    }else{
        exit(json(array("code"=>2,"msg"=>"缺少必要参数:url")));
    }
}else if($type == "deldowntask"){//删除下载任务
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['task'])){
        
        $redis = linkRedis();
        
        $data = dejson($redis->get($_GET['task']));
        if($data->fail || !$data->downing){      //如果这个下载连接是错误的 或者没有在下载
            if(!unlink($data->file)){            //删除失败
                $exist = "删除失败";
                if(!file_exists($data->file)){
                    $exist = "删除失败,文件不存在";
                }
                $redis->close();
                exit(json(array("code"=>3,"msg"=>$exist)));
            }else{
                $isok = false;
                if($redis->srem("task",$_GET['task'])){    //同样将这个查询key删除
                    $redis->del($_GET['task']);            //在redis中将这个key删除掉
                    $isok = true;
                }
                $redis->close();
                exit(json(array("code"=>1,"msg"=>"删除redis任务记录".$isok?"成功":"失败")));
            }
        }else{
            $redis->set("curlclose",$_GET['task']);
            $redis->close();
            exit(json(array("code"=>1)));
        }
    }else{
        exit(json(array("code"=>2,"msg"=>"缺少必要参数:task")));
    }
}else if($type == "getdowning"){    //获取下载进度【通过创建下载任务后返回的key】
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    if(isset($_GET['inquirykey'])){
        $redis = linkRedis();
        $_data = $redis->get($_GET['inquirykey']);
        $data = dejson($_data);
        if(!$data->downing){        //如果已经下载完成了
            if($redis->srem("task",$_GET['inquirykey'])){    //同样将这个查询key删除
                $redis->del($_GET['inquirykey']);        //如果已经下载完成了 就在redis中将这个key删除掉
            }
        }
        else if($data->starttime){
            if((microtime(true)*1000 - $data->starttime) > 1000*60*60*24 ){    //如果下载的时间已经超过了这个时间[24h] 则删除 (24h为脚本运行超时上限)
                if($redis->srem("task",$_GET['inquirykey'])){    //同样将这个查询key删除
                    $redis->del($_GET['inquirykey']);            //在redis中将这个key删除掉
                    $data->fail = true;                          //告诉前端出错
                    $_data =json($data);
                }
            }
        }
        $redis->close();
        exit($_data);
    }
}else if($type == "getcapa"){    //获取文件保存目录剩余空间使用情况[剩余空间/总空间]
    if(!islogin()){exit(json(array("code"=>4)));}   //没有登录 要求登录
    exit(json(array("code"=>1,"max"=>getmaxsize(),"free"=>getfreesize())));
}












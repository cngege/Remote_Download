<?php
function islogin(){
    //return true;
    if(!defined("Token"))
    {
        return false;
    }
    if(array_key_exists('key',$_COOKIE) && md5(PASSWD.Token) == $_COOKIE['key']){
        return true;
    }
    return false;
}

//前端手动输入key登陆
function login($key){
    $md5key = md5(md5($key));
    if($md5key == PASSWD){
        if(!defined("Token"))
        {
            $uuidval = uuid();
            writesetup("Token",$uuidval);
            setcookie('key',md5($md5key.$uuidval),time()+3600*24*30,"",gethost(),isHttps(),true);
            return true;
        }
        setcookie('key',md5($md5key.Token),time()+3600*24*30,"",gethost(),isHttps(),true);
        return true;
    }
    return false;
}

function logout(){
    //如果是登陆状态下退出登陆 那么就修改 **口令
    if(islogin())
    {
        //删除 setup.php
        $del = unlink(config."/setup.php");
        
        if($del)
        {
            //设置已有的SAVEPATH REDIS_IP REDIS_PORT PASSWD
            writesetup("SAVEPATH",SAVEPATH);
            writesetup("REDIS_IP",REDIS_IP);
            writesetup("REDIS_PORT",REDIS_PORT);
            writesetup("PASSWD",PASSWD);
            
            //再设置 **口令 Token
            writesetup("Token",uuid());
            setcookie('key',"",time()-3600,"",gethost(),isHttps(),true);
            return true;
        }
        else
        {
            return false;
        }
    }
    setcookie('key',"",time()-3600,"",gethost(),isHttps(),true);
    return true;
}
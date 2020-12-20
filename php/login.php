<?php
function islogin(){
    //return true;
    if(PASSWD == $_COOKIE['key']){
        return true;
    }
    return false;
}

//前端手动输入key登陆
function login($key){
    if(md5(md5($key)) == PASSWD){
        setcookie('key',md5(md5($key)),time()+3600*24*30,null,gethost(),null,true);
        return true;
    }
    return false;
}

function logout(){
    setcookie('key',"",time()-3600,null,gethost(),null,true);
}
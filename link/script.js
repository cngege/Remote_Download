//服务器地址 调试用 同目录下不填
let serveraddr="http://jp-tyo-dvm-2.sakurafrp.com:26292/wget/";
//let serveraddr="";

$(function(){
  //第一次打开页面的时候就获取一次文件列表
  getfilelist()
})

function getfilelist(){
  $.ajax({url:serveraddr+"download.php",data:{type:"getfilelist"},success:function(e){
      if(code(e)){
        alert(JSON.stringify(e.value));
      }
  }});
}

//安装配置页 判断是否有加载必须插件[按钮]
$(".install .install_curl button").click(function(event) {
  /* Act on the event */
  $(".install .install_curl").css("display","none");
  $(".install .setpath").css("display","inline");
});

//安装配置页 设置远程下载页保存路径[按钮]
$(".install .setpath button").click(function(event) {
  /* Act on the event */
  $.ajax({
    url: serveraddr+"download.php",
    dataType: 'json',
    data: {type: 'install',circuit:"setsavepath",path:$(".setpath .inputpath input").val()},
    success:function(e){
      if(code(e)){
        if(e.value){
          $(".install .setpath").css("display","none");
          $(".install .setpasswd").css("display","inline");
        }else{
          alert(e.msg);
        }
      }
    }
  })
});

//设置密码
$(".install .setpasswd button").click(function(event) {
  $.ajax({
    url: serveraddr+"download.php?type=install&circuit=setpasswd",
    type: 'POST',
    dataType: 'json',
    data: {key: $(".setpasswd .inputpasswd input").val()},
    success:function(e){
      if(code(e)){
        if(e.value){
          $(".install .setpasswd").css("display","none");
          $(".install").css("display","none");
          location.reload();
        }
      }
    }
  })
})

//登录
$(".install .login button").click(function(event) {
  /* Act on the event */
  $.ajax({
    url: serveraddr+"download.php?type=login",
    type: 'POST',
    dataType: 'json',
    data: {key: $(".login .inputpasswd input").val()},
    success:function(e){
      if(code(e)){
        if(e.value){
          $(".install .setpasswd").css("display","none");
          $(".install").css("display","none");
        }else{
          alert("密码不正确");
        }
      }
    }
  })
});










function code(event){
  num = event.code;
  switch (num) {
    case 0://没有安装[短弹窗警告 弹出安装窗口]
      $(".install,.install .install_curl").css("display","inline");
      let _install_curl = $.ajax({url:serveraddr+"download.php",data:{type:"install",circuit:"hascurl"},async:false}).responseJSON;
      if(code(_install_curl)){
        $("#is_ins_curl").text(_install_curl.value?"已安装":"未安装");
        $(".install .install_curl button").attr("disabled",!_install_curl.value);   //先禁用按钮
      }
      return false;
    break;
    case 1://正常
      return true;
    break;
    case 2://URL出错[短弹窗提示]
      alert(event.msg);
      return false;//阻止后面的操作
    break;
    case 3://服务端发生了错误[长弹窗警告]
      alert(event.msg);
      return false;
    break;
    case 4://没有登录[弹窗提示 + 调整登录窗口]
      $(".install,.install .login").css("display","inline");
      return false;
    break;
    default:
    return false;

  }
}

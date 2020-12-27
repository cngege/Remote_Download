//服务器地址 调试用 同目录下不填
//let serveraddr="http://jp-tyo-dvm-2.sakurafrp.com:26292/wget/";
let serveraddr="";
let func = [];

$(function(){
  if(document.URL.indexOf("file://")==0){
    serveraddr="http://jp-tyo-dvm-2.sakurafrp.com:26292/wget/";
    $.jqAlert({content:"使用DEBUG模式",type:"warning",autoTime:5});
  }
  //第一次打开页面的时候就获取一次文件列表
  getfilelist()
  //下面每一秒钟循环执行公开数组中的方法一般用作获取离线下载进度显示到前端
  setInterval(function(){
    for(i = 0;i<func.length;i++){
      if(typeof func[i] == "object" && typeof func[i][1] == "function"){
        func[i][1](func[i][0],i);
      }
    }
  },1000)
})







//URL下载btn【点击下载按钮的事件】:
$(".download_box .download_btn button").click(function(event) {
  /* Act on the event */
  let input = $(".download_input input");//[输入框节点]
  let clearid = null;
  if(input.val()!==''){
    $.jqAlert({content:"正在发送下载任务请求……",type:"success"});
    $.ajax({  //告诉服务器离线下载
      url: serveraddr+"download.php",
      data: {type: 'curl',url:input.val()},
      //timeout: 500,
      success:function(ve){
        if(code(ve)){
          if(ve.value){
            //{value:json数据,json:JSON路径}
            addfileing({key:ve.key});
            input.val('');
            $.jqAlert({content:"已建立下载任务",type:"success"});
          }
        }
      }
    })
  }
});


//安装配置页 判断是否有加载必须插件[按钮] 检查服务器扩展页按钮点击后
$(".install .install_rely button").click(function(event) {
  /* Act on the event */
  $(".install .install_rely").css("display","none");
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
          $(".install .setredis").css("display","inline");
        }else{
          $.jqAlert({content:e.msg,type:"error"});
        }
      }
    }
  })
});

//安装配置页 设置Redis地址和端口
$(".install .setredis button").click(function(event) {
  /* Act on the event */
  $.jqAlert({content:"正在验证连接……",type:"warning"})
  $.ajax({
    url: serveraddr+"download.php",
    dataType: 'json',
    data: {type: 'install',circuit:"setredis",address:$(".setredis .inputipbox input").val(),port:$(".setredis .inputportbox input").val()},
    success:function(e){
      if(code(e)){
        if(e.value){
          $.jqAlert({content:"Redis连接成功,请设置网站密码",type:"success"})
          $(".install .setredis").css("display","none");
          $(".install .setpasswd").css("display","inline");
        }else{
          $.jqAlert({content:e.msg,type:"error"});
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
        }else{
          $.jqAlert({content:"设置失败",type:"error"});
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
          getfilelist();
          $.jqAlert({content:"登录成功",type:"success"});
        }else{
          $.jqAlert({content:"密码不正确",type:"warning"});
        }
      }
    }
  })
});

// 设置按钮
$("#setup_btn").click(function(event) {
  /* Act on the event */
  //alert("建设中……");
});
// 退出登录按钮
$("#setup_logout").click(function(event) {
  /* Act on the event */
  $.ajax({
    url: serveraddr+"download.php",
    dataType: 'json',
    data: {type: "logout"},
    success:function(e){
      if(code(e)){
        if(e.value){
          location.reload();
        }
      }
    }
  })
});


// 页面内显示图片 控件事件
$(".imageview .closebtn").click(function(event) {
  /* Act on the event */
  $(".imageview").hide();
});




//获取并设置文件夹内文件
function getfilelist(){
  $.ajax({url:serveraddr+"download.php",data:{type:"getfilelist"},success:function(e){
      if(code(e)){
        //alert(JSON.stringify(e.value));

        for(let i in e.value.downok){
          addfileok(e.value.downok[i]);
        }
        for(let i in e.value.downing){
          addfileing(e.value.downing[i]);
        }
      }
  }});
}



function addfileok(fevent){
  let d = $(".copyright .download").clone(true);
  d.css("display","");
  d.find('.name').text(fevent.filename).attr("title",fevent.filename);
  //如果服务器指示这个下载任务是以报错而结束的话
  if(fevent.downinfo && fevent.downinfo.fail){
    d.find('.size').text("Download Error");
    d.find('.size').css("color","red");
    d.css("background","#dcdde1");
    if(fevent.downinfo.downsize&&fevent.downinfo.maxsize){
      d.find(".downloadbar").css("width",Math.round(fevent.downinfo.downsize/fevent.downinfo.maxsize * 100)+"%");
    }
  }else{
    d.find('.size').text(renderSize(fevent.filesize)).attr("title",renderSize(fevent.filesize));
  }
  d.data('data', fevent);
  d.data('type', "file");
  d.find('.open_btn').click(function(event) {
    /* Act on the event */
    //alert($(this).parent().parent().data("data").file)
    opennew(d.data("data"));
    //window.open(serveraddr+"download.php?type=openfile&file="+d.data("data").filename);
    //alert(JSON.stringify(d.data("data")));

  });
  d.find('.down_btn').click(function(event) {
    /* Act on the event */
    let eve = $(this).parent().parent().data("data");
    $.jqAlert({content:"准备下载……",type:"info"});
    $("#downiframe").attr('src', serveraddr+"download.php?type=download&file="+eve.filename);
  });
  d.find('.delete_btn').click(function(event) {
    /* Act on the event */
    let eve = $(this).parent().parent().data("data");
    $.ajax({
      url:serveraddr+"download.php",
      data:{type:"delfile",file:eve.filename},
      success:function(e){
        if(code(e)){
          if(e.value){
            d.hide("normal");
          }else{
            $.jqAlert({content:"删除失败",type:"warning"});
          }
        }
      }
    })

  });
  //d.prependTo($(".download_list"));
  d.appendTo($(".download_list"));
}

function addfileing(fevent){
  //将模板节点复制出来处理
  let d = $(".copyright .download").clone(true);
  d.css("display","");
  if(fevent.value){d.data('data', fevent.value)}  //只有当value存在的时候 才添加值到data
  d.data('type', "downinfo");
  d.find('.open_btn').click(function(event) {
    /* 浏览器打开-按钮点击事件 */
    if(d.data("type") == "file"){
      //let eve = $(this).parent().parent().data("data");
      //window.open(eve.file)
      opennew(d.data("data"));
      //window.open(serveraddr+"download.php?type=openfile&file="+d.data("data").filename);
      //alert(JSON.stringify(d.data("data")));
    }
  });
  d.find('.down_btn').click(function(event) {
    /* 下载到本地-按钮点击事件 */
    if(d.data("type") == "file"){
      let eve = $(this).parent().parent().data("data");
      $.jqAlert({content:"准备下载……",type:"info"});
      $("#downiframe").attr('src', serveraddr+"download.php?type=download&file="+eve.filename);
    }
  });
  d.find('.delete_btn').click(function(event) {
    /* 删除远端文件-按钮点击事件 */
    let eve = $(this).parent().parent().data("data");
    if(d.data("type") == "file"){
      $.ajax({
        url:serveraddr+"download.php",
        data:{type:"delfile",file:eve.filename},
        success:function(del_data){
          if(code(del_data)){
            if(del_data.value){
              d.hide("normal");
            }else{
              $.jqAlert({content:"删除失败",type:"warning"});
            }
          }
        }
      })
    }else if(d.data("type") == "downinfo"){   //结束下载任务
      $.ajax({
        url:serveraddr+"download.php",
        data:{type:"deldowntask",task:fevent.key},
        success:function(del_data){
          if(code(del_data)){
            d.hide("normal");
          }
        }
      })
    }
  });
  //添加到元素内开头
  d.prependTo($(".download_list"));
  //d.appendTo($(".download_list"));

  func.push([d,function(_e,i){
    if(_e.length <= 0 || _e.is(':hidden')){
      func[i]=[];
    }else{
      $.ajax({//请求下载进度
        url: serveraddr+"download.php",
        data:{type:"getdowning",inquirykey:fevent.key},
        dataType: 'json',
        success:function(e){
          if(!e.fail){
            if(!_e.data("data")){_e.data("data",e)}
            _e.find('.name').text(e.filename).attr("title",e.filename);
            if(e.maxsize!=0){
              _e.find(".downloadbar").css("width",Math.round(e.downsize/e.maxsize * 100)+"%");
            }
            if(!e.downing){
              //下载完成
              _e.data('type', "file");
              _e.find('.size').text("(结束)"+ renderSize(e.maxsize));
            }
            if((e.downsize == e.maxsize) && e.maxsize != 0){
              //下载完成
              func[i]=[]; //不再发出下载进度请求
              _e.data('type', "file");
              _e.find('.size').text("(完成)"+ renderSize(e.maxsize));
            }else{
              //正在下载的情况下
              if(e.maxsize!=0){
                _e.find('.size').text("("+Math.round(e.downsize/e.maxsize * 100)+"%"+")"+renderSize(e.downsize) + "/" + renderSize(e.maxsize));
              }
            }

          }else{
            func[i]=[]; //不再发出下载进度请求
            _e.find('.name').text(e.filename);
            _e.find('.size').text("Download Error");
          }
        },
        statusCode:{
          404:function(){
            //_e.find('.name').text(e.filename);
            _e.find('.size').text("404 FILE NOT FOUND");
          }
        }
      })
    }
  }]);
}

//计算大小 自动配置单位
function renderSize(value){
    if(null==value||value==''){
        return "0 Bytes";
    }
    var unitArr = new Array("Bytes","KB","MB","GB","TB","PB","EB","ZB","YB");
    var index=0,
        srcsize = parseFloat(value),
        index=Math.floor(Math.log(srcsize)/Math.log(1024));
    var size =srcsize/Math.pow(1024,index);
    //  保留的小数位数
    size=size.toFixed(2);
    return size+unitArr[index];
}

function opennew(data){
  let ext = data.filename.substr(data.filename.lastIndexOf(".")+1);
  let newopen = true;
  let img = ["png","jpg","jpeg","gif","webp"];
  $.each(img, function(index, el) {
    if(ext == el){
      //是图片
      $(".imageview img").attr("src",serveraddr+"download.php?type=openfile&file="+data.filename);
      $(".imageview .fileinfo").text(data.filename).attr("title",data.filename)
      $(".imageview").show();
      newopen = false;
    }
  });

  if(newopen)
    window.open(serveraddr+"download.php?type=openfile&file="+data.filename);
}


function code(event){
  num = event.code;
  switch (num) {
    case 0://没有安装[短弹窗警告 弹出安装窗口]
      $.jqAlert({content:"服务端没有进行配置,现在开始",type:"warning"});

      $(".install,.install .install_rely").css("display","inline");
      let _install_rely = $.ajax({url:serveraddr+"download.php",data:{type:"install",circuit:"has_rely"},async:false}).responseJSON;
      if(code(_install_rely)){
        $("#is_ins_curl").text(_install_rely.value.curl?"已安装":"未安装");
        $("#is_ins_redis").text(_install_rely.value.redis?"已安装":"未安装");
        $(".install .install_rely button").attr("disabled",!(_install_rely.value.curl&&_install_rely.value.redis));   //先禁用按钮
      }
      return false;
    break;
    case 1://正常
      return true;
    break;
    case 2://URL出错[短弹窗提示]
      $.jqAlert({content:event.msg,type:"error"});
      return false;//阻止后面的操作
    break;
    case 3://服务端发生了错误[长弹窗警告]
      $.jqAlert({content:event.msg,type:"error"});
      return false;
    break;
    case 4://没有登录[弹窗提示 + 调整登录窗口]
      $.jqAlert({content:"没有登录或密码过期,请登录",type:"warning"});
      if($(".install").css('display')=="none"||$(".install .login").css('display')=="none"){
        $(".install,.install .login").css("display","inline");
      }
      return false;
    break;
    default:
      $.jqAlert({content:"返回了未知的状态码："+num,type:"warning"});
      return false;

  }
}

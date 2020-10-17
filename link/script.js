//服务器地址 调试用 同目录下不填
let serveraddr="http://jp-tyo-dvm-2.sakurafrp.com:26292/wget/";
//let serveraddr="";
let func = [];

$(function(){
  //第一次打开页面的时候就获取一次文件列表
  getfilelist()
  setInterval(function(){
    for(i = 0;i<func.length;i++){
      if(typeof func[i] == "object" && typeof func[i][1] == "function"){
        func[i][1](func[i][0],i);
      }
    }
  },1000)
})







//URL下载btn:
$(".download_box .download_btn button").click(function(event) {
  /* Act on the event */
  let input = $(".download_input input");
  if(input.val()!==''){
    $.ajax({  //不要返回值 告诉服务器离线下载
      url: serveraddr+"download.php",
      data: {type: 'curl',url:input.val()},
      timeout: 20,
    })

    $.ajax({
      url: serveraddr+"download.php",
      dataType: 'json',
      data: {type: 'getdowninfo_one',url:input.val()},
      success:function(e){
        if(code(e)){
          if(e.value!==false){
            let d = $(".copyright .download").clone(true);
            d.css("display","");
            //d.find('.name').text(e.value[i].filename);
            //d.find('.size').text(renderSize(e.value[i].filesize));
            //d.data('data', e.value[i]);
            d.data('type', "downinfo");
            // d.find('.open_btn').click(function(event) {
            //   /* Act on the event */
            //   //alert($(this).parent().parent().data("data").file)
            //   window.open($(this).parent().parent().data("data").file)
            // });
            // d.find('.down_btn').click(function(event) {
            //   /* Act on the event */
            //   let eve = $(this).parent().parent().data("data");
            //   window.open(serveraddr+"download.php?type=download&file="+$(this).parent().parent().data("data").filename);
            // });
            // d.find('.delete_btn').click(function(event) {
            //   /* Act on the event */
            //   let eve = $(this).parent().parent().data("data");
            //   $.ajax({
            //     url:serveraddr+"download.php",
            //     data:{type:"delfile",file:eve.filename},
            //     success:function(e){
            //       if(code(e)){
            //         if(e.value){
            //           d.hide();
            //         }else{
            //           alert("删除失败");
            //         }
            //       }
            //     }
            //   })
            //
            // });
            //d.prependTo($(".download_list"));
            d.appendTo($(".download_list"));

            input.val('');

            func.push([d,function(_e,i){
              if(_e.is(':hidden')){
                func[i]=[];
              }else{
                $.ajax({//请求下载进度
                  url: serveraddr+e.value,
                  dataType: 'json',
                  success:function(e){
                    if(!e.fail){
                      _e.find('.name').text(e.filename);
                      _e.find('.size').text(renderSize(e.maxsize));
                      _e.find(".downloadbar").css("width",Math.round(e.downsize/e.maxsize)+"%")
                    }
                  }
                })
              }

            }]);

          }else{
            alert("调试:服务器没有发现下载信息文件");
          }
        }
      }
    })


  }

});


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
          getfilelist();
        }else{
          alert("密码不正确");
        }
      }
    }
  })
});




//获取并设置文件夹内文件
function getfilelist(){
  $.ajax({url:serveraddr+"download.php",data:{type:"getfilelist"},success:function(e){
      if(code(e)){
        //alert(JSON.stringify(e.value));
        for(let i in e.value){
          //e.value[i].filename
          let d = $(".copyright .download").clone(true);
          d.css("display","");
          d.find('.name').text(e.value[i].filename);
          d.find('.size').text(renderSize(e.value[i].filesize));
          d.data('data', e.value[i]);
          d.data('type', "file");
          d.find('.open_btn').click(function(event) {
            /* Act on the event */
            //alert($(this).parent().parent().data("data").file)
            window.open($(this).parent().parent().data("data").file)
          });
          d.find('.down_btn').click(function(event) {
            /* Act on the event */
            let eve = $(this).parent().parent().data("data");
            window.open(serveraddr+"download.php?type=download&file="+$(this).parent().parent().data("data").filename);
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
                    d.hide();
                  }else{
                    alert("删除失败");
                  }
                }
              }
            })

          });
          //d.prependTo($(".download_list"));
          d.appendTo($(".download_list"));
        }
      }
  }});
}

//计算大小 自动配置单位
function renderSize(value){
    if(null==value||value==''){
        return "0 Bytes";
    }
    var unitArr = new Array("Bytes","KB","MB","GB","TB","PB","EB","ZB","YB");
    var index=0,
        srcsize = parseFloat(value);
 index=Math.floor(Math.log(srcsize)/Math.log(1024));
    var size =srcsize/Math.pow(1024,index);
    //  保留的小数位数
    size=size.toFixed(2);
    return size+unitArr[index];
}

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

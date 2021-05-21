﻿# Remote_Download
 php离线下载
![完成图][1]

**部署文件到服务端后第一次在浏览器打开自动提示部署**

 - 检查服务器相关插件是否正常部署php_curl redis等
 - 提示输入离线在线的文件保存目录(最后以/结尾)
 - 提示输入redis 地址和端口 暂未支持输入redis密码
 - 提示设置网站离线下载(登录)密码

**全部配置完成后自动刷新页面,输入刚才设置的离线密码**
提示登录成功 即可在上方的输入框输入下载地址    点击输入框右边的按钮开始离线下载,下载任务显示在最上方的文件列表栏
**最上方进度条为显示服务器离线下载保存目录所在的分区剩余容量**

 - 下载成功 或 删除文件都会更新进度
 - 当服务端文件占用到达80%时显示进度条背景显示为黄色警告，当占用到达95%时，进度条背景显示为红色警告
 - 如果剩余空间不足以保存即将要下载的文件的时候 不下载并进行提示

**右下角设置按钮**

 - 设置按钮点击打开设置窗口
 - 当前仅支持设置离线下载cookie 和 退出登录
 - 登录cookie默认保存30天
 - 设置窗口中“指定下载文件重命名”功能实现

**离线文件列表栏**
从左到右分别为  文件名  文件大小  打开文件按钮 下载文件按钮  删除文件按钮
下载任务时 蓝色背景为已下载进度 文件大小出显示下载百分比  已下载大小，总大小
可以在下载进行中点击删除按钮删除下载任务。


**其他**
如果需要重新进行服务端配置或忘记密码，可删除服务端user文件夹即可
该前端页面弱兼容手机端
不支持其他语言
![内置图片查看][2]
![设置][3]
![移动端][4]

**开源库**
本项目涉及到的引用的其他项目:
Jquery 		: https://jquery.com/
Jquery_UI 	: https://jqueryui.com/
VideoJS		: https://videojs.com/
Iconfont
jqAlert		: https://www.jq22.com/jquery-info19211

  [1]: https://s.pc.qq.com/tousu/img/20210515/1434861_1621068253.jpg
  [2]: https://s.pc.qq.com/tousu/img/20210515/1364285_1621068259.jpg
  [3]: https://s.pc.qq.com/tousu/img/20210515/7459126_1621068265.jpg
  [4]: https://s.pc.qq.com/tousu/img/20210515/2859324_1621068269.jpg
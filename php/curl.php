<?php
class curl{
    public $url;
    public $fp;
    //public $fpjson;
    public $urlsize;
    public $redis;
    public $key;
    
    private $downfilename;
    
    public function __construct($_url){
        $this->url = $_url;
        $this->redis = linkRedis();
    }
    
    public function start(){
        try{
            ignore_user_abort(true); // 后台运行
            set_time_limit(3600*24); // 取消脚本运行时间的超时上限
            
            ob_end_clean();
            header("Connection: close");
            header("HTTP/1.1 200 OK");
            header('Content-type: application/json; charset=utf-8');
            ob_start();
            
            $this->downfilename = basename2($this->url);    //解析出将保存到本地的文件名
            $this->urlsize = filesize($this->url);
            $_ = "";
            while(file_exists(SAVEPATH.$_.$this->downfilename)){
                $_.="_";
            }
            //URL 解码 + "_"前缀
            $this->downfilename = urldecode($_.$this->downfilename);    //最后确认要保存到本地的文件名
            
            //file_put_contents(config."/install.lock",config."/".$this->downfilename.".json");
            $this->key = md5($this->downfilename);
            
            $this->redis->sadd("task",$this->key);                         //向redis：task集合前增加此下载任务
            echo json(array("code"=>1,"value"=>true,"key"=>$this->key));    //返回前端，传递此次任务的key
            
            header("Content-Length: ${ob_get_length()}");
            ob_end_flush();
            flush();
            if (function_exists("fastcgi_finish_request")) {
                fastcgi_finish_request(); /* 响应完成, 关闭连接 */
            } 
            
            $this->write($this->url,$this->downfilename,null,null,false,true);
            
            $this->fp = fopen(SAVEPATH.$this->downfilename, 'wb');
            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_FILE, $this->fp);
            //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //如果是0会导致curl的远程数据echo到前端
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt ($ch, CURLOPT_REFERER, $this->url);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:81.0) Gecko/20100101 Firefox/81.0'); 
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, "progress"));
            curl_setopt($ch,  CURLOPT_FOLLOWLOCATION, 1); // 302 跳转
            curl_exec($ch);
            if (curl_errno($ch)) {//如果发生了错误
                $this->write($this->url,$this->downfilename,$this->urlsize,null,true,false);
            }else{
                $_json = dejson($this->redis->get($this->key));
                $_json->downing=false;
                $this->redis->set($this->key,json($_json));
            }
            curl_close($ch);
            fclose($this->fp);
            $this->redis->close();
        }catch(Exception $e){
            $_json = dejson($this->redis->get($this->key));
            $_json->fail=true;
            $_json->downing=false;
            $this->redis->set($this->key,json($_json));
            
            exit(json(array("code"=>3,"msg"=>"CURL抛出异常:".$e->getMessage())));
        }

    }
    
    public function progress($fch, $countDownloadSize, $currentDownloadSize, $countUploadSize, $currentUploadSize){
        
        //如果要求结束下载的是我自己
        if($this->redis->get("curlclose")==$this->key){
            curl_close($fch);
            fclose($this->fp);
            
            $this->write($this->url,$this->downfilename,$countDownloadSize,$currentDownloadSize,false,false);
            //$this->redis->expire($this->key,1200);//设置失效时间
            $this->redis->close();
            
            @unlink(SAVEPATH.$this->downfilename);
            $this->redis->set("curlclose","");
            return;
        }
        $this->write($this->url,$this->downfilename,$countDownloadSize,$currentDownloadSize,false,!($countDownloadSize!=0 && $countDownloadSize == $currentDownloadSize));
        
    }
    
    public function write($_url,$_filename,$_max,$_size,$_fail=false,$_downing=true){
        $wdata=array(
            "url"=>$_url,                   //这个离线文件是下载的哪个URL
            "file"=>SAVEPATH.$this->downfilename,
            "filename"=>$_filename,         //保存到本地的文件名
            "maxsize"=>$_max,               //离线下载的文件的最大大小
            "downsize"=>$_size,             //离线下载的文件当前下载的大小
            "fail"=>$_fail,                 //是否错误 停止下载
            "close"=>false,                             //不写只读 如果为true 则中断下载
            "downing"=>$_downing            //是否正在下载中
        );
        $this->redis->set($this->key,json($wdata));
        //file_put_contents(config."/".$_filename.".json",json_encode($wdata));
    }
}









// * CURLOPT_INFILESIZE: 当你上传一个文件到远程站点，这个选项告诉PHP你上传文件的大小。
// * CURLOPT_VERBOSE: 如果你想CURL报告每一件意外的事情，设置这个选项为一个非零值。
// * CURLOPT_HEADER: 如果你想把一个头包含在输出中，设置这个选项为一个非零值。
// * CURLOPT_NOPROGRESS: 如果你不会PHP为CURL传输显示一个进程条，设置这个选项为一个非零值。注意：PHP自动设置这个选项为非零值，你应该仅仅为了调试的目的来改变这个选项。
// * CURLOPT_NOBODY: 如果你不想在输出中包含body部分，设置这个选项为一个非零值。
// * CURLOPT_FAILONERROR: 如果你想让PHP在发生错误(HTTP代码返回大于等于300)时，不显示，设置这个选项为一人非零值。默认行为是返回一个正常页，忽略代码。
// * CURLOPT_UPLOAD: 如果你想让PHP为上传做准备，设置这个选项为一个非零值。
// * CURLOPT_POST: 如果你想PHP去做一个正规的HTTP POST，设置这个选项为一个非零值。这个POST是普通的 application/x-www-from-urlencoded 类型，多数被HTML表单使用。
// * CURLOPT_FTPLISTONLY: 设置这个选项为非零值，PHP将列出FTP的目录名列表。
// * CURLOPT_FTPAPPEND: 设置这个选项为一个非零值，PHP将应用远程文件代替覆盖它。
// * CURLOPT_NETRC: 设置这个选项为一个非零值，PHP将在你的 ~./netrc 文件中查找你要建立连接的远程站点的用户名及密码。
// * CURLOPT_FOLLOWLOCATION: 设置这个选项为一个非零值(象 “Location: “)的头，服务器会把它当做HTTP头的一部分发送(注意这是递归的，PHP将发送形如 “Location: “的头)。
// * CURLOPT_PUT: 设置这个选项为一个非零值去用HTTP上传一个文件。要上传这个文件必须设置CURLOPT_INFILE和CURLOPT_INFILESIZE选项.
// * CURLOPT_MUTE: 设置这个选项为一个非零值，PHP对于CURL函数将完全沉默。
// * CURLOPT_TIMEOUT: 设置一个长整形数，作为最大延续多少秒。
// * CURLOPT_LOW_SPEED_LIMIT: 设置一个长整形数，控制传送多少字节。
// * CURLOPT_LOW_SPEED_TIME: 设置一个长整形数，控制多少秒传送CURLOPT_LOW_SPEED_LIMIT规定的字节数。
// * CURLOPT_RESUME_FROM: 传递一个包含字节偏移地址的长整形参数，(你想转移到的开始表单)。
// * CURLOPT_SSLVERSION: 传递一个包含SSL版本的长参数。默认PHP将被它自己努力的确定，在更多的安全中你必须手工设置。
// * CURLOPT_TIMECONDITION: 传递一个长参数，指定怎么处理CURLOPT_TIMEVALUE参数。你可以设置这个参数为TIMECOND_IFMODSINCE 或 TIMECOND_ISUNMODSINCE。这仅用于HTTP。
// * CURLOPT_TIMEVALUE: 传递一个从1970-1-1开始到现在的秒数。这个时间将被CURLOPT_TIMEVALUE选项作为指定值使用，或被默认TIMECOND_IFMODSINCE使用。

// 下列选项的值将被作为字符串：

// * CURLOPT_URL: 这是你想用PHP取回的URL地址。你也可以在用curl_init()函数初始化时设置这个选项。
// * CURLOPT_USERPWD: 传递一个形如[username]:[password]风格的字符串,作用PHP去连接。
// * CURLOPT_PROXYUSERPWD: 传递一个形如[username]:[password] 格式的字符串去连接HTTP代理。
// * CURLOPT_RANGE: 传递一个你想指定的范围。它应该是”X-Y”格式，X或Y是被除外的。HTTP传送同样支持几个间隔，用逗句来分隔(X-Y,N-M)。
// * CURLOPT_POSTFIELDS: 传递一个作为HTTP “POST”操作的所有数据的字符串。
// * CURLOPT_REFERER: 在HTTP请求中包含一个”referer”头的字符串。
// * CURLOPT_USERAGENT: 在HTTP请求中包含一个”user-agent”头的字符串。
// * CURLOPT_FTPPORT: 传递一个包含被ftp “POST”指令使用的IP地址。这个POST指令告诉远程服务器去连接我们指定的IP地址。这个字符串可以是一个IP地址，一个主机名，一个网络界面名(在UNIX下)，或是‘-’(使用系统默认IP地址)。
// * CURLOPT_COOKIE: 传递一个包含HTTP cookie的头连接。
// * CURLOPT_SSLCERT: 传递一个包含PEM格式证书的字符串。
// * CURLOPT_SSLCERTPASSWD: 传递一个包含使用CURLOPT_SSLCERT证书必需的密码。
// * CURLOPT_COOKIEFILE: 传递一个包含cookie数据的文件的名字的字符串。这个cookie文件可以是Netscape格式，或是堆存在文件中的HTTP风格的头。
// * CURLOPT_CUSTOMREQUEST: 当进行HTTP请求时，传递一个字符被GET或HEAD使用。为进行DELETE或其它操作是有益的，更Pass a string to be used instead of GET or HEAD when doing an HTTP request. This is useful for doing or another, more obscure, HTTP request. 注意: 在确认你的服务器支持命令先不要去这样做。下列的选项要求一个文件描述(通过使用fopen()函数获得)：　
// * CURLOPT_FILE: 这个文件将是你放置传送的输出文件，默认是STDOUT.
// * CURLOPT_INFILE: 这个文件是你传送过来的输入文件。
// * CURLOPT_WRITEHEADER: 这个文件写有你输出的头部分。
// * CURLOPT_STDERR: 这个文件写有错误而不是stderr。用来获取需要登录的页面的例子,当前做法是每次或许都登录一次,有需要的人再做改进了
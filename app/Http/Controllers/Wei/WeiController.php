<?php

namespace App\Http\Controllers\Wei;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
class WeiController extends Controller
{
    //
    public function valid(){
        echo $_GET['echostr'];
    }
    public function wxEvent(){
        //接受微信服务器推送
        $content=file_get_contents("php://input");
        $time=date("Y-m-d H:i:s");
        $str=$time . $content ."\n";
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
       // echo 'SUCCESS';
        $data = simplexml_load_string($content);
       // var_dump($data);
       // echo 'ToUserName:'.$data->ToUserName;echo"</br>";//微信号id
       //echo 'FromUserName:'.$data->FromUserName;echo"</br>";//用户openid
      // echo 'CreateTime:'.$data->CreateTime;echo"</br>";//时间
       //echo 'Event:'.$data->Event;echo"</br>";//事件类型
        $MsgType=$data->MsgType;
        $openid=$data->FromUserName;
        $wx_id=$data->ToUserName;
        $event=$data->Event;
        $MediaId=$data->MediaId;
        $token=$this->success_toke();
        //把文本存到数据库 ,图片，语音存到数据库
        if($MsgType=='text'){
            $m_text=$data->Content;
                //获取天气
                if(strpos($m_text,'+天气')){
                   //获取城市
                   $city=explode('+',$m_text)[0];
                  // echo $city; 
                   $url='https://free-api.heweather.net/s6/weather/now?key=HE1904161021221925&location='.$city;
                  $arr=json_decode(file_get_contents($url),true);
                    // echo '<pre>';print_r($arr);echo '</pre>';die;
                    if($arr['HeWeather6'][0]['status']=='unknown location'){
                        echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                    <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                    <CreateTime>'.time().'</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content>['.没有这个城市.']</Content>
                    </xml>
                    ';
                    }
                   $tmp=$arr['HeWeather6'][0]['now']['tmp'];//温度
                   $wind_dir=$arr['HeWeather6'][0]['now']['wind_dir'];//风向
                   $wind_sc=$arr['HeWeather6'][0]['now']['wind_sc'];//风力
                   $hum=$arr['HeWeather6'][0]['now']['hum'];//湿度
                   $str="温度: ".$tmp."\n"."风向：".$wind_dir."\n"."风力： ".$wind_sc."\n"."湿度".$hum."\n";
                   //echo $str;
                   if($arr['HeWeather6'][0]['status']=="ok"){
                    echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                    <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                    <CreateTime>'.time().'</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content>['.$str.']</Content>
                    </xml>
                    ';
                   }
                }else if($m_text=='图文消息'){
                    $res=DB::table('goods')->where(['goods_new'=>1])->take(1)->first();
                     $name=$res->goods_name;
                     $desc=$res->goods_desc;
                     $img=$res->goods_img;
                    $res=$res->goods_id;
                    $url="http://1809lianshijie.comcto.com/desc/$res";
                    echo '<xml>
                    <ToUserName><![CDATA['.$openid.']]></ToUserName>
                    <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                    <CreateTime>'.time().'</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>1</ArticleCount>
                    <Articles>
                      <item>
                        <Title><![CDATA['.$name.']]></Title>
                        <Description><![CDATA['.$desc.']]></Description>
                        <PicUrl><![CDATA['.'https://ss0.bdstatic.com/70cFuHSh_Q1YnxGkpoWK1HF6hhy/it/u=2984185296,2196422696&fm=27&gp=0.jpg'.']]></PicUrl>
                        <Url><![CDATA['.$url.']]></Url>
                      </item>
                    </Articles>
                  </xml>';
                die;
                }
            //把文字信息存到数据库
            $m_time=$data->CreateTime;
            $message=[
                'm_text'=>$m_text,
                'm_time'=>$m_time,
                'm_openid'=>$openid
            ];
            $res=DB::table('wx_message')->insert($message);
            if($res){
               // echo "成功";
            }else{
               // echo "失败";
            }
            //echo $Content;
        }else if($MsgType=='image'){
           // echo "a";
           $urla="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$token&media_id=$MediaId";
            //echo $url;die;
            $client = new Client;
            $response=$client->get(new Uri($urla));
           // var_dump($response);die;
           $header=$response->getHeaders();//获取响应头信息
         // print_r($header);die;
          $file_info=$header['Content-disposition'][0];
          //echo $file_info;die;
          $file_name=rtrim(substr($file_info,-20),'""');
             // echo $file_name;die;
             $new_file='weixin/'.substr(md5(time().mt_rand()),10,8).'_'.$file_name;
         //echo $new_file;die;
            $res=Storage::put($new_file,$response->getBody());
            if($res){
                echo "成功储存图片";  
            }else{          
                echo "失败储存图片";
            }
          
            $image=[
                'openid'=>$openid,
                'image'=>"\1809a\storage\app\weixin".$new_file
            ];
           // var_dump($image);die;
            $arr=DB::table('wx_image')->insert($image);
            if($arr){
                echo "ok保存";
            }else{
                echo "保存失败";
            }

        }else if($MsgType=='voice'){
            $urlb="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$token&media_id=$MediaId";
            //echo $url;die;
            $voice_str=file_get_contents($urlb);
            $file_name=time().mt_rand(11111,99999).'.amr';
            file_put_contents("/wwwroot/1809a/public/wx_voice/$file_name",$voice_str,FILE_APPEND);
           $voice_name="/wwwroot/1809a/public/wx_voice/$file_name";
           $voice=[
               'openid'=>$openid,
               'voice'=>$voice_name
           ];
           $arr=DB::table('wx_voice')->insert($voice);
           if($arr){
                echo"入库成功，语音";
           }else{
            echo"入库失败，语音";
           }
        }else if($MsgType=='video'){
            echo"video";
            $urlb="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$token&media_id=$MediaId";
            //echo $url;die;
            $video_time=date('Y-m-d H:i:s',time());
            $video_str=file_get_contents($urlb);
            file_put_contents("/wwwroot/1809a/public/wx_video/$video_time.mp4",$video_str,FILE_APPEND);
        }
       
        $whereOpenid=[
            'openid'=>$openid
        ];

        //print_r($u);die;
        if($event=='subscribe'){
            $userName=DB::table('userwx')->where($whereOpenid)->first();
            if($userName){
                    echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                    <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                    <CreateTime>'.time().'</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                   <Content>![CDATA['.'欢迎回来'.$userName->nickname.']]</Content>
                    </xml>
                    ';
            }else{
                $u=$this->getUserInfo($openid);
                $info=[
                    'openid'=>$openid,
                    'nickname'=>$u['nickname'],
                    'subscribe_time'=>$u['subscribe_time']
                ];
                $res=DB::table('userwx')->insert($info);
                if($res){
                    echo "ok";
                }else{
                    echo "no";
                }
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                    <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                    <CreateTime>'.time().'</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                   <Content>![CDATA['.'欢迎关注'.$u['nickname'].']]</Content>
                    </xml>
                    ';
            }
        }


    }
    //获取微信token
    public function success_toke(){
        // echo 1;die;
        //echo env('WX_APPID');die;
        $key="access_token";
        $token=Redis::get($key);
            //echo $token;die;
        if($token){
           // echo "cache";
           // return $token;
        }else{
            echo "No cache";
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET').'';
            // echo $url;
            $response=file_get_contents($url);

            //echo $response;die;
            $arr=json_decode($response,true);
            //var_dump($arr);
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token=$arr['access_token'];
        }
        return $token;

    }
    //测试
    public function test(){
        $access_token=$this->success_toke();
        echo $access_token;
    }
    //获取用户信息
    public function getUserInfo($openid){
        $a='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->success_toke().'&openid='.$openid.'&lang=zh_CN';
        //echo $a;
        $data=file_get_contents($a);
        $u=json_decode($data,true);
        return $u;
    }
    //创建公众号菜单
    public function createMenu(){
        //url
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->success_toke().'';
       // echo $url;
       //接口数据
       $post_arr=[
           'button'=>[
               [
                   'type'=>'click',
                   'name'=>'歌曲a',
                   'key'=>'key_menu_001'
               ],
               [
                'type'=>'click',
                'name'=>'书法',
                'key'=>'key_menu_002'
               ],
           ]
        ];
         $json_str=json_encode($post_arr,JSON_UNESCAPED_UNICODE);
       //发送请求
       $client = new Client;
       $response=$client->request('POST',$url,[
            'body'=>$json_str
       ]);
        //处理响应
        $res_str=$response->getBody();
        //echo $res_str;die;
        $arr=json_decode($res_str);
        if(($arr->errcode)>0){
            echo "创建菜单错误";
        }else{
            echo "创建菜单成功";
        }

    }
    //群发方法
    public function sendMse($openid_arr,$content){
        $msg=[
            "touser"=>$openid_arr,
            "msgtype"=>"text",
            "text"=>[
                "content"=>$content
                 ]
            ];
        $data=json_encode($msg,JSON_UNESCAPED_UNICODE);
        $url='https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$this->success_toke();
        $client=new Client();
        $response=$client->request('post',$url,[
            'body'=>$data
        ]);
        return $response->getBody();
    }
    //群发
    public function send(){
        $where=[
            'status'=>1
        ];
        $arr=DB::table('userwx')->where($where)->get()->toArray();
        //var_dump($arr);die;
       $openid_arr=array_column($arr,'openid');
        // print_r($openid_arr);
        $content="啊啊啊";
        $res=$this->sendMse($openid_arr,$content);
        echo $res;
    }
    //点击进入商品详情
    public function desc($id){
       $where=[
           'goods_id'=>$id
       ];
       $goodsinfo=DB::table('goods')->where($where)->first();
       //echo '<pre>';print_r($goodsinfo);echo '</pre>';die;
       $data=[
           'goodsinfo'=>$goodsinfo,
           'jsconfig'=>$this->cong(),
       ];
    //    $a=$this->cong();
    //    echo '<pre>';print_r($a);echo'</pre>';die;
       return view('wei.desc',$data);
    }
    //ticket
    function ticket(){
        $access_token=$this->success_toke();
        $key="ticket";
        $ticket=Redis::get($key);
        if($ticket){
           
        }else{
            $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$access_token&type=jsapi";
            $responsea=json_decode(file_get_contents($url),true);
            // return $responsea;
                Redis::set($key,$responsea['ticket']);
                Redis::expire($key,3600);
                $ticket=$responsea['ticket'];
        }
        return $ticket;
    }
    //
    function cong(){
        $ticket=$this->ticket();
        //生成签名
        $nonceStr=Str::random(10);
       
        //dd($ticket);   
        $timestamp=time();
        $current_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'];
        //echo($current_url);die;
        $string1 = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$current_url";
        $sign= sha1($string1);
        $jsconfig=[
            'appId'=>env('WX_APPID'),   //公众号的唯一标识
            'timestamp'=>$timestamp,   //生成签名的时间戳
            'nonceStr'=> $nonceStr,     //生成签名的随机串
            'signature'=> $sign,   //签名
            'current_url'=>$current_url
        ];
       return $jsconfig;
    }
}
                            

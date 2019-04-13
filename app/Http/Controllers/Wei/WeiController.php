<?php

namespace App\Http\Controllers\Wei;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client; 
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
            $m_time=$data->CreateTime;
            $message=[
                'm_text'=>$m_text,
                'm_time'=>$m_time,
                'm_openid'=>$openid
            ];
            $res=DB::table('wx_message')->insert($message);
            if($res){
                echo "成功";
            }else{
                echo "失败";
            }
            //echo $Content;
        }else if($MsgType=='image'){
            $urla="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$token&media_id=$MediaId";
            //echo $url;die;
            $img_time=date('Y-m-d H:i:s',time());
            $img_str=file_get_contents($urla);
            file_put_contents("/wwwroot/1809a/public/wx_img/$img_time.jpg",$img_str,FILE_APPEND);
        }else if($MsgType=='voice'){
            $urlb="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$token&media_id=$MediaId";
            //echo $url;die;
            $voice_time=date('Y-m-d H:i:s',time());
            $voice_str=file_get_contents($urlb);
            file_put_contents("/wwwroot/1809a/public/wx_voice/$voice_time.mp3",$voice_str,FILE_APPEND);
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
            echo "cache";
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
}


<?php

namespace App\Http\Controllers\Wei;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
//        echo 'ToUserName:'.$data->ToUserName;echo"</br>";//微信号id
//        echo 'FromUserName:'.$data->FromUserName;echo"</br>";//用户openid
//        echo 'CreateTime:'.$data->CreateTime;echo"</br>";//时间
//        echo 'Event:'.$data->Event;echo"</br>";//事件类型
        $wx_id=$data->ToUserName;
        $openid=$data->FromUserName;
        $whereOpenid=[
            'openid'=>$openid
        ];

        $event=$data->Event;
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

    //            echo $response;die;
            $arr=json_decode($response,true);
            //var_dump($arr);
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token=$arr['access_token'];
        }
        return $token;

    }
    public function test(){
        $access_token=$this->success_toke();
        echo $access_token;
    }

    //
    public function getUserInfo($openid){
        $a='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->success_toke().'&openid='.$openid.'&lang=zh_CN';
        //echo $a;
        $data=file_get_contents($a);
        $u=json_decode($data,true);
        return $u;
    }
}


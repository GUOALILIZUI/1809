<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;


class WeiController extends Controller
{

    //接口配置
    public function check(Request $request){
        //首次接入检测请求是否为微信
        //echo $request->input('echostr');exit;
        $content=file_get_contents("php://input");
        $time=date("Y-m-d H:i:s");
        $str=$time.$content.'\n';
        file_put_contents("logs/check.log",$str,FILE_APPEND);
        $xmlObj=simplexml_load_string($content);
        $FromUserName=$xmlObj->FromUserName;
        $Event=$xmlObj->Event;
        //用户信息
        $access=$this->accessToken();
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access&openid=$FromUserName&lang=zh_CN";
        $response=file_get_contents($url);
        $info=json_decode($response,true);
        $name=$info['nickname'];

        //入库
        if($Event=='subscribe'){
            $weiInfo=[
                'name'=>$name,
                'sex'=>$info['sex'],
                'img'=>$info['headimgurl'],
                'openid'=>$info['openid'],
                'time'=>time()
            ];
           DB::table('ks')->insert($weiInfo);
        }
    }

    //获取accessToken
    public function accessToken(){
        $key='aa';
        $token=Redis::get($key);
        //if($token){

        //}else{
            $appId="wxdd0d451ebdddd4f9";
            $app_secret="3a0980e46f62a1f9b759fa11adaab484";
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$app_secret";
            //var_dump($url);
            $response=file_get_contents($url);
            //echo $response;echo '<hr>';
            $arr=json_decode($response,true);

            //accesstoken存缓存
            $key='aa';
            Redis::set($key,$arr['access_token']);
            //Redis::get($key);
            Redis::expire($key,3600);
            $token=$arr['access_token'];
            print_r($token);
        //}
        return $token;



    }

    //一级菜单
    public function custom(){
        $access = $this->accessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$access";
        $arr = array(
            "button"=>array(
                array(
                    "name"=>"位置",
                    "type"=>"location_select",
                    "key"=>"rselfmenu_2_0"
                ),
                array(
                    "name"=>"酸酸的",
                    "sub_button"=>array(
                        array(
                            "type"=>"view",
                            "name"=>"百度度",
                            "url"=>"http://www.baidu.com/"
                        ),
                        array(
                            "type"=>"click",
                            "name"=>"点击",
                            "key"=>"V1001_TODAY_MUSIC"
                        ),
                        array(
                            "type"=>"pic_sysphoto",
                            "name"=>"拍照",
                            "key"=>"rselfmenu_1_0", 
                            "sub_button"=> [ ]

                        ),

                    ),
                )

            )
        );
        $strJson=json_encode($arr,JSON_UNESCAPED_UNICODE);
        $client=new Client();
        $response =$client->request('POST',$url,[
            'body'  => $strJson,
        ]);
        $objJson=$response->getBody();
        $info=json_decode($objJson,true);
        print_r($info);

    }



}

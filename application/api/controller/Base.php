<?php
namespace app\api\controller;
/**
 * 接口基类
 * @since   2018/04/25 创建
 * @author  itarvin itarvin@163.com
 */
use think\Controller;
use app\model\Admin;
use think\facade\Request;
use think\facade\Cookie;
use app\Util\ReturnCode;
use app\Util\Tools;
class Base extends Controller
{
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        // 校验是否存在当前cookie
        if( Cookie::has('identity')){

            $token = Cookie::get('identity');
            // 获取客户端设备
            $agent = Request::header('User-Agent');
            // 析出用户id和客户信息
            $result = $this->analysisCode($token);
            // 解析token验证
            $key = json_decode(base64_decode($result['token'], true), true);

            $uid = json_decode(base64_decode($result['uid']));

            $preview = Admin::field('users, pwd')->find($uid);

            $this->uid = $uid;
            // var_dump($this->uid);
            // 验证是否当前用户设备与提交的用户设备一致.
            if( $key['agent'] == $agent && md5($preview['users'].$preview['pwd']) == $key['salt']){

                $this->AuthPermission = '200';
            }else{

                if( md5($preview['users'].$preview['pwd']) == $key['salt']){

                    $this->AuthPermission = '300';
                    cookie('identity', null);
                }else {

                    $this->AuthPermission = '400';
                    cookie('identity', null);
                }
            }
        }else {

            $this->AuthPermission = '400';
        }
    }


    /**
     * 由状态值返回对应对应回应
     * @param $res
     * @return array
     */
    public function returnRes($AuthPermission, $isCheck = 'false')
    {
        if( $isCheck == 'true'){
            if( $AuthPermission == "400"){

                return buildReturn(['status' =>ReturnCode::ACCOUNTEXPIRED,'info' => "请您先登录账户！"]);
            }else if( $AuthPermission == "300"){

                return buildReturn(['status' =>ReturnCode::AUTH_ERROR,'info' => Tools::errorCode(ReturnCode::AUTH_ERROR)]);
            }
        }
    }


    /**
     * base64二次加密数据进行析出
     * @return array
     */
    private function analysisCode($token)
    {
        // 析出当前数据对比验证
        $passwordBook = Tools::makeRandom();
        // 分离关键字母
        $begin = substr($token,0,1);
        $finish = substr($token,-1);
        $middles = substr($token,1,-1);
        // 定位出UID位置以及单位长度
        $beginKey = array_search($begin,$passwordBook);
        $finishKey = array_search($finish,$passwordBook);
        // 析出用户id
        $uidkey = substr($middles,$beginKey,$finishKey);
        // 合并剩下的字符串
        $all = substr($middles,0,$beginKey).substr($middles,($beginKey+$finishKey));
        // 计算长度
        $allLen = strlen($all);
        // 取出32位介质分离出tokeb前半部和后半部
        $reTokenStart = mb_substr($all, 0, (($allLen-32)/2), 'utf-8');
        $reTokenEnd = mb_substr($all, (($allLen-32)/2) + 32);
        // 合并数据返回
        $result = $reTokenStart.$reTokenEnd;
        return array(
            'uid'   => $uidkey,
            'token' => $result
        );
    }

    /**
     * 空操作返回状态
     * @return json
     */
    public function _empty($name)
    {
        return json(array(
            'status' => '404',
            'info' => '你想要的操作对象！程序员小哥哥没找到！(╯﹏╰)'
        ));
    }
}

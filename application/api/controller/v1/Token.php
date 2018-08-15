<?php
namespace app\api\controller\v1;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Oauth;

/**
 * 生成token
 */
class Token
{
	use Send;

	/**
	 * 请求时间差
	 */
	public static $timeDif = 10000;

	public static $accessTokenPrefix = 'accessToken_';
	public static $expires = 720000;
	/**
	 * 测试appid，正式请数据库进行相关验证
	 */
	public static $appid = 'tp5restfultest';
	/**
	 * appsercet
	 */
	public static $appsercet = '123456';
	/**
	 * 生成token
	 */
	public function token(Request $request)
	{
		//参数验证
		$validate = new \app\api\validate\Token;
		if(!$validate->check(input(''))){
			return self::returnMsg(401,$validate->getError());
		}
		self::checkParams(input(''));  //参数校验
		//数据库已经有一个用户,这里需要根据input('mobile')去数据库查找有没有这个用户
		input('uid') = 1; //虚拟一个uid返回给调用方
		try {
			$accessToken = self::setAccessToken(input(''));  //传入参数应该是根据手机号查询改用户的数据
			return self::returnMsg(200,'success',$accessToken);
		} catch (Exception $e) {
			return self::returnMsg(500,'fail',$e);
		}
	}

	/**
	 * 参数检测
	 */
	public static function checkParams($params = [])
	{	
		//时间戳校验
		if(abs($params['timestamp'] - time()) > self::$timeDif){

			return self::returnMsg(401,'请求时间戳与服务器时间戳异常','timestamp：'.time());
		}

		//appid检测，这里是在本地进行测试，正式的应该是查找数据库或者redis进行验证
		if($params['appid'] !== self::$appid){
			return self::returnMsg(401,'appid 错误');
		}

		//签名检测
		$sign = Oauth::makeSign($params,self::$appsercet);
		if($sign !== $params['sign']){
			return self::returnMsg(401,'sign错误','sign：'.$sign);
		}
	}

	/**
     * 设置AccessToken
     * @param $clientInfo
     * @return int
     */
    protected function setAccessToken($clientInfo)
    {
        //生成令牌
        $accessToken = self::buildAccessToken();
        $accessTokenInfo = [
            'access_token' => $accessToken,//访问令牌
            'expires_time' => time() + self::$expires,      //过期时间时间戳
            'client' => $clientInfo,//用户信息
        ];
        self::saveAccessToken($accessToken, $accessTokenInfo);
        return $accessTokenInfo;
    }

    /**
     * 生成AccessToken
     * @return string
     */
    protected static function buildAccessToken($lenght = 32)
    {
        //生成AccessToken
        $str_pol = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
		return substr(str_shuffle($str_pol), 0, $lenght);

    }

    /**
     * 存储
     * @param $accessToken
     * @param $accessTokenInfo
     */
    protected static function saveAccessToken($accessToken, $accessTokenInfo)
    {
        //存储accessToken
        cache(self::$accessTokenPrefix . $accessToken, $accessTokenInfo, self::$expires);
    }
}
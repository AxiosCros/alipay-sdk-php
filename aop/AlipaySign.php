<?php
/**
 * Created by PhpStorm.
 * User: jiehua
 * Date: 15/5/2
 * Time: 下午6:21
 */

namespace Alipay;

class AlipaySign
{
    /**
     * 响应签名节点名
     */
    const SIGN_NODE = "sign";

    /**
     * 签名类型
     *
     * @var string
     */
    protected $type = 'RSA2';

    /**
     * 商户私钥（又称：小程序私钥，App私钥等）
     * 支持文件路径或私钥字符串，用于生成签名
     *
     * @var string
     */
    protected $appPrivateKey;

    /**
     * 支付宝公钥
     * 支持文件路径或公钥字符串，用于验证签名
     *
     * @var string
     */
    protected $alipayPublicKey;

    /**
     * 创建 AlipaySign 实例
     *
     * @param  string $signType
     * @param  string $appPrivateKey
     * @param  string $alipayPublicKey
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function create($appPrivateKey, $alipayPublicKey, $signType = 'RSA2')
    {
        $instance = new self();
        $typeAlgoMap = $instance->typeAlgoMap();
        if(!isset($typeAlgoMap[$signType])) {
            throw new \InvalidArgumentException('Unknown sign type: ' . $signType);
        }
        $instance->type = $signType;
        $instance->appPrivateKey = $instance->getKey($appPrivateKey, true);
        $instance->alipayPublicKey = $instance->getKey($alipayPublicKey, false);
        return $instance;
    }

    protected function __construct()
    {
    }

    public function __destruct()
    {
        @openssl_free_key($this->appPrivateKey);
        @openssl_free_key($this->alipayPublicKey);
    }

    /**
     * 签名（计算 Sign 值）
     *
     * @param string $data
     * @return void
     * @see https://docs.open.alipay.com/291/106118
     */
    public function generate($data)
    {
        openssl_sign($data, $sign, $this->appPrivateKey, $this->getSignAlgo());
        return base64_encode($sign);
    }

    public function generateByParams($params)
    {
        $data = $this->convertSignData($params);
        return $this->generate($data);
    }

    /**
     * 验签（验证 Sign 值）
     *
     * @param [type] $sign
     * @param [type] $data
     * @return void
     * @see https://docs.open.alipay.com/200/106120
     */
    public function verify($sign, $data)
    {
        return 1 === openssl_verify($data, base64_decode($sign), $this->alipayPublicKey, $this->getSignAlgo());
    }

    /**
     * 使用密钥字符串或路径加载密钥
     *
     * @param  string  $keyOrFilePath
     * @param  boolean $isPrivate
     * @return resource
     * @throws AlipayInvalidKeyException
     */
    protected function getKey($keyOrFilePath, $isPrivate = true)
    {
        if (file_exists($keyOrFilePath) && is_file($keyOrFilePath)) {
            $key = file_get_contents($keyOrFilePath);
        } else {
            $key = $keyOrFilePath;
        }
        if ($isPrivate) {
            $keyResource = openssl_pkey_get_private($key);
        } else {
            $keyResource = openssl_pkey_get_public($key);
        }
        if ($keyResource === false) {
            throw new AlipayInvalidKeyException('Invalid key: ' . $keyOrFilePath);
        }
        return $keyResource;
    }

    public function convertSignData($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        foreach ($params as $k => $v) {
            if (false === AlipayHelper::isEmpty($v) && "@" !== substr($v, 0, 1)) {
                $stringToBeSigned .= "&{$k}={$v}";
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 1);
        return $stringToBeSigned;
    }

    protected function typeAlgoMap()
    {
        return [
            'RSA' => OPENSSL_ALGO_SHA1,
            'RSA2' => OPENSSL_ALGO_SHA256,
        ];
    }

    public function getSignType()
    {
        return $this->type;
    }

    public function getSignAlgo()
    {
        return $this->typeAlgoMap()[$this->type];
    }
}
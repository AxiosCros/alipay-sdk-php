<?php

use PHPUnit\Framework\TestCase;
use Alipay\AlipayResponse;
use Alipay\AlipayResponseFactory;

class ResponseTest extends TestCase
{
    const SIGN = 'AN_EXAMPLE_SIGN';

    public function testFactory()
    {
        $parser = new AlipayResponseFactory();
        $this->assertEquals('JSON', $parser->getFormat());
        return $parser;
    }

    /**
     * @depends testFactory
     */
    public function testParseError(AlipayResponseFactory $parser)
    {
        $response = '{
            "error_response": {
                "code": "20000",
                "msg": "Service Currently Unavailable",
                "sub_code": "isp.unknow-error",
                "sub_msg": "系统繁忙"
            }
        }';
        $ins = $parser->parse($response);
        $this->assertInstanceOf('Alipay\AlipayResponse', $ins);
        $this->assertFalse($ins->isSuccess());
        return $ins;
    }

    /**
     * @depends testFactory
     */
    public function testParseSuccess(AlipayResponseFactory $parser)
    {
        $response = '{
            "alipay_system_oauth_token_response": {
                "user_id": "2088102150477652",
                "access_token": "20120823ac6ffaa4d2d84e7384bf983531473993",
                "expires_in": "3600",
                "refresh_token": "20120823ac6ffdsdf2d84e7384bf983531473993",
                "re_expires_in": "3600"
            },
            "sign": "' . self::SIGN . '"
        }';
        $ins = $parser->parse($response);
        $this->assertInstanceOf('Alipay\AlipayResponse', $ins);
        $this->assertEquals($response, $ins->getRaw());
        $this->assertTrue($ins->isSuccess());
        return $ins;
    }

    /**
     * @depends testFactory
     * @expectedException Alipay\Exception\AlipayInvalidResponseException
     */
    public function testParseInvalidResponse(AlipayResponseFactory $parser)
    {
        $response = 'this is an invalid response';
        $parser->parse($response);
    }

    /**
     * @depends testParseSuccess
     */
    public function testStripData(AlipayResponse $ins)
    {
        $data = $ins->stripData();
        $this->assertGreaterThan(0, strlen($data));
    }

    /**
     * @depends testParseSuccess
     */
    public function testGetSign(AlipayResponse $ins)
    {
        $sign = $ins->getSign();
        $this->assertEquals(self::SIGN, $sign);
    }

    /**
     * @depends testParseError
     * @expectedException Alipay\Exception\AlipayInvalidResponseException
     */
    public function testSignNotFound(AlipayResponse $ins)
    {
        $ins->getSign();
    }

    /**
     * @depends testParseSuccess
     */
    public function testGetData(AlipayResponse $ins)
    {
        $data = $ins->getData();
        $this->assertTrue(is_array($data));

        $data = $ins->getData(false);
        $this->assertTrue(is_object($data));
    }

    /**
     * @depends testParseError
     */
    public function testGetError(AlipayResponse $ins)
    {
        $data = $ins->getError();
        $this->assertTrue(is_array($data));

        $data = $ins->getError(false);
        $this->assertTrue(is_object($data));
    }

    /**
     * @depends testParseError
     * @expectedException Alipay\Exception\AlipayErrorResponseException
     */
    public function testGetDataFromError(AlipayResponse $ins)
    {
        $ins->getData();
    }

    /**
     * @depends testParseSuccess
     */
    public function testGetErrorFromSuccess(AlipayResponse $ins)
    {
        $error = $ins->getError();
        $this->assertNull($error);
    }
}
<?php

namespace Pushcommerce\Tests;

use Pushcommerce\Client;
use Pushcommerce\Exception\BadMethodCallException;
use Pushcommerce\HttpClient\Plugin\Authentication;
use Http\Client\Common\Plugin;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldNotHaveToPassHttpClientToConstructor()
    {
        $client = new Client();

        $this->assertInstanceOf('\Http\Client\HttpClient', $client->getHttpClient());
    }

    /**
     * @test
     */
    public function shouldPassHttpClientInterfaceToConstructor()
    {
        $client = new Client($this->getMock('Http\Client\HttpClient'));

        $this->assertInstanceOf('Http\Client\HttpClient', $client->getHttpClient());
    }

    /**
     * @test
     * @dataProvider getAuthenticationFullData
     */
    public function shouldAuthenticateUsingAllGivenParameters($login, $password, $method)
    {
        $client = $this->getMock('Pushcommerce\Client', array('addPlugin', 'removePlugin'));
        $client->expects($this->once())
            ->method('addPlugin')
            ->with($this->equalTo(new Authentication($login, $password, $method)));

        $client->expects($this->once())
            ->method('removePlugin')
            ->with(Authentication::class);

        $client->authenticate($login, $password, $method);
    }

    public function getAuthenticationFullData()
    {
        return array(
            array('login', 'password', Client::AUTH_HTTP_PASSWORD),
            array('token', null, Client::AUTH_HTTP_TOKEN),
            array('token', null, Client::AUTH_URL_TOKEN),
            array('client_id', 'client_secret', Client::AUTH_URL_CLIENT_ID),
        );
    }

    /**
     * @test
     * @dataProvider getAuthenticationPartialData
     */
    public function shouldAuthenticateUsingGivenParameters($token, $method)
    {
        $client = $this->getMock('Pushcommerce\Client', array('addPlugin', 'removePlugin'));
        $client->expects($this->once())
            ->method('addPlugin')
            ->with($this->equalTo(new Authentication($token, null, $method)));

        $client->expects($this->once())
            ->method('removePlugin')
            ->with(Authentication::class);

        $client->authenticate($token, $method);
    }

    public function getAuthenticationPartialData()
    {
        return array(
            array('token', Client::AUTH_HTTP_TOKEN),
            array('token', Client::AUTH_URL_TOKEN),
        );
    }

    /**
     * @test
     * @expectedException \Pushcommerce\Exception\InvalidArgumentException
     */
    public function shouldThrowExceptionWhenAuthenticatingWithoutMethodSet()
    {
        $client = new Client();

        $client->authenticate('login', null, null);
    }

    /**
     * @test
     */
    public function shouldClearHeaders()
    {
        $client = $this->getMock('Pushcommerce\Client', array('addPlugin', 'removePlugin'));
        $client->expects($this->once())
            ->method('addPlugin')
            ->with($this->isInstanceOf(Plugin\HeaderAppendPlugin::class));

        $client->expects($this->once())
            ->method('removePlugin')
            ->with(Plugin\HeaderAppendPlugin::class);

        $client->clearHeaders();
    }

    /**
     * @test
     */
    public function shouldAddHeaders()
    {
        $headers = array('header1', 'header2');

        $client = $this->getMock('Pushcommerce\Client', array('addPlugin', 'removePlugin'));
        $client->expects($this->once())
            ->method('addPlugin')
            // TODO verify that headers exists
            ->with($this->isInstanceOf(Plugin\HeaderAppendPlugin::class));

        $client->expects($this->once())
            ->method('removePlugin')
            ->with(Plugin\HeaderAppendPlugin::class);

        $client->addHeaders($headers);
    }

    /**
     * @test
     * @dataProvider getApiClassesProvider
     */
    public function shouldGetApiInstance($apiName, $class)
    {
        $client = new Client();

        $this->assertInstanceOf($class, $client->api($apiName));
    }

    /**
     * @test
     * @dataProvider getApiClassesProvider
     */
    public function shouldGetMagicApiInstance($apiName, $class)
    {
        $client = new Client();

        $this->assertInstanceOf($class, $client->$apiName());
    }

    /**
     * @test
     * @expectedException \Pushcommerce\Exception\InvalidArgumentException
     */
    public function shouldNotGetApiInstance()
    {
        $client = new Client();
        $client->api('do_not_exist');
    }

    /**
     * @test
     * @expectedException BadMethodCallException
     */
    public function shouldNotGetMagicApiInstance()
    {
        $client = new Client();
        $client->doNotExist();
    }

    public function getApiClassesProvider()
    {
        return [
            array('user', 'Pushcommerce\Api\User'),
            array('users', 'Pushcommerce\Api\User'),

            array('customer', 'Pushcommerce\Api\Customer'),
            array('customers', 'Pushcommerce\Api\Customer'),

            array('order', 'Pushcommerce\Api\Order'),
            array('orders', 'Pushcommerce\Api\Order'),

            array('product', 'Pushcommerce\Api\Product'),
            array('products', 'Pushcommerce\Api\Product'),

            array('search', 'Pushcommerce\Api\Search'),

            array('store', 'Pushcommerce\Api\Store'),
            array('store', 'Pushcommerce\Api\Store'),
        ];
    }
}
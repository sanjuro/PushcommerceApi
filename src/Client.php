<?php

namespace Pushcommerce;

use Pushcommerce\Api\ApiInterface;
use Pushcommerce\Exception\InvalidArgumentException;
use Pushcommerce\HttpClient\Plugin\PushcommerceExceptionThrower;
use Pushcommerce\Exception\BadMethodCallException;
use Pushcommerce\HttpClient\Plugin\Authentication;
use Pushcommerce\HttpClient\Plugin\History;
use Pushcommerce\HttpClient\Plugin\PathPrepend;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\StreamFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\MessageFactory;
use Nyholm\Psr7\Factory\StreamFactory;
use Psr\Cache\CacheItemPoolInterface;

class Client
{
    /**
     * Constant for authentication method. Indicates the default, but deprecated
     * login with username and token in URL.
     */
    const AUTH_URL_TOKEN = 'url_token';

    /**
     * Constant for authentication method. Not indicates the new login, but allows
     * usage of unauthenticated rate limited requests for given client_id + client_secret.
     */
    const AUTH_URL_CLIENT_ID = 'url_client_id';

    /**
     * Constant for authentication method. Indicates the new favored login method
     * with username and password via HTTP Authentication.
     */
    const AUTH_HTTP_PASSWORD = 'http_password';

    /**
     * Constant for authentication method. Indicates the new login method with
     * with username and token via HTTP Authentication.
     */
    const AUTH_HTTP_TOKEN = 'http_token';

    /**
     * @var string
     */
    private $apiVersion;

    /**
     * The object that sends HTTP messages
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * A HTTP client with all our plugins
     *
     * @var PluginClient
     */
    private $pluginClient;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var StreamFactory
     */
    private $streamFactory;

    /**
     * @var Plugin[]
     */
    private $plugins = [];

    /**
     * True if we should create a new Plugin client at next request.
     * @var bool
     */
    private $httpClientModified = true;

    /**
     * Http headers
     * @var array
     */
    private $headers = [];

    /**
     * @var History
     */
    private $responseHistory;

    /**
     * Instantiate a new Pushcommerce client.
     *
     * @param HttpClient|null $httpClient
     * @param string|null     $apiVersion
     * @param string|null     $enterpriseUrl
     */
    public function __construct(HttpClient $httpClient = null, $apiVersion = null, $enterpriseUrl = null)
    {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->messageFactory = MessageFactoryDiscovery::find();
        $this->streamFactory = StreamFactoryDiscovery::find();

        $this->responseHistory = new History();
        $this->addPlugin(new PushcommerceExceptionThrower());
        $this->addPlugin(new Plugin\HistoryPlugin($this->responseHistory));
        $this->addPlugin(new Plugin\RedirectPlugin());
        $this->addPlugin(new Plugin\AddHostPlugin(UriFactoryDiscovery::find()->createUri('https://api.pushcommerce.com')));

        $this->apiVersion = $apiVersion ?: 'v1';
        $this->addHeaders(['Accept' => sprintf('application/vnd.pushcommerce.%s+json', $this->apiVersion)]);

        if ($enterpriseUrl) {
            $this->setEnterpriseUrl($enterpriseUrl);
        }
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return ApiInterface
     */
    public function api($name)
    {
        switch ($name) {
            case 'me':
            case 'current_user':
            case 'currentUser':
                $api = new Api\CurrentUser($this);
                break;

            case 'customer':
            case 'customers':
                $api = new Api\Customer($this);
                break;

            case 'order':
            case 'orders':
                $api = new Api\Order($this);
                break;

            case 'product':
            case 'products':
                $api = new Api\Product($this);
                break;

            case 'store':
            case 'stores':
                $api = new Api\Store($this);
                break;

            case 'user':
            case 'users':
                $api = new Api\User($this);
                break;

            case 'authorization':
            case 'authorizations':
                $api = new Api\Authorizations($this);
                break;

            case 'meta':
                $api = new Api\Meta($this);
                break;

            default:
                throw new InvalidArgumentException(sprintf('Undefined api instance called: "%s"', $name));
        }

        return $api;
    }

    /**
     * Authenticate a user for all next requests.
     *
     * @param string      $tokenOrLogin Pushcommerce private token/username/client ID
     * @param null|string $password     Pushcommerce password/secret (optionally can contain $authMethod)
     * @param null|string $authMethod   One of the AUTH_* class constants
     *
     * @throws InvalidArgumentException If no authentication method was given
     */
    public function authenticate($tokenOrLogin, $password = null, $authMethod = null)
    {
        if (null === $password && null === $authMethod) {
            throw new InvalidArgumentException('You need to specify authentication method!');
        }

        if (null === $authMethod && in_array($password, array(self::AUTH_URL_TOKEN, self::AUTH_URL_CLIENT_ID, self::AUTH_HTTP_PASSWORD, self::AUTH_HTTP_TOKEN))) {
            $authMethod = $password;
            $password   = null;
        }

        if (null === $authMethod) {
            $authMethod = self::AUTH_HTTP_PASSWORD;
        }

        $this->removePlugin(Authentication::class);
        $this->addPlugin(new Authentication($tokenOrLogin, $password, $authMethod));
    }

    /**
     * Add a new plugin to the end of the plugin chain.
     *
     * @param Plugin $plugin
     */
    public function addPlugin(Plugin $plugin)
    {
        $this->plugins[] = $plugin;
        $this->httpClientModified = true;
    }

    /**
     * Remove a plugin by its fully qualified class name (FQCN).
     *
     * @param string $fqcn
     */
    public function removePlugin($fqcn)
    {
        foreach ($this->plugins as $idx => $plugin) {
            if ($plugin instanceof $fqcn) {
                unset($this->plugins[$idx]);
                $this->httpClientModified = true;
            }
        }
    }

    /**
     * @return HttpMethodsClient
     */
    public function getHttpClient()
    {
        if ($this->httpClientModified) {
            $this->httpClientModified = false;
            $this->pushBackCachePlugin();

            $this->pluginClient = new HttpMethodsClient(
                new PluginClient($this->httpClient, $this->plugins),
                $this->messageFactory
            );
        }

        return $this->pluginClient;
    }

    /**
     * @param HttpClient $httpClient
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClientModified = true;
        $this->httpClient = $httpClient;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Clears used headers.
     */
    public function clearHeaders()
    {
        $this->headers = array(
            'Accept' => sprintf('application/vnd.pushcommerce.%s+json', $this->getApiVersion()),
        );

        $this->removePlugin(Plugin\HeaderAppendPlugin::class);
        $this->addPlugin(new Plugin\HeaderAppendPlugin($this->headers));
    }

    /**
     * @param array $headers
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        $this->removePlugin(Plugin\HeaderAppendPlugin::class);
        $this->addPlugin(new Plugin\HeaderAppendPlugin($this->headers));
    }

    /**
     * Add a cache plugin to cache responses locally.
     *
     * @param CacheItemPoolInterface $cache
     * @param array                  $config
     */
    public function addCache(CacheItemPoolInterface $cachePool, array $config = [])
    {
        $this->removeCache();
        $this->addPlugin(new Plugin\CachePlugin($cachePool, $this->streamFactory, $config));
    }

    /**
     * Remove the cache plugin
     */
    public function removeCache()
    {
        $this->removePlugin(Plugin\CachePlugin::class);
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return ApiInterface
     */
    public function __call($name, $args)
    {
        try {
            return $this->api($name);
        } catch (InvalidArgumentException $e) {
            throw new BadMethodCallException(sprintf('Undefined method called: "%s"', $name));
        }
    }

    /**
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function getLastResponse()
    {
        return $this->responseHistory->getLastResponse();
    }

    /**
     * Make sure to move the cache plugin to the end of the chain
     */
    private function pushBackCachePlugin()
    {
        $cachePlugin = null;
        foreach ($this->plugins as $i => $plugin) {
            if ($plugin instanceof Plugin\CachePlugin) {
                $cachePlugin = $plugin;
                unset($this->plugins[$i]);

                $this->plugins[] = $cachePlugin;

                return;
            }
        }
    }
}
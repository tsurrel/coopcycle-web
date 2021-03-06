<?php

namespace Tests\AppBundle;

use Prophecy\Argument;
use Stripe\ApiRequestor;
use Stripe\Stripe;
use Stripe\HttpClient;

trait StripeTrait
{
    protected static $stripeApiKey = 'sk_test_123';

    protected function setUp()
    {
        // Save original values so that we can restore them after running tests
        $this->origApiBase = Stripe::$apiBase;
        $this->origApiKey = Stripe::$apiKey;
        $this->origApiVersion = Stripe::$apiVersion;
        $this->origAccountId = Stripe::$accountId;

        $stripeMockApiBase = getenv('STRIPE_MOCK_API_BASE');
        if (!$stripeMockApiBase) {
            // By default, use Docker
            $stripeMockApiBase = 'http://stripe_mock:12111';
        }

        // Set up host and credentials for stripe-mock
        Stripe::$apiBase = $stripeMockApiBase;
        Stripe::setApiKey(self::$stripeApiKey);
        Stripe::setApiVersion(null);
        Stripe::setAccountId(null);

        // Set up the HTTP client mocker
        $this->stripeHttpClient = $this->prophesize(HttpClient\ClientInterface::class);

        // By default, use the real HTTP client
        ApiRequestor::setHttpClient(HttpClient\CurlClient::instance());
    }

    protected function tearDown()
    {
        // Restore original values
        Stripe::$apiBase = $this->origApiBase;
        Stripe::setApiKey($this->origApiKey);
        Stripe::setApiVersion($this->origApiVersion);
        Stripe::setAccountId($this->origAccountId);
    }

    private function shouldSendStripeRequest($method, $path, array $params = [], $headers = null, $hasFile = false)
    {
        ApiRequestor::setHttpClient($this->stripeHttpClient->reveal());

        $absUrl = Stripe::$apiBase . $path;

        $this->stripeHttpClient
            ->request(strtolower($method), $absUrl, Argument::type('array'), $params, $hasFile)
            ->shouldBeCalled()
            ->will(function ($args) {
                $curlClient = HttpClient\CurlClient::instance();
                return $curlClient->request($args[0], $args[1], $args[2], $args[3], $args[4]);
            });
    }
}

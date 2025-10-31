<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\CreateDomainRequest;
use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(CreateDomainRequest::class)]
final class CreateDomainRequestTest extends RequestTestCase
{
    private CreateDomainRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new CreateDomainRequest(
            'example.com',
            '192.168.1.1'
        );
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains', $this->request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertEquals('POST', $this->request->getRequestMethod());
    }

    public function testGetRequestOptionsWithRequiredParams(): void
    {
        $options = $this->request->getRequestOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('json', $options);
        $this->assertIsArray($options['json']);
        $jsonOptions = $options['json'];
        $this->assertArrayHasKey('name', $jsonOptions);
        $this->assertArrayHasKey('ip_address', $jsonOptions);

        $this->assertEquals('example.com', $jsonOptions['name']);
        $this->assertEquals('192.168.1.1', $jsonOptions['ip_address']);
    }

    public function testGetRequestOptionsWithNullIpAddress(): void
    {
        $request = new CreateDomainRequest('example.com');
        $options = $request->getRequestOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('json', $options);
        $this->assertIsArray($options['json']);
        $jsonOptions = $options['json'];
        $this->assertArrayHasKey('name', $jsonOptions);
        $this->assertArrayNotHasKey('ip_address', $jsonOptions);

        $this->assertEquals('example.com', $jsonOptions['name']);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

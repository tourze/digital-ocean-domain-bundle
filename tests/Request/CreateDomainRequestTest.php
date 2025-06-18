<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\CreateDomainRequest;
use PHPUnit\Framework\TestCase;

class CreateDomainRequestTest extends TestCase
{
    private CreateDomainRequest $request;

    protected function setUp(): void
    {
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
        $this->assertArrayHasKey('json', $options);
        $this->assertArrayHasKey('name', $options['json']);
        $this->assertArrayHasKey('ip_address', $options['json']);

        $this->assertEquals('example.com', $options['json']['name']);
        $this->assertEquals('192.168.1.1', $options['json']['ip_address']);
    }

    public function testGetRequestOptionsWithNullIpAddress(): void
    {
        $request = new CreateDomainRequest('example.com');
        $options = $request->getRequestOptions();
        $this->assertArrayHasKey('json', $options);
        $this->assertArrayHasKey('name', $options['json']);
        $this->assertArrayNotHasKey('ip_address', $options['json']);

        $this->assertEquals('example.com', $options['json']['name']);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

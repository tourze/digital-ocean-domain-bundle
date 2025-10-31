<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\GetDomainRequest;
use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(GetDomainRequest::class)]
final class GetDomainRequestTest extends RequestTestCase
{
    private GetDomainRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new GetDomainRequest('example.com');
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains/example.com', $this->request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertEquals('GET', $this->request->getRequestMethod());
    }

    public function testGetRequestOptions(): void
    {
        $options = $this->request->getRequestOptions();
        $this->assertIsArray($options);
        // GET请求不应有JSON数据
        $this->assertArrayNotHasKey('json', $options);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

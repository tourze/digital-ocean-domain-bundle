<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\GetDomainRecordRequest;
use PHPUnit\Framework\TestCase;

class GetDomainRecordRequestTest extends TestCase
{
    private GetDomainRecordRequest $request;

    protected function setUp(): void
    {
        $this->request = new GetDomainRecordRequest('example.com', 12345);
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains/example.com/records/12345', $this->request->getRequestPath());
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

<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\DeleteDomainRecordRequest;
use PHPUnit\Framework\TestCase;

class DeleteDomainRecordRequestTest extends TestCase
{
    private DeleteDomainRecordRequest $request;

    protected function setUp(): void
    {
        $this->request = new DeleteDomainRecordRequest('example.com', 12345);
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains/example.com/records/12345', $this->request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertEquals('DELETE', $this->request->getRequestMethod());
    }

    public function testGetRequestOptions(): void
    {
        $options = $this->request->getRequestOptions();

        $this->assertIsArray($options);
        // DELETE请求不应有JSON数据
        $this->assertArrayNotHasKey('json', $options);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

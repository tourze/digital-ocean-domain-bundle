<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\DeleteDomainRequest;
use PHPUnit\Framework\TestCase;

class DeleteDomainRequestTest extends TestCase
{
    private DeleteDomainRequest $request;

    protected function setUp(): void
    {
        $this->request = new DeleteDomainRequest('example.com');
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains/example.com', $this->request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertEquals('DELETE', $this->request->getRequestMethod());
    }

    public function testGetRequestOptions(): void
    {
        $options = $this->request->getRequestOptions();
        // 删除请求不应有JSON数据
        $this->assertArrayNotHasKey('json', $options);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

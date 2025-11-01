<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\DeleteDomainRecordRequest;
use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(DeleteDomainRecordRequest::class)]
final class DeleteDomainRecordRequestTest extends RequestTestCase
{
    private DeleteDomainRecordRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

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

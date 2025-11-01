<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\ListDomainRecordsRequest;
use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(ListDomainRecordsRequest::class)]
final class ListDomainRecordsRequestTest extends RequestTestCase
{
    private ListDomainRecordsRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new ListDomainRecordsRequest('example.com');
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains/example.com/records', $this->request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertEquals('GET', $this->request->getRequestMethod());
    }

    public function testGetRequestOptionsDefault(): void
    {
        $options = $this->request->getRequestOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('query', $options);
        $this->assertIsArray($options['query']);
        $queryOptions = $options['query'];
        $this->assertArrayHasKey('page', $queryOptions);
        $this->assertArrayHasKey('per_page', $queryOptions);

        $this->assertEquals(1, $queryOptions['page']);
        $this->assertEquals(20, $queryOptions['per_page']);
    }

    public function testGetRequestOptionsWithPagination(): void
    {
        $this->request->setPage(3);
        $this->request->setPerPage(50);
        $options = $this->request->getRequestOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('query', $options);
        $this->assertIsArray($options['query']);
        $queryOptions = $options['query'];
        $this->assertArrayHasKey('page', $queryOptions);
        $this->assertArrayHasKey('per_page', $queryOptions);

        $this->assertEquals(3, $queryOptions['page']);
        $this->assertEquals(50, $queryOptions['per_page']);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

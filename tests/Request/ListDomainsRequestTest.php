<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\ListDomainsRequest;
use PHPUnit\Framework\TestCase;

class ListDomainsRequestTest extends TestCase
{
    private ListDomainsRequest $request;

    protected function setUp(): void
    {
        $this->request = new ListDomainsRequest();
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains', $this->request->getRequestPath());
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
        $this->assertArrayHasKey('page', $options['query']);
        $this->assertArrayHasKey('per_page', $options['query']);

        $this->assertEquals(1, $options['query']['page']);
        $this->assertEquals(20, $options['query']['per_page']);
    }

    public function testGetRequestOptionsWithPagination(): void
    {
        $this->request->setPage(3)->setPerPage(50);
        $options = $this->request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('query', $options);
        $this->assertArrayHasKey('page', $options['query']);
        $this->assertArrayHasKey('per_page', $options['query']);

        $this->assertEquals(3, $options['query']['page']);
        $this->assertEquals(50, $options['query']['per_page']);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\UpdateDomainRecordRequest;
use PHPUnit\Framework\TestCase;

class UpdateDomainRecordRequestTest extends TestCase
{
    private UpdateDomainRecordRequest $request;

    protected function setUp(): void
    {
        $this->request = new UpdateDomainRecordRequest(
            'example.com',
            12345,
            'A',
            'www',
            '192.168.1.1'
        );
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/domains/example.com/records/12345', $this->request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertEquals('PUT', $this->request->getRequestMethod());
    }

    public function testGetRequestOptionsWithRequiredParamsOnly(): void
    {
        $options = $this->request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('json', $options);
        $this->assertArrayHasKey('type', $options['json']);
        $this->assertArrayHasKey('name', $options['json']);
        $this->assertArrayHasKey('data', $options['json']);

        $this->assertEquals('A', $options['json']['type']);
        $this->assertEquals('www', $options['json']['name']);
        $this->assertEquals('192.168.1.1', $options['json']['data']);
    }

    public function testGetRequestOptionsWithAllParams(): void
    {
        $this->request->setPriority(10)
            ->setPort(80)
            ->setTtl(3600)
            ->setWeight(100)
            ->setFlags('flags')
            ->setTag('tag');

        $options = $this->request->getRequestOptions();

        $this->assertArrayHasKey('priority', $options['json']);
        $this->assertArrayHasKey('port', $options['json']);
        $this->assertArrayHasKey('ttl', $options['json']);
        $this->assertArrayHasKey('weight', $options['json']);
        $this->assertArrayHasKey('flags', $options['json']);
        $this->assertArrayHasKey('tag', $options['json']);

        $this->assertEquals(10, $options['json']['priority']);
        $this->assertEquals(80, $options['json']['port']);
        $this->assertEquals(3600, $options['json']['ttl']);
        $this->assertEquals(100, $options['json']['weight']);
        $this->assertEquals('flags', $options['json']['flags']);
        $this->assertEquals('tag', $options['json']['tag']);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

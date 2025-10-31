<?php

namespace DigitalOceanDomainBundle\Tests\Request;

use DigitalOceanDomainBundle\Request\UpdateDomainRecordRequest;
use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(UpdateDomainRecordRequest::class)]
final class UpdateDomainRecordRequestTest extends RequestTestCase
{
    private UpdateDomainRecordRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->assertIsArray($options['json']);
        $jsonOptions = $options['json'];
        $this->assertArrayHasKey('type', $jsonOptions);
        $this->assertArrayHasKey('name', $jsonOptions);
        $this->assertArrayHasKey('data', $jsonOptions);

        $this->assertEquals('A', $jsonOptions['type']);
        $this->assertEquals('www', $jsonOptions['name']);
        $this->assertEquals('192.168.1.1', $jsonOptions['data']);
    }

    public function testGetRequestOptionsWithAllParams(): void
    {
        $this->request->setPriority(10);
        $this->request->setPort(80);
        $this->request->setTtl(3600);
        $this->request->setWeight(100);
        $this->request->setFlags('flags');
        $this->request->setTag('tag');

        $options = $this->request->getRequestOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('json', $options);
        $this->assertIsArray($options['json']);
        $jsonOptions = $options['json'];

        $this->assertArrayHasKey('priority', $jsonOptions);
        $this->assertArrayHasKey('port', $jsonOptions);
        $this->assertArrayHasKey('ttl', $jsonOptions);
        $this->assertArrayHasKey('weight', $jsonOptions);
        $this->assertArrayHasKey('flags', $jsonOptions);
        $this->assertArrayHasKey('tag', $jsonOptions);

        $this->assertEquals(10, $jsonOptions['priority']);
        $this->assertEquals(80, $jsonOptions['port']);
        $this->assertEquals(3600, $jsonOptions['ttl']);
        $this->assertEquals(100, $jsonOptions['weight']);
        $this->assertEquals('flags', $jsonOptions['flags']);
        $this->assertEquals('tag', $jsonOptions['tag']);
    }

    public function testSetApiKey(): void
    {
        $this->request->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->request->getApiKey());
    }
}

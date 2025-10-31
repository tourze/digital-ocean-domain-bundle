<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanDomainBundle\Service\ResponseValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ResponseValidator::class)]
final class ResponseValidatorTest extends TestCase
{
    private ResponseValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ResponseValidator();
    }

    /**
     * @param array<string, mixed> $input
     * @param array{domains: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>} $expected
     */
    #[DataProvider('provideValidListDomainsResponse')]
    public function testValidateListDomainsResponseSuccess(array $input, array $expected): void
    {
        $result = $this->validator->validateListDomainsResponse($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param array<string, mixed> $input
     * @param array{domain_records: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>} $expected
     */
    #[DataProvider('provideValidListDomainRecordsResponse')]
    public function testValidateListDomainRecordsResponseSuccess(array $input, array $expected): void
    {
        $result = $this->validator->validateListDomainRecordsResponse($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     */
    #[DataProvider('provideValidDomainResponse')]
    public function testValidateDomainResponseSuccess(array $input, array $expected): void
    {
        $result = $this->validator->validateDomainResponse($input);

        $this->assertEquals($expected, $result);
    }

    public function testValidateDomainResponseWithInvalidData(): void
    {
        $input = ['domain' => 'invalid_string'];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid domain data in response');

        $this->validator->validateDomainResponse($input);
    }

    public function testValidateListDomainsResponseWithInvalidDomainsData(): void
    {
        $input = [
            'domains' => 'invalid_string',
            'meta' => [],
            'links' => [],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid first array data in response');

        $this->validator->validateListDomainsResponse($input);
    }

    public function testValidateListDomainsResponseWithInvalidMetaData(): void
    {
        $input = [
            'domains' => [],
            'meta' => 'invalid_string',
            'links' => [],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid meta data in response');

        $this->validator->validateListDomainsResponse($input);
    }

    public function testValidateListDomainsResponseWithInvalidLinksData(): void
    {
        $input = [
            'domains' => [],
            'meta' => [],
            'links' => 'invalid_string',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid links data in response');

        $this->validator->validateListDomainsResponse($input);
    }

    /**
     * @return array<string, array{array<string, mixed>, array{domains: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>}}>
     */
    public static function provideValidListDomainsResponse(): array
    {
        return [
            'empty_response' => [
                [
                    'domains' => [],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'domains' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'response_with_domains' => [
                [
                    'domains' => [
                        ['name' => 'example.com', 'ttl' => 1800],
                        ['name' => 'test.com', 'ttl' => 3600],
                    ],
                    'meta' => ['total' => 2],
                    'links' => ['next' => '/domains?page=2'],
                ],
                [
                    'domains' => [
                        ['name' => 'example.com', 'ttl' => 1800],
                        ['name' => 'test.com', 'ttl' => 3600],
                    ],
                    'meta' => ['total' => 2],
                    'links' => ['next' => '/domains?page=2'],
                ],
            ],
            'missing_fields_response' => [
                [],
                [
                    'domains' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, array{domain_records: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>}}>
     */
    public static function provideValidListDomainRecordsResponse(): array
    {
        return [
            'empty_response' => [
                [
                    'domain_records' => [],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'domain_records' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'response_with_records' => [
                [
                    'domain_records' => [
                        ['id' => 123, 'type' => 'A', 'name' => 'www', 'data' => '192.168.1.1'],
                        ['id' => 124, 'type' => 'MX', 'name' => '@', 'data' => 'mail.example.com'],
                    ],
                    'meta' => ['total' => 2],
                    'links' => ['next' => '/records?page=2'],
                ],
                [
                    'domain_records' => [
                        ['id' => 123, 'type' => 'A', 'name' => 'www', 'data' => '192.168.1.1'],
                        ['id' => 124, 'type' => 'MX', 'name' => '@', 'data' => 'mail.example.com'],
                    ],
                    'meta' => ['total' => 2],
                    'links' => ['next' => '/records?page=2'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function provideValidDomainResponse(): array
    {
        return [
            'simple_domain' => [
                [
                    'domain' => ['name' => 'example.com', 'ttl' => 1800],
                ],
                ['name' => 'example.com', 'ttl' => 1800],
            ],
            'domain_with_numeric_keys' => [
                [
                    'domain' => ['key123' => 'value123', 'name' => 'test.com'],
                ],
                ['key123' => 'value123', 'name' => 'test.com'],
            ],
            'empty_domain' => [
                [
                    'domain' => [],
                ],
                [],
            ],
        ];
    }
}

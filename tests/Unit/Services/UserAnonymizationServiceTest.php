<?php

namespace Tests\Unit\Services;

use App\Services\UserAnonymizationService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UserAnonymizationServiceTest extends TestCase
{
    private UserAnonymizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserAnonymizationService;
    }

    public function test_should_anonymize_returns_true_for_configured_users()
    {
        Config::set('telegram.anonymized_users', ['testuser', 'john123']);

        $this->assertTrue($this->service->shouldAnonymize('testuser'));
        $this->assertTrue($this->service->shouldAnonymize('@testuser'));
        $this->assertTrue($this->service->shouldAnonymize('TESTUSER'));
        $this->assertTrue($this->service->shouldAnonymize('@TESTUSER'));
        $this->assertTrue($this->service->shouldAnonymize('john123'));
    }

    public function test_should_anonymize_returns_false_for_non_configured_users()
    {
        Config::set('telegram.anonymized_users', ['testuser']);

        $this->assertFalse($this->service->shouldAnonymize('otheruser'));
        $this->assertFalse($this->service->shouldAnonymize('@randomuser'));
    }

    public function test_anonymize_preserves_at_symbol()
    {
        Config::set('telegram.anonymized_users', ['testuser']);

        $this->assertEquals('@t******r', $this->service->anonymize('@testuser'));
        $this->assertEquals('t******r', $this->service->anonymize('testuser'));
    }

    public function test_anonymize_handles_different_username_lengths()
    {
        Config::set('telegram.anonymized_users', ['ab', 'abc', 'abcd', 'abcdefghij']);

        // 2 characters
        $this->assertEquals('**', $this->service->anonymize('ab'));
        $this->assertEquals('@**', $this->service->anonymize('@ab'));

        // 3 characters
        $this->assertEquals('a*c', $this->service->anonymize('abc'));
        $this->assertEquals('@a*c', $this->service->anonymize('@abc'));

        // 4 characters
        $this->assertEquals('a**d', $this->service->anonymize('abcd'));

        // 10 characters
        $this->assertEquals('a********j', $this->service->anonymize('abcdefghij'));
        $this->assertEquals('@a********j', $this->service->anonymize('@abcdefghij'));
    }

    public function test_anonymize_returns_original_for_non_configured_users()
    {
        Config::set('telegram.anonymized_users', ['testuser']);

        $this->assertEquals('otheruser', $this->service->anonymize('otheruser'));
        $this->assertEquals('@otheruser', $this->service->anonymize('@otheruser'));
    }

    public function test_process_data_anonymizes_username_fields()
    {
        Config::set('telegram.anonymized_users', ['john', 'jane']);

        $data = [
            'username' => 'john',
            'user_name' => '@jane',
            'from' => 'john',
            'other_field' => 'john', // Should not be anonymized
            'nested' => [
                'author' => '@jane',
                'text' => 'Hello from jane', // Should not be anonymized
            ],
        ];

        $result = $this->service->processData($data);

        $this->assertEquals('j**n', $result['username']);
        $this->assertEquals('@j**e', $result['user_name']);
        $this->assertEquals('j**n', $result['from']);
        $this->assertEquals('john', $result['other_field']); // Not anonymized
        $this->assertEquals('@j**e', $result['nested']['author']);
        $this->assertEquals('Hello from jane', $result['nested']['text']); // Not anonymized
    }

    public function test_process_data_handles_objects()
    {
        Config::set('telegram.anonymized_users', ['testuser']);

        $data = new \stdClass;
        $data->username = 'testuser';
        $data->name = '@testuser';
        $data->other = 'testuser';

        $result = $this->service->processData($data);

        $this->assertEquals('t******r', $result->username);
        $this->assertEquals('@t******r', $result->name);
        $this->assertEquals('testuser', $result->other);
    }

    public function test_normalize_username_removes_at_and_lowercases()
    {
        // Using reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeUsername');
        $method->setAccessible(true);

        $this->assertEquals('testuser', $method->invoke($this->service, '@TestUser'));
        $this->assertEquals('testuser', $method->invoke($this->service, 'TestUser'));
        $this->assertEquals('testuser', $method->invoke($this->service, '@testuser'));
        $this->assertEquals('testuser', $method->invoke($this->service, 'testuser'));
    }

    public function test_anonymization_with_real_usernames()
    {
        Config::set('telegram.anonymized_users', ['JohnDoe123', 'AliceSmith', 'BobJones', 'TestUser99']);

        $this->assertEquals('@J********3', $this->service->anonymize('@JohnDoe123'));
        $this->assertEquals('@A********h', $this->service->anonymize('@AliceSmith'));
        $this->assertEquals('@B******s', $this->service->anonymize('@BobJones'));
        $this->assertEquals('@T********9', $this->service->anonymize('@TestUser99'));
    }
}

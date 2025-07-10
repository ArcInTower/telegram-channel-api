<?php

namespace Tests\Unit\Services\Telegram\Statistics;

use App\Services\Telegram\Statistics\StatisticsCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class StatisticsCalculatorTest extends TestCase
{
    private StatisticsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new StatisticsCalculator;
    }

    public function test_calculate_returns_empty_statistics_for_no_messages()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $result = $this->calculator->calculate([], $startDate, $endDate);

        $this->assertEquals(0, $result['summary']['total_messages']);
        $this->assertEquals(0, $result['summary']['unique_users']);
        $this->assertEquals(0, $result['summary']['total_replies']);
        $this->assertEquals(0, $result['summary']['reply_rate']);
        $this->assertEmpty($result['top_users']);
    }

    public function test_calculate_processes_messages_correctly()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $messages = [
            [
                'from_id' => ['user_id' => 123],
                'message' => 'Hello world',
                'date' => Carbon::now()->subDays(3)->timestamp,
            ],
            [
                'from_id' => ['user_id' => 123],
                'message' => 'Another message',
                'date' => Carbon::now()->subDays(2)->timestamp,
                'reply_to' => ['reply_to_msg_id' => 1],
            ],
            [
                'from_id' => ['user_id' => 456],
                'message' => 'Hi there',
                'date' => Carbon::now()->subDays(1)->timestamp,
            ],
        ];

        $result = $this->calculator->calculate($messages, $startDate, $endDate);

        $this->assertEquals(3, $result['summary']['total_messages']);
        $this->assertEquals(2, $result['summary']['unique_users']);
        $this->assertEquals(1, $result['summary']['total_replies']);
        $this->assertGreaterThan(0, $result['summary']['reply_rate']);
        $this->assertCount(2, $result['top_users']);
    }

    public function test_calculate_ignores_service_messages()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $messages = [
            [
                '_' => 'messageService',
                'from_id' => ['user_id' => 123],
                'date' => Carbon::now()->timestamp,
            ],
            [
                'from_id' => ['user_id' => 123],
                'message' => 'Regular message',
                'date' => Carbon::now()->timestamp,
            ],
        ];

        $result = $this->calculator->calculate($messages, $startDate, $endDate);

        $this->assertEquals(1, $result['summary']['total_messages']);
    }

    public function test_calculate_handles_user_info_cache()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $messages = [
            [
                'from_id' => ['user_id' => 123],
                'message' => 'Test',
                'date' => Carbon::now()->timestamp,
            ],
        ];

        $userInfoCache = [
            123 => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'johndoe',
            ],
        ];

        $result = $this->calculator->calculate($messages, $startDate, $endDate, $userInfoCache);

        $this->assertEquals('@johndoe', $result['top_users'][0]['user_name']);
    }

    public function test_calculate_activity_patterns()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $messages = [
            [
                'from_id' => ['user_id' => 123],
                'message' => 'Morning message',
                'date' => Carbon::now()->setTime(9, 0)->timestamp,
            ],
            [
                'from_id' => ['user_id' => 123],
                'message' => 'Another morning message',
                'date' => Carbon::now()->setTime(9, 30)->timestamp,
            ],
            [
                'from_id' => ['user_id' => 456],
                'message' => 'Evening message',
                'date' => Carbon::now()->setTime(20, 0)->timestamp,
            ],
        ];

        $result = $this->calculator->calculate($messages, $startDate, $endDate);

        $this->assertEquals(2, $result['activity_patterns']['by_hour']['09:00']);
        $this->assertEquals(1, $result['activity_patterns']['by_hour']['20:00']);
        $this->assertEquals('9:00', $result['peak_activity']['hour']);
    }
}

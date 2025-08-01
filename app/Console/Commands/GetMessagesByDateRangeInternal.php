<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\MessageService;
use Carbon\Carbon;
use danog\MadelineProto\Logger;

class GetMessagesByDateRangeInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:messages-by-date-internal 
                            {channel : The channel username or ID}
                            {from : Start date (YYYY-MM-DD)}
                            {to : End date (YYYY-MM-DD)}
                            {--format=json : Output format (json, table, count)}
                            {--limit=100 : Maximum number of messages to retrieve}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to get messages by date range';

    /**
     * Hide this command from the list
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private MessageService $messageService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Configure MadelineProto to suppress logs before any initialization
        putenv('MADELINE_SUPPRESS_LOGS=true');
        
        $channel = $this->argument('channel');
        $fromDate = $this->argument('from');
        $toDate = $this->argument('to');
        $format = $this->option('format');
        $limit = (int) $this->option('limit');

        // Suppress any remaining output
        ob_start();
        
        try {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();

            $messages = $this->messageService->getMessagesByDateRange(
                $channel,
                $from,
                $to,
                $limit
            );

            // Clean output buffer to remove MadelineProto logs
            ob_end_clean();

            if ($messages === null || empty($messages)) {
                echo "Error: No messages found in the specified date range\n";
                return Command::FAILURE;
            }

            // Format output based on option
            switch ($format) {
                case 'count':
                    echo count($messages);
                    break;
                    
                case 'table':
                    $this->outputAsTable($messages);
                    break;
                    
                case 'json':
                default:
                    $this->outputAsJson($messages);
                    break;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            ob_end_clean();
            echo "Error: " . $e->getMessage() . "\n";
            return Command::FAILURE;
        }
    }

    /**
     * Output messages in table format
     */
    private function outputAsTable(array $messages): void
    {
        // Get user info for all unique user IDs
        $userIds = [];
        foreach ($messages as $message) {
            if (isset($message['from_id']) && is_numeric($message['from_id'])) {
                $userIds[] = $message['from_id'];
            }
        }
        $userIds = array_unique($userIds);
        
        // Fetch user info
        $userNames = [];
        if (!empty($userIds)) {
            try {
                ob_start();
                $api = app(\App\Contracts\TelegramApiInterface::class);
                foreach ($userIds as $userId) {
                    try {
                        $userInfo = $api->getInfo($userId);
                        if ($userInfo && isset($userInfo['User'])) {
                            $user = $userInfo['User'];
                            $name = '';
                            if (isset($user['first_name'])) {
                                $name = $user['first_name'];
                            }
                            if (isset($user['last_name'])) {
                                $name .= ' ' . $user['last_name'];
                            }
                            if (empty(trim($name)) && isset($user['username'])) {
                                $name = '@' . $user['username'];
                            }
                            if (!empty(trim($name))) {
                                $userNames[$userId] = trim($name);
                            }
                        }
                    } catch (\Exception $e) {
                        // If we can't get user info, we'll use the ID
                    }
                }
                ob_end_clean();
            } catch (\Exception $e) {
                ob_end_clean();
            }
        }
        
        $rows = [];
        foreach ($messages as $message) {
            $date = isset($message['date']) 
                ? Carbon::createFromTimestamp($message['date'])->format('Y-m-d H:i:s')
                : 'N/A';
            
            $text = $message['message'] ?? '';
            // Truncate long messages
            if (strlen($text) > 60) {
                $text = substr($text, 0, 57) . '...';
            }
            
            // Determine sender
            $from = 'Channel';
            if (isset($message['from_id']) && is_numeric($message['from_id'])) {
                $userId = $message['from_id'];
                $from = $userNames[$userId] ?? 'User ' . $userId;
            } elseif (isset($message['post']) && $message['post'] === true) {
                $from = 'Channel';
            }
            
            $rows[] = [
                'id' => $message['id'] ?? 'N/A',
                'date' => $date,
                'from' => $from,
                'text' => $text
            ];
        }

        // Output as simple table
        echo "ID\tDate\t\t\tFrom\t\t\tText\n";
        echo str_repeat('-', 90) . "\n";
        foreach ($rows as $row) {
            // Pad the from field for better alignment
            $fromPadded = str_pad($row['from'], 20);
            echo "{$row['id']}\t{$row['date']}\t{$fromPadded}\t{$row['text']}\n";
        }
    }

    /**
     * Output messages in clean JSON format
     */
    private function outputAsJson(array $messages): void
    {
        // Get user info for all unique user IDs
        $userIds = [];
        foreach ($messages as $message) {
            if (isset($message['from_id']) && is_numeric($message['from_id'])) {
                $userIds[] = $message['from_id'];
            }
        }
        $userIds = array_unique($userIds);
        
        // Fetch user info
        $userNames = [];
        if (!empty($userIds)) {
            try {
                ob_start();
                $api = app(\App\Contracts\TelegramApiInterface::class);
                foreach ($userIds as $userId) {
                    try {
                        $userInfo = $api->getInfo($userId);
                        if ($userInfo && isset($userInfo['User'])) {
                            $user = $userInfo['User'];
                            $name = '';
                            if (isset($user['first_name'])) {
                                $name = $user['first_name'];
                            }
                            if (isset($user['last_name'])) {
                                $name .= ' ' . $user['last_name'];
                            }
                            if (empty(trim($name)) && isset($user['username'])) {
                                $name = '@' . $user['username'];
                            }
                            if (!empty(trim($name))) {
                                $userNames[$userId] = trim($name);
                            }
                        }
                    } catch (\Exception $e) {
                        // If we can't get user info, we'll use the ID
                    }
                }
                ob_end_clean();
            } catch (\Exception $e) {
                ob_end_clean();
            }
        }
        
        $cleanMessages = [];
        foreach ($messages as $message) {
            $cleanMessage = [
                'id' => $message['id'] ?? null,
                'date' => isset($message['date']) 
                    ? Carbon::createFromTimestamp($message['date'])->format('Y-m-d H:i:s')
                    : null,
                'from' => 'Channel',
                'message' => $message['message'] ?? '',
            ];
            
            // Set the sender name
            if (isset($message['from_id']) && is_numeric($message['from_id'])) {
                $userId = $message['from_id'];
                $cleanMessage['from'] = $userNames[$userId] ?? 'User ' . $userId;
                $cleanMessage['from_id'] = $userId;
            } elseif (isset($message['post']) && $message['post'] === true) {
                $cleanMessage['from'] = 'Channel';
            }
            
            // Add reply information if exists
            if (isset($message['reply_to']) && isset($message['reply_to']['reply_to_msg_id'])) {
                $cleanMessage['reply_to_message_id'] = $message['reply_to']['reply_to_msg_id'];
            }
            
            // Add media type if exists
            if (isset($message['media'])) {
                $cleanMessage['has_media'] = true;
                if (isset($message['media']['_'])) {
                    $cleanMessage['media_type'] = str_replace('messageMedia', '', $message['media']['_']);
                }
            }
            
            // Add entities count if exists (links, mentions, etc)
            if (isset($message['entities']) && is_array($message['entities'])) {
                $cleanMessage['entities_count'] = count($message['entities']);
            }
            
            $cleanMessages[] = $cleanMessage;
        }
        
        echo json_encode($cleanMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
<?php

namespace YoutubeChatCapture;

use Google\Client;
use Google\Service\YouTube;
use Google\Service\Exception as GoogleException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use React\EventLoop\LoopInterface;
use YoutubeChatCapture\Models\ChatMessage;
use YoutubeChatCapture\Models\ProcessingBatch;

class ChatCapture
{
    private const BATCH_SIZE = 1000;
    private const PROCESS_INTERVAL = 3; // seconds
    private array $messageBuffer = [];
    private ?string $nextPageToken = null;

    public function __construct(
        private readonly Client $client,
        private readonly LoopInterface $loop,
        private readonly Logger $logger
    ) {
        $this->logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
    }

    public function startCapture(string $liveChatId): void
    {
        $youtube = new YouTube($this->client);

        // Set up periodic chat message fetching
        $this->loop->addPeriodicTimer(self::PROCESS_INTERVAL, function () use ($youtube, $liveChatId) {
            try {
                $this->fetchChatMessages($youtube, $liveChatId);
                if (count($this->messageBuffer) >= self::BATCH_SIZE) {
                    $this->processBatch();
                }
            } catch (\Exception $e) {
                $this->logger->error('Error fetching chat messages: ' . $e->getMessage());
            }
        });

        // Set up periodic batch processing for remaining messages
        $this->loop->addPeriodicTimer(1.0, function () {
            if (!empty($this->messageBuffer)) {
                $this->processBatch();
            }
        });
    }

    private function getChannelName(YouTube $youtube, string $channelId): string
    {
        try {
            $response = $youtube->channels->listChannels('snippet', [
                'id' => $channelId
            ]);

            if ($response->getItems()) {
                return $response->getItems()[0]->getSnippet()->getTitle();
            }
            
            return '';
        } catch (\Exception $e) {
            $this->logger->error('Error fetching channel name: ' . $e->getMessage());
            return '';
        }
    }

    private function fetchChatMessages(YouTube $youtube, string $liveChatId): void
    {
        try {
            $optParams = [
                'maxResults' => 50
            ];

            if ($this->nextPageToken) {
                $optParams['pageToken'] = $this->nextPageToken;
            }

            $this->logger->info('API Request params: ' . json_encode($optParams));
            
            try {
                $response = $youtube->liveChatMessages->listLiveChatMessages(
                    $liveChatId,
                    'id,snippet',
                    $optParams
                );
                $this->logger->info('Raw API Response: ' . json_encode($response));
            } catch (GoogleException $e) {
                $this->logger->error('Google API Error: ' . json_encode([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'errors' => $e->getErrors()
                ]));
                throw $e;
            }

            $this->nextPageToken = $response->nextPageToken;

            foreach ($response->getItems() as $message) {
                $snippet = $message->getSnippet();
                $authorChannelId = $snippet->getAuthorChannelId();
                $channelId = is_object($authorChannelId) ? $authorChannelId->getChannelId() : $authorChannelId;
                
                $this->messageBuffer[] = [
                    'message_content' => $snippet->getTextMessageDetails() ? $snippet->getTextMessageDetails()->getMessageText() : '',
                    'user_youtube_id' => $channelId,
                    'user_display_name' => $this->getChannelName($youtube, $channelId),
                    'timestamp' => date('Y-m-d H:i:s.u', strtotime($snippet->getPublishedAt())),
                    'chat_id' => $liveChatId,
                    'live_stream_id' => $snippet->getLiveChatId()
                ];
            }

            $this->logger->info('Successfully fetched ' . count($response->getItems()) . ' messages');
        } catch (\Exception $e) {
            $this->logger->error('Error fetching messages: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processBatch(): void
    {
        if (empty($this->messageBuffer)) {
            return;
        }

        try {
            // Create batch record
            $batch = ProcessingBatch::create([
                'batch_status' => 'processing',
                'started_at' => date('Y-m-d H:i:s')
            ]);

            $messages = array_splice($this->messageBuffer, 0, self::BATCH_SIZE);
            
            // Insert messages in chunks to avoid memory issues
            foreach (array_chunk($messages, 100) as $chunk) {
                ChatMessage::insert($chunk);
            }

            // Update batch status
            $batch->update([
                'batch_status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ]);

            $this->logger->info('Processed batch #' . $batch->id . ' with ' . count($messages) . ' messages');
        } catch (\Exception $e) {
            if (isset($batch)) {
                $batch->update([
                    'batch_status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
            $this->logger->error('Error processing batch: ' . $e->getMessage());
        }
    }
} 
<?php

namespace YoutubeChatCapture;

use Google_Client;
use Google_Service_YouTube;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use React\EventLoop\LoopInterface;
use React\MySQL\ConnectionInterface;
use React\Promise\PromiseInterface;

class ChatCapture
{
    private const BATCH_SIZE = 1000;
    private const PROCESS_INTERVAL = 0.1; // seconds
    private array $messageBuffer = [];
    private ?string $nextPageToken = null;

    public function __construct(
        private readonly Google_Client $client,
        private readonly ConnectionInterface $db,
        private readonly LoopInterface $loop,
        private readonly Logger $logger
    ) {
        $this->logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
    }

    public function startCapture(string $liveChatId): void
    {
        $youtube = new Google_Service_YouTube($this->client);

        // Set up periodic chat message fetching
        $this->loop->addPeriodicTimer(self::PROCESS_INTERVAL, function () use ($youtube, $liveChatId) {
            $this->fetchChatMessages($youtube, $liveChatId)
                ->then(function () {
                    if (count($this->messageBuffer) >= self::BATCH_SIZE) {
                        $this->processBatch();
                    }
                })
                ->catch(function (\Exception $e) {
                    $this->logger->error('Error fetching chat messages: ' . $e->getMessage());
                });
        });

        // Set up periodic batch processing for remaining messages
        $this->loop->addPeriodicTimer(1.0, function () {
            if (!empty($this->messageBuffer)) {
                $this->processBatch();
            }
        });
    }

    private function fetchChatMessages(Google_Service_YouTube $youtube, string $liveChatId): PromiseInterface
    {
        return \React\Promise\resolve()->then(function () use ($youtube, $liveChatId) {
            $params = [
                'liveChatId' => $liveChatId,
                'part' => 'snippet,authorDetails',
                'maxResults' => 2000
            ];

            if ($this->nextPageToken) {
                $params['pageToken'] = $this->nextPageToken;
            }

            try {
                $response = $youtube->liveChatMessages->listLiveChatMessages($liveChatId, $params);
                $this->nextPageToken = $response->getNextPageToken();

                foreach ($response->getItems() as $message) {
                    $this->messageBuffer[] = [
                        'message_content' => $message->getSnippet()->getDisplayMessage(),
                        'user_youtube_id' => $message->getAuthorDetails()->getChannelId(),
                        'user_display_name' => $message->getAuthorDetails()->getDisplayName(),
                        'timestamp' => date('Y-m-d H:i:s.u', strtotime($message->getSnippet()->getPublishedAt())),
                        'chat_id' => $liveChatId,
                        'live_stream_id' => $message->getSnippet()->getLiveChatId()
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->error('Error fetching messages: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    private function processBatch(): void
    {
        if (empty($this->messageBuffer)) {
            return;
        }

        $batch = array_splice($this->messageBuffer, 0, self::BATCH_SIZE);
        
        // Create batch record
        $this->db->query(
            'INSERT INTO processing_batches (batch_status, started_at) VALUES (?, NOW(3))',
            ['processing']
        )->then(function ($result) use ($batch) {
            $batchId = $result->insertId;
            
            // Prepare bulk insert
            $placeholders = [];
            $values = [];
            foreach ($batch as $message) {
                $placeholders[] = '(?, ?, ?, ?, ?, ?)';
                $values = array_merge($values, [
                    $message['message_content'],
                    $message['user_youtube_id'],
                    $message['user_display_name'],
                    $message['timestamp'],
                    $message['chat_id'],
                    $message['live_stream_id']
                ]);
            }

            $sql = 'INSERT INTO chat_messages (message_content, user_youtube_id, user_display_name, timestamp, chat_id, live_stream_id) VALUES ' . 
                   implode(', ', $placeholders);

            return $this->db->query($sql, $values)->then(
                function () use ($batchId) {
                    return $this->db->query(
                        'UPDATE processing_batches SET batch_status = ?, completed_at = NOW(3) WHERE id = ?',
                        ['completed', $batchId]
                    );
                }
            );
        })->catch(function (\Exception $e) {
            $this->logger->error('Error processing batch: ' . $e->getMessage());
            throw $e;
        });
    }
} 
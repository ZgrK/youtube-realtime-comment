<?php

require 'vendor/autoload.php';

use YoutubeChatCapture\ChatCapture;
use YoutubeChatCapture\YouTubeHelper;
use YoutubeChatCapture\Models\Stream;
use React\EventLoop\Loop;
use Monolog\Logger;

$logger = new Logger('youtube-chat-capture');
$loop = Loop::get();

$helper = new YouTubeHelper();
$client = $helper->getClient();
$capture = new ChatCapture($client, $loop, $logger);

// Get active streams
$activeStreams = Stream::findActive();

foreach ($activeStreams as $stream) {
    try {
        // Get live chat ID if not already set
        if (!$stream->live_chat_id) {
            $liveChatId = $helper->getLiveChatId($stream->youtube_url);
            if ($liveChatId) {
                $stream->update(['live_chat_id' => $liveChatId]);
            } else {
                $logger->error("Could not get live chat ID for stream: " . $stream->youtube_url);
                continue;
            }
        }

        // Start capturing for this stream
        $capture->startCapture($stream->live_chat_id);
        $logger->info("Started capturing chat for stream: " . $stream->youtube_url);
    } catch (Exception $e) {
        $logger->error("Error processing stream {$stream->youtube_url}: " . $e->getMessage());
    }
}

$loop->run(); 
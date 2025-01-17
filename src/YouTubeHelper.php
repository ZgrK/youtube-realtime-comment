<?php

namespace YoutubeChatCapture;

use Google_Client;
use Google_Service_YouTube;

class YouTubeHelper
{
    private Google_Service_YouTube $youtube;

    public function __construct(Google_Client $client)
    {
        $this->youtube = new Google_Service_YouTube($client);
    }

    public function getLiveChatId(string $youtubeUrl): ?string
    {
        try {
            // Extract video ID from URL
            $videoId = $this->extractVideoId($youtubeUrl);
            if (!$videoId) {
                throw new \Exception('Invalid YouTube URL');
            }

            // Get video details
            $response = $this->youtube->videos->listVideos('liveStreamingDetails', [
                'id' => $videoId
            ]);

            if (empty($response->items)) {
                throw new \Exception('Video not found or not a live stream');
            }

            $video = $response->items[0];
            if (!$video->getLiveStreamingDetails() || !$video->getLiveStreamingDetails()->getActiveLiveChatId()) {
                throw new \Exception('This video does not have an active live chat');
            }

            return $video->getLiveStreamingDetails()->getActiveLiveChatId();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get live chat ID: ' . $e->getMessage());
        }
    }

    private function extractVideoId(string $url): ?string
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
} 
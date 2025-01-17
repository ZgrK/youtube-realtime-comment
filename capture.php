<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Google_Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use React\EventLoop\Factory;
use React\MySQL\Factory as MySQLFactory;
use YoutubeChatCapture\ChatCapture;
use YoutubeChatCapture\YouTubeHelper;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required([
    'YOUTUBE_API_KEY',
    'MYSQL_HOST',
    'MYSQL_USER',
    'MYSQL_PASSWORD',
    'MYSQL_DATABASE',
    'YOUTUBE_URL'
]);

// Initialize logger
$logger = new Logger('youtube-chat-capture');
$logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

try {
    // Initialize Google Client
    $client = new Google_Client();
    $client->setDeveloperKey($_ENV['YOUTUBE_API_KEY']);
    $client->setApplicationName('YouTube Chat Capture');

    // Get Live Chat ID from YouTube URL
    $youtubeHelper = new YouTubeHelper($client);
    $liveChatId = $youtubeHelper->getLiveChatId($_ENV['YOUTUBE_URL']);
    $logger->info('Retrieved Live Chat ID: ' . $liveChatId);

    // Initialize Event Loop
    $loop = Factory::create();

    // Initialize MySQL Connection
    $mysql = new MySQLFactory($loop);
    $uri = sprintf(
        'mysql://%s:%s@%s/%s',
        $_ENV['MYSQL_USER'],
        $_ENV['MYSQL_PASSWORD'],
        $_ENV['MYSQL_HOST'],
        $_ENV['MYSQL_DATABASE']
    );
    
    $mysql->createConnection($uri)->then(
        function ($connection) use ($client, $loop, $logger, $liveChatId) {
            $logger->info('Connected to MySQL database');
            
            // Initialize and start chat capture
            $chatCapture = new ChatCapture($client, $connection, $loop, $logger);
            $chatCapture->startCapture($liveChatId);
            
            $logger->info('Chat capture started');
        },
        function (\Exception $e) use ($logger) {
            $logger->error('Could not connect to MySQL: ' . $e->getMessage());
            exit(1);
        }
    );

    // Run the event loop
    $loop->run();
} catch (\Exception $e) {
    $logger->error('Fatal error: ' . $e->getMessage());
    exit(1);
} 
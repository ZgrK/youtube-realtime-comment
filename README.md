# YouTube Live Chat Capture System

A high-performance PHP system for capturing and storing YouTube live stream chat messages in real-time.

## Features

- Real-time chat message capture from YouTube Live Stream
- High-performance batch processing (1000+ messages per second)
- Asynchronous database operations
- Error handling and logging
- Optimized MySQL table structure with proper indexing
- Connection pooling
- Automatic Live Chat ID extraction from YouTube URLs

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer
- YouTube Data API v3 credentials

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd youtube-chat-capture
```

2. Install dependencies:
```bash
composer install
```

3. Set up the database:
```bash
mysql -u your_user -p < database/schema.sql
```

4. Configure environment variables:
```bash
cp .env.example .env
```
Edit `.env` and fill in your credentials:
- `YOUTUBE_API_KEY`: Your YouTube Data API key
- `MYSQL_HOST`: MySQL host (default: localhost)
- `MYSQL_USER`: MySQL username
- `MYSQL_PASSWORD`: MySQL password
- `MYSQL_DATABASE`: Database name (default: youtube_chat_capture)
- `YOUTUBE_URL`: YouTube live stream URL (e.g., https://www.youtube.com/watch?v=video_id)

## Usage

1. Start the chat capture system:
```bash
php capture.php
```

The system will:
- Extract Live Chat ID from the provided YouTube URL
- Connect to the YouTube Live Stream Chat API
- Start capturing messages in real-time
- Process messages in batches
- Store them in the MySQL database

## Database Structure

### chat_messages table
- `id`: Unique message identifier
- `message_content`: The chat message content
- `user_youtube_id`: YouTube channel ID of the message sender
- `user_display_name`: Display name of the message sender
- `timestamp`: When the message was sent
- `chat_id`: YouTube Live Chat ID
- `live_stream_id`: YouTube Live Stream ID
- `created_at`: When the message was captured

### processing_batches table
- Tracks batch processing status
- Helps with monitoring and debugging

## Performance Optimizations

- Batch processing for database insertions
- Asynchronous database operations using ReactPHP
- Optimized table indexes
- Connection pooling
- Efficient message buffering

## Error Handling

The system includes comprehensive error handling and logging:
- Database connection errors
- API rate limiting
- Message processing failures
- Batch processing status tracking
- YouTube URL validation and Live Chat ID extraction errors

## Monitoring

Monitor the system using the following:
- Check logs in stderr
- Monitor the `processing_batches` table for batch status
- MySQL slow query log for performance issues

## License

[Your License]

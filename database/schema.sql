CREATE DATABASE IF NOT EXISTS youtube_chat_capture;
USE youtube_chat_capture;

CREATE TABLE IF NOT EXISTS chat_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_content TEXT NOT NULL,
    user_youtube_id VARCHAR(255) NOT NULL,
    user_display_name VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP(3) NOT NULL,
    chat_id VARCHAR(255) NOT NULL,
    live_stream_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    INDEX idx_livestream_timestamp (live_stream_id, timestamp),
    INDEX idx_chat_id (chat_id),
    INDEX idx_user_youtube_id (user_youtube_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS processing_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL,
    started_at TIMESTAMP(3) NULL,
    completed_at TIMESTAMP(3) NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    INDEX idx_status_created (batch_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
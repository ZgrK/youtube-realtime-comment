<?php

namespace YoutubeChatCapture\Database\Migrations;

use YoutubeChatCapture\Database\Migration;

class CreateStreamsTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS streams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            youtube_url VARCHAR(255) NOT NULL,
            live_chat_id VARCHAR(255),
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->pdo->exec($sql);
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS streams");
    }
} 
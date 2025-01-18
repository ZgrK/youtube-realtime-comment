<?php

namespace YoutubeChatCapture\Models;

use PDO;
use YoutubeChatCapture\Database\DB;

class Stream
{
    private static ?PDO $db = null;

    public function __construct(
        public ?int $id = null,
        public string $youtube_url = '',
        public ?string $live_chat_id = null,
        public bool $is_active = true,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {
        if (self::$db === null) {
            self::$db = DB::getInstance();
        }
    }

    public static function create(array $data): self
    {
        if (self::$db === null) {
            self::$db = DB::getInstance();
        }

        $stmt = self::$db->prepare("
            INSERT INTO streams (youtube_url, live_chat_id, is_active)
            VALUES (:youtube_url, :live_chat_id, :is_active)
        ");

        $stmt->execute([
            ':youtube_url' => $data['youtube_url'],
            ':live_chat_id' => $data['live_chat_id'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
        ]);

        $data['id'] = self::$db->lastInsertId();
        return new self(...$data);
    }

    public static function findAll(): array
    {
        if (self::$db === null) {
            self::$db = DB::getInstance();
        }

        $stmt = self::$db->query("SELECT * FROM streams ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function findActive(): array
    {
        if (self::$db === null) {
            self::$db = DB::getInstance();
        }

        $stmt = self::$db->prepare("SELECT * FROM streams WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function update(array $data): bool
    {
        $stmt = self::$db->prepare("
            UPDATE streams 
            SET youtube_url = :youtube_url,
                live_chat_id = :live_chat_id,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        return $stmt->execute([
            ':youtube_url' => $data['youtube_url'] ?? $this->youtube_url,
            ':live_chat_id' => $data['live_chat_id'] ?? $this->live_chat_id,
            ':is_active' => $data['is_active'] ?? $this->is_active,
            ':id' => $this->id
        ]);
    }

    public function delete(): bool
    {
        $stmt = self::$db->prepare("DELETE FROM streams WHERE id = ?");
        return $stmt->execute([$this->id]);
    }
} 
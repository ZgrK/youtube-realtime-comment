<?php

namespace YoutubeChatCapture\Database;

use PDO;

class MigrationManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->createMigrationsTable();
    }

    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }

    public function migrate(): void
    {
        $files = glob(__DIR__ . '/migrations/*.php');
        sort($files);

        $batch = $this->getNextBatchNumber();

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            
            if ($this->hasMigrationRun($migrationName)) {
                continue;
            }

            require_once $file;
            $className = "YoutubeChatCapture\\Database\\Migrations\\$migrationName";
            $migration = new $className($this->pdo);
            
            $this->pdo->beginTransaction();
            try {
                $migration->up();
                $this->logMigration($migrationName, $batch);
                $this->pdo->commit();
                echo "Migrated: $migrationName\n";
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                echo "Error migrating $migrationName: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as batch FROM migrations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['batch'] ?? 0) + 1;
    }

    private function hasMigrationRun(string $migration): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    private function logMigration(string $migration, int $batch): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);
    }
} 
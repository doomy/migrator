<?php

declare(strict_types=1);

namespace Doomy\Migrator;

use Dibi\DriverException;
use Doomy\CustomDibi\Connection;
use Doomy\Ormtopus\DataEntityManager;

class Migrator
{
    public const DB_CODE_TABLE_DOES_NOT_EXIST = 1146;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DataEntityManager
     */
    private $data;

    private $log;

    /**
     * @var array
     */
    private $config;

    public function __construct(Connection $connection, DataEntityManager $data, array $config)
    {
        $this->connection = $connection;
        $this->data = $data;
        $this->config = $config;
    }

    public function migrate()
    {
        try {
            $allMigrations = $this->data->findAll(Migration::class);
        } catch (DriverException $e) {
            if ($e->getCode() === self::DB_CODE_TABLE_DOES_NOT_EXIST) {
                $this->createMigrationsTable();
                return $this->migrate();
            }

            $this->log .= sprintf('Unknown error: %s', $e->getMessage());
            return;
        }
        try {
            $migrationFiles = scandir($this->config['migrations_directory']);
        } catch (\ErrorException $e) {
            $this->log .= sprintf('Unknown error: %s', $e->getMessage());
            return;
        }

        $newMigrationsApplied = false;

        foreach ($migrationFiles as $migrationFile) {
            $migrationId = $this->getMigrationIdFromFilename($migrationFile);
            if (! $migrationId) {
                continue;
            }
            if (! $this->migrationIsApplied($migrationId, $allMigrations)) {
                $this->log .= "applying migration {$migrationId} \n";
                $sql = file_get_contents($this->config['migrations_directory'] . '/' . $migrationFile);
                $this->log .= "sql executed: {$sql} \n";
                $this->query($sql);
                try {
                    $this->data->save(Migration::class, [
                        'MIGRATION_ID' => $migrationId,
                        'MIGRATED_DATE' => new \DateTime(),
                    ]);
                } catch (\Exception $e) {
                }
                $this->log .= "OK. \n \n";
                $newMigrationsApplied = true;
            }
        }
        if (! $newMigrationsApplied) {
            $this->log .= "No new migrations \n";
        }
    }

    public function getOutput(): string
    {
        return $this->log;
    }

    private function getMigrationIdFromFilename($migrationFilename)
    {
        $parts = explode('.', $migrationFilename);
        $extension = array_pop($parts);
        if (! $extension || $extension !== 'sql') {
            return null;
        }
        return array_shift($parts);
    }

    private function query($sql)
    {
        $sql = str_replace("\r\n", '', $sql);
        $parts = explode(';', $sql);
        foreach ($parts as $part) {
            if (! $part || ctype_space($part)) {
                continue;
            }
            $this->connection->query($part);
        }
    }

    private function migrationIsApplied($migrationId, $allMigrations)
    {
        foreach ($allMigrations as $migration) {
            if ($migration->MIGRATION_ID === $migrationId) {
                return true;
            }
        }

        return false;
    }

    private function createMigrationsTable(): void
    {
        $this->log .= "Migration table does not exist. Creating... \n \n";
        $sqlCreate = 'CREATE TABLE IF NOT EXISTS t_migration (
                  migration_id VARCHAR(255) NOT NULL,
                  migrated_date DATETIME,
                  PRIMARY KEY(migration_id)
                )';
        $this->log .= "sql executed: {$sqlCreate} \n";
        $this->query($sqlCreate);
    }
}

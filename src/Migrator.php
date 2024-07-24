<?php

declare(strict_types=1);

namespace Doomy\Migrator;

use Dibi\Connection;
use Dibi\DriverException;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Repository\Helper\DbHelper;
use Doomy\Repository\Model\Entity;
use Doomy\Repository\TableDefinition\TableDefinitionFactory;

class Migrator
{
    public const DB_CODE_TABLE_DOES_NOT_EXIST = 1146;

    public const MIGRATION_NUMBER_FILENAME_PADDING_CHARS = 3;

    private string $log = '';

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        private Connection $connection,
        private DataEntityManager $data,
        private array $config,
        private TableDefinitionFactory $tableDefinitionFactory,
        private DbHelper $dbHelper
    ) {
    }

    public function migrate(): void
    {
        try {
            $allMigrations = $this->data->findAll(Migration::class);
        } catch (DriverException $e) {
            if ($e->getCode() === self::DB_CODE_TABLE_DOES_NOT_EXIST) {
                $this->createMigrationsTable();
                $this->migrate();
                return;
            }

            $this->log .= sprintf('Unknown error: %s', $e->getMessage());
            return;
        }

        $newMigrationsApplied = false;
        foreach ($this->getMigrationFiles() as $migrationFile) {
            $migrationId = $this->getMigrationIdFromFilename($migrationFile);
            if (! $migrationId) {
                continue;
            }
            if (! $this->migrationIsApplied($migrationId, $allMigrations)) {
                $this->log .= "applying migration {$migrationId} \n";
                $sql = file_get_contents($this->config['migrations_directory'] . '/' . $migrationFile);
                $this->log .= "sql executed: {$sql} \n";

                if (! is_string($sql)) {
                    throw new \Exception('Migration file is not readable');
                }

                $this->query($sql);
                try {
                    $migration = new Migration($migrationId, new \DateTime());
                    $this->data->save(Migration::class, $migration);
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

    /**
     * @param class-string<Entity> $entityClass
     */
    public function createMigrationFromEntity(string $entityClass): string
    {
        $tableDefinition = $this->tableDefinitionFactory->createTableDefinition($entityClass);
        $sql = $this->dbHelper->getCreateTable($tableDefinition);
        $number = $this->getMaxMigrationNumber() + 1;
        $migrationId = str_pad(
            (string) $number,
            self::MIGRATION_NUMBER_FILENAME_PADDING_CHARS,
            '0',
            STR_PAD_LEFT
        ) . '-' . $tableDefinition->getTableName();
        file_put_contents($this->config['migrations_directory'] . '/' . $migrationId . '.sql', $sql);
        return $migrationId;
    }

    private function getMigrationIdFromFilename(string $migrationFilename): ?string
    {
        $parts = explode('.', $migrationFilename);
        $extension = array_pop($parts);
        if (! $extension || $extension !== 'sql') {
            return null;
        }
        return array_shift($parts);
    }

    private function getMigrationNumberFromFilename(string $migrationFilename): int
    {
        $parts = explode('-', $migrationFilename);
        return $parts[0] ? (int) $parts[0] : throw new \Exception('Invalid migration filename');
    }

    private function query(string $sql): void
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

    /**
     * @param Migration[] $allMigrations
     */
    private function migrationIsApplied(string $migrationId, array $allMigrations): bool
    {
        foreach ($allMigrations as $migration) {
            if ($migration->getMigrationId() === $migrationId) {
                return true;
            }
        }

        return false;
    }

    private function createMigrationsTable(): void
    {
        $this->log .= "Migration table does not exist. Creating... \n \n";

        $tableDefinition = $this->tableDefinitionFactory->createTableDefinition(Migration::class);
        $sqlCreate = $this->dbHelper->getCreateTable($tableDefinition);

        $this->log .= "sql executed: {$sqlCreate} \n";
        $this->query($sqlCreate);
    }

    private function getMaxMigrationNumber(): int
    {
        $max = 0;
        foreach ($this->getMigrationFiles() as $migrationFile) {
            $migrationNumber = $this->getMigrationNumberFromFilename($migrationFile);
            if ($migrationNumber > $max) {
                $max = $migrationNumber;
            }
        }
        return $max;
    }

    /**
     * @return string[]
     */
    private function getMigrationFiles(): array
    {
        $migrationDirectory = $this->config['migrations_directory'];
        if (! is_string($migrationDirectory)) {
            throw new \Exception('Migrations directory is not a string');
        }
        $migrationFiles = scandir($migrationDirectory);
        if ($migrationFiles === false) {
            throw new \Exception('Error reading migration files');
        }
        return $migrationFiles;
    }
}

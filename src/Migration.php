<?php

declare(strict_types=1);

namespace Doomy\Migrator;

use Doomy\Repository\Model\Entity;
use Doomy\Repository\TableDefinition\Attribute\Column\Identity;
use Doomy\Repository\TableDefinition\Attribute\Column\PrimaryKey;
use Doomy\Repository\TableDefinition\Attribute\Table;

#[Table('t_migration')]
class Migration extends Entity
{
    public function __construct(
        #[PrimaryKey]
        #[Identity]
        private ?string $migrationId,
        private \DateTimeInterface $migrationDate
    ) {
    }

    public function getMigrationId(): ?string
    {
        return $this->migrationId;
    }

    public function getMigrationDate(): \DateTimeInterface
    {
        return $this->migrationDate;
    }
}

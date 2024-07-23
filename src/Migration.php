<?php

declare(strict_types=1);

namespace Doomy\Migrator;

use Doomy\Repository\Model\Entity;

class Migration extends Entity
{
    public const TABLE = 't_migration';

    public const IDENTITY_COLUMN = 'migration_id';

    public $MIGRATION_ID;

    public $MIGRATION_DATE;
}

<?php

namespace Doomy\Migrator;

use Doomy\Repository\Model\Entity;

class Migration extends Entity
{
    const TABLE = "t_migration";
    const IDENTITY_COLUMN = "migration_id";

    public $MIGRATION_ID;
    public $MIGRATION_DATE;
}
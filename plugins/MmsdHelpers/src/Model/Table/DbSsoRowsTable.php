<?php

namespace MmsdHelpers\Model\Table;

use Cake\ORM\Table;

class DbSsoRowsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('app_manager.DbSSO');
    }
}
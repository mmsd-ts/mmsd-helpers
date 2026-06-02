<?php

namespace MmsdHelpers\Model\Table;

use Cake\ORM\Table;

class AppAuditRecordsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('dbo.AppAudit');
    }
}
<?php

namespace MmsdHelpers\Model\Table;

use Cake\ORM\Table;

class UsStatesTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('dbo.US_States');
        $this->setPrimaryKey('us_state_id');
    }
    public function stateList(): array
    {
        $keyField = 'us_state_id';
        $displayField = 'name';
        $list = $this->find('list',
            keyField: $keyField,
            valueField: $displayField
        )
        ->order([
            'is_default' => 'desc',
            'name' => 'asc',
        ]);
        return $list->toArray();
    }
}
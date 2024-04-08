<?php

namespace MmsdHelpers\Model\Table;

use Cake\ORM\Table;

class AddressesTable extends Table
{
    
    public function initialize(array $config): void
    {
        $this->setTable('dbo.AddressLookup');
        $this->setPrimaryKey('id');
    }

    public function tagList(): array
    {
        $list = [];
        $rows = $this->find()
            ->select([
                'tag',
            ])
            ->where([
                'districtID' => '200',
                'tag IS NOT' => null,
            ])
            ->group([
                'tag',
            ])
            ->order([
                'tag' => 'asc',
            ])
        ;
        foreach ($rows as $row) {
            $list[$row->tag] = $row->tag;
        }
        return $list;
    }
    
}
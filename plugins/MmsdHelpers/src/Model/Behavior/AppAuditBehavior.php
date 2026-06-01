<?php
namespace MmsdHelpers\Model\Behavior;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Log\Log;

class AppAuditBehavior extends Behavior
{
    public function afterSave(EventInterface $event, EntityInterface $entity)
    {
        $dump = [];
        $dump['Table'] = $this->table()->getTable();
        $dump['Class'] = $entity->getSource();
        $dump['Action'] = ($entity->isNew()) ? 'Insert' : 'Update';
        $dump['PrimaryKey'] = $entity->id;
        $auditedData = [
            'old' => [],
            'new' => [],
        ];
        if ($entity->isNew()) {
//            foreach ($entity->toArray() as $key) {
//                if (in_array($key, ['created', 'modified',])) {
//                    continue;
//                }
//                $auditedData['new'][$key] = $entity->key;
//            }
            $dump['ToArray'] = $entity->toArray();
        } else {
            foreach ($entity->getOriginalValues() as $key => $originalValue) {
                if (in_array($key, ['created', 'modified',])) {
                    continue;
                }
                if ($originalValue != $entity->$key) {
                    $auditedData['old'][$key] = $originalValue;
                    $auditedData['new'][$key] = $entity->$key;
                }
            }
        }
        $dump['AuditedData'] = json_encode($auditedData);
        Log::debug(print_r($dump,true));
    }
}
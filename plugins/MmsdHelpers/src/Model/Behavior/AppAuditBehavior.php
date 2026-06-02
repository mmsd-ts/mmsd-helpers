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
        $ignoredKeys = ['created','modified','audit_user','audit_impersonator'];
        if ($entity->isNew()) {
            foreach ($entity->toArray() as $key => $value) {
                if (in_array($key, $ignoredKeys)) {
                    continue;
                }
                $auditedData['new'][$key] = $value;
            }
        } else {
            foreach ($entity->getOriginalValues() as $key => $originalValue) {
                if (in_array($key, $ignoredKeys)) {
                    continue;
                }
                if ($originalValue != $entity->$key) {
                    $auditedData['old'][$key] = $originalValue;
                    $auditedData['new'][$key] = $entity->$key;
                }
            }
        }
        $dump['AuditedData'] = json_encode($auditedData);
        $dump['User'] = $entity->audit_user;
        $dump['Impersonator'] = $entity->audit_impersonator;
        Log::debug(print_r($dump,true));
    }
}
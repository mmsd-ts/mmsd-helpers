<?php
namespace MmsdHelpers\Model\Behavior;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

class AppAuditBehavior extends Behavior
{
    private array $ignoredKeys = ['created','modified','audit_user','audit_impersonator'];
    public function afterSave(EventInterface $event, EntityInterface $entity): void
    {
        $auditedData = [
            'old' => [],
            'new' => [],
        ];
        if ($entity->isNew()) {
            foreach ($entity->toArray() as $key => $value) {
                if (in_array($key, $this->ignoredKeys)) {
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
        $appAuditRecordsTable = $this->fetchTable('AppAuditRecords');
        $appAuditRecord = $appAuditRecordsTable->newEntity([
            'user' => $entity->audit_user,
            'impersonator' => $entity->audit_impersonator,
            'className' => $entity->getSource(),
            'tableName' => $this->table()->getTable(),
            'recordAction' => ($entity->isNew()) ? 'Insert' : 'Update',
            'primaryKey' => $entity->id,
            'auditedData' => json_encode($auditedData),
        ]);
        $appAuditRecordsTable->save($appAuditRecord);
    }
    public function afterDelete(EventInterface $event, EntityInterface $entity): void
    {
        $auditedData = [
            'old' => [],
            'new' => [],
        ];
        foreach ($entity->toArray() as $key => $value) {
            if (in_array($key, $this->ignoredKeys)) {
                continue;
            }
            $auditedData['old'][$key] = $value;
        }
        $appAuditRecordsTable = $this->fetchTable('AppAuditRecords');
        $appAuditRecord = $appAuditRecordsTable->newEntity([
            'user' => $entity->audit_user,
            'impersonator' => $entity->audit_impersonator,
            'className' => $entity->getSource(),
            'tableName' => $this->table()->getTable(),
            'recordAction' => 'Delete',
            'primaryKey' => $entity->id,
            'auditedData' => json_encode($auditedData),
        ]);
        $appAuditRecordsTable->save($appAuditRecord);
    }
}
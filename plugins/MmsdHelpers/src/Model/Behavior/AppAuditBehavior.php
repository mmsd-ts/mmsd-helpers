<?php
namespace MmsdHelpers\Model\Behavior;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Core\Configure;

class AppAuditBehavior extends Behavior
{
    use LocatorAwareTrait;
    private array $ignoredKeys = ['created','modified','audit_user','audit_impersonator'];
    private array $userData = [];
    private ?string $auditAppName;
    public function initialize(array $options): void
    {
        $this->auditAppName = null;
        if (!empty(Configure::read('App.auditAppName'))) {
            $this->auditAppName = Configure::read('App.auditAppName');
        }
        if (!empty(Configure::read('App.Audit.UserData'))) {
            $this->userData = Configure::read('App.Audit.UserData');
        }
    }
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
                if (in_array($key, $this->ignoredKeys)) {
                    continue;
                }
                if ($originalValue != $entity->$key) {
                    $auditedData['old'][$key] = $originalValue;
                    $auditedData['new'][$key] = $entity->$key;
                }
            }
        }
        $appAuditRecordsTable = $this->fetchTable('MmsdHelpers.AppAuditRecords');
        $appAuditRecord = $appAuditRecordsTable->newEntity([
            'appUser' => $this->userData['audit_user'],
            'appImpersonator' => $this->userData['audit_impersonator'],
            'appName' => $this->auditAppName,
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
        $appAuditRecordsTable = $this->fetchTable('MmsdHelpers.AppAuditRecords');
        $appAuditRecord = $appAuditRecordsTable->newEntity([
            'appUser' => $this->userData['audit_user'],
            'appImpersonator' => $this->userData['audit_impersonator'],
            'appName' => $this->auditAppName,
            'className' => $entity->getSource(),
            'tableName' => $this->table()->getTable(),
            'recordAction' => 'Delete',
            'primaryKey' => $entity->id,
            'auditedData' => json_encode($auditedData),
        ]);
        $appAuditRecordsTable->save($appAuditRecord);
    }
}
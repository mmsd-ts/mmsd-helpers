<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\ORM\Entity;

class AppAuditComponent extends Component
{
    public function userData(): array
    {
        $patchData = [
            'audit_user' => null,
            'audit_impersonator' => null,
        ];
        if ($this->getController()->getRequest()->getSession()->check('Auth')) {
            $patchData['audit_user'] = $this->makeValue(
                $this->getController()->getRequest()->getSession()->read('Auth')
            );
        }
        if ($this->getController()->getRequest()->getSession()->check('AuthImpersonate')) {
            $patchData['audit_impersonator'] = $this->makeValue(
                $this->getController()->getRequest()->getSession()->read('AuthImpersonate')
            );
        }
        return $patchData;
    }
    private function makeValue(Entity $user): string|int
    {
        $value = '';
        if (!empty($user->username)) {
            $value = $user->username;
            if (!empty($user->fullName)) {
                $value .= " - {$user->fullName}";
            }
        } else {
            $value = $user->id;
        }
        return $value;
    }
}
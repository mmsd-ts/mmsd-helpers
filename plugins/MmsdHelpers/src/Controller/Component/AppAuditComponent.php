<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\Entity;
use Cake\Core\Configure;

class AppAuditComponent extends Component
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $makeValue = function (Entity $user): string|int {
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
        };
        $userData = [
            'audit_user' => null,
            'audit_impersonator' => null,
        ];
        if ($this->getController()->getRequest()->getSession()->check('Auth')) {
            $userData['audit_user'] = $makeValue(
                $this->getController()->getRequest()->getSession()->read('Auth')
            );
        }
        if ($this->getController()->getRequest()->getSession()->check('AuthImpersonate')) {
            $userData['audit_impersonator'] = $makeValue(
                $this->getController()->getRequest()->getSession()->read('AuthImpersonate')
            );
        }
        Configure::write('App.Audit.UserData',$userData);
    }
}
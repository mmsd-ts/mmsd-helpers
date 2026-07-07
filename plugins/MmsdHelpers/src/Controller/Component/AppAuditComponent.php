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
        $config += [
            'sessionVariable' => 'Auth',
            'identifier' => 'username',
            'fullName' => 'fullName',
            'primaryKey' => 'id',
            'impersonateSessionVariable' => 'AuthImpersonate', // just in case Cake Authentication changes this name
        ];
        $makeValue = function (Entity $user) use ($config): string|int {
            $value = '';
            if (!empty($user->{$config['identifier']})) {
                $value = $user->{$config['identifier']};
                if (!empty($user->{$config['fullName']})) {
                    $value .= ' - ' . $user->{$config['fullName']};
                }
            } else {
                $value = $user->{$config['primaryKey']};
            }
            return $value;
        };
        $userData = [
            'audit_user' => null,
            'audit_impersonator' => null,
        ];
        if ($this->getController()->getRequest()->getSession()->check($config['sessionVariable'])) {
            $userData['audit_user'] = $makeValue(
                $this->getController()->getRequest()->getSession()->read($config['sessionVariable'])
            );
        }
        if ($this->getController()->getRequest()->getSession()->check($config['impersonateSessionVariable'])) {
            $userData['audit_impersonator'] = $makeValue(
                $this->getController()->getRequest()->getSession()->read($config['impersonateSessionVariable'])
            );
        }
        Configure::write('App.Audit.UserData',$userData);
    }
}
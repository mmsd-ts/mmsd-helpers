<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;

class CheckAccessComponent extends Component
{
    public $components = ['MmsdHelpers.CheckRole'];
    protected $roles = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->roles = (!empty($config['overrideRoles'])) ? $config['overrideRoles'] : [];
    }

    public function accessCheck(string $sessionKey, string $value, array $overrideRoles = []): bool
    {
        $allRoles = array_merge($overrideRoles,$this->roles);
        if ($this->CheckRole->check($allRoles)) {
            return true;
        }
        if ($this->getController()->getRequest()->getSession()->check($sessionKey)) {
            return (in_array($value,$this->getController()->getRequest()->getSession()->read($sessionKey)));
        }
        return false;
    }
}

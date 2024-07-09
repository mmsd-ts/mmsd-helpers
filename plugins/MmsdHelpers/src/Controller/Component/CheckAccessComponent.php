<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;

class CheckAccessComponent extends Component
{
    public array $components = ['MmsdHelpers.CheckRole'];
    protected array $roles = [];

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

    public function addItem(string $sessionKey, string $value): void
    {
        if ($this->getController()->getRequest()->getSession()->check($sessionKey)) {
            $values = $this->getController()->getRequest()->getSession()->read($sessionKey);
            if (!in_array($value,$values)) {
                $values[] = $value;
            }
            $this->getController()->getRequest()->getSession()->write($sessionKey,$values);
        } else {
            $this->writeArray($sessionKey, [$value]);
        }
    }

    public function writeArray(string $sessionKey, array $values): void
    {
        $this->getController()->getRequest()->getSession()->write($sessionKey,$values);
    }
}

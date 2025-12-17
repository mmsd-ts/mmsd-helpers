<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Cookie\Cookie;
use RuntimeException;

class CheckRoleComponent extends Component
{
    // This variable is discontinued. Use DbSso instead.
    private string $cookiePrefix = 'SSO_MMSD';
    private array $allAccessRoles = ['Administrator'];
    private array $elevatedRoles = ['Administrator'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        foreach (['allAccessRoles','elevatedRoles'] as $group) {
            if (isset($config[$group])) {
                if ($config[$group] === false) {
                    $this->$group = [];
                } else {
                    $this->$group = (is_array($config[$group]))
                        ? $config[$group]
                        : [$config[$group]]
                    ;
                }
            }
        }
    }

    public function ssoCheck(string $appName)
    {
        throw new \RuntimeException("CheckRole::ssoCheck() has been discontinued. Use DbSso instead.");
    }

    public function ssoRegister(string $username, string $appName)
    {
        throw new \RuntimeException("CheckRole::ssoRegister() has been discontinued. Use DbSso instead.");
    }

    public function ssoRemove(string $appName, bool $forceOut = false)
    {
        throw new \RuntimeException("CheckRole::ssoRemove() has been discontinued. Use DbSso instead.");
    }
    public function check($roles = []): bool
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        if (!empty($this->allAccessRoles)) {
            $roles = array_merge($roles,$this->allAccessRoles);
        }
        if ((!empty($this->elevatedRoles))
            and ($this->getController()->Authentication->isImpersonating())
        ) {
            $roles = array_diff($roles, $this->elevatedRoles);
        }
        if (!empty($roles)) {
            foreach ($roles as $role) {
                try {
                    $this->getController()->Authentication->getIdentityData('username');
                } catch (RuntimeException $e) {
                    break;
                }
                $isRole = "is{$role}";
                if (
                    (!empty($this->getController()->Authentication->getIdentityData($role)))
                    or (!empty($this->getController()->Authentication->getIdentityData($isRole)))
                ){
                    return true;
                }
            }
        }
        return false;
    }
    public function isOnly($roles = []): bool
    {
        try {
            $this->getController()->Authentication->getIdentityData('username');
        } catch (RuntimeException $e) {
            return false;
        }
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        if ((!empty($this->elevatedRoles))
            and ($this->getController()->Authentication->isImpersonating())
        ) {
            $roles = array_diff($roles, $this->elevatedRoles);
        }
        $allRoles = [];
        foreach ($roles as $role) {
            $allRoles[] = $role;
            $allRoles[] = "is{$role}";
        }
        $identity = $this->getController()->Authentication->getIdentity()->getOriginalData()->toArray();
        foreach ($identity as $key => $value) {
            if (is_array($value)) { continue; }
            if (strpos($key,'is') !== 0) { continue; }
            if (in_array($key,$allRoles)) { continue; }
            if (!empty($value)) {
                return false;
            }
        }
        return true;
    }
}

<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Cookie\Cookie;
use RuntimeException;

class CheckRoleComponent extends Component
{

    // Do not ever change this. (See Application->middleware()->$cookies)
    private string $cookiePrefix = 'SSO_MMSD';
    private array $allAccessRoles = ['Administrator'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        if (isset($config['allAccessRoles'])) {
            if ($config['allAccessRoles'] === false) {
                $this->allAccessRoles = [];
            } else {
                $this->allAccessRoles = (is_array($config['allAccessRoles']))
                    ? $config['allAccessRoles']
                    : [$config['allAccessRoles']]
                ;
            }
        }
    }

    public function ssoCheck(string $appName)
    {
        throw new \RuntimeException("CheckRole::ssoCheck() has been discontinued by the Mean Programmers Club™.");
    }

    public function ssoRegister(string $username, string $appName)
    {
        throw new \RuntimeException("CheckRole::ssoRegister() has been discontinued by the Mean Programmers Club™.");
    }

    public function ssoRemove(string $appName, bool $forceOut = false): void
    {
        $appCookie = (new Cookie("{$this->cookiePrefix}_{$appName}"))
            ->withValue('0')
            ->withPath('/')
            ->withExpiry(new \DateTime('+3 second'))
        ;
        $this->getController()->setResponse($this->getController()->getResponse()->withCookie($appCookie));
        if ($forceOut) {
            $ssoCookie = (new Cookie($this->cookiePrefix))
                ->withPath('/')
            ;
            $this->getController()->setResponse($this->getController()->getResponse()->withExpiredCookie($ssoCookie));
        }
    }
    public function check($roles = []): bool
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        if (!empty($this->allAccessRoles)) {
            $roles = array_merge($roles,$this->allAccessRoles);
        }
        if (!empty($roles)) {
            foreach ($roles as $role) {
                try {
                    $this->getController()->Authentication->getIdentityData($role);
                } catch (RuntimeException $e) {
                    break;
                }
                $isRole = "is{$role}";
                if (
                    (!empty($this->getController()->Authentication->getIdentityData($role)))
                    or
                    (!empty($this->getController()->Authentication->getIdentityData($isRole)))
                ){
                    return true;
                }
            }
        }
        return false;
    }
    public function isOnly($roles = []): bool
    {
        if (!is_array($roles)) {
            $roles = [$roles];
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

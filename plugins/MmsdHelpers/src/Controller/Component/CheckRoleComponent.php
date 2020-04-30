<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Cookie\Cookie;

class CheckRoleComponent extends Component
{

    // Do not ever change this. (See Application->middleware()->$cookies)
    private $cookiePrefix = 'SSO_MMSD';

    public function ssoCheck(string $appName): bool
    {
        $ssoCookie = $this->getController()->getRequest()->getCookie($this->cookiePrefix);
        $appCookie = $this->getController()->getRequest()->getCookie("{$this->cookiePrefix}_{$appName}") ?? '1';
        if (!empty($ssoCookie)) {
            if (($appCookie == '1') and ($this->getController()->getRequest()->getAttribute('authentication')->getResult()->isValid())) {
                if (!empty($this->getController()->Authentication->getIdentityData('id'))) {
                    return true;
                }
            }
            if ($appCookie == '1') {
                $usersTable = $this->getController()->loadModel('Users');
                $username = $ssoCookie;
                $user = $usersTable->find('byUsername',['username' => $username])->first();
                if (!empty($user)) {
                    $this->getController()->getRequest()->getSession()->write('App.impersonatorID',$user->id);
                    $this->getController()->Authentication->setIdentity($user);
                    $appCookie = (new Cookie("{$this->cookiePrefix}_{$appName}"))
                        ->withValue('1')
                        ->withPath('/')
                        ->withExpiry(new \DateTime('+8 hour'))
                    ;
                    $this->getController()->setResponse($this->getController()->getResponse()->withCookie($appCookie));
                    return true;
                }
            }
        }
        return false;
    }

    public function ssoRegister(string $username, string $appName): void
    {
        $ssoCookie = $this->getController()->getRequest()->getCookie($this->cookiePrefix);
        if (empty($ssoCookie)) {
            $ssoCookie = (new Cookie($this->cookiePrefix))
                ->withValue($username)
                ->withPath('/')
                ->withExpiry(new \DateTime('+8 hour'))
            ;
            $this->getController()->setResponse($this->getController()->getResponse()->withCookie($ssoCookie));
        }
        $appCookie = (new Cookie("{$this->cookiePrefix}_{$appName}"))
            ->withValue('1')
            ->withPath('/')
            ->withExpiry(new \DateTime('+8 hour'))
        ;
        $this->getController()->setResponse($this->getController()->getResponse()->withCookie($appCookie));
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

    public function check($roles = ''): bool
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        $identityHasRole = false;
        if (!empty($roles)) {
            foreach ($roles as $role) {
                $isRole = "is{$role}";
                if (
                    (!empty($this->getController()->getRequest()->getAttribute('identity')->$role))
                    or
                    (!empty($this->getController()->getRequest()->getAttribute('identity')->$isRole))
                ){
                    $identityHasRole = true;
                    break;
                }
            }
        }
        return $identityHasRole;
    }

}

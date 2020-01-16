<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;

class CheckRoleComponent extends Component
{

    public function check(array $roles = []): bool
    {
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

<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Inflector;
use Authentication\IdentityInterface;
use RuntimeException;

class BsNavbarHelper extends Helper
{
    protected $links = [];
    protected $linkMap = [];
    protected $params = [];
    protected $identity;
    protected $helpers = ['Html','Url'];
    private $allAccessRoles = ['Administrator'];
    
    /**
     * 
     * {@inheritDoc}
     * @see \Cake\View\Helper::initialize()
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->links = $config['links'];
        $this->linkMap = $config['linkMap'];
        $this->params = $this->getView()->getRequest()->getAttribute('params');
        $this->identity = $this->getView()->getRequest()->getAttribute('identity');
        
        if (empty($this->params)) {
            return;
        }
        
        if ((!empty($this->identity)) and (!$this->identity instanceof IdentityInterface)) {
            throw new RuntimeException(sprintf('Identity found in request does not implement %s', IdentityInterface::class));
        }

        if (isset($config['allAccessRoles'])) {
            if ($config['allAccessRoles'] === false) {
                $this->allAccessRoles = [];
            } elseif (is_array($config['allAccessRoles'])) {
                $this->allAccessRoles = $config['allAccessRoles'];
            } else {
                $this->allAccessRoles = [$config['allAccessRoles']];
            }
        }
    }
    
    /**
     * 
     * Parameters are only kept for backwards compatibility
     * 
     * @param array $requestAttributes
     * @param int $authLevel
     * @return NULL|string
     */
    public function navbarLinks(array $requestAttributes = [], int $authLevel = 0)
    {
        
        $navbarLinks = null;
        
        $currentKey = null;
        if (empty($this->params['prefix'])) {
            $this->params['prefix'] = false;
        }
        $currentUrlParams = [
            'prefix' => $this->params['prefix'],
            'controller' => $this->params['controller'],
            'action' => $this->params['action'],
            '_ext' => $this->params['_ext'],
            '_base' => false,
        ];
        foreach ($this->params['pass'] as $pass) {
            $currentUrlParams[] = $pass;
        }
        $currentUrl = $this->Url->build($currentUrlParams);
        
        foreach ($this->linkMap as $rawPattern => $key) {
            $pattern = $this->_patternize($rawPattern);
            if (preg_match($pattern, $currentUrl) === 1) {
                $currentKey = $key;
                break;
            }
        }
        
        foreach ($this->links as $key => $link) {
            
            $link += [
                'linkText' => Inflector::humanize(Inflector::underscore($key)),
                'authLevel' => 0,
                'prefix' => false,
                'controller' => 'Users',
                'action' => 'index',
                'roles' => $this->allAccessRoles,
                'params' => [],
                'children' => [],
                'link_id' => null,
                'item_id' => null,
            ];
            
            if ($authLevel < $link['authLevel']) { continue; }
            
            if (!empty($this->identity)) {
                if (!empty($link['roles'])) {
                    if (!$this->checkRole($link['roles'])) {
                        continue;
                    }
                }
            }
            
            $navbarLink = null;
            
            $active = ($key == $currentKey);
            $itemActiveCssClass = ($active) ? 'active' : null;
            $activeSrMarker = ($active) ? '<span class="sr-only">(' . __('current page') . ')</span>' : null;
            $itemID = (!empty($link['item_id'])) ? $link['item_id'] : "navbar-item-{$key}";
            
            $itemDropdownCssClass = (!empty($link['children'])) ? 'dropdown' : null;
            
            $parentUrlParams = [
                'prefix' => $link['prefix'],
                'controller' => $link['controller'],
                'action' => $link['action'],
            ];
            if (!empty($link['params'])) {
                foreach ($link['params'] as $param) {
                    $parentUrlParams[] = $param;
                }
            }
            $parentOptionsParams = [
                'class' => 'nav-link',
                'id' => (!empty($link['link_id'])) ? $link['link_id'] : "navbar-link-{$key}",
            ];
            
            if (!empty($link['children'])) {
                $parentOptionsParams = [
                    'class' => 'nav-link dropdown-toggle',
                    'id' => 'navbar-' . $key . '-dropdown-toggle',
                    'data-toggle' => 'dropdown',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false',
                ];
            }
            
            $parentLink = $this->Html->link(__($link['linkText']),$parentUrlParams,$parentOptionsParams);
            
            $navbarLink = <<<LI
<li class="nav-item $itemActiveCssClass $itemDropdownCssClass" id="$itemID">$parentLink $activeSrMarker
LI;
            if (!empty($link['children'])) {
                $navbarLink .= <<<DIV
<div class="dropdown-menu" aria-labelledby="navbar-$key-dropdown-toggle">
DIV;
                foreach ($link['children'] as $childKey => $childLink) {
                    
                    $childLink += [
                        'linkText' => Inflector::humanize(Inflector::underscore($childKey)),
                        'prefix' => false,
                        'controller' => 'Users',
                        'action' => 'index',
                        'params' => [],
                        'roles' => $this->allAccessRoles,
                    ];
                    if (!empty($this->identity)) {
                        if (!empty($childLink['roles'])) {
                            if (!$this->checkRole($childLink['roles'])) {
                                continue;
                            }
                        }
                    }
                    
                    $childUrlParams = [
                        'prefix' => $childLink['prefix'],
                        'controller' => $childLink['controller'],
                        'action' => $childLink['action'],
                    ];
                    if (!empty($childLink['params'])) {
                        foreach ($childLink['params'] as $param) {
                            $childUrlParams[] = $param;
                        }
                    }
                    $childOptionsParams = [
                        'class' => 'dropdown-item text-primary',
                    ];
                    $navbarLink .= $this->Html->link(__($childLink['linkText']),$childUrlParams,$childOptionsParams);
                }
                $navbarLink .= '</div>';
            }
            $navbarLink .= '</li>';
            $navbarLinks .= $navbarLink;
        }
        
        return $navbarLinks;
    }
    
    private function _patternize($rawPattern) : string
    {
        $pattern = $rawPattern;
        
        $pattern = str_replace('/', '\\/', $pattern);
        $pattern = "/^{$pattern}/";
        
        return $pattern;
    }

    private function checkRole($roles = '') : bool
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        if (!empty($this->allAccessRoles)) {
            $roles = array_merge($roles,$this->allAccessRoles);
        }
        $identityHasRole = false;
        foreach ($roles as $role) {
            $isRole = "is{$role}";
            if (
                (!empty($this->identity->$role))
                or
                (!empty($this->identity->$isRole))
            ){
                $identityHasRole = true;
                break;
            }
        }
        return $identityHasRole;
    }
    
}
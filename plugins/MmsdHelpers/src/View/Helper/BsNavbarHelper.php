<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Inflector;

class BsNavbarHelper extends Helper
{
    public $links = [];
    public $linkMap = [];
    public $helpers = ['Html','Url'];
    
    /**
     * 
     * {@inheritDoc}
     * @see \Cake\View\Helper::initialize()
     */
    public function initialize(array $config)
    {
        $this->links = $config['links'];
        $this->linkMap = $config['linkMap'];
    }
    
    /**
     * 
     * @param array $requestAttributes
     * @param int $authLevel
     * @return NULL|string
     */
    public function navbarLinks(array $requestAttributes, int $authLevel = 0)
    {
        $requestParams = $requestAttributes['params'];
        
        $navbarLinks = null;
        
        $currentKey = null;
        if (empty($requestParams['prefix'])) {
            $requestParams['prefix'] = false;
        }
        $currentUrlParams = [
            'prefix' => $requestParams['prefix'],
            'controller' => $requestParams['controller'],
            'action' => $requestParams['action'],
            '_base' => false,
        ];
        foreach ($requestParams['pass'] as $pass) {
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
                'params' => [],
                'children' => [],
            ];
            
            if ($authLevel < $link['authLevel']) { continue; }
            
            $navbarLink = null;
            
            $active = ($key == $currentKey);
            $itemActiveCssClass = ($active) ? 'active' : null;
            $activeSrMarker = ($active) ? '<span class="sr-only">(' . __('current page') . ')</span>' : null;
            
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
<li class="nav-item $itemActiveCssClass $itemDropdownCssClass">$parentLink $activeSrMarker
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
                    ];
                    
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
    
    private function _patternize($rawPattern) {
        $pattern = $rawPattern;
        
        $pattern = str_replace('/', '\\/', $pattern);
        $pattern = "/^{$pattern}/";
        
        return $pattern;
    }
    
}
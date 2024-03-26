<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Inflector;
use Authentication\IdentityInterface;
use RuntimeException;

class BsNavbarHelper extends Helper
{
    protected array $links = [];
    protected array $linkMap = [];
    protected array $params = [];
    protected IdentityInterface $identity;
    protected array $helpers = ['Html','Url'];
    private array $allAccessRoles = ['Administrator'];
    
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
            } else {
                $this->allAccessRoles = (is_array($config['allAccessRoles']))
                    ? $config['allAccessRoles']
                    : [$config['allAccessRoles']];
            }
        }
    }
    public function navbarLinks(): string
    {
        $navbarListItems = '';
        $currentKey = '';
        if (empty($this->params['prefix'])) {
            $this->params['prefix'] = false;
        }
        $currentUrl = $this->Url->build($this->createUrlArray($this->params));
        // Find current key from pattern matching current URL
        foreach ($this->linkMap as $rawPattern => $key) {
            $pattern = $this->patternize($rawPattern);
            if (preg_match($pattern, $currentUrl) === 1) {
                $currentKey = $key;
                break;
            }
        }
        foreach ($this->links as $key => $link) {
            $link = $this->initItemArray($link, $key);
            // Check role
            if (!$this->userCanAccessLink($link['roles'])) {
                continue;
            }
            $classes = [
                'li' => ['nav-item',],
                'a' => ['nav-link',],
            ];
            $attributes = [
                'li' => [
                    'id' => (!empty($link['item_id'])) ?  $link['item_id'] : "navbar-item-{$key}",
                ],
                'a' => [
                    'id' => (!empty($link['link_id'])) ? $link['link_id'] : "navbar-link-{$key}",
                ],
            ];
            $childrenUl = '';
            if ($key == $currentKey) {
                $classes['a'][] = 'active';
                $attributes['a']['aria-current'] = 'page';
            }
            if (!empty($link['children'])) {
                $classes['li'][] = 'dropdown';
                $attributes['li']['data-bs-theme'] = 'light';
                $classes['a'][] = 'dropdown-toggle';
                $attributes['a']['role'] = 'button';
                $attributes['a']['data-bs-toggle'] = 'dropdown';
                $attributes['a']['aria-expanded'] = 'false';
                $childrenUl = $this->childrenUl($link['children'], $key);
            }
            $attributes['li']['class'] = implode(' ',$classes['li']);
            $attributes['a']['class'] = implode(' ',$classes['a']);
            $liAttr = $this->keyedArrayToString($attributes['li']);
            $navbarListItem = "<li {$liAttr}>";
            $linkUrlArray = $this->createUrlArray($link);
            $navbarListItem .= $this->Html->link($link['linkText'], $linkUrlArray, $attributes['a']);
            $navbarListItem .= $childrenUl;
            $navbarListItem .= '</li>';
            $navbarListItems .= $navbarListItem;
        }
        
        return $navbarListItems;
    }
    public function checkRole($roles = []) : bool
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        if (!empty($this->allAccessRoles)) {
            $roles = array_merge($roles,$this->allAccessRoles);
        }
        foreach ($roles as $role) {
            $isRole = "is{$role}";
            if (
                (!empty($this->identity->$role))
                or
                (!empty($this->identity->$isRole))
            ){
                return true;
            }
        }
        return false;
    }
    private function initItemArray(array $item, string $key): array
    {
        $item += [
            'linkText' => Inflector::humanize(Inflector::underscore($key)),
            'prefix' => false,
            'controller' => 'Users',
            'action' => 'index',
            'params' => [],
            '_ext' => null,
            'roles' => [],
            'children' => [],
            'item_id' => null,
            'link_id' => null,
        ];
        return $item;
    }
    private function userCanAccessLink(array $roles): bool
    {
        if ((!empty($this->identity))
            and (!empty($roles))
        ) {
            return $this->checkRole($roles);
        }
        return true;
    }
    private function createUrlArray(array $item): array
    {
        $urlArray = [
            'prefix' => $item['prefix'],
            'controller' => $item['controller'],
            'action' => $item['action'],
            '_ext' => $item['_ext'],
            '_base' => false,
        ];
        if (!empty($item['params'])) {
            foreach ($item['params'] as $param) {
                $urlArray[] = $param;
            }
        }
        return $urlArray;
    }
    private function childrenUl(array $children, string $parentKey): string
    {
        $ul = '<ul class="dropdown-menu">';
        foreach ($children as $childKey => $child) {
            $child = $this->initItemArray($child, $childKey);
            if (!$this->userCanAccessLink($child['roles'])) {
                continue;
            }
            $urlArray = $this->createUrlArray($child);
            $item_id = (!empty($child['item_id'])) ? $child['item_id'] : "navbar-item-{$parentKey}-{$childKey}";
            $link_id = (!empty($child['link_id'])) ? $child['link_id'] : "navbar-link-{$parentKey}-{$childKey}";
            $ul .= "<li class='dropdown-item' id='{$item_id}'>";
            $ul .= $this->Html->link($child['linkText'],$urlArray,[
                'id' => $link_id,
            ]);
            $ul .= '</li>';
        }
        $ul .= '</ul>';
        return $ul;
    }
    private function keyedArrayToString(array $items): string
    {
        $callback = function(string $k, string $v): string { return "{$k}='{$v}'"; };
        return implode(' ', array_map($callback, array_keys($items), array_values($items)));
    }
    private function patternize($rawPattern) : string
    {
        $pattern = $rawPattern;
        $pattern = str_replace('/', '\\/', $pattern);
        return "/^{$pattern}/";
    }
}

<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;

class ClassByNumberHelper extends Helper
{
    private $rules = [];
    /**
     * 
     * {@inheritDoc}
     * @see \Cake\View\Helper::initialize()
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->rules = $config['rules'];
    }
    public function getRules(): array
    {
        return $this->rules;
    }
    public function getClass(string $number): ?string
    {
        foreach ($this->rules as $ruleNumber => $ruleClass) {
            if (preg_match('/^\d/',$ruleNumber)) {
                if ($number <= $ruleNumber) {
                    return $ruleClass;
                }
            }
        }
        if (!empty($this->rules['default'])) {
            return $this->rules['default'];
        }
        return null;
    }
    
}

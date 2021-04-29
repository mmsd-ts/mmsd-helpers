<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;

class BsFormHelper extends Helper
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \Cake\View\Helper::initialize()
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->setConfig([
            'rules' => $config['rules'],
        ]);
        
    }

    public function getClass(string $number): ?string
    {
        $rules = $this->getConfig('rules');
        foreach ($rules as $ruleNumber => $ruleClass) {
            if (preg_match('/^\d/',$ruleNumber)) {
                if ($number <= $ruleNumber) {
                    return $ruleClass;
                }
            }
        }
        if (!empty($rules['default'])) {
            return $rules['default'];
        }
        return null;
    }
}

<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;
use \Cake\ORM\Entity;
use Cake\Utility\Inflector;

class BsFormHelper extends Helper
{
    protected array $helpers = ['Form'];
    private $nonControlOptions = [
        'label',
        'labelClass',
        'help',
        'valid',
        'invalid',
        'helpText',
        'validMessage',
        'invalidMessage',
        'helpMessage',
        'validText',
        'invalidText',
        'optionsClass',
        'selectOptionString',
        'rowClass',
        'labelAppend',
        'labelAppendChar',
        'requiredChar',
        'requiredClass',
        'errorClass',
        'labelClassOverride',
    ];
    /**
     * See https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls:-the-autocomplete-attribute
     *
     * @var array
     */
    private $autocompleteMap = [
        'username' => 'username',
        'password' => 'current-password',
        'fullname' => 'name',
        'firstname' => 'given-name',
        'middlename' => 'additional-name',
        'lastname' => 'family-name',
        'suffix' => 'honorific-suffix',
        'alias' => 'nickname',
        'addresstext' => 'street-address',
        'fulladdress' => 'street-address',
        'line1' => 'address-line1',
        'line2' => 'address-line2',
        'city' => 'address-level2',
        'state' => 'address-level1',
        'zip' => 'postal-code',
        'birthdate' => 'bday',
        'gender' => 'sex',
        'homephone' => 'home tel',
        'workphone' => 'work tel',
        'cellphone' => 'mobile tel',
        'email' => 'email',
        'phone' => 'tel',
    ];
    
    /**
     *
     * {@inheritDoc}
     * @see \Cake\View\Helper::initialize()
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setConfig([
            'entity' => null,
            'errors' => false,
            'defaults' => [
                'labelClass' => null,
                'requiredChar' => null,
                'requiredClass' => null,
                'labelAppendChar' => null,
                // removed:
//                'errorClass' => 'text-danger',
//                'useBrowserAutocomplete' => true,
//                'inputLayout' => 'Default',
//                'checkIdHasHyphen' => false,
            ],
        ]);
        if (!empty($config['defaults'])) {
            $this->setDefaults($config['defaults']);
        }
    }
    
    /**
     *
     * @param array $newDefaults
     */
    public function setDefaults(array $newDefaults = []): void
    {
        $this->setConfig('defaults', array_merge($this->getConfig('defaults'), $newDefaults));
    }
    
    /**
     * This function is not used or necessary any longer but it's here so you don't get errors
     * @param \Cake\ORM\Entity $formEntity
     */
    public function setEntity(Entity $formEntity = null): ?Entity
    {
        $this->setConfig('entity', $formEntity);
        if (!empty($formEntity)) {
            if ($formEntity->getErrors()) {
                $this->setConfig('errors', $formEntity->getErrors());
            }
        }
        return $this->getConfig('entity');
    }
    
    public function control(string $name, array $options = []): ?string
    {
        $type = 'text';
        $inline = false;
        if (!empty($options['type'])) {
            $type = strtolower($options['type']);
            unset($options['type']);
            $inline_pos = stripos($type, '-inline');
            if ($inline_pos !== false) {
                $type = substr($type, 0, $inline_pos);
                // these types are always inline-ish
                if (!in_array($type, ['checkbox', 'radio', 'switch'])) {
                    $inline = true;
                }
            }
        }
        $options += [
            'id' => Inflector::dasherize($name),
            'label' => Inflector::humanize(Inflector::underscore($name)),
        ];
        $parts = [];
        $parts['control'] = $this->makeControl($type, $name, $options);
        if ($type !== 'radio') {
            $parts['label'] = $this->makeLabel($type, $options, $inline);
        }
        if ((!empty($options['help']))
            or (!empty($options['helpText']))
            or (!empty($options['helpMessage']))
        ) {
            $help = $options['help'] ?? $options['helpText'] ?? $options['helpMessage'];
            $parts['help'] = $this->makeExtraDiv('help', $help);
        }
        if ((!empty($options['invalid']))
            or (!empty($options['invalidText']))
            or (!empty($options['invalidMessage']))
        ) {
            $invalid = $options['invalid'] ?? $options['invalidText'] ?? $options['invalidMessage'];
            $parts['invalid'] = $this->makeExtraDiv('invalid', $invalid);
        }
        // take all the parts and return HTML/Form strings
        if ($type === 'checkbox') {
            return $this->checkboxDefault($parts);
        } elseif ($type === 'switch') {
            return $this->checkboxDefault($parts, true);
        } elseif ($type === 'radio') {
            return $this->radioDefault($parts);
        } elseif ($inline) {
            return $this->inputInline($parts);
        } else {
            return $this->inputDefault($parts);
        }
    }
    public function inputDefault(array $parts): string
    {
        $invalid = (!empty($parts['invalid'])) ? $parts['invalid'] : null;
        $help = (!empty($parts['help'])) ? $parts['help'] : null;
        return <<<"HTML"
        {$parts['label']}
        {$parts['control']}
        {$invalid}
        {$help}

HTML;
    
    }
    public function inputInline(array $parts): string
    {
        $invalid = (!empty($parts['invalid'])) ? $parts['invalid'] : null;
        $help = null;
        if (!empty($parts['help'])) {
            $help = <<<"HELP"
    <div class="col-auto">
        {$parts['help']}
    </div>

HELP;

        }
        return <<<"HTML"
<div class="row g-3 align-items-center">
    <div class="col-auto">
        {$parts['label']}
    </div>
    <div class="col-auto">
        {$parts['control']}
        {$invalid}
    </div>
    {$help}
</div>

HTML;
    
    }
    public function checkboxDefault(array $parts, bool $isSwitch = false): string
    {
        $invalid = (!empty($parts['invalid'])) ? $parts['invalid'] : null;
        $help = (!empty($parts['help'])) ? $parts['help'] : null;
        $divClass = 'form-check' . ($isSwitch ? ' form-switch' : '');
        return <<<"HTML"
<div class="{$divClass}">
    {$parts['control']}
    {$parts['label']}
    {$invalid}
    {$help}
</div>

HTML;
    
    }
    public function radioDefault(array $parts): string
    {
        // invalid doesn't work, it works on each radio option but that is dumb
        $help = (!empty($parts['help'])) ? $parts['help'] : null;
        return <<<"HTML"
{$parts['control']}
{$help}

HTML;
    
    }
    public function makeControl(string $type, string $name, array $options): string
    {
        $class = 'form-control';
        if ($type === 'select') {
            $class = 'form-select';
        } elseif (in_array($type, ['checkbox', 'radio', 'switch'])) {
            $class = 'form-check-input';
            if ($type === 'switch') {
                $options['role'] = 'switch';
            }
        }
        $controlClass = $this->getOptionValue($options, 'class');
        if (!empty($controlClass)) {
            $class .= ' ' . $controlClass;
        }
        $options['class'] = $class;
        // Clean options array:
        $cleanOptions = [];
        foreach ($options as $key => $value) {
            if (!in_array($key, $this->nonControlOptions)) {
                $cleanOptions[$key] = $value;
            }
        }
        // "Autocomplete" attribute
        if ((empty($cleanOptions['autocomplete']))
            and (!empty($this->autocompleteMap[strtolower($name)]))
        ) {
            $cleanOptions['autocomplete'] = $this->autocompleteMap[strtolower($name)];
        }
        if ($type === 'radio') {
            $radioLabelClass = 'form-check-label';
            $labelClass = $this->getOptionValue($options, 'labelClass');
            if (!empty($labelClass)) {
                $radioLabelClass .= ' ' . $labelClass;
            }
            if (!empty($options['required'])) {
                $requiredClass = $this->getOptionValue($options, 'requiredClass');
                if (!empty($requiredClass)) {
                    $radioLabelClass .= ' ' . $requiredClass;
                }
            }
            $cleanOptions['label'] = [
                'class' => $radioLabelClass,
            ];
        }
        if ($type === 'switch') {
            $type = 'checkbox';
        }
        if (in_array($type, ['radio', 'select'])) {
            if (empty($options['options'])) {
                $options['options'] = [
                    0 => 'No options found',
                ];
            }
            return $this->Form->$type($name, $options['options'], $cleanOptions);
        }
        return $this->Form->$type($name, $cleanOptions);
    }
    
    public function makeLabel(string $type, array $options, bool $inline): string
    {
        $label = $options['label'];
        $class = 'form-label';
        if ($inline) {
            $class = 'col-form-label';
        }
        if (in_array($type, ['checkbox', 'switch'])) {
            $class = 'form-check-label';
            // override labelAppendChar since label appears after form control
            $options['labelAppendChar'] = false;
        }
        if (!empty($options['required'])) {
            $requiredClass = $this->getOptionValue($options, 'requiredClass');
            if (!empty($requiredClass)) {
                $class .= ' ' . $requiredClass;
            }
            $requiredChar = $this->getOptionValue($options, 'requiredChar');
            if (!empty($requiredChar)) {
                $label .= $requiredChar;
            }
        }
        $labelClass = $this->getOptionValue($options, 'labelClass');
        if (!empty($labelClass)) {
            $class .= ' ' . $labelClass;
        }
        $labelAppendChar = $this->getOptionValue($options, 'labelAppendChar');
        if (!empty($labelAppendChar)) {
            $label .= $labelAppendChar;
        }
        return $this->Form->label($options['id'], $label, [
            'id' => "label-{$options['id']}",
            'class' => $class,
        ]);
    }
    public function makeExtraDiv(string $type, string|array $text): string
    {
        $info = (is_array($text)) ? $text : ['contents' => $text];
        $class = 'form-text';
        if ($type === 'invalid') {
            $class = 'invalid-feedback';
        }
        if (!empty($info['class'])) {
            $class .= ' ' . $info['class'];
        }
        return <<<"HTML"
<div class="{$class}">{$info['contents']}</div>

HTML;
    
    }
    public function getOptionValue(array $options, string $key): string
    {
        if (isset($options[$key])
            and ($options[$key] === false)
        ) {
            return '';
        }
        if (!empty($options[$key])) {
            return $options[$key];
        }
        if (!empty($this->getConfig("defaults.{$key}"))) {
            return $this->getConfig("defaults.{$key}");
        }
        return '';
    }
}

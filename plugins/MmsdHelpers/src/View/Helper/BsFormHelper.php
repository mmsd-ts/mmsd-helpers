<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\Utility\Inflector;

class BsFormHelper extends Helper
{
    
    use IdGeneratorTrait;
    
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
    ];
    
    /**
     * 
     * {@inheritDoc}
     * @see \Cake\View\Helper::initialize()
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        
        $this->setConfig([
            'entity' => null,
            'errors' => false,
            'defaults' => [
                'requiredChar' => null,
                'requiredClass' => null,
                'labelAppendChar' => null,
                'errorClass' => 'text-danger',
                'useBrowswerAutocomplete' => true,
                'inputLayout' => 'Default',
            ],
            // Legacy only kept for backwards compatibility so Bill doesn't freak out
            'required_star' => false,
            'required_class' => null,
            'autocomplete' => true,
            'error_class' => 'text-danger',
            'label_append' => false,
            'label_append_char' => ':',
        ]);
        
    }
    
    /**
     * 
     * @param \Cake\ORM\Entity $formEntity
     */
    public function setEntity(\Cake\ORM\Entity $formEntity = null)
    {
        $this->setConfig('entity',$formEntity);
        if (!empty($formEntity)) {
            if ($formEntity->getErrors()) {
                $this->setConfig('errors',$formEntity->getErrors());
            }
        }
        return $this->getConfig('entity');
    }
    
    /**
     *
     * @param array $newDefaults
     */
    public function setDefaults(array $newDefaults = [])
    {
        $setDefaults = $this->getConfig('defaults');
        return $this->setConfig('defaults', array_merge($setDefaults, $newDefaults));
    }
    
    /**
     * 
     * @param string $name
     * @param array $options
     * @param array $config
     * @return string
     */
    public function input(string $name, array $options = [], array $config = [])
    {
        $options = $this->initializeOptions($name,$options);
        $config += [
            'layout' => $this->getConfig('defaults.inputLayout'),
            'labelColumnWidth' => '6',
            'widgetColumnWidth' => '6',
            'outerDivClass' => null,
        ];
        $config['layout'] = ucfirst($config['layout']);
        $formatter = "input{$config['layout']}";
        
        $options['class'] = $this->addToClass($options['class'],'form-control');
        
        $config['outerDivClass'] = $this->addToClass($config['outerDivClass'],'form-group');
        
        if (in_array($name, ['password','passwd','pwd',])) {
            $options['type'] = 'password';
        }
        
        $options = $this->convertOptionAliases($options);
                                                                      // bc for Bill
        if (($this->getConfig('defaults.useBrowswerAutocomplete')) or ($this->getConfig('autocomplete'))) { 
            if (!empty($this->autocompleteMap[strtolower($name)])) {
                $options['autocomplete'] = $this->autocompleteMap[strtolower($name)];
            }
        }
        
        $widgetInfo = $this->processOptions($name, $options);
        
        $widgetInfo['validityText'] = $this->validityText($widgetInfo);
        $widgetInfo['afterWidgetText'] = $this->afterWidgetText($widgetInfo);
        
        return $this->$formatter($widgetInfo, $config);
    }
    
    /**
     * 
     * @param array $widgetInfo
     * @param array $config
     * @return string
     */
    public function inputDefault(array $widgetInfo = null, array $config = null)
    {
        $returnHtml = '';
        $returnHtml .= <<<"HTML"
<div class="{$config['outerDivClass']}">
\t<label class="{$widgetInfo['labelClass']}" for="{$widgetInfo['id']}">{$widgetInfo['label']}</label>

HTML;
        if ($widgetInfo['type'] == 'select') {
            $returnHtml .= <<<"HTML"
\t<select name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" class="{$widgetInfo['class']}"{$widgetInfo['otherAttrs']}>
\t{$widgetInfo['selectOptionString']}
\t</select>

HTML;
        } elseif ($widgetInfo['type'] == 'textarea') {
            $returnHtml .= <<<"HTML"
\t<textarea name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" class="{$widgetInfo['class']}"{$widgetInfo['otherAttrs']}>{$widgetInfo['defaultValue']}</textarea>

HTML;
        } else {
            $returnHtml .= <<<"HTML"
\t<input type="{$widgetInfo['type']}" name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" value="{$widgetInfo['defaultValue']}" class="{$widgetInfo['class']}"{$widgetInfo['otherAttrs']}>

HTML;
        }
        
        $returnHtml .= <<<"HTML"
\t{$widgetInfo['validityText']}
\t{$widgetInfo['afterWidgetText']}
</div>

HTML;
        return $returnHtml;
    }
    
    /**
     * 
     * @param array $widgetInfo
     * @param array $config
     * @return string
     */
    public function inputFlat(array $widgetInfo = null, array $config = null)
    {
        $widgetInfo['labelClass'] = $this->addToClass($widgetInfo['labelClass'],'col-form-label');
        $widgetInfo['labelClass'] = $this->addToClass($widgetInfo['labelClass'],"col-md-{$config['labelColumnWidth']}");
        $config['outerDivClass'] = $this->addToClass($config['outerDivClass'],'row');
        
        $returnHtml = '';
        $returnHtml .= <<<"HTML"
<div class="{$config['outerDivClass']}">
\t<label class="{$widgetInfo['labelClass']}" for="{$widgetInfo['id']}">{$widgetInfo['label']}</label>
\t<div class="col-md-{$config['widgetColumnWidth']}">

HTML;
        if ($widgetInfo['type'] == 'select') {
            $returnHtml .= <<<"HTML"
\t<select name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" class="{$widgetInfo['class']}"{$widgetInfo['otherAttrs']}>
\t{$widgetInfo['selectOptionString']}
\t</select>

HTML;
        } elseif ($widgetInfo['type'] == 'textarea') {
            $returnHtml .= <<<"HTML"
\t<textarea name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" class="{$widgetInfo['class']}"{$widgetInfo['otherAttrs']}>{$widgetInfo['defaultValue']}</textarea>

HTML;
        } else {
            $returnHtml .= <<<"HTML"
\t<input type="{$widgetInfo['type']}" name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" value="{$widgetInfo['defaultValue']}" class="{$widgetInfo['class']}"{$widgetInfo['otherAttrs']}>

HTML;
        }
        
        $returnHtml .= <<<"HTML"
\t{$widgetInfo['validityText']}
\t{$widgetInfo['afterWidgetText']}
\t</div>
</div>

HTML;
        return $returnHtml;
    }
    
    /**
     * 
     * @param string $name
     * @param array $options
     * @param array $config
     * @return string
     */
    public function check(string $name, array $options = [], array $config = [])
    {
        // override default options if not set in ctp
        $options += [
            'type' => 'checkbox',
            'empty' => true,
        ];
        $options = $this->initializeOptions($name,$options);
        
        $config += [
            'formCheckClass' => null,
            'labelFirst' => false,
            'flat' => false,
            'layout' => null,
        ];
        if ($config['layout'] == 'Flat') {
            $config['flat'] = true;
        }
        
        $options['class'] = $this->addToClass($options['class'],'form-check-input');
        $options['labelClass'] = $this->addToClass($options['labelClass'],'form-check-label');
        
        $options = $this->convertOptionAliases($options);
        
        $returnHtml = '';
        $widgetInfo = $this->processOptions($name, $options);
        $widgetIndex = 0;
        $outputCount = count($widgetInfo['options']);
        if ($widgetInfo['prepend']['contents']) { ++$outputCount; }
        if ($widgetInfo['append']['contents']) { ++$outputCount; }
        $columnsPerOption = floor(12 / $outputCount);
        
        $config['formCheckClass'] = $this->addToClass($config['formCheckClass'],'form-check');
        
        if ($config['flat']) {
            $returnHtml .= "<div class=\"container\"><div class=\"row\">\n";
        }
        
        if ($widgetInfo['prepend']['contents']) {
            if ($config['flat']) {
                $returnHtml .= "<div class=\"col-{$columnsPerOption}\">\n";
            } else {
                $returnHtml .= "<div>\n";
            }
            $returnHtml .= <<<"PREPEND"
    <span class="{$widgetInfo['prepend']['class']}">{$widgetInfo['prepend']['contents']}</span>
</div>

PREPEND;
        }
        
        foreach ($widgetInfo['options'] as $key => $value) {
            $thisId = $this->makeId($name, $key);
            
            $checked = ($this->valueIsSelected($widgetInfo['defaultValue'],$key)) ? ' checked' : null;
            
            if ($config['flat']) {
                $returnHtml .= "<div class=\"col-{$columnsPerOption} {$config['formCheckClass']}\">\n";
            } else {
                $returnHtml .= "<div class=\"{$config['formCheckClass']}\">\n";
            }
            
            if (($widgetInfo['empty']) and ($widgetIndex == 0)) {
                $emptyValue = ($widgetInfo['type'] == 'checkbox') ? '0' : '';
                $emptyId = $this->makeId("_{$widgetInfo['name']}");
                $returnHtml .= <<<"HTML"
\t<input type="hidden" name="{$widgetInfo['name']}" id="{$emptyId}" value="{$emptyValue}">

HTML;
            }
            
            $labelHtml = <<<"HTML"
\t<label for="{$thisId}" class="{$widgetInfo['labelClass']}">{$value}</label>

HTML;
            if ($config['labelFirst']) {
                $returnHtml .= $labelHtml;
            }
            
            $returnHtml .= <<<"HTML"
\t<input type="{$widgetInfo['type']}" name="{$widgetInfo['name']}" id="{$thisId}" value="{$key}" class="{$widgetInfo['class']}"{$widgetInfo['otherAttrs']}{$checked}>

HTML;
            
            if (!$config['labelFirst']) {
                $returnHtml .= $labelHtml;
            }
            
            if ($widgetIndex + 1 == count($widgetInfo['options'])) {
                $returnHtml .= $this->validityText($widgetInfo);
                $returnHtml .= $this->afterWidgetText($widgetInfo);
            }
            
            $returnHtml .= "</div>\n";
            ++$widgetIndex;
        }
        
        if ($widgetInfo['append']['contents']) {
            if ($config['flat']) {
                $returnHtml .= "<div class=\"col-{$columnsPerOption}\">\n";
            } else {
                $returnHtml .= "<div>\n";
            }
            $returnHtml .= <<<"APPEND"
    <span class="{$widgetInfo['append']['class']}">{$widgetInfo['append']['contents']}</span>
</div>

APPEND;
        }
        
        if ($config['flat']) {
            $returnHtml .= "</div></div>\n";
        }
        
        return $returnHtml;
        
    }
    
    /**
     * 
     * @param array $options
     * @return array[]
     */
    private function convertOptionAliases(array $options)
    {
        $convertedOptions = [];
        $optionAliases = [
            'validText' => 'validMessage',
            'invalidText' => 'invalidMessage',
            'helpMessage' => 'helpText',
        ];
        foreach ($options as $key => $value) {
            if (!empty($optionAliases[$key])) {
                $convertedOptions[$optionAliases[$key]] = $value;
            } else {
                $convertedOptions[$key] = $value;
            }
        }
        return $convertedOptions;
    }
    
    /**
     * 
     * @param string $name
     * @param array $options
     * @return array
     */
    private function processOptions(string $name, array $options = [])
    {
        // Named options do not pass through
        $namedOptions = ['type','id','value','class','label','labelClass',
                         'helpText','validMessage','invalidMessage',
                         'options','optionsClass','selectOptionString','empty',
                         'rowClass','prepend','append','labelAppend','labelAppendChar',
                         'requiredChar','requiredClass',
                         'errorClass',
        ];
        
        // bc for Bill
        if (!empty($this->getConfig('label_append'))) {
            if ($options['labelAppend'] !== false) {
                $appendCharacter = '';
                if ($options['labelAppend'] === true) {
                    $appendCharacter = $this->getConfig('label_append_char');
                } elseif (!empty($options['labelAppend'])) {
                    $appendCharacter = $options['labelAppend'];
                } elseif ($this->getConfig('label_append')) {
                    $appendCharacter = $this->getConfig('label_append_char');
                }
                if (strlen($appendCharacter)) {
                    $options['label'] .= $appendCharacter;
                }
            }
        }
        if ($options['labelAppendChar'] !== false) {
            $options['label'] .= $options['labelAppendChar'] ?? $this->getConfig('defaults.labelAppendChar');
        }
        
        if (!empty($options['required'])) {
            if ($options['requiredChar'] !== false) {
                // bc for Bill
                if ($this->getConfig('required_star')) {
                    $options['label'] .= '*';
                } else {
                    $options['label'] .= $options['requiredChar'] ?? $this->getConfig('defaults.requiredChar');
                }
            }
            if ($options['requiredClass'] !== false) {
                // bc for Bill
                if (!empty($this->getConfig('required_class'))) {
                    $options['labelClass'] = $this->addToClass($options['labelClass'], $this->getConfig('required_class'));
                } else {
                    $requiredClass = $options['requiredClass'] ?? $this->getConfig('defaults.requiredClass');
                    if (!empty($requiredClass)) {
                        $options['labelClass'] = $this->addToClass($options['labelClass'], $requiredClass);
                    }
                }
            }
        }
        
        $widgetInfo = $options;
        $widgetInfo['name'] = $this->processName($name);
        
        $widgetInfo['defaultValue'] = null;
        $thisEntity = $this->getConfig('entity');
        $useEntityValue = true;
        $alwaysUseEntityValueTypes = ['check','radio','select'];
        if ((!empty($thisEntity)) and (isset($thisEntity->$name))) {
            if ((!in_array($options['type'],$alwaysUseEntityValueTypes)) and (empty($thisEntity->$name))) {
                $useEntityValue = false;
            }
        } else {
            $useEntityValue = false;
        }
        if ($useEntityValue) {
            $widgetInfo['defaultValue'] = $this->processValue($thisEntity->$name, $options['type']);
        } else {
            $widgetInfo['defaultValue'] = $this->processValue($widgetInfo['value'], $options['type']);
        }
        
        if (!empty($this->getConfig('errors')[$name])) {
            $errorMessages = [];
            foreach ($this->getConfig('errors')[$name] as $rule => $message) {
                $errorMessages[] = $message;
            }
            $widgetInfo['errors'] = implode('<br>', $errorMessages);
        }
        
        $otherAttrs = [];
        foreach ($options as $key => $value) {
            if (!in_array($key, $namedOptions)) {
                $otherAttrs[$key] = $value;
            }
        }
        $widgetInfo['otherAttrs'] = $this->makeAttrString($otherAttrs);
        
        if (!empty($options['helpText']['contents'])) {
            $helpTextId = "{$options['id']}-helptext";
            $widgetInfo['otherAttrs'] .= " aria-describedby=\"{$helpTextId}\"";
            $widgetInfo['helpText']['id'] = "id=\"{$helpTextId}\"";
        }
        
        $widgetInfo['validMessage']['class'] = $this->addToClass($options['validMessage']['class'],'valid-feedback');
        $widgetInfo['invalidMessage']['class'] = $this->addToClass($options['invalidMessage']['class'],'invalid-feedback');
        
        if (!empty($widgetInfo['options'])) {
            if ($widgetInfo['type'] == 'select') {
                $selectOptions = [];
                if ($widgetInfo['empty']) {
                    $emptyLabel = (is_string($widgetInfo['empty'])) ? $widgetInfo['empty'] : null;
                    $selectOptions[] = "<option value=\"\" class=\"{$widgetInfo['optionsClass']}\">{$emptyLabel}</option>";
                }
                foreach ($widgetInfo['options'] as $key => $value) {
                    $selected = ($this->valueIsSelected($widgetInfo['defaultValue'],$key)) ? ' selected' : null;
                    if (is_array($value)) {
                        $optionAttributes = [];
                        foreach ($value as $attr => $setting) {
                            if ($attr == 'value') {
                                continue;
                            }
                            if ($setting === true) {
                                $optionAttributes[] = "{$attr}=\"{$attr}\"";
                            } elseif ($setting !== false) {
                                $optionAttributes[] = "{$attr}=\"{$setting}\"";
                            }
                        }
                        $optionAttributesString = implode(' ', $optionAttributes);
                        $selectOptions[] = "<option value=\"{$key}\" class=\"{$widgetInfo['optionsClass']}\" {$selected} {$optionAttributesString}>{$value['value']}</option>";
                    } else {
                        $selectOptions[] = "<option value=\"{$key}\" class=\"{$widgetInfo['optionsClass']}\" {$selected}>{$value}</option>";
                    }
                }
                $widgetInfo['selectOptionString'] = implode('', $selectOptions);
            }
        }
        
        return $widgetInfo;
    }
    
    /**
     * 
     * @param string $name
     * @param array $options
     * @return array
     */
    private function initializeOptions(string $name, array $options)
    {
        $options += [
            'type' => 'text',
            'id' => $this->makeId($name),
            'value' => null,
            'class' => null,
            'label' => Inflector::humanize(Inflector::underscore($name)),
            'labelClass' => null,
            'helpText' => [],
            'validMessage' => [],
            'invalidMessage' => [],
            'options' => [],
            'optionsClass' => null,
            'selectOptionString' => null,
            'empty' => false,
            'rowClass' => null,
            'prepend' => [],
            'append' => [],
            'labelAppend' => null,
            'labelAppendChar' => null,
            'requiredChar' => null,
            'requiredClass' => null,
            'errorClass' => null,
        ];
        $options['helpText'] += [
            'contents' => null,
            'class' => 'form-text',
            'element' => 'small',
        ];
        $options['validMessage'] += [
            'contents' => null,
            'class' => null,
        ];
        $options['invalidMessage'] += [
            'contents' => null,
            'class' => null,
        ];
        $options['prepend'] += [
            'contents' => null,
            'class' => null,
        ];
        $options['append'] += [
            'contents' => null,
            'class' => null,
        ];
        return $options;
    }
    
    /**
     * 
     * @param string $name
     * @param string $value
     * @return string
     */
    private function makeId(string $name, string $value = null)
    {
        return $this->_domId("{$name}{$value}");
    }
    
    /**
     * 
     * @param string $name
     * @return string|mixed
     */
    private function processName(string $name)
    {
        $processedName = '';
        $nameArray = explode('.', $name);
        $processedName = array_shift($nameArray);
        if (count($nameArray)) {
            $processedName .= '[' . implode('][', $nameArray) . ']';
        }
        return $processedName;
    }
    
    /**
     * 
     * @param string $originalClassString
     * @param string $targetClass
     * @return string
     */
    private function addToClass(string $originalClassString = null, string $targetClass)
    {
        return $this->editClassString($originalClassString, $targetClass, 'add');
    }
    
    /**
     * 
     * @param string $originalClassString
     * @param string $targetClass
     * @return string
     */
    private function removeFromClass(string $originalClassString = null, string $targetClass)
    {
        return $this->editClassString($originalClassString, $targetClass, 'delete');
    }
    
    /**
     * 
     * @param string $originalClassString
     * @param string $searchClass
     * @param string $replaceClass
     * @return string
     */
    private function replaceClass(string $originalClassString = null, string $searchClass, string $replaceClass)
    {
        $classString = $this->editClassString($originalClassString, $searchClass, 'delete');
        if ($classString != $originalClassString) {
            return $this->editClassString($classString, $replaceClass, 'add');
        } else {
            return $originalClassString;
        }
    }
    
    /**
     * 
     * @param string $originalClassString
     * @param string $targetClass
     * @param string $action
     * @return string
     */
    private function editClassString(string $originalClassString = null, string $targetClass, string $action = 'add')
    {
        $newClassString = '';
        $classArray = (!empty($originalClassString)) ? explode(' ',$originalClassString) : [];
        if ($action == 'add') {
            $classArray[] = $targetClass;
        }
        if ($action == 'delete') {
            for ($i = 0; $i < count($classArray); ++$i) {
                if ($classArray[$i] == $targetClass) {
                    unset($classArray[$i]);
                }
            }
        }
        $newClassString = implode(' ', $classArray);
        return $newClassString;
    }
    
    /**
     * 
     * @param array $attrs
     * @return string
     */
    private function makeAttrString(array $attrs)
    {
        $stringArray = [];
        foreach ($attrs as $key => $value) {
            if ($value === false) {
                continue;
            } elseif ($value === true) {
                $stringArray[] = $key;
            } else {
                if (!is_array($value)) {
                    $stringArray[] = "{$key}=\"{$value}\"";
                } else {
                    $stringArray[] = "{$key}=\"Handling of array values is currently undefined\"";
                }
            }
        }
        return (!empty($stringArray)) ? ' ' . implode(' ', $stringArray) : '';
    }
    
    /**
     * 
     * @param string $valueStored
     * @param string $valueChoice
     * @return boolean
     */
    private function valueIsSelected(string $valueStored = null, string $valueChoice = null) {
        return ((string)$valueStored === ((string)$valueChoice));
    }
    
    /**
     * 
     * @param mixed $value
     * @param string $type
     * @return string
     */
    private function processValue($value,string $type = null) {
        if ($value === true) {
            return '1';
        } elseif ($value === false) {
            return '0';
        } elseif ($type == 'date') {
            return $this->formatDate($value);
        } elseif ($type == 'time') {
            return $this->formatTime($value);
        } else {
            return $value;
        }
    }
    
    /**
     * 
     * @param mixed $value
     * @return string
     */
    private function formatDate($value) {
        if (
            ($value instanceof \DateTime)
            or
            ($value instanceof \Cake\I18n\FrozenDate)
            or
            ($value instanceof \Cake\I18n\FrozenTime)
            or
            ($value instanceof \Cake\I18n\Time)
        ){
            return $value->format('Y-m-d');
        } else {
            return $value;
        }
    }
    
    /**
     * 
     * @param mixed $value
     * @return string
     */
    private function formatTime($value) {
        if (
            ($value instanceof \DateTime)
            or
            ($value instanceof \Cake\I18n\FrozenDate)
            or
            ($value instanceof \Cake\I18n\FrozenTime)
            or
            ($value instanceof \Cake\I18n\Time)
        ){
            return $value->format('H:i');
        } else {
            return $value;
        }
    }
    
    /**
     * 
     * @param array $widgetInfo
     * @return string
     */
    private function validityText(array $widgetInfo)
    {
        $returnHtml = '';
        
        // valid message
        if (!empty($widgetInfo['validMessage']['contents'])) {
            $returnHtml .= <<<"HTML"
\t<div class="{$widgetInfo['validMessage']['class']}">
\t\t{$widgetInfo['validMessage']['contents']}
\t</div>

HTML;
        }
        // invalid message
        if (!empty($widgetInfo['invalidMessage']['contents'])) {
            $returnHtml .= <<<"HTML"
\t<div class="{$widgetInfo['invalidMessage']['class']}">
\t\t{$widgetInfo['invalidMessage']['contents']}
\t</div>

HTML;
        }
        
        return $returnHtml;
    }
    
    /**
     * 
     * @param array $widgetInfo
     * @return string
     */
    private function afterWidgetText(array $widgetInfo)
    {
        $returnHtml = '';
        
        // helptext
        if (!empty($widgetInfo['helpText']['contents'])) {
            $returnHtml .= <<<"HTML"
\t<{$widgetInfo['helpText']['element']} {$widgetInfo['helpText']['id']} class="{$widgetInfo['helpText']['class']}">
\t\t{$widgetInfo['helpText']['contents']}
\t</{$widgetInfo['helpText']['element']}>

HTML;
        }
        
        // errors
        if (!empty($widgetInfo['errors'])) {
            $errorClass = null;
            if ($widgetInfo['errorClass'] !== false) {
                                                           // bc for Bill
                $errorClass = $widgetInfo['errorClass'] ?? $this->getConfig('error_class') ?? $this->getConfig('defaults.errorClass');
            }
            $returnHtml .= <<<"HTML"
\t<div class="{$errorClass}">
\t\t{$widgetInfo['errors']}
\t</div>

HTML;
        }
        
        return $returnHtml;
    }
    
}

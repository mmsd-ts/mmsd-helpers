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
            'required_star' => false,
            'required_class' => null,
            'autocomplete' => true,
            'errors' => false,
            'error_class' => 'text-danger',
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
     * @param string $name
     * @param array $options
     * @param array $config
     * @return string
     */
    public function input(string $name, array $options = [], array $config = [])
    {
        $config += [
            'layout' => 'FormGroup',
            'formControlClass' => 'form-control',
            'formGroupClass' => null,
        ];
        $formatter = "input{$config['layout']}";
        
        $options['class'] = (!empty($options['class'])) ? "{$config['formControlClass']} {$options['class']}" : $config['formControlClass'];
        
        if ($config['layout'] == 'FormGroupHorizontal') {
            if (!empty($options['labelClass'])) {
                $options['labelClass'] .= ' col-form-label';
            } else {
                $options['labelClass'] = 'col-1 col-form-label';
            }
            if (empty($options['rowClass'])) {
                $options['rowClass'] = 'col-5';
            }
        }
        
        if (in_array($name, ['password','passwd',])) {
            $options['type'] = 'password';
        }
        
        $options = $this->_convertOptionAliases($options);
        
        if (($this->getConfig('autocomplete')) and (!empty($this->autocompleteMap[strtolower($name)]))) {
            $options['autocomplete'] = $this->autocompleteMap[strtolower($name)];
        }
        
        $widgetInfo = $this->_processOptions($name, $options);
        
        return $this->$formatter($widgetInfo, $config);
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
        $options += [
            'type' => 'checkbox',
            'empty' => true,
        ];
        $config += [
            'formCheckClass' => null,
            'labelFirst' => false,
            'flat' => false,
        ];
        $options['class'] = (!empty($options['class'])) ? "form-check-input {$options['class']}" : 'form-check-input';
        $options['labelClass'] = (!empty($options['labelClass'])) ? "form-check-label {$options['labelClass']}" : 'form-check-label';
        
        $options = $this->_convertOptionAliases($options);
        
        $returnHtml = '';
        $widgetInfo = $this->_processOptions($name, $options);
        $widgetIndex = 0;
        $outputCount = count($widgetInfo['options']);
        if ($widgetInfo['prepend']['contents']) { ++$outputCount; }
        if ($widgetInfo['append']['contents']) { ++$outputCount; }
        $columnsPerOption = floor(12 / $outputCount);
        
        $formCheckClass = trim("form-check {$config['formCheckClass']}");
        
        if ($config['flat']) {
            $returnHtml .= "<div class=\"container\"><div class=\"row\">\n";
        }
        
        if ($widgetInfo['prepend']['contents']) {
            $prependClass = $this->_makeClassString($widgetInfo['prepend']['class']);
            if ($config['flat']) {
                $returnHtml .= "<div class=\"col-{$columnsPerOption}\">\n";
            } else {
                $returnHtml .= "<div>\n";
            }
            $returnHtml .= <<<"PREPEND"
    <span{$prependClass}>{$widgetInfo['prepend']['contents']}</span>
</div>

PREPEND;
        }
        
        foreach ($widgetInfo['options'] as $key => $value) {
            $thisId = $this->_makeId($name, $key);
            
            $checked = ($this->_valueIsSelected($widgetInfo['defaultValue'],$key)) ? ' checked' : null;
            
            if ($config['flat']) {
                $returnHtml .= "<div class=\"col-{$columnsPerOption} {$formCheckClass}\">\n";
            } else {
                $returnHtml .= "<div class=\"{$formCheckClass}\">\n";
            }
            
            if (($widgetInfo['empty']) and ($widgetIndex == 0)) {
                $emptyValue = ($widgetInfo['type'] == 'checkbox') ? '0' : '';
                $emptyId = $this->_makeId("_{$widgetInfo['name']}");
                $returnHtml .= <<<"HTML"
\t<input type="hidden" name="{$widgetInfo['name']}" id="{$emptyId}" value="{$emptyValue}">

HTML;
            }
            
            $labelHtml = <<<"HTML"
\t<label for="{$thisId}"{$widgetInfo['labelClass']}>{$value}</label>

HTML;
            if ($config['labelFirst']) {
                $returnHtml .= $labelHtml;
            }
            
            $returnHtml .= <<<"HTML"
\t<input type="{$widgetInfo['type']}" name="{$widgetInfo['name']}" id="{$thisId}" value="{$key}"{$widgetInfo['widgetClass']}{$widgetInfo['otherAttrs']}{$checked}>

HTML;
            
            if (!$config['labelFirst']) {
                $returnHtml .= $labelHtml;
            }
            
            if ($widgetIndex + 1 == count($widgetInfo['options'])) {
                $returnHtml .= $this->_validityText($widgetInfo);
                $returnHtml .= $this->_afterWidgetText($widgetInfo);
            }
            
            $returnHtml .= "</div>\n";
            ++$widgetIndex;
        }
        
        if ($widgetInfo['append']['contents']) {
            $appendClass = $this->_makeClassString($widgetInfo['append']['class']);
            if ($config['flat']) {
                $returnHtml .= "<div class=\"col-{$columnsPerOption}\">\n";
            } else {
                $returnHtml .= "<div>\n";
            }
            $returnHtml .= <<<"APPEND"
    <span{$appendClass}>{$widgetInfo['append']['contents']}</span>
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
     * @param array $widgetInfo
     * @param array $config
     * @return NULL|string
     */
    public function inputFormGroup(array $widgetInfo = null, array $config = null)
    {
        if (empty($widgetInfo)) { return null; }
        
        $validityText = $this->_validityText($widgetInfo);
        
        $formGroupClass = trim('form-group ' . $config['formGroupClass']);
        
        $returnHtml = "<div class=\"{$formGroupClass}\">\n";
        // label
        $returnHtml .= <<<"HTML"
\t<label for="{$widgetInfo['id']}"{$widgetInfo['labelClass']}>{$widgetInfo['label']}</label>

HTML;
        // control
        if ($widgetInfo['type'] == 'select') {
            $returnHtml .= <<<"HTML"
\t<select name="{$widgetInfo['name']}" id="{$widgetInfo['id']}"{$widgetInfo['widgetClass']}{$widgetInfo['otherAttrs']}>
\t{$widgetInfo['selectOptionString']}
\t</select>
\t{$validityText}

HTML;
        } elseif ($widgetInfo['type'] == 'textarea') {
            $returnHtml .= <<<"HTML"
\t<textarea name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" {$widgetInfo['widgetClass']}{$widgetInfo['otherAttrs']}>{$widgetInfo['defaultValue']}</textarea>
\t{$validityText}

HTML;
        } else {
            
            if ((!empty($widgetInfo['prepend']['contents'])) or (!empty($widgetInfo['append']['contents']))) {
                $returnHtml .= <<<"HTML"
\t<div class="input-group">

HTML;
            }
            
            if (!empty($widgetInfo['prepend']['contents'])) {
                $prependClass = $this->_makeClassString($widgetInfo['prepend']['class']);
                $returnHtml .= <<<"HTML"
\t\t<div class="input-group-prepend">
\t\t\t<div class="input-group-text">
\t\t\t\t<span{$prependClass}>{$widgetInfo['prepend']['contents']}</span>
\t\t\t</div>
\t\t</div>

HTML;
            }
            
            $returnHtml .= <<<"HTML"
\t<input type="{$widgetInfo['type']}" name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" value="{$widgetInfo['defaultValue']}"{$widgetInfo['widgetClass']}{$widgetInfo['otherAttrs']}>

HTML;
            
            if (!empty($widgetInfo['append']['contents'])) {
                $appendClass = $this->_makeClassString($widgetInfo['append']['class']);
                $returnHtml .= <<<"HTML"
\t\t<div class="input-group-append">
\t\t\t<div class="input-group-text">
\t\t\t\t<span{$appendClass}>{$widgetInfo['append']['contents']}</span>
\t\t\t</div>
\t\t</div>

HTML;
            }
                
            $returnHtml .= $validityText;
            
            if ((!empty($widgetInfo['prepend']['contents'])) or (!empty($widgetInfo['append']['contents']))) {
                $returnHtml .= <<<"HTML"
\t</div>

HTML;
            }
            
        }
        
        $returnHtml .= $this->_afterWidgetText($widgetInfo);
        
        $returnHtml .= "</div>\n";
        return $returnHtml;
    }
    
    /**
     * 
     * @param array $widgetInfo
     * @param array $config
     * @return NULL|string
     */
    public function inputFormGroupHorizontal(array $widgetInfo = null, array $config = null)
    {
        if (empty($widgetInfo)) { return null; }
        
        $validityText = $this->_validityText($widgetInfo);
        
        
//         if (!empty($widgetInfo['labelClass'])) {
//             $widgetInfo['labelClass'] = preg_replace('/"$/', ' col-form-label"', $widgetInfo['labelClass']);
//         } else {
//             $widgetInfo['labelClass'] = $this->_makeClassString('col-1 col-form-label');
//         }
//         if (empty($widgetInfo['rowClass'])) {
//             $widgetInfo['rowClass'] = $this->_makeClassString('col-5');
//         }
        
        $formGroupClass = trim('form-group ' . $config['formGroupClass']);
        
        $returnHtml = "<div class=\"{$formGroupClass}\">\n";
        // label
        $returnHtml .= <<<"HTML"
\t<label for="{$widgetInfo['id']}"{$widgetInfo['labelClass']}>{$widgetInfo['label']}</label>
\t<div{$widgetInfo['rowClass']}>

HTML;
        // control
        if ($widgetInfo['type'] == 'select') {
            $returnHtml .= <<<"HTML"
\t<select name="{$widgetInfo['name']}" id="{$widgetInfo['id']}"{$widgetInfo['widgetClass']}{$widgetInfo['otherAttrs']}>
\t{$widgetInfo['selectOptionString']}
\t</select>
\t{$validityText}

HTML;
        } elseif ($widgetInfo['type'] == 'textarea') {
            $returnHtml .= <<<"HTML"
\t<textarea name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" {$widgetInfo['widgetClass']}{$widgetInfo['otherAttrs']}>{$widgetInfo['defaultValue']}</textarea>
\t{$validityText}

HTML;
            } else {
                
                if ((!empty($widgetInfo['prepend']['contents'])) or (!empty($widgetInfo['append']['contents']))) {
                    $returnHtml .= <<<"HTML"
\t<div class="input-group">

HTML;
                }
                
                if (!empty($widgetInfo['prepend']['contents'])) {
                    $prependClass = $this->_makeClassString($widgetInfo['prepend']['class']);
                    $returnHtml .= <<<"HTML"
\t\t<div class="input-group-prepend">
\t\t\t<div class="input-group-text">
\t\t\t\t<span{$prependClass}>{$widgetInfo['prepend']['contents']}</span>
\t\t\t</div>
\t\t</div>

HTML;
                }
                
                $returnHtml .= <<<"HTML"
\t<input type="{$widgetInfo['type']}" name="{$widgetInfo['name']}" id="{$widgetInfo['id']}" value="{$widgetInfo['defaultValue']}"{$widgetInfo['widgetClass']}{$widgetInfo['otherAttrs']}>

HTML;
                
                if (!empty($widgetInfo['append']['contents'])) {
                    $appendClass = $this->_makeClassString($widgetInfo['append']['class']);
                    $returnHtml .= <<<"HTML"
\t\t<div class="input-group-append">
\t\t\t<div class="input-group-text">
\t\t\t\t<span{$appendClass}>{$widgetInfo['append']['contents']}</span>
\t\t\t</div>
\t\t</div>

HTML;
                }
                
                $returnHtml .= $validityText;
                
                if ((!empty($widgetInfo['prepend']['contents'])) or (!empty($widgetInfo['append']['contents']))) {
                    $returnHtml .= <<<"HTML"
\t</div>

HTML;
                }
            }
            
            $returnHtml .= "</div>\n";
            
            $returnHtml .= $this->_afterWidgetText($widgetInfo);
            
            $returnHtml .= "</div>\n";
            return $returnHtml;
    }
    
    /**
     * 
     * @param array $widgetInfo
     * @return string
     */
    private function _validityText(array $widgetInfo)
    {
        $returnHtml = '';
        
        // valid message
        if (!empty($widgetInfo['validMessage']['contents'])) {
            $returnHtml .= <<<"HTML"
\t<div{$widgetInfo['validMessage']['class']}>
\t\t{$widgetInfo['validMessage']['contents']}
\t</div>

HTML;
        }
        // invalid message
        if (!empty($widgetInfo['invalidMessage']['contents'])) {
            $returnHtml .= <<<"HTML"
\t<div{$widgetInfo['invalidMessage']['class']}>
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
    private function _afterWidgetText(array $widgetInfo)
    {
        $returnHtml = '';
        
        // helptext
        if (!empty($widgetInfo['helpText']['contents'])) {
            $returnHtml .= <<<"HTML"
\t<{$widgetInfo['helpText']['element']}{$widgetInfo['helpText']['id']}{$widgetInfo['helpText']['class']}>
\t\t{$widgetInfo['helpText']['contents']}
\t</{$widgetInfo['helpText']['element']}>

HTML;
        }
        
        // errors
        if (!empty($widgetInfo['errors'])) {
            $errorClass = $this->getConfig('error_class');
            $returnHtml .= <<<"HTML"
\t<div class="{$errorClass}">
\t\t{$widgetInfo['errors']}
\t</div>

HTML;
        }
        
        return $returnHtml;
    }
    
    /**
     * 
     * @param array $options
     * @return array[]
     */
    private function _convertOptionAliases(array $options)
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
     * @return array|NULL
     */
    private function _processOptions(string $name, array $options = [])
    {
        $namedOptions = ['type','id','value','class','label','labelClass',
                         'helpText','validMessage','invalidMessage',
                         'options','optionsClass','selectOptionString','empty',
                         'rowClass','prepend','append',
        ];
        $options += [
            'type' => 'text',
            'id' => $this->_makeId($name),
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
        ];
        $options['helpText'] += [
            'contents' => null,
            'class' => null,
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
        
        if (!empty($options['required'])) {
            if ($this->getConfig('required_star')) {
                $options['label'] .= '*';
            }
            if (!empty($this->getConfig('required_class'))) {
                $options['labelClass'] .= ' ' . $this->getConfig('required_class');
            }
        }
        
        $widgetInfo = $options;
        $widgetInfo['name'] = $this->_processName($name);
        
        $widgetInfo['defaultValue'] = null;
        $thisEntity = $this->getConfig('entity');
        if ((!empty($thisEntity)) and (isset($thisEntity->$name))) {
            $widgetInfo['defaultValue'] = $this->_processValue($thisEntity->$name, $options['type']);
        } else {
            $widgetInfo['defaultValue'] = $this->_processValue($widgetInfo['value'], $options['type']);
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
        $widgetInfo['otherAttrs'] = $this->_makeAttrString($otherAttrs);
        
        if (!empty($options['helpText']['contents'])) {
            $helpTextId = "{$options['id']}-helptext";
            $widgetInfo['otherAttrs'] .= " aria-describedby=\"{$helpTextId}\"";
            $widgetInfo['helpText']['id'] = " id=\"{$helpTextId}\"";
        }
        
        $widgetInfo['widgetClass'] = $this->_makeClassString($options['class']);
        $widgetInfo['labelClass'] = $this->_makeClassString($options['labelClass']);
        $widgetInfo['rowClass'] = $this->_makeClassString($options['rowClass']);
        $widgetInfo['helpText']['class'] = $this->_makeClassString($options['helpText']['class']);
        $widgetInfo['validMessage']['class'] = $this->_makeClassString("valid-feedback {$options['validMessage']['class']}");
        $widgetInfo['invalidMessage']['class'] = $this->_makeClassString("invalid-feedback {$options['invalidMessage']['class']}");
        $widgetInfo['optionsClass'] = $this->_makeClassString($options['optionsClass']);
        
        if (!empty($widgetInfo['options'])) {
            if ($widgetInfo['type'] == 'select') {
                $selectOptions = [];
                if ($widgetInfo['empty']) {
                    $selectOptions[] = "<option value=\"\"{$widgetInfo['optionsClass']}></option>";
                }
                foreach ($widgetInfo['options'] as $key => $value) {
                    $selected = ($this->_valueIsSelected($widgetInfo['defaultValue'],$key)) ? ' selected' : null;
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
                        $selectOptions[] = "<option value=\"{$key}\"{$widgetInfo['optionsClass']}{$selected} {$optionAttributesString}>{$value['value']}</option>";
                    } else {
                        $selectOptions[] = "<option value=\"{$key}\"{$widgetInfo['optionsClass']}{$selected}>{$value}</option>";
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
     * @param string $value
     * @return string
     */
    private function _makeId(string $name, string $value = null)
    {
        return $this->_domId("{$name}{$value}");
    }
    
    /**
     * 
     * @param string $name
     * @return string|mixed
     */
    private function _processName(string $name)
    {
        $nameArray = explode('.', $name);
        $processedName = array_shift($nameArray);
        if (count($nameArray)) {
            $processedName .= '[' . implode('][', $nameArray) . ']';
        }
        return $processedName;
    }
    
    /**
     * 
     * @param string $passedClasses
     * @return string|NULL
     */
    private function _makeClassString(string $passedClasses = null)
    {
        if (!empty($passedClasses)) {
            $passedClasses = trim($passedClasses);
            return " class=\"{$passedClasses}\"";
        }
        return null;
    }
    
    /**
     * 
     * @param array $attrs
     * @return string|NULL
     */
    private function _makeAttrString(array $attrs)
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
        return (!empty($stringArray)) ? ' ' . implode(' ', $stringArray) : null;
    }
    
    /**
     * 
     * @param string $valueStored
     * @param string $valueChoice
     * @return boolean
     */
    private function _valueIsSelected(string $valueStored = null, string $valueChoice = null) {
        return ((string)$valueStored === ((string)$valueChoice));
    }
    
    private function _processValue($value,$type = null) {
        if ($value === true) {
            return '1';
        } elseif ($value === false) {
            return '0';
        } elseif ($type == 'date') {
            return $this->_formatDate($value);
        } elseif ($type == 'time') {
            return $this->_formatTime($value);
        } else {
            return $value;
        }
    }
    
    private function _formatDate($value) {
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
    
    private function _formatTime($value) {
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
    
}
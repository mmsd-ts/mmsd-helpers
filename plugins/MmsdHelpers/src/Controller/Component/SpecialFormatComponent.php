<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;

class SpecialFormatComponent extends Component
{
    private $result = [];

    public function telephoneNumber(string $originalNumber)
    {
        $this->start($originalNumber);
        $oldNumbers = $this->reduceToNumbers($originalNumber);
        $numberArray = explode(' ',$oldNumbers);
        if ($numberArray[0] == '1') {
            array_shift($numberArray);
        }

        if ((substr($originalNumber,0,1) == '+') or (count($numberArray) > 4)) {
            $this->setMessage(__('International phone numbers are not supported'));
            return $this->result;
        }
        $newNumber = '';
        if (count($numberArray) > 2) {
            $badAreaCodes = ['555','800','833','844','855','866','877','880','881','882','888',
                            '211','311','411','511','611','711','811','900','911'];
            $badExchanges = ['555','211','311','411','511','611','711','811','911'];
            if ((in_array($numberArray[0],$badAreaCodes)) or (in_array($numberArray[1],$badExchanges))) {
                $this->setMessage(__('Phone number invalid'));
                return $this->result;
            }

            $newNumber = "({$numberArray[0]}){$numberArray[1]}-{$numberArray[2]}";
            if (!empty($numberArray[3])) {
                $newNumber .= "x{$numberArray[3]}";
            }
            $this->setFormattedString($newNumber);
        }

        $phonePattern = '/^\([2-9]\d{2}\)[2-9]\d{2}-\d{4}(x\d+)*$/';
        if (preg_match($phonePattern,$newNumber) !== 1) {
            $this->setMessage(__('Phone number format not recognized'));
            return $this->result;
        }

        if ($oldNumbers != $this->reduceToNumbers($newNumber)) {
            $this->result['message'] = sprintf(__('The phone number %1$s was changed to %2$s. Please make sure it is correct.'),$originalNumber,$newNumber);
        }
        $this->setValid(true);
        return $this->result;
    }

    public function properName(string $originalName, bool $preserveCase = false)
    {
        $this->start($originalName);
        $newName = $originalName;
        $newName = str_replace(
            ['à','á','â','ã','ä','å','æ','ç','é','ê','ë','ì','í','î','ï','ð','ñ','ò',
             'ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ','À','Á','Â','Ã','Ä','Å',
             'Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø',
             'Ù','Ú','Û','Ü','Ý','Þ','ß',],
            ['a','a','a','a','a','a','ae','c','e','e','e','i','i','i','i','th','n','o',
             'o','o','o','o','o','u','u','u','u','y','th','y','A','A','A','A','A','A',
             'AE','C','E','E','E','E','I','I','I','I','TH','N','O','O','O','O','O','O',
             'U','U','U','U','Y','TH','ss',],
            $newName
        );
        $newName = preg_replace('/[^a-zA-Z \'\.\-]/','',$newName);
        $newName = trim(preg_replace('/\s\s+/',' ',$newName));
        
        if ((preg_match('/[A-Z]/',$newName) === 0) or (preg_match('/[a-z]/',$newName) === 0)) {
            if (!$preserveCase) {
                $ucfirstName = [];
                foreach (explode(' ',$newName) as $part) {
                    if (stripos($part,'Mc') === 0) {
                        $ucfirstName[] = 'Mc' . strtoupper(substr($part,2,1)) . strtolower(substr($part,3));
                    } else {
                        $ucfirstName[] = ucfirst(strtolower($part));
                    }
                }
                $newName = implode(' ',$ucfirstName);
            }
        }
        
        if ($originalName != $newName) {
            $this->result['message'] = __('Name was changed');
        }
        $this->setFormattedString($newName);
        $this->setValid(true);
        return $this->result;
    }
    
    private function start(string $originalString)
    {
        $this->result = [
            'valid' => false,
            'originalString' => $originalString,
            'message' => null,
            'formattedString' => null,
        ];
    }

    private function setValid(bool $valid)
    {
        $this->result['valid'] = $valid;
    }

    private function setMessage(string $message) {
        $this->result['message'] = $message;
    }

    private function setOriginalString(string $str) {
        $this->result['originalString'] = $str;
    }

    private function setFormattedString(string $str) {
        $this->result['formattedString'] = $str;
    }

    private function reduceToNumbers(string $str)
    {
        $str = preg_replace('/\D/',' ',$str);
        $str = trim(preg_replace('/\s\s+/',' ',$str));
        return $str;
    }

}

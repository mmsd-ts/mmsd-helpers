<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;

class SpecialFormatComponent extends Component
{
    public $result = [
        'valid' => false,
        'message' => string(null),
        'formattedString' => (string)null,
        'originalString' => (string)null,
    ];

    public function telephoneNumber(string $oldNumber)
    {
        $this->$result['originalString'] = $oldNumber;
        if (substr($originalPhoneNumber,0,1) == '+') {
            $this->setInvalid(__('International phone numbers are not supported'));
            return $this->result;
        }
        
        $numbers = preg_replace('/\D/',' ',$oldNumber);
        $numbers = trim(preg_replace('/\s\s+/',' ',$numbers));
        $numberArray = explode(' ',$numbers);

        $newNumber = "({$numberArray[0]}){$numberArray[1]}-{$numberArray[2]}";
        if (!empty($numberArray[3])) {
            $newNumber .= "x{$numberArray[3]}";
        }
        $this->result['formattedString'] = $newNumber;
        if ($oldNumber != $newNumber) {
            $this->result['message'] = sprintf(__('The phone number %1$s was changed to %2$s. Please make sure it is correct.'),$oldNumber,$newNumber);
        }
        return $this->result;
    }

    private function setInvalid(string $message) {
        $this->result['valid'] = false;
        $this->result['message'] = $message;
        return true;
    }

}

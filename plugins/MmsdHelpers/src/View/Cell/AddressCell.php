<?php 
namespace MmsdHelpers\View\Cell;

use Cake\View\Cell;

class AddressCell extends Cell
{
    protected $_validCellOptions = ['allowEnteredAddress','notFoundMessage',];
    protected $allowEnteredAddress = true;
    protected $notFoundMessage = '';
    public function display()
    {
        $addressesTable = $this->fetchTable('MmsdHelpers.Addresses');
        $usStatesTable = $this->fetchTable('MmsdHelpers.UsStates');
        $this->set('tagList',$addressesTable->tagList());
        $this->set('stateList',$usStatesTable->stateList());
        $this->set('allowEnteredAddress', $this->allowEnteredAddress);
        $this->set('notFoundMessage', $this->notFoundMessage);
    }
}

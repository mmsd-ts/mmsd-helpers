<?php 
namespace MmsdHelpers\View\Cell;

use Cake\View\Cell;

class AddressCell extends Cell
{
    protected array $_validCellOptions = ['allowEnteredAddress','notFoundMessage',];
    protected bool $allowEnteredAddress = true;
    protected string $notFoundMessage = '';
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

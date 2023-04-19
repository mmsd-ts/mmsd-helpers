<?php 
namespace MmsdHelpers\View\Cell;

use Cake\View\Cell;

class AddressCell extends Cell
{
    public function display()
    {
        $addressesTable = $this->fetchTable('MmsdHelpers.Addresses');
        $usStatesTable = $this->fetchTable('MmsdHelpers.UsStates');
        $this->set('tagList',$addressesTable->tagList());
        $this->set('stateList',$usStatesTable->stateList());
    }
}

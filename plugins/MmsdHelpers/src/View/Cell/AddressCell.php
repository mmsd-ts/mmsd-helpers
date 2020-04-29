<?php 
namespace MmsdHelpers\View\Cell;

use Cake\View\Cell;

class AddressCell extends Cell
{
    public function display()
    {
        $addressesTable = $this->loadModel('MmsdHelpers.Addresses');
        $usStatesTable = $this->loadModel('MmsdHelpers.UsStates');
        $this->set('tagList',$addressesTable->tagList());
        $this->set('stateList',$usStatesTable->stateList());
    }
}

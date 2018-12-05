<?php

namespace MmsdHelpers\Test\TestCase\View\Helper;

use MmsdHelpers\View\Helper\BsFormHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class BsFormHelperTest extends TestCase
{
    
    public function setUp()
    {
        parent::setUp();
        $View = new View();
        $this->BsForm = new BsFormHelper($View);
    }
    
    public function testRadio()
    {
        $choices = ['1'=>'Yes','0'=>'No'];
        $this->assertContains("name=\"test\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertContains("id=\"test1\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertContains("id=\"test0\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertContains("type=\"radio\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertContains("class=\"form-check\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertContains("class=\"form-check-input\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertContains("class=\"form-check-label\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertContains("type=\"hidden\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
        ]));
        $this->assertNotContains("type=\"hidden\"", $this->BsForm->check('test',[
            'type'=>'radio',
            'options' => $choices,
            'empty' => false,
        ]));
        $this->assertContains("Student[111][answer]", $this->BsForm->check('Student.111.answer',[
            'type'=>'radio',
            'options' => $choices,
        ]));
    }
    
    public function testEntity()
    {
        $fakeEntity = new \Cake\ORM\Entity;
        $fakeBsEntity = $this->BsForm->setEntity($fakeEntity);
        $this->assertNotNull($fakeBsEntity);
        $this->assertInstanceOf(\Cake\ORM\Entity::class, $fakeBsEntity);
        $nullBsEntity = $this->BsForm->setEntity(null);
        $this->assertNull($nullBsEntity);
        $this->assertNotInstanceOf(\Cake\ORM\Entity::class, $nullBsEntity);
        
    }
    
    public function testPresetDate()
    {
        $dateObj = new \Cake\I18n\FrozenDate('2018-04-01');
        $fakeEntity = new \Cake\ORM\Entity([
            'fakeDate' => 'not a date',
            'realDate' => $dateObj,
        ]);
        $fakeBsEntity = $this->BsForm->setEntity($fakeEntity);
        $this->assertContains('not a date', $this->BsForm->input('fakeDate',[
            'type' => 'date',
        ]));
        $this->assertContains('2018-04-01', $this->BsForm->input('realDate',[
            'type' => 'date',
        ]));
    }
    
}
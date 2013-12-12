<?php
use Evasive\Evasive;

/**
 * @group library
 */
class EvasiveTest extends PHPUnit_Framework_TestCase
{

    private $evasive;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->evasive = new Evasive('Dummy');
    }

    public function testInvalidOptionsToConstructor()
    {
        $this->setExpectedException("Evasive\\RuntimeException", sprintf('%s expects the $type argument to be a valid class name; received "%s"', '__construct', 'in-valid'));
        
        $validator = new Evasive('in-valid');
    }
    
    
}

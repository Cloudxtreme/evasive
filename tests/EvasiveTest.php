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
        
        $evasive = new Evasive('in-valid');
    }

    public function testConstructorOptions()
    {
        
        $evasive = new Evasive('Dummy', ['pageCount' => 10, 'pageInternal' => 30]);
        
        $this->assertEquals(10, $evasive->getPageCount());
        $this->assertEquals(30, $evasive->getPageInterval());
    }
}

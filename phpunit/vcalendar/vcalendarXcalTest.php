<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class XcalTest extends iCalCreator_TestCase
{
	protected $outputformat = 'xcal';
	
	public function testSetAndGetiCalscale()
	{
		$this->assertCalscale('JULIAN', 'calscale="JULIAN"');
	}
	
	public function testSetAndGetiMethod()
	{
		$this->assertMethod('COUNTER', 'method="COUNTER"');
	}
			
	public function testiCreateProdId()
	{		
		$this->assertContains('prodid=',$this->cal->createProdid());
	}
	
	public function testiVersion()
	{
		$this->assertVersion('2.0', 'version="2.0"');
	}
}
<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class IcalTest extends iCalCreator_TestCase
{
	protected $outputformat = 'ical';
	
	public function testSetAndGetiCalscale()
	{
		$this->assertCalscale('JULIAN', 'CALSCALE:JULIAN');
	}
	
	public function testSetAndGetiMethod()
	{
		$this->assertMethod('COUNTER', 'METHOD:COUNTER');
	}
			
	public function testiCreateProdId()
	{		
		$this->assertContains('PRODID:',$this->cal->createProdid());
	}
	
	public function testiVersion()
	{
		$this->assertVersion('2.0', 'VERSION:2.0');
	}
}
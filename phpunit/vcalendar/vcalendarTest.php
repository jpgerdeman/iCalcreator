<?php
class vcalendarTest extends iCalCreator_TestCase
{

	/**
	 * @dataProvider provideProperties
	 */
	public function testProperties($propertyname, $propertyvalue)
	{		
		$this->cal->setProperty($propertyname, $propertyvalue);
		$got = $this->cal->getProperty($propertyname);
		
		$this->assertEquals($propertyvalue, $got);
		
	}
	
	public function provideProperties()
	{
		return array(
			array('calscale', 'JULIAN'),
			array('method', 'REQUEST')
		);
	}
}
?>

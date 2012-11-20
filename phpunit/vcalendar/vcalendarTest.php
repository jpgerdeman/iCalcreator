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
	
	public function testComponents()
	{
		$component = new vtodo();
		$this->cal->addComponent($component);
		$got = $this->cal->getComponent('VTODO', '1');
		
		$this->assertEquals($component, $got);
		
//		$got = $this->cal->selectComponents(false, false, false, false, false, false, 'VTODO');
//		
//		$this->assertEquals($component, $got);
		
		$got = $this->cal->deleteComponent('VTODO', '1');
		$got = $this->cal->getComponent('VTODO', '1');
		
		$this->assertFalse($got);
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

<?php
class componentHolderTest extends iCalCreator_TestCase
{	
	public function testSetComponentsSimple()
	{
		$holder = new componentHolder();
				
		$todo = new vtodo();	
		$event = new vevent();	
		$todoid = $todo->getProperty('uid');
		
		// simple addition
		$holder ->setComponent($todo);
		$got = $holder->components[0];
		$this->assertEquals($todo, $got);		
	}
	
	public function testSetComponentsByPosition()
	{
		$holder = new componentHolder();
				
		$todo = new vtodo();	
		$event = new vevent();	
		$todoid = $todo->getProperty('uid');
				
		// set position
		$holder->setComponent($todo, 99);
		$holder->setComponent($event);
		$this->assertEquals($todo, $holder->components[99 -1]);		
	}
	
	public function testSetComponentsType()
	{
		$holder = new componentHolder();
				
		$todo = new vtodo();	
		$event = new vevent();	
		$todo2 = new vtodo();
		$todoid = $todo->getProperty('uid');
		
		$holder->components[] = $todo;
		$holder->components[] = $todo2;
		
		// set by type
		$holder->setComponent($todo, 'vtodo', 1);		
		$this->assertEquals($todo, $holder->components[0]);
	}
	
	public function testSetComponentsUid()
	{
		$holder = new componentHolder();
				
		$todo = new vtodo();	
		$event = new vevent();	
		$todo2 = new vtodo();
		$todoid = $todo->getProperty('uid');
		
		$holder->components[] = $todo2;
		$holder->components[] = $todo;
				
		// set by uid
		$holder->setComponent($event, $todoid);
		$this->assertEquals($event, $holder->components[1]);		
	}
		
	public function testDeleteComponents()
	{		
		$holder = new componentHolder();
		
		$todo = new vtodo();	
		$event = new vevent();	
		$todo2 = new vtodo();
		$todoid = $todo->getProperty('uid');
		
		$holder->components[] = $todo;
		$holder->components[] = $event;
		$holder->components[] = $todo2;
				
		$holder->deleteComponent('VEVENT', '1');
		$this->assertFalse( count($holder->components) > 2 );
		$this->assertFalse(isset($holder->components[1]));
	}
}
?>

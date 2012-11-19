<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class iCalCreator_TestCase extends PHPUnit_Framework_TestCase
{
	protected $outputformat = 'ical';
	
	public function setUp()
	{
		$this->cal = new vcalendar( array('format' => $this->outputformat));		
	}
}

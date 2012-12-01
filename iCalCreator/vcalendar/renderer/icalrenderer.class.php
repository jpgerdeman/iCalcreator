<?php

class icalrenderer extends baserenderer
{

	/**
	 * creates formatted output for calendar property calscale
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	function createCalscale()
	{
		if (empty($this->calscale))
			return FALSE;

		return 'CALSCALE:' . $this->calscale . $this->nl;
	}

	/**
	 * creates formatted output for calendar property prodid
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.12.11 - 2012-05-13
	 * @return string
	 */
	function createProdid()
	{
		$toolbox = new calendarComponent();
		$toolbox->setConfig($this->calendar->getConfig());
		return $toolbox->_createElement('PRODID', '', $this->prodid);		
	}

	/**
	 * creates formatted output for calendar property method
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	function createMethod()
	{
		if (empty($this->method))
			return FALSE;
		return 'METHOD:' . $this->method . $this->nl;
	}

	/**
	 * creates formatted output for calendar property version

	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	function createVersion()
	{
				return 'VERSION:' . $this->version . $this->nl;
	}

	/**
	 * creates formatted output for calendar object instance
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	function createCalendar()
	{
		$calendarInit = $calendarxCaldecl = $calendarStart = $calendar = '';
		$calendarStart = 'BEGIN:VCALENDAR' . $this->nl;
		$calendarStart .= $this->createVersion();
		$calendarStart .= $this->createProdid();
		$calendarStart .= $this->createCalscale();
		$calendarStart .= $this->createMethod();
		$calendar .= $this->createXprop();

		foreach ($this->components as $component)
		{
			if (empty($component))
				continue;
			$component->setConfig($this->calendar->getConfig(), FALSE, TRUE);
			$calendar .= $component->createComponent($this->xcaldecl);
		}
		$calendar .= 'END:VCALENDAR' . $this->nl;
	
		return $calendarInit . $calendarxCaldecl . $calendarStart . $calendar;
	}

}
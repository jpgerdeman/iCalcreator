<?php

/**
 * Description of XCalRenderer
 *
 */
class ICalRenderer extends BaseRenderer
{
	/**
	 * creates formatted output for calendar property calscale
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	public function createCalscale()
	{
		if (empty($this->calendar->calscale))
			return FALSE;
		return 'CALSCALE:' . $this->calendar->calscale . $this->nl;		
	}
	
	/**
	 * creates formatted output for calendar property version
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	public function createVersion()
	{
		return 'VERSION:' . $this->calendar->getVersion() . $this->nl;
	}
	
	/**
	 * creates formatted output for calendar property prodid
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.12.11 - 2012-05-13
	 * @return string
	 */
	public function createProdid()
	{
		$toolbox = new calendarComponent();
		$toolbox->setConfig($this->calendar->getConfig());
		return $toolbox->_createElement('PRODID', '', $this->calendar->getProdid());	
	
	}

	/**
	 * creates formatted output for calendar property method
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	public function createMethod()
	{
		if (empty($this->calendar->method))
			return FALSE;

		return 'METHOD:' . $this->calendar->method . $this->nl;
	}

	/**
	 * creates formatted output for calendar object instance
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	public function render()
	{
		$calendarInit = $calendarxCaldecl = $calendarStart = $calendar = '';

		$calendarStart = 'BEGIN:VCALENDAR' . $this->nl;

		$calendarStart .= $this->createVersion();
		$calendarStart .= $this->createProdid();
		$calendarStart .= $this->createCalscale();
		$calendarStart .= $this->createMethod();

		$calendar .= $this->createXprop();

		foreach ($this->calendar->components as $component)
		{
			if (empty($component))
				continue;
			$component->setConfig($this->calendar->getConfig(), FALSE, TRUE);
			$calendar .= $component->createComponent($this->calendar->xcaldecl);
		}
		$calendar .= 'END:VCALENDAR' . $this->nl;

		return $calendarInit . $calendarxCaldecl . $calendarStart . $calendar;
	}

}

?>

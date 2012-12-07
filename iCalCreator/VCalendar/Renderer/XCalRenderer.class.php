<?php
/**
 * Description of XCalRenderer
 *
 */
class XCalRenderer extends BaseRenderer
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
		return $this->nl . ' calscale="' . $this->calendar->calscale . '"';
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
		return $this->nl . ' version="' . $this->calendar->getVersion() . '"';
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
		return $this->nl . ' prodid="' . $this->calendar->getProdid() . '"';	
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
		
		return $this->nl . ' method="' . $this->calendar->method . '"';		
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
		
		$calendarInit = '<?xml version="1.0" encoding="UTF-8"?>' . $this->nl .
						'<!DOCTYPE vcalendar PUBLIC "-//IETF//DTD XCAL/iCalendar XML//EN"' . $this->nl .
						'"http://www.ietf.org/internet-drafts/draft-ietf-calsch-many-xcal-01.txt"';
		$calendarStart = '>' . $this->nl . '<vcalendar';
		
		$calendarStart .= $this->createVersion();
		$calendarStart .= $this->createProdid();
		$calendarStart .= $this->createCalscale();
		$calendarStart .= $this->createMethod();
		$calendarStart .= '>' . $this->nl;
		$calendar .= $this->createXprop();

		foreach ($this->calendar->components as $component)
		{
			if (empty($component))
				continue;
			$component->setConfig($this->calendar->getConfig(), FALSE, TRUE);
			$calendar .= $component->createComponent($this->calendar->xcaldecl);
		}
		if (( 0 < count($this->calendar->xcaldecl)))
		{ 
			$calendarInit .= ' [';
			$old_xcaldecl = array();
			foreach ($this->calendar->xcaldecl as $declix => $declPart)
			{
				if (( 0 < count($old_xcaldecl)) &&
						isset($declPart['uri']) && isset($declPart['external']) &&
						isset($old_xcaldecl['uri']) && isset($old_xcaldecl['external']) &&
						( in_array($declPart['uri'], $old_xcaldecl['uri'])) &&
						( in_array($declPart['external'], $old_xcaldecl['external'])))
					continue; // no duplicate uri and ext. references
				if (( 0 < count($old_xcaldecl)) &&
						!isset($declPart['uri']) && !isset($declPart['uri']) &&
						isset($declPart['ref']) && isset($old_xcaldecl['ref']) &&
						( in_array($declPart['ref'], $old_xcaldecl['ref'])))
					continue; // no duplicate element declarations
				$calendarxCaldecl .= $this->nl . '<!';
				foreach ($declPart as $declKey => $declValue)
				{
					switch ($declKey)
					{	 // index
						case 'xmldecl':		// no 1
							$calendarxCaldecl .= $declValue . ' ';
							break;
						case 'uri':		 // no 2
							$calendarxCaldecl .= $declValue . ' ';
							$old_xcaldecl['uri'][] = $declValue;
							break;
						case 'ref':		 // no 3
							$calendarxCaldecl .= $declValue . ' ';
							$old_xcaldecl['ref'][] = $declValue;
							break;
						case 'external':	   // no 4
							$calendarxCaldecl .= '"' . $declValue . '" ';
							$old_xcaldecl['external'][] = $declValue;
							break;
						case 'type':		// no 5
							$calendarxCaldecl .= $declValue . ' ';
							break;
						case 'type2':	   // no 6
							$calendarxCaldecl .= $declValue;
							break;
					}
				}
				$calendarxCaldecl .= '>';
			}
			$calendarxCaldecl .= $this->nl . ']';
		}
		
		$calendar .= '</vcalendar>' . $this->nl;
		
		return $calendarInit . $calendarxCaldecl . $calendarStart . $calendar;
	}

}

?>

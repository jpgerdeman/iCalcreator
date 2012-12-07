<?php

/**
 * Description of BaseRenderer
 *
 */
abstract class BaseRenderer
{

	protected $calendar;

	public function __construct(vcalendar $calendar)
	{
		$this->calendar = $calendar;
		$this->format = $calendar->format;
		$this->nl = $calendar->getConfig('NEWLINECHAR');		
	}
	
	static public function factory( vcalendar $calendar )			
	{
		if( $calendar->format == 'xcal' )
		{
			return new XCalRenderer($calendar);
		}
		else
		{
			return new ICalRenderer($calendar);
		}
	}

	/**
	 * creates formatted output for calendar property calscale
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	abstract public function createCalscale();

	/**
	 * creates formatted output for calendar property version

	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	abstract public function createVersion();

	/**
	 * creates formatted output for calendar property x-prop, iCal format only
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-11-01
	 * @return string
	 */
	public function createXprop()
	{
		if (empty($this->calendar->xprop) || !is_array($this->calendar->xprop))
			return FALSE;
		$output = null;
		$toolbox = new calendarComponent();
		$toolbox->setConfig($this->calendar->getConfig());
		foreach ($this->calendar->xprop as $label => $xpropPart)
		{
			if (!isset($xpropPart['value']) || ( empty($xpropPart['value']) && !is_numeric($xpropPart['value'])))
			{
				$output .= $toolbox->_createElement($label);
				continue;
			}
			$attributes = $toolbox->_createParams($xpropPart['params'], array('LANGUAGE'));
			if (is_array($xpropPart['value']))
			{
				foreach ($xpropPart['value'] as $pix => $theXpart)
					$xpropPart['value'][$pix] = $toolbox->_strrep($theXpart);
				$xpropPart['value'] = implode(',', $xpropPart['value']);
			}
			else
				$xpropPart['value'] = $toolbox->_strrep($xpropPart['value']);
			$output .= $toolbox->_createElement($label, $attributes, $xpropPart['value']);
			if (is_array($toolbox->xcaldecl) && ( 0 < count($toolbox->xcaldecl)))
			{
				foreach ($toolbox->xcaldecl as $localxcaldecl)
					$this->calendar->xcaldecl[] = $localxcaldecl;
			}
		}
		return $output;
	}

	/**
	 * creates formatted output for calendar property prodid
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.12.11 - 2012-05-13
	 * @return string
	 */
	abstract public function createProdid();

	/**
	 * creates formatted output for calendar property method
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	abstract public function createMethod();

	/**
	 * creates formatted output for calendar object instance
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.16 - 2011-10-28
	 * @return string
	 */
	abstract public function render();

}

?>

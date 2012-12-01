<?php

abstract class baserenderer
{
	function baserenderer( $calendar )
	{		
		$this->calendar = $calendar;
		$this->calscale = $this->calendar->getCalscale();
		$this->method = $this->calendar->getMethod();
		$this->prodid = $this->calendar->createProdId();
		$this->version = $this->calendar->getVersion();
		$this->xprop = $this->calendar->getXProp();
		$this->nl = $this->calendar->getConfig('NEWLINECHAR');
		$this->components = $this->calendar->componentHolder->components;
		// getconfig returns format in wrong casing
		$this->format = strtolower($this->calendar->getConfig('FORMAT'));		
	}
	
	/**
 * creates formatted output for calendar property calscale
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  abstract function createCalscale();
	
  /**
 * creates formatted output for calendar property prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.11 - 2012-05-13
 * @return string
 */
  abstract function createProdid();
  
  /**
 * creates formatted output for calendar property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  abstract function createMethod();
  
  /**
 * creates formatted output for calendar property version

 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  abstract function createVersion();
  
  /**
 * creates formatted output for calendar property x-prop, iCal format only
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-11-01
 * @return string
 */
  function createXprop() {
    if( empty( $this->xprop ) || !is_array( $this->xprop )) return FALSE;
    $output = null;
    $toolbox = new calendarComponent();
    $toolbox->setConfig( $this->calendar->getConfig());
    foreach( $this->xprop as $label => $xpropPart ) {
      if( !isset($xpropPart['value']) || ( empty( $xpropPart['value'] ) && !is_numeric( $xpropPart['value'] ))) {
        $output  .= $toolbox->_createElement( $label );
        continue;
      }
      $attributes = $toolbox->_createParams( $xpropPart['params'], array( 'LANGUAGE' ));
      if( is_array( $xpropPart['value'] )) {
        foreach( $xpropPart['value'] as $pix => $theXpart )
          $xpropPart['value'][$pix] = $toolbox->_strrep( $theXpart );
        $xpropPart['value']  = implode( ',', $xpropPart['value'] );
      }
      else
        $xpropPart['value'] = $toolbox->_strrep( $xpropPart['value'] );
      $output    .= $toolbox->_createElement( $label, $attributes, $xpropPart['value'] );
      if( is_array( $toolbox->xcaldecl ) && ( 0 < count( $toolbox->xcaldecl ))) {
        foreach( $toolbox->xcaldecl as $localxcaldecl )
          $this->xcaldecl[] = $localxcaldecl;
      }
    }
    return $output;
  }

	/**
 * creates formatted output for calendar object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  abstract function createCalendar();
}
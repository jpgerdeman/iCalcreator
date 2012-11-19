<?php

class baserenderer
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
		$this->components = $this->calendar->components;
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
  function createCalscale() {
    if( empty( $this->calscale )) return FALSE;
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' calscale="'.$this->calscale.'"';
        break;
      default:
        return 'CALSCALE:'.$this->calscale.$this->nl;
        break;
    }
  }
	
  /**
 * creates formatted output for calendar property prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.11 - 2012-05-13
 * @return string
 */
  function createProdid() {
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' prodid="'.$this->prodid.'"';
        break;
      default:
        $toolbox = new calendarComponent();
        $toolbox->setConfig( $this->calendar->getConfig());
        return $toolbox->_createElement( 'PRODID', '', $this->prodid );
        break;
    }
  }
  
  /**
 * creates formatted output for calendar property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  function createMethod() {
    if( empty( $this->method )) return FALSE;
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' method="'.$this->method.'"';
        break;
      default:
        return 'METHOD:'.$this->method.$this->nl;
        break;
    }
  }
  
  /**
 * creates formatted output for calendar property version

 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  function createVersion() {
    
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' version="'.$this->version.'"';
        break;
      default:
        return 'VERSION:'.$this->version.$this->nl;
        break;
    }
  }
  
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
  function createCalendar() {
    $calendarInit = $calendarxCaldecl = $calendarStart = $calendar = '';
    switch( $this->format ) {
      case 'xcal':
        $calendarInit  = '<?xml version="1.0" encoding="UTF-8"?>'.$this->nl.
                         '<!DOCTYPE vcalendar PUBLIC "-//IETF//DTD XCAL/iCalendar XML//EN"'.$this->nl.
                         '"http://www.ietf.org/internet-drafts/draft-ietf-calsch-many-xcal-01.txt"';
        $calendarStart = '>'.$this->nl.'<vcalendar';
        break;
      default:
        $calendarStart = 'BEGIN:VCALENDAR'.$this->nl;
        break;
    }
    $calendarStart .= $this->createVersion();
    $calendarStart .= $this->createProdid();
    $calendarStart .= $this->createCalscale();
    $calendarStart .= $this->createMethod();
    if( 'xcal' == $this->format )
      $calendarStart .= '>'.$this->nl;
    $calendar .= $this->createXprop();

    foreach( $this->components as $component ) {
      if( empty( $component )) continue;
      $component->setConfig( $this->calendar->getConfig(), FALSE, TRUE );
      $calendar .= $component->createComponent( $this->xcaldecl );
    }
    if(( 'xcal' == $this->format ) && ( 0 < count( $this->xcaldecl ))) { // xCal only
      $calendarInit .= ' [';
      $old_xcaldecl  = array();
      foreach( $this->xcaldecl as $declix => $declPart ) {
        if(( 0 < count( $old_xcaldecl))    &&
             isset( $declPart['uri'] )     && isset( $declPart['external'] )     &&
             isset( $old_xcaldecl['uri'] ) && isset( $old_xcaldecl['external'] ) &&
           ( in_array( $declPart['uri'],      $old_xcaldecl['uri'] ))            &&
           ( in_array( $declPart['external'], $old_xcaldecl['external'] )))
          continue; // no duplicate uri and ext. references
        if(( 0 < count( $old_xcaldecl))    &&
            !isset( $declPart['uri'] )     && !isset( $declPart['uri'] )         &&
             isset( $declPart['ref'] )     && isset( $old_xcaldecl['ref'] )      &&
           ( in_array( $declPart['ref'],      $old_xcaldecl['ref'] )))
          continue; // no duplicate element declarations
        $calendarxCaldecl .= $this->nl.'<!';
        foreach( $declPart as $declKey => $declValue ) {
          switch( $declKey ) {                    // index
            case 'xmldecl':                       // no 1
              $calendarxCaldecl .= $declValue.' ';
              break;
            case 'uri':                           // no 2
              $calendarxCaldecl .= $declValue.' ';
              $old_xcaldecl['uri'][] = $declValue;
              break;
            case 'ref':                           // no 3
              $calendarxCaldecl .= $declValue.' ';
              $old_xcaldecl['ref'][] = $declValue;
              break;
            case 'external':                      // no 4
              $calendarxCaldecl .= '"'.$declValue.'" ';
              $old_xcaldecl['external'][] = $declValue;
              break;
            case 'type':                          // no 5
              $calendarxCaldecl .= $declValue.' ';
              break;
            case 'type2':                         // no 6
              $calendarxCaldecl .= $declValue;
              break;
          }
        }
        $calendarxCaldecl .= '>';
      }
      $calendarxCaldecl .= $this->nl.']';
    }
    switch( $this->format ) {
      case 'xcal':
        $calendar .= '</vcalendar>'.$this->nl;
        break;
      default:
        $calendar .= 'END:VCALENDAR'.$this->nl;
        break;
    }
    return $calendarInit.$calendarxCaldecl.$calendarStart.$calendar;
  }
}
<?php
/*********************************************************************************/
/*          iCalcreator XML (rfc6321) helper functions                           */
/*********************************************************************************/
/**
 * format iCal XML output, rfc6321, using PHP SimpleXMLElement
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.3 - 2012-04-19
 * @param object $calendar, iCalcreator vcalendar instance reference
 * @return string
 */
function iCal2XML( & $calendar ) {
            /** fix an SimpleXMLElement instance and create root element */
  $xmlstr     = '<?xml version="1.0" encoding="utf-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">';
  $xmlstr    .= '<!-- created utilizing kigkonsult.se '.ICALCREATOR_VERSION.' iCal2XMl (rfc6321) -->';
  $xmlstr    .= '</icalendar>';
  $xml        = new SimpleXMLElement( $xmlstr );
  $vcalendar  = $xml->addChild( 'vcalendar' );
            /** fix calendar properties */
  $properties = $vcalendar->addChild( 'properties' );
  $calProps = array( 'prodid', 'version', 'calscale', 'method' );
  foreach( $calProps as $calProp ) {
    if( FALSE !== ( $content = $calendar->getProperty( $calProp )))
      _addXMLchild( $properties, $calProp, 'text', $content );
  }
  while( FALSE !== ( $content = $calendar->getProperty( FALSE, FALSE, TRUE )))
    _addXMLchild( $properties, $content[0], 'unknown', $content[1]['value'], $content[1]['params'] );
  $langCal = $calendar->getConfig( 'language' );
            /** prepare to fix components with properties */
  $components    = $vcalendar->addChild( 'components' );
  $comps         = array( 'vtimezone', 'vevent', 'vtodo', 'vjournal', 'vfreebusy' );
  $eventProps    = array( 'dtstamp', 'dtstart', 'uid',
                          'class', 'created', 'description', 'geo', 'last-modified', 'location', 'organizer', 'priority',
                          'sequence', 'status', 'summary', 'transp', 'url', 'recurrence-id', 'rrule', 'dtend', 'duration',
                          'attach', 'attendee', 'categories', 'comment', 'contact', 'exdate', 'request-status', 'related-to', 'resources', 'rdate',
                          'x-prop' );
  $todoProps     = array( 'dtstamp', 'uid',
                          'class', 'completed', 'created', 'description', 'geo', 'last-modified', 'location', 'organizer', 'percent-complete', 'priority',
                          'recurrence-id', 'sequence', 'status', 'summary', 'url', 'rrule', 'dtstart', 'due', 'duration',
                          'attach', 'attendee', 'categories', 'comment', 'contact', 'exdate', 'request-status', 'related-to', 'resources', 'rdate',
                          'x-prop' );
  $journalProps  = array( 'dtstamp', 'uid',
                          'class', 'created', 'dtstart', 'last-modified', 'organizer', 'recurrence-id', 'sequence', 'status', 'summary', 'url', 'rrule',
                          'attach', 'attendee', 'categories', 'comment', 'contact',
                          'description',
                          'exdate', 'related-to', 'rdate', 'request-status',
                          'x-prop' );
  $freebusyProps = array( 'dtstamp', 'uid',
                          'contact', 'dtstart', 'dtend', 'duration', 'organizer', 'url',
                          'attendee', 'comment', 'freebusy', 'request-status',
                          'x-prop' );
  $timezoneProps = array( 'tzid',
                          'last-modified', 'tzurl',
                          'x-prop' );
  $alarmProps    = array( 'action', 'description', 'trigger', 'summary',
                          'attendee',
                          'duration', 'repeat', 'attach',
                          'x-prop' );
  $stddghtProps  = array( 'dtstart', 'tzoffsetto', 'tzoffsetfrom',
                          'rrule',
                          'comment', 'rdate', 'tzname',
                          'x-prop' );
  foreach( $comps as $compName ) {
    switch( $compName ) {
      case 'vevent':
        $props        = & $eventProps;
        $subComps     = array( 'valarm' );
        $subCompProps = & $alarmProps;
        break;
      case 'vtodo':
        $props        = & $todoProps;
        $subComps     = array( 'valarm' );
        $subCompProps = & $alarmProps;
        break;
      case 'vjournal':
        $props        = & $journalProps;
        $subComps     = array();
        $subCompProps = array();
        break;
      case 'vfreebusy':
        $props        = & $freebusyProps;
        $subComps     = array();
        $subCompProps = array();
        break;
      case 'vtimezone':
        $props        = & $timezoneProps;
        $subComps     = array( 'standard', 'daylight' );
        $subCompProps = & $stddghtProps;
        break;
    } // end switch( $compName )
            /** fix component properties */
    while( FALSE !== ( $component = $calendar->getComponent( $compName ))) {
      $child      = $components->addChild( $compName );
      $properties = $child->addChild( 'properties' );
      $langComp = $component->getConfig( 'language' );
      foreach( $props as $prop ) {
        switch( $prop ) {
          case 'attach':          // may occur multiple times, below
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              $type = ( isset( $content['params']['VALUE'] ) && ( 'BINARY' == $content['params']['VALUE'] )) ? 'binary' : 'uri';
              unset( $content['params']['VALUE'] );
              _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
            }
            break;
          case 'attendee':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              if( isset( $content['params']['CN'] ) && !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'cal-address', $content['value'], $content['params'] );
            }
            break;
          case 'exdate':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              $type = ( isset( $content['params']['VALUE'] ) && ( 'DATE' == $content['params']['VALUE'] )) ? 'date' : 'date-time';
              unset( $content['params']['VALUE'] );
              foreach( $content['value'] as & $exDate ) {
                if( (  isset( $exDate['tz'] ) &&  // fix UTC-date if offset set
                       iCalUtilityFunctions::_isOffset( $exDate['tz'] ) &&
                     ( 'Z' != $exDate['tz'] ))
                 || (  isset( $content['params']['TZID'] ) &&
                       iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                     ( 'Z' != $content['params']['TZID'] ))) {
                  $offset = isset( $exDate['tz'] ) ? $exDate['tz'] : $content['params']['TZID'];
                  $date = mktime( (int)  $exDate['hour'],
                                  (int)  $exDate['min'],
                                  (int) ($exDate['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                  (int)  $exDate['month'],
                                  (int)  $exDate['day'],
                                  (int)  $exDate['year'] );
                  unset( $exDate['tz'] );
                  $exDate = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                  unset( $exDate['unparsedtext'] );
                }
              }
              _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
            }
            break;
          case 'freebusy':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'period', $content['value'], $content['params'] );
            break;
          case 'request-status':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              if( !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'rstatus', $content['value'], $content['params'] );
            }
            break;
          case 'rdate':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              $type = 'date-time';
              if( isset( $content['params']['VALUE'] )) {
                if( 'DATE' == $content['params']['VALUE'] )
                  $type = 'date';
                elseif( 'PERIOD' == $content['params']['VALUE'] )
                  $type = 'period';
              }
              if( 'period' == $type ) {
                foreach( $content['value'] as & $rDates ) {
                  if( (  isset( $rDates[0]['tz'] ) &&  // fix UTC-date if offset set
                         iCalUtilityFunctions::_isOffset( $rDates[0]['tz'] ) &&
                       ( 'Z' != $rDates[0]['tz'] ))
                   || (  isset( $content['params']['TZID'] ) &&
                         iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                       ( 'Z' != $content['params']['TZID'] ))) {
                    $offset = isset( $rDates[0]['tz'] ) ? $rDates[0]['tz'] : $content['params']['TZID'];
                    $date = mktime( (int)  $rDates[0]['hour'],
                                    (int)  $rDates[0]['min'],
                                    (int) ($rDates[0]['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                    (int)  $rDates[0]['month'],
                                    (int)  $rDates[0]['day'],
                                    (int)  $rDates[0]['year'] );
                    unset( $rDates[0]['tz'] );
                    $rDates[0] = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                    unset( $rDates[0]['unparsedtext'] );
                  }
                  if( isset( $rDates[1]['year'] )) {
                    if( (  isset( $rDates[1]['tz'] ) &&  // fix UTC-date if offset set
                           iCalUtilityFunctions::_isOffset( $rDates[1]['tz'] ) &&
                         ( 'Z' != $rDates[1]['tz'] ))
                     || (  isset( $content['params']['TZID'] ) &&
                           iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                         ( 'Z' != $content['params']['TZID'] ))) {
                      $offset = isset( $rDates[1]['tz'] ) ? $rDates[1]['tz'] : $content['params']['TZID'];
                      $date = mktime( (int)  $rDates[1]['hour'],
                                      (int)  $rDates[1]['min'],
                                      (int) ($rDates[1]['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                      (int)  $rDates[1]['month'],
                                      (int)  $rDates[1]['day'],
                                      (int)  $rDates[1]['year'] );
                      unset( $rDates[1]['tz'] );
                      $rDates[1] = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                      unset( $rDates[1]['unparsedtext'] );
                    }
                  }
                }
              }
              elseif( 'date-time' == $type ) {
                foreach( $content['value'] as & $rDate ) {
                  if( (  isset( $rDate['tz'] ) &&  // fix UTC-date if offset set
                         iCalUtilityFunctions::_isOffset( $rDate['tz'] ) &&
                       ( 'Z' != $rDate['tz'] ))
                   || (  isset( $content['params']['TZID'] ) &&
                         iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                       ( 'Z' != $content['params']['TZID'] ))) {
                    $offset = isset( $rDate['tz'] ) ? $rDate['tz'] : $content['params']['TZID'];
                    $date = mktime( (int)  $rDate['hour'],
                                    (int)  $rDate['min'],
                                    (int) ($rDate['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                    (int)  $rDate['month'],
                                    (int)  $rDate['day'],
                                    (int)  $rDate['year'] );
                    unset( $rDate['tz'] );
                    $rDate = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                    unset( $rDate['unparsedtext'] );
                  }
                }
              }
              unset( $content['params']['VALUE'] );
              _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
            }
            break;
          case 'categories':
          case 'comment':
          case 'contact':
          case 'description':
          case 'related-to':
          case 'resources':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              if(( 'related-to' != $prop ) && !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
            }
            break;
          case 'x-prop':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $content[0], 'unknown', $content[1]['value'], $content[1]['params'] );
            break;
          case 'created':         // single occurence below, if set
          case 'completed':
          case 'dtstamp':
          case 'last-modified':
            $utcDate = TRUE;
          case 'dtstart':
          case 'dtend':
          case 'due':
          case 'recurrence-id':
            if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              if( isset( $content['params']['VALUE'] ) && ( 'DATE' == $content['params']['VALUE'] )) {
                $type = 'date';
                unset( $content['value']['hour'], $content['value']['min'], $content['value']['sec'] );
              }
              else {
                $type = 'date-time';
                if( isset( $utcDate ) && !isset( $content['value']['tz'] ))
                  $content['value']['tz'] = 'Z';
                if( (  isset( $content['value']['tz'] ) &&  // fix UTC-date if offset set
                       iCalUtilityFunctions::_isOffset( $content['value']['tz'] ) &&
                     ( 'Z' != $content['value']['tz'] ))
                 || (  isset( $content['params']['TZID'] ) &&
                       iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                     ( 'Z' != $content['params']['TZID'] ))) {
                  $offset = isset( $content['value']['tz'] ) ? $content['value']['tz'] : $content['params']['TZID'];
                  $date = mktime( (int)  $content['value']['hour'],
                                  (int)  $content['value']['min'],
                                  (int) ($content['value']['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                  (int)  $content['value']['month'],
                                  (int)  $content['value']['day'],
                                  (int)  $content['value']['year'] );
                  unset( $content['value']['tz'], $content['params']['TZID'] );
                  $content['value'] = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                  unset( $content['value']['unparsedtext'] );
                }
                elseif( isset( $content['value']['tz'] ) && !empty( $content['value']['tz'] ) &&
                      ( 'Z' != $content['value']['tz'] ) && !isset( $content['params']['TZID'] )) {
                  $content['params']['TZID'] = $content['value']['tz'];
                  unset( $content['value']['tz'] );
                }
              }
              unset( $content['params']['VALUE'] );
              if(( isset( $content['params']['TZID'] ) && empty( $content['params']['TZID'] )) || @is_null( $content['params']['TZID'] ))
                unset( $content['params']['TZID'] );
              _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
            }
            unset( $utcDate );
            break;
          case 'duration':
            if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'duration', $content['value'], $content['params'] );
            break;
          case 'rrule':
            while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'recur', $content['value'], $content['params'] );
            break;
          case 'class':
          case 'location':
          case 'status':
          case 'summary':
          case 'transp':
          case 'tzid':
          case 'uid':
            if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              if((( 'location' == $prop ) || ( 'summary' == $prop )) && !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
            }
            break;
          case 'geo':
            if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'geo', $content['value'], $content['params'] );
            break;
          case 'organizer':
            if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
              if( isset( $content['params']['CN'] ) && !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'cal-address', $content['value'], $content['params'] );
            }
            break;
          case 'percent-complete':
          case 'priority':
          case 'sequence':
            if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'integer', $content['value'], $content['params'] );
            break;
          case 'tzurl':
          case 'url':
            if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'uri', $content['value'], $content['params'] );
            break;
        } // end switch( $prop )
      } // end foreach( $props as $prop )
            /** fix subComponent properties, if any */
      foreach( $subComps as $subCompName ) {
        while( FALSE !== ( $subcomp = $component->getComponent( $subCompName ))) {
          $child2     = $child->addChild( $subCompName );
          $properties = $child2->addChild( 'properties' );
          $langComp   = $subcomp->getConfig( 'language' );
          foreach( $subCompProps as $prop ) {
            switch( $prop ) {
              case 'attach':          // may occur multiple times, below
                while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
                  $type = ( isset( $content['params']['VALUE'] ) && ( 'BINARY' == $content['params']['VALUE'] )) ? 'binary' : 'uri';
                  unset( $content['params']['VALUE'] );
                  _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
                }
                break;
              case 'attendee':
                while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
                  if( isset( $content['params']['CN'] ) && !isset( $content['params']['LANGUAGE'] )) {
                    if( $langComp )
                      $content['params']['LANGUAGE'] = $langComp;
                    elseif( $langCal )
                      $content['params']['LANGUAGE'] = $langCal;
                  }
                  _addXMLchild( $properties, $prop, 'cal-address', $content['value'], $content['params'] );
                }
                break;
              case 'comment':
              case 'tzname':
                while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
                  if( !isset( $content['params']['LANGUAGE'] )) {
                    if( $langComp )
                      $content['params']['LANGUAGE'] = $langComp;
                    elseif( $langCal )
                      $content['params']['LANGUAGE'] = $langCal;
                  }
                  _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
                }
                break;
              case 'rdate':
                while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
                  $type = 'date-time';
                  if( isset( $content['params']['VALUE'] )) {
                    if( 'DATE' == $content['params']['VALUE'] )
                      $type = 'date';
                    elseif( 'PERIOD' == $content['params']['VALUE'] )
                      $type = 'period';
                  }
                  if( 'period' == $type ) {
                    foreach( $content['value'] as & $rDates ) {
                      if( (  isset( $rDates[0]['tz'] ) &&  // fix UTC-date if offset set
                             iCalUtilityFunctions::_isOffset( $rDates[0]['tz'] ) &&
                          ( 'Z' != $rDates[0]['tz'] ))
                       || (  isset( $content['params']['TZID'] ) &&
                             iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                           ( 'Z' != $content['params']['TZID'] ))) {
                        $offset = isset( $rDates[0]['tz'] ) ? $rDates[0]['tz'] : $content['params']['TZID'];
                        $date = mktime( (int)  $rDates[0]['hour'],
                                        (int)  $rDates[0]['min'],
                                        (int) ($rDates[0]['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                        (int)  $rDates[0]['month'],
                                        (int)  $rDates[0]['day'],
                                        (int)  $rDates[0]['year'] );
                        unset( $rDates[0]['tz'] );
                        $rDates[0] = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                        unset( $rDates[0]['unparsedtext'] );
                      }
                      if( isset( $rDates[1]['year'] )) {
                        if( (  isset( $rDates[1]['tz'] ) &&  // fix UTC-date if offset set
                               iCalUtilityFunctions::_isOffset( $rDates[1]['tz'] ) &&
                             ( 'Z' != $rDates[1]['tz'] ))
                         || (  isset( $content['params']['TZID'] ) &&
                               iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                             ( 'Z' != $content['params']['TZID'] ))) {
                          $offset = isset( $rDates[1]['tz'] ) ? $rDates[1]['tz'] : $content['params']['TZID'];
                          $date = mktime( (int)  $rDates[1]['hour'],
                                          (int)  $rDates[1]['min'],
                                          (int) ($rDates[1]['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                          (int)  $rDates[1]['month'],
                                          (int)  $rDates[1]['day'],
                                          (int)  $rDates[1]['year'] );
                          unset( $rDates[1]['tz'] );
                          $rDates[1] = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                          unset( $rDates[1]['unparsedtext'] );
                        }
                      }
                    }
                  }
                  elseif( 'date-time' == $type ) {
                    foreach( $content['value'] as & $rDate ) {
                      if( (  isset( $rDate['tz'] ) &&  // fix UTC-date if offset set
                             iCalUtilityFunctions::_isOffset( $rDate['tz'] ) &&
                           ( 'Z' != $rDate['tz'] ))
                       || (  isset( $content['params']['TZID'] ) &&
                             iCalUtilityFunctions::_isOffset( $content['params']['TZID'] ) &&
                           ( 'Z' != $content['params']['TZID'] ))) {
                        $offset = isset( $rDate['tz'] ) ? $rDate['tz'] : $content['params']['TZID'];
                        $date = mktime( (int)  $rDate['hour'],
                                        (int)  $rDate['min'],
                                        (int) ($rDate['sec'] + iCalUtilityFunctions::_tz2offset( $offset )),
                                        (int)  $rDate['month'],
                                        (int)  $rDate['day'],
                                        (int)  $rDate['year'] );
                        unset( $rDate['tz'] );
                        $rDate = iCalUtilityFunctions::_date_time_string( date( 'Ymd\THis\Z', $date ), 6 );
                        unset( $rDate['unparsedtext'] );
                      }
                    }
                  }
                  unset( $content['params']['VALUE'] );
                  _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
                }
                break;
              case 'x-prop':
                while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
                  _addXMLchild( $properties, $content[0], 'unknown', $content[1]['value'], $content[1]['params'] );
                break;
              case 'action':      // single occurence below, if set
              case 'description':
              case 'summary':
                if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
                  if(( 'action' != $prop ) && !isset( $content['params']['LANGUAGE'] )) {
                    if( $langComp )
                      $content['params']['LANGUAGE'] = $langComp;
                    elseif( $langCal )
                      $content['params']['LANGUAGE'] = $langCal;
                  }
                  _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
                }
                break;
              case 'dtstart':
                if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
                  unset( $content['value']['tz'], $content['params']['VALUE'] ); // always local time
                  _addXMLchild( $properties, $prop, 'date-time', $content['value'], $content['params'] );
                }
                break;
              case 'duration':
                if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
                  _addXMLchild( $properties, $prop, 'duration', $content['value'], $content['params'] );
                break;
              case 'repeat':
                if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
                  _addXMLchild( $properties, $prop, 'integer', $content['value'], $content['params'] );
                break;
              case 'trigger':
                if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
                  if( isset( $content['value']['year'] )   &&
                      isset( $content['value']['month'] )  &&
                      isset( $content['value']['day'] ))
                    $type = 'date-time';
                  else
                    $type = 'duration';
                  _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
                }
                break;
              case 'tzoffsetto':
              case 'tzoffsetfrom':
                if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
                  _addXMLchild( $properties, $prop, 'utc-offset', $content['value'], $content['params'] );
                break;
              case 'rrule':
                while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
                  _addXMLchild( $properties, $prop, 'recur', $content['value'], $content['params'] );
                break;
            } // switch( $prop )
          } // end foreach( $subCompProps as $prop )
        } // end while( FALSE !== ( $subcomp = $component->getComponent( subCompName )))
      } // end foreach( $subCombs as $subCompName )
    } // end while( FALSE !== ( $component = $calendar->getComponent( $compName )))
  } // end foreach( $comps as $compName)
  return $xml->asXML();
}
/**
 * Add children to a SimpleXMLelement
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.1 - 2012-01-16
 * @param object $parent,  reference to a SimpleXMLelement node
 * @param string $name,    new element node name
 * @param string $type,    content type, subelement(-s) name
 * @param string $content, new subelement content
 * @param array  $params,  new element 'attributes'
 * @return void
 */
function _addXMLchild( & $parent, $name, $type, $content, $params=array()) {
            /** create new child node */
  $child = $parent->addChild( strtolower( $name ));
            /** fix attributes */
  if( is_array( $content ) && isset( $content['fbtype'] )) {
    $params['FBTYPE'] = $content['fbtype'];
    unset( $content['fbtype'] );
  }
  if( isset( $params['VALUE'] ))
    unset( $params['VALUE'] );
  if(( 'trigger' == $name ) && ( 'duration' == $type ) && ( TRUE !== $content['relatedStart'] ))
    $params['RELATED'] = 'END';
  if( !empty( $params )) {
    $parameters = $child->addChild( 'parameters' );
    foreach( $params as $param => $parVal ) {
      $param = strtolower( $param );
      if( 'x-' == substr( $param, 0, 2  )) {
        $p1 = $parameters->addChild( $param );
        $p2 = $p1->addChild( 'unknown', htmlspecialchars( $parVal ));
      }
      else {
        $p1 = $parameters->addChild( $param );
        switch( $param ) {
          case 'altrep':
          case 'dir':            $ptype = 'uri';            break;
          case 'delegated-from':
          case 'delegated-to':
          case 'member':
          case 'sent-by':        $ptype = 'cal-address';    break;
          case 'rsvp':           $ptype = 'boolean';        break ;
          default:               $ptype = 'text';           break;
        }
        if( is_array( $parVal )) {
          foreach( $parVal as $pV )
            $p2 = $p1->addChild( $ptype, htmlspecialchars( $pV ));
        }
        else
          $p2 = $p1->addChild( $ptype, htmlspecialchars( $parVal ));
      }
    }
  }
  if( empty( $content ) && ( '0' != $content ))
    return;
            /** store content */
  switch( $type ) {
    case 'binary':
      $v = $child->addChild( $type, $content );
      break;
    case 'boolean':
      break;
    case 'cal-address':
      $v = $child->addChild( $type, $content );
      break;
    case 'date':
      if( array_key_exists( 'year', $content ))
        $content = array( $content );
      foreach( $content as $date ) {
        $str = sprintf( '%04d-%02d-%02d', $date['year'], $date['month'], $date['day'] );
        $v = $child->addChild( $type, $str );
      }
      break;
    case 'date-time':
      if( array_key_exists( 'year', $content ))
        $content = array( $content );
      foreach( $content as $dt ) {
        if( !isset( $dt['hour'] )) $dt['hour'] = 0;
        if( !isset( $dt['min'] ))  $dt['min']  = 0;
        if( !isset( $dt['sec'] ))  $dt['sec']  = 0;
        $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02d', $dt['year'], $dt['month'], $dt['day'], $dt['hour'], $dt['min'], $dt['sec'] );
        if( isset( $dt['tz'] ) && ( 'Z' == $dt['tz'] ))
          $str .= 'Z';
        $v = $child->addChild( $type, $str );
      }
      break;
    case 'duration':
      $output = (( 'trigger' == $name ) && ( FALSE !== $content['before'] )) ? '-' : '';
      $v = $child->addChild( $type, $output.iCalUtilityFunctions::_format_duration( $content ) );
      break;
    case 'geo':
      $v1 = $child->addChild( 'latitude',  number_format( (float) $content['latitude'],  6, '.', '' ));
      $v1 = $child->addChild( 'longitude', number_format( (float) $content['longitude'], 6, '.', '' ));
      break;
    case 'integer':
      $v = $child->addChild( $type, $content );
      break;
    case 'period':
      if( !is_array( $content ))
        break;
      foreach( $content as $period ) {
        $v1 = $child->addChild( $type );
        $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02d', $period[0]['year'], $period[0]['month'], $period[0]['day'], $period[0]['hour'], $period[0]['min'], $period[0]['sec'] );
        if( isset( $period[0]['tz'] ) && ( 'Z' == $period[0]['tz'] ))
          $str .= 'Z';
        $v2 = $v1->addChild( 'start', $str );
        if( array_key_exists( 'year', $period[1] )) {
          $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02d', $period[1]['year'], $period[1]['month'], $period[1]['day'], $period[1]['hour'], $period[1]['min'], $period[1]['sec'] );
          if( isset($period[1]['tz'] ) && ( 'Z' == $period[1]['tz'] ))
            $str .= 'Z';
          $v2 = $v1->addChild( 'end', $str );
        }
        else
          $v2 = $v1->addChild( 'duration', iCalUtilityFunctions::_format_duration( $period[1] ));
      }
      break;
    case 'recur':
      foreach( $content as $rulelabel => $rulevalue ) {
        $rulelabel = strtolower( $rulelabel );
        switch( $rulelabel ) {
          case 'until':
            if( isset( $rulevalue['hour'] ))
              $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02dZ', $rulevalue['year'], $rulevalue['month'], $rulevalue['day'], $rulevalue['hour'], $rulevalue['min'], $rulevalue['sec'] );
            else
              $str = sprintf( '%04d-%02d-%02d', $rulevalue['year'], $rulevalue['month'], $rulevalue['day'] );
            $v = $child->addChild( $rulelabel, $str );
            break;
          case 'bysecond':
          case 'byminute':
          case 'byhour':
          case 'bymonthday':
          case 'byyearday':
          case 'byweekno':
          case 'bymonth':
          case 'bysetpos': {
            if( is_array( $rulevalue )) {
              foreach( $rulevalue as $vix => $valuePart )
                $v = $child->addChild( $rulelabel, $valuePart );
            }
            else
              $v = $child->addChild( $rulelabel, $rulevalue );
            break;
          }
          case 'byday': {
            if( isset( $rulevalue['DAY'] )) {
              $str  = ( isset( $rulevalue[0] )) ? $rulevalue[0] : '';
              $str .= $rulevalue['DAY'];
              $p    = $child->addChild( $rulelabel, $str );
            }
            else {
              foreach( $rulevalue as $valuePart ) {
                if( isset( $valuePart['DAY'] )) {
                  $str  = ( isset( $valuePart[0] )) ? $valuePart[0] : '';
                  $str .= $valuePart['DAY'];
                  $p    = $child->addChild( $rulelabel, $str );
                }
                else
                  $p    = $child->addChild( $rulelabel, $valuePart );
              }
            }
            break;
          }
          case 'freq':
          case 'count':
          case 'interval':
          case 'wkst':
          default:
            $p = $child->addChild( $rulelabel, $rulevalue );
            break;
        } // end switch( $rulelabel )
      } // end foreach( $content as $rulelabel => $rulevalue )
      break;
    case 'rstatus':
      $v = $child->addChild( 'code', number_format( (float) $content['statcode'], 2, '.', ''));
      $v = $child->addChild( 'description', htmlspecialchars( $content['text'] ));
      if( isset( $content['extdata'] ))
        $v = $child->addChild( 'data', htmlspecialchars( $content['extdata'] ));
      break;
    case 'text':
      if( !is_array( $content ))
        $content = array( $content );
      foreach( $content as $part )
        $v = $child->addChild( $type, htmlspecialchars( $part ));
      break;
    case 'time':
      break;
    case 'uri':
      $v = $child->addChild( $type, $content );
      break;
    case 'utc-offset':
      if( in_array( substr( $content, 0, 1 ), array( '-', '+' ))) {
        $str     = substr( $content, 0, 1 );
        $content = substr( $content, 1 );
      }
      else
        $str     = '+';
      $str .= substr( $content, 0, 2 ).':'.substr( $content, 2, 2 );
      if( 4 < strlen( $content ))
        $str .= ':'.substr( $content, 4 );
      $v = $child->addChild( $type, $str );
      break;
    case 'unknown':
    default:
      if( is_array( $content ))
        $content = implode( '', $content );
      $v = $child->addChild( 'unknown', htmlspecialchars( $content ));
      break;
  }
}
/**
 * parse xml string into iCalcreator instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.2 - 2012-01-31
 * @param  string $xmlstr
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixed  iCalcreator instance or FALSE on error
 */
function & XMLstr2iCal( $xmlstr, $iCalcfg=array()) {
  libxml_use_internal_errors( TRUE );
  $xml = simplexml_load_string( $xmlstr );
  if( !$xml ) {
    $str    = '';
    $return = FALSE;
    foreach( libxml_get_errors() as $error ) {
      switch ( $error->level ) {
        case LIBXML_ERR_FATAL:   $str .= ' FATAL ';   break;
        case LIBXML_ERR_ERROR:   $str .= ' ERROR ';   break;
        case LIBXML_ERR_WARNING:
        default:                 $str .= ' WARNING '; break;
      }
      $str .= PHP_EOL.'Error when loading XML';
      if( !empty( $error->file ))
        $str .= ',  file:'.$error->file.', ';
      $str .= ', line:'.$error->line;
      $str .= ', ('.$error->code.') '.$error->message;
    }
    error_log( $str );
    if( LIBXML_ERR_WARNING != $error->level )
      return $return;
    libxml_clear_errors();
  }
  return xml2iCal( $xml, $iCalcfg );
}
/**
 * parse xml file into iCalcreator instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since  2.11.2 - 2012-01-20
 * @param  string $xmlfile
 * @param  array$iCalcfg iCalcreator config array (opt)
 * @return mixediCalcreator instance or FALSE on error
 */
function & XMLfile2iCal( $xmlfile, $iCalcfg=array()) {
  libxml_use_internal_errors( TRUE );
  $xml = simplexml_load_file( $xmlfile );
  if( !$xml ) {
    $str = '';
    foreach( libxml_get_errors() as $error ) {
      switch ( $error->level ) {
        case LIBXML_ERR_FATAL:   $str .= 'FATAL ';   break;
        case LIBXML_ERR_ERROR:   $str .= 'ERROR ';   break;
        case LIBXML_ERR_WARNING:
        default:                 $str .= 'WARNING '; break;
      }
      $str .= 'Failed loading XML'.PHP_EOL;
      if( !empty( $error->file ))
        $str .= ' file:'.$error->file.', ';
      $str .= 'line:'.$error->line.PHP_EOL;
      $str .= '('.$error->code.') '.$error->message.PHP_EOL;
    }
    error_log( $str );
    if( LIBXML_ERR_WARNING != $error->level )
      return FALSE;
    libxml_clear_errors();
  }
  return xml2iCal( $xml, $iCalcfg );
}
/**
 * parse SimpleXMLElement instance into iCalcreator instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since  2.11.2 - 2012-01-27
 * @param  object $xmlobj  SimpleXMLElement
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixed  iCalcreator instance or FALSE on error
 */
function & XML2iCal( $xmlobj, $iCalcfg=array()) {
  $iCal = new vcalendar( $iCalcfg );
  foreach( $xmlobj->children() as $icalendar ) { // vcalendar
    foreach( $icalendar->children() as $calPart ) { // calendar properties and components
      if( 'components' == $calPart->getName()) {
        foreach( $calPart->children() as $component ) { // single components
          if( 0 < $component->count())
            _getXMLComponents( $iCal, $component );
        }
      }
      elseif(( 'properties' == $calPart->getName()) && ( 0 < $calPart->count())) {
        foreach( $calPart->children() as $calProp ) { // calendar properties
         $propName = $calProp->getName();
          if(( 'calscale' != $propName ) && ( 'method' != $propName ) && ( 'x-' != substr( $propName,0,2 )))
            continue;
          $params = array();
          foreach( $calProp->children() as $calPropElem ) { // single calendar property
            if( 'parameters' == $calPropElem->getName())
              $params = _getXMLParams( $calPropElem );
            else
              $iCal->setProperty( $propName, reset( $calPropElem ), $params );
          } // end foreach( $calProp->children() as $calPropElem )
        } // end foreach( $calPart->properties->children() as $calProp )
      } // end if( 0 < $calPart->properties->count())
    } // end foreach( $icalendar->children() as $calPart )
  } // end foreach( $xmlobj->children() as $icalendar )
  return $iCal;
}
/**
 * parse SimpleXMLElement instance property parameters and return iCalcreator property parameter array
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since  2.11.2 - 2012-01-15
 * @param  object $parameters SimpleXMLElement
 * @return array  iCalcreator property parameter array
 */
function _getXMLParams( & $parameters ) {
  if( 1 > $parameters->count())
    return array();
  $params = array();
  foreach( $parameters->children() as $parameter ) { // single parameter key
    $key   = strtoupper( $parameter->getName());
    $value = array();
    foreach( $parameter->children() as $paramValue ) // skip parameter value type
      $value[] = reset( $paramValue );
    if( 2 > count( $value ))
      $params[$key] = html_entity_decode( reset( $value ));
    else
      $params[$key] = $value;
  }
  return $params;
}
/**
 * parse SimpleXMLElement instance components, create iCalcreator component and update
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since  2.11.2 - 2012-01-15
 * @param  array  $iCal iCalcreator calendar instance
 * @param  object $component SimpleXMLElement
 * @return void
 */
function _getXMLComponents( & $iCal, & $component ) {
  $compName = $component->getName();
  $comp     = & $iCal->newComponent( $compName );
  $subComponents = array( 'valarm', 'standard', 'daylight' );
  foreach( $component->children() as $compPart ) { // properties and (opt) subComponents
    if( 1 > $compPart->count())
      continue;
    if( in_array( $compPart->getName(), $subComponents ))
      _getXMLComponents( $comp, $compPart );
    elseif( 'properties' == $compPart->getName()) {
      foreach( $compPart->children() as $property ) // properties as single property
        _getXMLProperties( $comp, $property );
    }
  } // end foreach( $component->children() as $compPart )
}
/**
 * parse SimpleXMLElement instance property, create iCalcreator component property
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since  2.11.2 - 2012-01-27
 * @param  array  $iCal iCalcreator calendar instance
 * @param  object $component SimpleXMLElement
 * @return void
 */
function _getXMLProperties( & $iCal, & $property ) {
  $propName  = $property->getName();
  $value     = $params = array();
  $valueType = '';
  foreach( $property->children() as $propPart ) { // calendar property parameters (opt) and value(-s)
    $valueType = $propPart->getName();
    if( 'parameters' == $valueType) {
      $params = _getXMLParams( $propPart );
      continue;
    }
    switch( $valueType ) {
      case 'binary':
        $value = reset( $propPart );
        break;
      case 'boolean':
        break;
      case 'cal-address':
        $value = reset( $propPart );
        break;
      case 'date':
        $params['VALUE'] = 'DATE';
      case 'date-time':
        if(( 'exdate' == $propName ) || ( 'rdate' == $propName ))
          $value[] = reset( $propPart );
        else
          $value = reset( $propPart );
        break;
      case 'duration':
        $value = reset( $propPart );
        break;
//        case 'geo':
      case 'latitude':
      case 'longitude':
        $value[$valueType] = reset( $propPart );
        break;
      case 'integer':
        $value = reset( $propPart );
        break;
      case 'period':
        if( 'rdate' == $propName )
          $params['VALUE'] = 'PERIOD';
        $pData = array();
        foreach( $propPart->children() as $periodPart )
          $pData[] = reset( $periodPart );
        if( !empty( $pData ))
          $value[] = $pData;
        break;
//        case 'rrule':
      case 'freq':
      case 'count':
      case 'until':
      case 'interval':
      case 'wkst':
        $value[$valueType] = reset( $propPart );
        break;
      case 'bysecond':
      case 'byminute':
      case 'byhour':
      case 'bymonthday':
      case 'byyearday':
      case 'byweekno':
      case 'bymonth':
      case 'bysetpos':
        $value[$valueType][] = reset( $propPart );
        break;
      case 'byday':
        $byday = reset( $propPart );
        if( 2 == strlen( $byday ))
          $value[$valueType][] = array( 'DAY' => $byday );
        else {
          $day = substr( $byday, -2 );
          $key = substr( $byday, 0, ( strlen( $byday ) - 2 ));
          $value[$valueType][] = array( $key, 'DAY' => $day );
        }
        break;
//      case 'rstatus':
      case 'code':
        $value[0] = reset( $propPart );
        break;
      case 'description':
        $value[1] = reset( $propPart );
        break;
      case 'data':
        $value[2] = reset( $propPart );
        break;
      case 'text':
        $text = str_replace( array( "\r\n", "\n\r", "\r", "\n"), '\n', reset( $propPart ));
        $value['text'][] = html_entity_decode( $text );
        break;
      case 'time':
        break;
      case 'uri':
        $value = reset( $propPart );
        break;
      case 'utc-offset':
        $value = str_replace( ':', '', reset( $propPart ));
        break;
      case 'unknown':
      default:
        $value = html_entity_decode( reset( $propPart ));
        break;
    } // end switch( $valueType )
  } // end  foreach( $property->children() as $propPart )
  if( 'freebusy' == $propName ) {
    $fbtype = $params['FBTYPE'];
    unset( $params['FBTYPE'] );
    $iCal->setProperty( $propName, $fbtype, $value, $params );
  }
  elseif( 'geo' == $propName )
    $iCal->setProperty( $propName, $value['latitude'], $value['longitude'], $params );
  elseif( 'request-status' == $propName ) {
    if( !isset( $value[2] ))
      $value[2] = FALSE;
    $iCal->setProperty( $propName, $value[0], $value[1], $value[2], $params );
  }
  else {
    if( isset( $value['text'] ) && is_array( $value['text'] )) {
      if(( 'categories' == $propName ) || ( 'resources' == $propName ))
        $value = $value['text'];
      else
        $value = reset( $value['text'] );
    }
    $iCal->setProperty( $propName, $value, $params );
  }
}
/**
 * Additional functions to use with vtimezone components
 * For use with
 * iCalcreator (kigkonsult.se/iCalcreator/index.php)
 * copyright (c) 2011 Yitzchok Lavi
 * icalcreator@onebigsystem.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/**
 * Additional functions to use with vtimezone components
 *
 * Before calling the functions, set time zone 'GMT' ('date_default_timezone_set')!
 *
 * @author Yitzchok Lavi <icalcreator@onebigsystem.com>
 *         adjusted for iCalcreator Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @version 1.0.2 - 2011-02-24
 *
 */
/**
 * Returns array with the offset information from UTC for a (UTC) datetime/timestamp in the
 * timezone, according to the VTIMEZONE information in the input array.
 *
 * $param array  $timezonesarray, output from function getTimezonesAsDateArrays (below)
 * $param string $tzid,           time zone identifier
 * $param mixed  $timestamp,      timestamp or a UTC datetime (in array format)
 * @return array, time zone data with keys for 'offsetHis', 'offsetSec' and 'tzname'
 *
 */
function getTzOffsetForDate($timezonesarray, $tzid, $timestamp) {
    if( is_array( $timestamp )) {
//$disp = sprintf( '%04d%02d%02d %02d%02d%02d', $timestamp['year'], $timestamp['month'], $timestamp['day'], $timestamp['hour'], $timestamp['min'], $timestamp['sec'] ); // test ###
      $timestamp = gmmktime(
            $timestamp['hour'],
            $timestamp['min'],
            $timestamp['sec'],
            $timestamp['month'],
            $timestamp['day'],
            $timestamp['year']
            ) ;
//echo '<td colspan="4">&nbsp;'."\n".'<tr><td>&nbsp;<td class="r">'.$timestamp.'<td class="r">'.$disp.'<td colspan="4">&nbsp;'."\n".'<tr><td colspan="3">&nbsp;'; // test ###
    }
    $tzoffset = array();
    // something to return if all goes wrong (such as if $tzid doesn't find us an array of dates)
    $tzoffset['offsetHis'] = '+0000';
    $tzoffset['offsetSec'] = 0;
    $tzoffset['tzname']    = '?';
    if( !isset( $timezonesarray[$tzid] ))
      return $tzoffset;
    $tzdatearray = $timezonesarray[$tzid];
    if ( is_array($tzdatearray) ) {
        sort($tzdatearray); // just in case
        if ( $timestamp < $tzdatearray[0]['timestamp'] ) {
            // our date is before the first change
            $tzoffset['offsetHis'] = $tzdatearray[0]['tzbefore']['offsetHis'] ;
            $tzoffset['offsetSec'] = $tzdatearray[0]['tzbefore']['offsetSec'] ;
            $tzoffset['tzname']    = $tzdatearray[0]['tzbefore']['offsetHis'] ; // we don't know the tzname in this case
        } elseif ( $timestamp >= $tzdatearray[count($tzdatearray)-1]['timestamp'] ) {
            // our date is after the last change (we do this so our scan can stop at the last record but one)
            $tzoffset['offsetHis'] = $tzdatearray[count($tzdatearray)-1]['tzafter']['offsetHis'] ;
            $tzoffset['offsetSec'] = $tzdatearray[count($tzdatearray)-1]['tzafter']['offsetSec'] ;
            $tzoffset['tzname']    = $tzdatearray[count($tzdatearray)-1]['tzafter']['tzname'] ;
        } else {
            // our date somewhere in between
            // loop through the list of dates and stop at the one where the timestamp is before our date and the next one is after it
            // we don't include the last date in our loop as there isn't one after it to check
            for ( $i = 0 ; $i <= count($tzdatearray)-2 ; $i++ ) {
                if(( $timestamp >= $tzdatearray[$i]['timestamp'] ) && ( $timestamp < $tzdatearray[$i+1]['timestamp'] )) {
                    $tzoffset['offsetHis'] = $tzdatearray[$i]['tzafter']['offsetHis'] ;
                    $tzoffset['offsetSec'] = $tzdatearray[$i]['tzafter']['offsetSec'] ;
                    $tzoffset['tzname']    = $tzdatearray[$i]['tzafter']['tzname'] ;
                    break;
                }
            }
        }
    }
    return $tzoffset;
}
/**
 * Returns an array containing all the timezone data in the vcalendar object
 *
 * @param object $vcalendar, iCalcreator calendar instance
 * @return array, time zone transition timestamp, array before(offsetHis, offsetSec), array after(offsetHis, offsetSec, tzname)
 *                based on the timezone data in the vcalendar object
 *
 */
function getTimezonesAsDateArrays($vcalendar) {
    $timezonedata = array();
    while( $vtz = $vcalendar->getComponent( 'vtimezone' )) {
        $tzid       = $vtz->getProperty('tzid');
        $alltzdates = array();
        while ( $vtzc = $vtz->getComponent( 'standard' )) {
            $newtzdates = expandTimezoneDates($vtzc);
            $alltzdates = array_merge($alltzdates, $newtzdates);
        }
        while ( $vtzc = $vtz->getComponent( 'daylight' )) {
            $newtzdates = expandTimezoneDates($vtzc);
            $alltzdates = array_merge($alltzdates, $newtzdates);
        }
        sort($alltzdates);
        $timezonedata[$tzid] = $alltzdates;
    }
    return $timezonedata;
}
/**
 * Returns an array containing time zone data from vtimezone standard/daylight instances
 *
 * @param object $vtzc, an iCalcreator calendar standard/daylight instance
 * @return array, time zone data; array before(offsetHis, offsetSec), array after(offsetHis, offsetSec, tzname)
 *
 */
function expandTimezoneDates($vtzc) {
    $tzdates = array();
    // prepare time zone "description" to attach to each change
    $tzbefore = array();
    $tzbefore['offsetHis']  = $vtzc->getProperty('tzoffsetfrom') ;
    $tzbefore['offsetSec'] = iCalUtilityFunctions::_tz2offset($tzbefore['offsetHis']);
    if(( '-' != substr( (string) $tzbefore['offsetSec'], 0, 1 )) && ( '+' != substr( (string) $tzbefore['offsetSec'], 0, 1 )))
      $tzbefore['offsetSec'] = '+'.$tzbefore['offsetSec'];
    $tzafter = array();
    $tzafter['offsetHis']   = $vtzc->getProperty('tzoffsetto') ;
    $tzafter['offsetSec']  = iCalUtilityFunctions::_tz2offset($tzafter['offsetHis']);
    if(( '-' != substr( (string) $tzafter['offsetSec'], 0, 1 )) && ( '+' != substr( (string) $tzafter['offsetSec'], 0, 1 )))
      $tzafter['offsetSec'] = '+'.$tzafter['offsetSec'];
    if( FALSE === ( $tzafter['tzname'] = $vtzc->getProperty('tzname')))
      $tzafter['tzname'] = $tzafter['offsetHis'];
    // find out where to start from
    $dtstart = $vtzc->getProperty('dtstart');
    $dtstarttimestamp = mktime(
            $dtstart['hour'],
            $dtstart['min'],
            $dtstart['sec'],
            $dtstart['month'],
            $dtstart['day'],
            $dtstart['year']
            ) ;
    if( !isset( $dtstart['unparsedtext'] )) // ??
      $dtstart['unparsedtext'] = sprintf( '%04d%02d%02dT%02d%02d%02d', $dtstart['year'], $dtstart['month'], $dtstart['day'], $dtstart['hour'], $dtstart['min'], $dtstart['sec'] );
    if ( $dtstarttimestamp == 0 ) {
        // it seems that the dtstart string may not have parsed correctly
        // let's set a timestamp starting from 1902, using the time part of the original string
        // so that the time will change at the right time of day
        // at worst we'll get midnight again
        $origdtstartsplit = explode('T',$dtstart['unparsedtext']) ;
        $dtstarttimestamp = strtotime("19020101",0);
        $dtstarttimestamp = strtotime($origdtstartsplit[1],$dtstarttimestamp);
    }
    // the date (in dtstart and opt RDATE/RRULE) is ALWAYS LOCAL (not utc!!), adjust from 'utc' to 'local' timestamp
    $diff  = -1 * $tzbefore['offsetSec'];
    $dtstarttimestamp += $diff;
                // add this (start) change to the array of changes
    $tzdates[] = array(
        'timestamp' => $dtstarttimestamp,
        'tzbefore'  => $tzbefore,
        'tzafter'   => $tzafter
        );
    $datearray = getdate($dtstarttimestamp);
    // save original array to use time parts, because strtotime (used below) apparently loses the time
    $changetime = $datearray ;
    // generate dates according to an RRULE line
    $rrule = $vtzc->getProperty('rrule') ;
    if ( is_array($rrule) ) {
        if ( $rrule['FREQ'] == 'YEARLY' ) {
            // calculate transition dates starting from DTSTART
            $offsetchangetimestamp = $dtstarttimestamp;
            // calculate transition dates until 10 years in the future
            $stoptimestamp = strtotime("+10 year",time());
            // if UNTIL is set, calculate until then (however far ahead)
            if ( isset( $rrule['UNTIL'] ) && ( $rrule['UNTIL'] != '' )) {
                $stoptimestamp = mktime(
                    $rrule['UNTIL']['hour'],
                    $rrule['UNTIL']['min'],
                    $rrule['UNTIL']['sec'],
                    $rrule['UNTIL']['month'],
                    $rrule['UNTIL']['day'],
                    $rrule['UNTIL']['year']
                    ) ;
            }
            $count = 0 ;
            $stopcount = isset( $rrule['COUNT'] ) ? $rrule['COUNT'] : 0 ;
            $daynames = array(
                        'SU' => 'Sunday',
                        'MO' => 'Monday',
                        'TU' => 'Tuesday',
                        'WE' => 'Wednesday',
                        'TH' => 'Thursday',
                        'FR' => 'Friday',
                        'SA' => 'Saturday'
                        );
            // repeat so long as we're between DTSTART and UNTIL, or we haven't prepared COUNT dates
            while ( $offsetchangetimestamp < $stoptimestamp && ( $stopcount == 0 || $count < $stopcount ) ) {
                // break up the timestamp into its parts
                $datearray = getdate($offsetchangetimestamp);
                if ( isset( $rrule['BYMONTH'] ) && ( $rrule['BYMONTH'] != 0 )) {
                    // set the month
                    $datearray['mon'] = $rrule['BYMONTH'] ;
                }
                if ( isset( $rrule['BYMONTHDAY'] ) && ( $rrule['BYMONTHDAY'] != 0 )) {
                    // set specific day of month
                    $datearray['mday']  = $rrule['BYMONTHDAY'];
                } elseif ( is_array($rrule['BYDAY']) ) {
                    // find the Xth WKDAY in the month
                    // the starting point for this process is the first of the month set above
                    $datearray['mday'] = 1 ;
                    // turn $datearray as it is now back into a timestamp
                    $offsetchangetimestamp = mktime(
                        $datearray['hours'],
                        $datearray['minutes'],
                        $datearray['seconds'],
                        $datearray['mon'],
                        $datearray['mday'],
                        $datearray['year']
                            );
                    if ($rrule['BYDAY'][0] > 0) {
                        // to find Xth WKDAY in month, we find last WKDAY in month before
                        // we do that by finding first WKDAY in this month and going back one week
                        // then we add X weeks (below)
                        $offsetchangetimestamp = strtotime($daynames[$rrule['BYDAY']['DAY']],$offsetchangetimestamp);
                        $offsetchangetimestamp = strtotime("-1 week",$offsetchangetimestamp);
                    } else {
                        // to find Xth WKDAY before the end of the month, we find the first WKDAY in the following month
                        // we do that by going forward one month and going to WKDAY there
                        // then we subtract X weeks (below)
                        $offsetchangetimestamp = strtotime("+1 month",$offsetchangetimestamp);
                        $offsetchangetimestamp = strtotime($daynames[$rrule['BYDAY']['DAY']],$offsetchangetimestamp);
                    }
                    // now move forward or back the appropriate number of weeks, into the month we want
                    $offsetchangetimestamp = strtotime($rrule['BYDAY'][0] . " week",$offsetchangetimestamp);
                    $datearray = getdate($offsetchangetimestamp);
                }
                // convert the date parts back into a timestamp, setting the time parts according to the
                // original time data which we stored
                $offsetchangetimestamp = mktime(
                    $changetime['hours'],
                    $changetime['minutes'],
                    $changetime['seconds'] + $diff,
                    $datearray['mon'],
                    $datearray['mday'],
                    $datearray['year']
                        );
                // add this change to the array of changes
                $tzdates[] = array(
                    'timestamp' => $offsetchangetimestamp,
                    'tzbefore'  => $tzbefore,
                    'tzafter'   => $tzafter
                    );
                // update counters (timestamp and count)
                $offsetchangetimestamp = strtotime("+" . (( isset( $rrule['INTERVAL'] ) && ( $rrule['INTERVAL'] != 0 )) ? $rrule['INTERVAL'] : 1 ) . " year",$offsetchangetimestamp);
                $count += 1 ;
            }
        }
    }
    // generate dates according to RDATE lines
    while ($rdates = $vtzc->getProperty('rdate')) {
        if ( is_array($rdates) ) {

            foreach ( $rdates as $rdate ) {
                // convert the explicit change date to a timestamp
                $offsetchangetimestamp = mktime(
                        $rdate['hour'],
                        $rdate['min'],
                        $rdate['sec'] + $diff,
                        $rdate['month'],
                        $rdate['day'],
                        $rdate['year']
                        ) ;
                // add this change to the array of changes
                $tzdates[] = array(
                    'timestamp' => $offsetchangetimestamp,
                    'tzbefore'  => $tzbefore,
                    'tzafter'   => $tzafter
                    );
            }
        }
    }
    return $tzdates;
}

/*********************************************************************************/
/*          iCalcreator vCard helper functions                                   */
/*********************************************************************************/
/**
 * convert single ATTENDEE, CONTACT or ORGANIZER (in email format) to vCard
 * returns vCard/TRUE or if directory (if set) or file write is unvalid, FALSE
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.2 - 2012-07-11
 * @param object $email
 * $param string $version, vCard version (default 2.1)
 * $param string $directory, where to save vCards (default FALSE)
 * $param string $ext, vCard file extension (default 'vcf')
 * @return mixed
 */
function iCal2vCard( $email, $version='2.1', $directory=FALSE, $ext='vcf' ) {
  if( FALSE === ( $pos = strpos( $email, '@' )))
    return FALSE;
  if( $directory ) {
    if( DIRECTORY_SEPARATOR != substr( $directory, ( 0 - strlen( DIRECTORY_SEPARATOR ))))
      $directory .= DIRECTORY_SEPARATOR;
    if( !is_dir( $directory ) || !is_writable( $directory ))
      return FALSE;
  }
            /* prepare vCard */
  $email  = str_replace( 'MAILTO:', '', $email );
  $name   = $person = substr( $email, 0, $pos );
  if( ctype_upper( $name ) || ctype_lower( $name ))
    $name = array( $name );
  else {
    if( FALSE !== ( $pos = strpos( $name, '.' ))) {
      $name = explode( '.', $name );
      foreach( $name as $k => $part )
        $name[$k] = ucfirst( $part );
    }
    else { // split camelCase
      $chars = $name;
      $name  = array( $chars[0] );
      $k     = 0;
      $x     = 1;
      while( FALSE !== ( $char = substr( $chars, $x, 1 ))) {
        if( ctype_upper( $char )) {
          $k += 1;
          $name[$k] = '';
        }
        $name[$k]  .= $char;
        $x++;
      }
    }
  }
  $nl     = "\r\n";
  $FN     = 'FN:'.implode( ' ', $name ).$nl;
  $name   = array_reverse( $name );
  $N      = 'N:'.array_shift( $name );
  $scCnt  = 0;
  while( NULL != ( $part = array_shift( $name ))) {
    if(( '4.0' != $version ) || ( 4 > $scCnt ))
      $scCnt += 1;
    $N   .= ';'.$part;
  }
  while(( '4.0' == $version ) && ( 4 > $scCnt )) {
    $N   .= ';';
    $scCnt += 1;
  }
  $N     .= $nl;
  $EMAIL  = 'EMAIL:'.$email.$nl;
           /* create vCard */
  $vCard  = 'BEGIN:VCARD'.$nl;
  $vCard .= "VERSION:$version$nl";
  $vCard .= 'PRODID:-//kigkonsult.se '.ICALCREATOR_VERSION."//$nl";
  $vCard .= $N;
  $vCard .= $FN;
  $vCard .= $EMAIL;
  $vCard .= 'REV:'.gmdate( 'Ymd\THis\Z' ).$nl;
  $vCard .= 'END:VCARD'.$nl;
            /* save each vCard as (unique) single file */
  if( $directory ) {
    $fname = $directory.preg_replace( '/[^a-z0-9.]/i', '', $email );
    $cnt   = 1;
    $dbl   = '';
    while( is_file ( $fname.$dbl.'.'.$ext )) {
      $cnt += 1;
      $dbl = "_$cnt";
    }
    if( FALSE === file_put_contents( $fname, $fname.$dbl.'.'.$ext ))
      return FALSE;
    return TRUE;
  }
            /* return vCard */
  else
    return $vCard;
}
/**
 * convert ATTENDEEs, CONTACTs and ORGANIZERs (in email format) to vCards
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.2 - 2012-05-07
 * @param object $calendar, iCalcreator vcalendar instance reference
 * $param string $version, vCard version (default 2.1)
 * $param string $directory, where to save vCards (default FALSE)
 * $param string $ext, vCard file extension (default 'vcf')
 * @return mixed
 */
function iCal2vCards( & $calendar, $version='2.1', $directory=FALSE, $ext='vcf' ) {
  $hits   = array();
  $vCardP = array( 'ATTENDEE', 'CONTACT', 'ORGANIZER' );
  foreach( $vCardP as $prop ) {
    $hits2 = $calendar->getProperty( $prop );
    foreach( $hits2 as $propValue => $occCnt ) {
      if( FALSE === ( $pos = strpos( $propValue, '@' )))
        continue;
      $propValue = str_replace( 'MAILTO:', '', $propValue );
      if( isset( $hits[$propValue] ))
        $hits[$propValue] += $occCnt;
      else
        $hits[$propValue]  = $occCnt;
    }
  }
  if( empty( $hits ))
    return FALSE;
  ksort( $hits );
  $output   = '';
  foreach( $hits as $email => $skip ) {
    $res = iCal2vCard( $email, $version, $directory, $ext );
    if( $directory && !$res )
      return FALSE;
    elseif( !$res )
      return $res;
    else
      $output .= $res;
  }
  if( $directory )
    return TRUE;
  if( !empty( $output ))
    return $output;
  return FALSE;
}
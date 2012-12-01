<?php
/*********************************************************************************/
/**
 * vcalendar class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 */
class vcalendar {
            //  calendar property variables
	/** @var string GREGORIAN is the only scale recognized by RFC5545 */
  var $calscale;
  /** @var string one of PUBLISH, REQUEST , REFRESH, CANCEL, ADD, REPLY, COUNTER, DECLINECOUNTER */
  var $method;
  /**
	 * Property Name: PRODID
	 *
	 *  The identifier is RECOMMENDED to be the identical syntax to the
	 * [RFC 822] addr-spec. A good method to assure uniqueness is to put the
	 * domain name or a domain literal IP address of the host on which.. .
	 * 
	 * Conformance: The property MUST be specified once in an iCalendar object.
	 * Description: The vendor of the implementation SHOULD assure that this
	 * is a globally unique identifier; using some technique such as an FPI
	 * value, as defined in [ISO 9070].
	 * 
	 * @var string
	 */
  var $prodid;
  /**
	 * Property Name: VERSION
	 *
	 * Description: A value of "2.0" corresponds to this memo.
	 * 
	 * @var string
	 */
  var $version;
  /**
	 * A dictionary of calendar Properties.
	 * 
	 * The key is the name of the property, the value is itself dictionary of 
	 * 'value' and 'params' keys.
	 * 
	 * array(
	 *   'X-PROP' => array(
	 *                   'value' => 3,
	 *                   'params' => 'foo'
	 *               );
	 * );
	 * 
	 * @var array calendar property variables
	 */
  var $xprop;
            //  container for calendar components
  var $components;
            //  component config variables
  var $allowEmpty;
  var $unique_id;
  var $language;
  var $directory;
  var $filename;
  var $url;
  var $delimiter;
  var $nl;
  var $format;
  var $dtzid;
            //  component internal variables
  var $attributeDelimiter;
  var $valueInit;
            //  component xCal declaration container
  var $xcaldecl;
/**
 * constructor for calendar object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 * @param array $config
 * @return void
 */
  function vcalendar ( $config = array()) {
    $this->_makeVersion();
    $this->calscale   = null;
    $this->method     = null;
    $this->_makeUnique_id();
    $this->prodid     = null;
    $this->xprop      = array();
    $this->language   = null;
    $this->directory  = null;
    $this->filename   = null;
    $this->url        = null;
    $this->dtzid      = null;
/**
 *   language = <Text identifying a language, as defined in [RFC 1766]>
 */
    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

    $this->xcaldecl   = array();
    $this->components = array();
	$this->componentHolder = new componentHolder();
  }
/*********************************************************************************/
/**
 * Property Name: CALSCALE
 */
function getCalscale()
{
	return $this->calscale;
}
/**
 * set calendar property calscale
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @param string $value
 * @return void
 */
  function setCalscale( $value ) {
    if( empty( $value )) return FALSE;
    $this->calscale = $value;
  }
/*********************************************************************************/
/**
 * Property Name: METHOD
 */
function getMethod(){
	return $this->method;
}
/**
 * set calendar property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-20-23
 * @param string $value
 * @return bool
 */
  function setMethod( $value ) {
    if( empty( $value )) return FALSE;
    $this->method = $value;
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: PRODID
 *
 *  The identifier is RECOMMENDED to be the identical syntax to the
 * [RFC 822] addr-spec. A good method to assure uniqueness is to put the
 * domain name or a domain literal IP address of the host on which.. .
 */
/**
 * creates formatted output for calendar property prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.11 - 2012-05-13
 * @return string
 */
  function createProdid() {
    if( !isset( $this->prodid ))
      $this->_makeProdid();
    return $this->prodid;
  }
/**
 * make default value for calendar prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.8 - 2009-12-30
 * @return void
 */
  function _makeProdid() {
    $this->prodid  = '-//'.$this->unique_id.'//NONSGML kigkonsult.se '.ICALCREATOR_VERSION.'//'.strtoupper( $this->language );
  }
/**
 * Conformance: The property MUST be specified once in an iCalendar object.
 * Description: The vendor of the implementation SHOULD assure that this
 * is a globally unique identifier; using some technique such as an FPI
 * value, as defined in [ISO 9070].
 */
/**
 * make default unique_id for calendar prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.3.0 - 2006-08-10
 * @return void
 */
  function _makeUnique_id() {
    $this->unique_id  = ( isset( $_SERVER['SERVER_NAME'] )) ? gethostbyname( $_SERVER['SERVER_NAME'] ) : 'localhost';
  }
/*********************************************************************************/
/**
 * Property Name: VERSION
 *
 * Description: A value of "2.0" corresponds to this memo.
 */
function getVersion()
{
	if( empty( $this->version ))
      $this->_makeVersion();
	return $this->version;
}
/**
 * set default calendar version
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.3.0 - 2006-08-10
 * @return void
 */
  function _makeVersion() {
    $this->version = '2.0';
  }
/**
 * set calendar version
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-23
 * @param string $value
 * @return void
 */
  function setVersion( $value ) {
    if( empty( $value )) return FALSE;
    $this->version = $value;
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: x-prop
 */
  function getXprop()
  {
	  return $this->xprop;
  }
/**
 * set calendar property x-prop
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.9 - 2012-01-16
 * @param string $label
 * @param string $value
 * @param array $params optional
 * @return bool
 */
  function setXprop( $label, $value, $params=FALSE ) {
    if( empty( $label ))
      return FALSE;
    if( 'X-' != strtoupper( substr( $label, 0, 2 )))
      return FALSE;
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = null; else return FALSE;
    $xprop           = array( 'value' => $value );
    $xprop['params'] = iCalUtilityFunctions::_setParams( $params );
    if( !is_array( $this->xprop )) $this->xprop = array();
    $this->xprop[strtoupper( $label )] = $xprop;
    return TRUE;
  }
/*********************************************************************************/
/**
 * delete calendar property value
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $propName, bool FALSE => X-property
 * @param int $propix, optional, if specific property is wanted in case of multiply occurences
 * @return bool, if successfull delete
 */
  function deleteProperty( $propName=FALSE, $propix=FALSE ) {
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( !$propix )
      $propix = ( isset( $this->propdelix[$propName] ) && ( 'X-PROP' != $propName )) ? $this->propdelix[$propName] + 2 : 1;
    $this->propdelix[$propName] = --$propix;
    $return = FALSE;
    switch( $propName ) {
      case 'CALSCALE':
        if( isset( $this->calscale )) {
          $this->calscale = null;
          $return = TRUE;
        }
        break;
      case 'METHOD':
        if( isset( $this->method )) {
          $this->method   = null;
          $return = TRUE;
        }
        break;
      default:
        $reduced = array();
        if( $propName != 'X-PROP' ) {
          if( !isset( $this->xprop[$propName] )) { unset( $this->propdelix[$propName] ); return FALSE; }
          foreach( $this->xprop as $k => $a ) {
            if(( $k != $propName ) && !empty( $a ))
              $reduced[$k] = $a;
          }
        }
        else {
          if( count( $this->xprop ) <= $propix )  return FALSE;
          $xpropno = 0;
          foreach( $this->xprop as $xpropkey => $xpropvalue ) {
            if( $propix != $xpropno )
              $reduced[$xpropkey] = $xpropvalue;
            $xpropno++;
          }
        }
        $this->xprop = $reduced;
        if( empty( $this->xprop )) {
          unset( $this->propdelix[$propName] );
          return FALSE;
        }
        return TRUE;
    }
    return $return;
  }
/**
 * get calendar property value/params
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.13.4 - 2012-08-08
 * @param string $propName, optional
 * @param int $propix, optional, if specific property is wanted in case of multiply occurences
 * @param bool $inclParam=FALSE
 * @return mixed
 */
  function getProperty( $propName=FALSE, $propix=FALSE, $inclParam=FALSE ) {
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( 'X-PROP' == $propName ) {
      if( !$propix )
        $propix  = ( isset( $this->propix[$propName] )) ? $this->propix[$propName] + 2 : 1;
      $this->propix[$propName] = --$propix;
    }
    else
      $mProps    = array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO', 'RESOURCES' );
    switch( $propName ) {
      case 'ATTENDEE':
      case 'CATEGORIES':
      case 'CONTACT':
      case 'DTSTART':
      case 'GEOLOCATION':
      case 'LOCATION':
      case 'ORGANIZER':
      case 'PRIORITY':
      case 'RESOURCES':
      case 'STATUS':
      case 'SUMMARY':
      case 'RECURRENCE-ID-UID':
      case 'RELATED-TO':
      case 'R-UID':
      case 'UID':
      case 'URL':
        $output  = array();
        foreach ( $this->components as $cix => $component) {
          if( !in_array( $component->objName, array('vevent', 'vtodo', 'vjournal', 'vfreebusy' )))
            continue;
          if( in_array( strtoupper( $propName ), $mProps )) {
            $component->_getProperties( $propName, $output );
            continue;
          }
          elseif(( 3 < strlen( $propName )) && ( 'UID' == substr( $propName, -3 ))) {
            if( FALSE !== ( $content = $component->getProperty( 'RECURRENCE-ID' )))
              $content = $component->getProperty( 'UID' );
          }
          elseif( 'GEOLOCATION' == $propName ) {
            $content = $component->getProperty( 'LOCATION' );
            $content = ( !empty( $content )) ? $content.' ' : '';
            if(( FALSE === ( $geo     = $component->getProperty( 'GEO' ))) || empty( $geo ))
              continue;
            if( 0.0 < $geo['latitude'] )
              $sign   = '+';
            else
              $sign   = ( 0.0 > $geo['latitude'] ) ? '-' : '';
            $content .= ' '.$sign.sprintf( "%09.6f", abs( $geo['latitude'] ));
            $content  = rtrim( rtrim( $content, '0' ), '.' );
            if( 0.0 < $geo['longitude'] )
              $sign   = '+';
            else
              $sign   = ( 0.0 > $geo['longitude'] ) ? '-' : '';
            $content .= $sign.sprintf( '%8.6f', abs( $geo['longitude'] )).'/';
          }
          elseif( FALSE === ( $content = $component->getProperty( $propName )))
            continue;
          if(( FALSE === $content ) || empty( $content ))
            continue;
          elseif( is_array( $content )) {
            if( isset( $content['year'] )) {
              $key  = sprintf( '%04d%02d%02d', $content['year'], $content['month'], $content['day'] );
              if( !isset( $output[$key] ))
                $output[$key] = 1;
              else
                $output[$key] += 1;
            }
            else {
              foreach( $content as $partValue => $partCount ) {
                if( !isset( $output[$partValue] ))
                  $output[$partValue] = $partCount;
                else
                  $output[$partValue] += $partCount;
              }
            }
          } // end elseif( is_array( $content )) {
          elseif( !isset( $output[$content] ))
            $output[$content] = 1;
          else
            $output[$content] += 1;
        } // end foreach ( $this->components as $cix => $component)
        if( !empty( $output ))
          ksort( $output );
        return $output;
        break;
      case 'CALSCALE':
        return ( !empty( $this->calscale )) ? $this->calscale : FALSE;
        break;
      case 'METHOD':
        return ( !empty( $this->method )) ? $this->method : FALSE;
        break;
      case 'PRODID':
        if( empty( $this->prodid ))
          $this->_makeProdid();
        return $this->prodid;
        break;
      case 'VERSION':
        return ( !empty( $this->version )) ? $this->version : FALSE;
        break;
      default:
        if( $propName != 'X-PROP' ) {
          if( !isset( $this->xprop[$propName] )) return FALSE;
          return ( $inclParam ) ? array( $propName, $this->xprop[$propName] )
                                : array( $propName, $this->xprop[$propName]['value'] );
        }
        else {
          if( empty( $this->xprop )) return FALSE;
          $xpropno = 0;
          foreach( $this->xprop as $xpropkey => $xpropvalue ) {
            if( $propix == $xpropno )
              return ( $inclParam ) ? array( $xpropkey, $this->xprop[$xpropkey] )
                                    : array( $xpropkey, $this->xprop[$xpropkey]['value'] );
            else
              $xpropno++;
          }
          unset( $this->propix[$propName] );
          return FALSE; // not found ??
        }
    }
    return FALSE;
  }
/**
 * general vcalendar property setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.13 - 2007-11-04
 * @param mixed $args variable number of function arguments,
 *                    first argument is ALWAYS component name,
 *                    second ALWAYS component value!
 * @return bool
 */
  function setProperty () {
    $numargs    = func_num_args();
    if( 1 > $numargs )
      return FALSE;
    $arglist    = func_get_args();
    $arglist[0] = strtoupper( $arglist[0] );
    switch( $arglist[0] ) {
      case 'CALSCALE':
        return $this->setCalscale( $arglist[1] );
      case 'METHOD':
        return $this->setMethod( $arglist[1] );
      case 'VERSION':
        return $this->setVersion( $arglist[1] );
      default:
        if( !isset( $arglist[1] )) $arglist[1] = null;
        if( !isset( $arglist[2] )) $arglist[2] = null;
        return $this->setXprop( $arglist[0], $arglist[1], $arglist[2] );
    }
    return FALSE;
  }
/*********************************************************************************/
/**
 * get vcalendar config values or * calendar components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.7 - 2012-01-12
 * @param mixed $config
 * @return value
 */
  function getConfig( $config = FALSE ) {
    if( !$config ) {
      $return = array();
      $return['ALLOWEMPTY']  = $this->getConfig( 'ALLOWEMPTY' );
      $return['DELIMITER']   = $this->getConfig( 'DELIMITER' );
      $return['DIRECTORY']   = $this->getConfig( 'DIRECTORY' );
      $return['FILENAME']    = $this->getConfig( 'FILENAME' );
      $return['DIRFILE']     = $this->getConfig( 'DIRFILE' );
      $return['FILESIZE']    = $this->getConfig( 'FILESIZE' );
      $return['FORMAT']      = $this->getConfig( 'FORMAT' );
      if( FALSE !== ( $lang  = $this->getConfig( 'LANGUAGE' )))
        $return['LANGUAGE']  = $lang;
      $return['NEWLINECHAR'] = $this->getConfig( 'NEWLINECHAR' );
      $return['UNIQUE_ID']   = $this->getConfig( 'UNIQUE_ID' );
      if( FALSE !== ( $url   = $this->getConfig( 'URL' )))
        $return['URL']       = $url;
      $return['TZID']        = $this->getConfig( 'TZID' );
      return $return;
    }
    switch( strtoupper( $config )) {
      case 'ALLOWEMPTY':
        return $this->allowEmpty;
        break;
      case 'COMPSINFO':
        unset( $this->compix );
        $info = array();
        foreach( $this->components as $cix => $component ) {
          if( empty( $component )) continue;
          $info[$cix]['ordno'] = $cix + 1;
          $info[$cix]['type']  = $component->objName;
          $info[$cix]['uid']   = $component->getProperty( 'uid' );
          $info[$cix]['props'] = $component->getConfig( 'propinfo' );
          $info[$cix]['sub']   = $component->getConfig( 'compsinfo' );
        }
        return $info;
        break;
      case 'DELIMITER':
        return $this->delimiter;
        break;
      case 'DIRECTORY':
        if( empty( $this->directory ) && ( '0' != $this->directory ))
          $this->directory = '.';
        return $this->directory;
        break;
      case 'DIRFILE':
        return $this->getConfig( 'directory' ).$this->getConfig( 'delimiter' ).$this->getConfig( 'filename' );
        break;
      case 'FILEINFO':
        return array( $this->getConfig( 'directory' )
                    , $this->getConfig( 'filename' )
                    , $this->getConfig( 'filesize' ));
        break;
      case 'FILENAME':
        if( empty( $this->filename ) && ( '0' != $this->filename )) {
          if( 'xcal' == $this->format )
            $this->filename = date( 'YmdHis' ).'.xml'; // recommended xcs.. .
          else
            $this->filename = date( 'YmdHis' ).'.ics';
        }
        return $this->filename;
        break;
      case 'FILESIZE':
        $size    = 0;
        if( empty( $this->url )) {
          $dirfile = $this->getConfig( 'dirfile' );
          if( !is_file( $dirfile ) || ( FALSE === ( $size = filesize( $dirfile ))))
            $size = 0;
          clearstatcache();
        }
        return $size;
        break;
      case 'FORMAT':
        return ( $this->format == 'xcal' ) ? 'xCal' : 'iCal';
        break;
      case 'LANGUAGE':
         /* get language for calendar component as defined in [RFC 1766] */
        return $this->language;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        return $this->nl;
        break;
      case 'TZID':
        return $this->dtzid;
        break;
      case 'UNIQUE_ID':
        return $this->unique_id;
        break;
      case 'URL':
        if( !empty( $this->url ))
          return $this->url;
        else
          return FALSE;
        break;
    }
  }
/**
 * general vcalendar config setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.12 - 2012-05-13
 * @param mixed  $config
 * @param string $value
 * @return void
 */
  function setConfig( $config, $value = FALSE) {
    if( is_array( $config )) {
      $ak = array_keys( $config );
      foreach( $ak as $k ) {
        if( 'DIRECTORY' == strtoupper( $k )) {
          if( FALSE === $this->setConfig( 'DIRECTORY', $config[$k] ))
            return FALSE;
          unset( $config[$k] );
        }
        elseif( 'NEWLINECHAR' == strtoupper( $k )) {
          if( FALSE === $this->setConfig( 'NEWLINECHAR', $config[$k] ))
            return FALSE;
          unset( $config[$k] );
        }
      }
      foreach( $config as $cKey => $cValue ) {
        if( FALSE === $this->setConfig( $cKey, $cValue ))
          return FALSE;
      }
      return TRUE;
    }
    $res = FALSE;
    switch( strtoupper( $config )) {
      case 'ALLOWEMPTY':
        $this->allowEmpty = $value;
        $subcfg  = array( 'ALLOWEMPTY' => $value );
        $res = TRUE;
        break;
      case 'DELIMITER':
        $this->delimiter = $value;
        return TRUE;
        break;
      case 'DIRECTORY':
        $value   = trim( $value );
        $del     = $this->getConfig('delimiter');
        if( $del == substr( $value, ( 0 - strlen( $del ))))
          $value = substr( $value, 0, ( strlen( $value ) - strlen( $del )));
        if( is_dir( $value )) {
            /* local directory */
          clearstatcache();
          $this->directory = $value;
          $this->url       = null;
          return TRUE;
        }
        else
          return FALSE;
        break;
      case 'FILENAME':
        $value   = trim( $value );
        if( !empty( $this->url )) {
            /* remote directory+file -> URL */
          $this->filename = $value;
          return TRUE;
        }
        $dirfile = $this->getConfig( 'directory' ).$this->getConfig( 'delimiter' ).$value;
        if( file_exists( $dirfile )) {
            /* local file exists */
          if( is_readable( $dirfile ) || is_writable( $dirfile )) {
            clearstatcache();
            $this->filename = $value;
            return TRUE;
          }
          else
            return FALSE;
        }
        elseif( is_readable($this->getConfig( 'directory' ) ) || is_writable( $this->getConfig( 'directory' ) )) {
            /* read- or writable directory */
          $this->filename = $value;
          return TRUE;
        }
        else
          return FALSE;
        break;
      case 'FORMAT':
        $value   = trim( strtolower( $value ));
        if( 'xcal' == $value ) {
          $this->format             = 'xcal';
          $this->attributeDelimiter = $this->nl;
          $this->valueInit          = null;
        }
        else {
          $this->format             = null;
          $this->attributeDelimiter = ';';
          $this->valueInit          = ':';
        }
        $subcfg  = array( 'FORMAT' => $value );
        $res = TRUE;
        break;
      case 'LANGUAGE': // set language for calendar component as defined in [RFC 1766]
        $value   = trim( $value );
        $this->language = $value;
        $this->_makeProdid();
        $subcfg  = array( 'LANGUAGE' => $value );
        $res = TRUE;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        $this->nl = $value;
        if( 'xcal' == $value ) {
          $this->attributeDelimiter = $this->nl;
          $this->valueInit          = null;
        }
        else {
          $this->attributeDelimiter = ';';
          $this->valueInit          = ':';
        }
        $subcfg  = array( 'NL' => $value );
        $res = TRUE;
        break;
      case 'TZID':
        $this->dtzid = $value;
        $subcfg  = array( 'TZID' => $value );
        $res = TRUE;
        break;
      case 'UNIQUE_ID':
        $value   = trim( $value );
        $this->unique_id = $value;
        $this->_makeProdid();
        $subcfg  = array( 'UNIQUE_ID' => $value );
        $res = TRUE;
        break;
      case 'URL':
            /* remote file - URL */
        $value     = trim( $value );
        $value     = str_replace( 'HTTP://',   'http://', $value );
        $value     = str_replace( 'WEBCAL://', 'http://', $value );
        $value     = str_replace( 'webcal://', 'http://', $value );
        $this->url = $value;
        $this->directory = null;
        $parts     = pathinfo( $value );
        return $this->setConfig( 'filename',  $parts['basename'] );
        break;
      default:  // any unvalid config key.. .
        return TRUE;
    }
    if( !$res ) return FALSE;
    if( isset( $subcfg ) && !empty( $this->components )) {
      foreach( $subcfg as $cfgkey => $cfgvalue ) {
        foreach( $this->components as $cix => $component ) {
          $res = $component->setConfig( $cfgkey, $cfgvalue, TRUE );
          if( !$res )
            break 2;
          $this->components[$cix] = $component->copy(); // PHP4 compliant
        }
      }
    }
    return $res;
  }
/*********************************************************************************/
/**
 * add calendar component to container
 *
 * alias to setComponent
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 1.x.x - 2007-04-24
 * @param object $component calendar component
 * @return void
 */
  function addComponent( $component ) {
    $this->setComponent( $component );
  }
/**
 * delete calendar component from container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $arg1 ordno / component type / component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return void
 */
  function deleteComponent( $arg1, $arg2=FALSE  ) {
    return $this->componentHolder->deleteComponent( $arg1, $arg2 );
  }
/**
 * get calendar component from container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.13.5 - 2012-08-08
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return object
 */
  function getComponent( $arg1=FALSE, $arg2=FALSE ) {
   return $this->componentHolder->getComponent( $arg1, $arg2 );
  }
/**
 * create new calendar component, already included within calendar
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.33 - 2011-01-03
 * @param string $compType component type
 * @return object (reference)
 */
  function & newComponent( $compType ) {
    $config = $this->getConfig();
    return $this->componentHolder->newComponent( $compType, $config );
  }
/**
 * select components from calendar on date or selectOption basis
 *
 * Ensure DTSTART is set for every component.
 * No date controls occurs.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.22 - 2012-02-13
 * @param mixed $startY optional, start Year,  default current Year ALT. array selecOptions ( *[ <propName> => <uniqueValue> ] )
 * @param int   $startM optional, start Month, default current Month
 * @param int   $startD optional, start Day,   default current Day
 * @param int   $endY   optional, end   Year,  default $startY
 * @param int   $endY   optional, end   Month, default $startM
 * @param int   $endY   optional, end   Day,   default $startD
 * @param mixed $cType  optional, calendar component type(-s), default FALSE=all else string/array type(-s)
 * @param bool  $flat   optional, FALSE (default) => output : array[Year][Month][Day][]
 *                                TRUE            => output : array[] (ignores split)
 * @param bool  $any    optional, TRUE (default) - select component(-s) that occurs within period
 *                                FALSE          - only component(-s) that starts within period
 * @param bool  $split  optional, TRUE (default) - one component copy every DAY it occurs during the
 *                                                 period (implies flat=FALSE)
 *                                FALSE          - one occurance of component only in output array
 * @return array or FALSE
 */
  function selectComponents( $startY=FALSE, $startM=FALSE, $startD=FALSE, $endY=FALSE, $endM=FALSE, $endD=FALSE, $cType=FALSE, $flat=FALSE, $any=TRUE, $split=TRUE ) {
	  return $this->componentHolder->selectComponents( $startY, $startM, $startD, $endY, $endM, $endD, $cType, $flat, $any, $split);
  }
/**
 * select components from calendar on based on specific property value(-s)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.13.4 - 2012-08-07
 * @param array $selectOptions, (string) key => (mixed) value, (key=propertyName)
 * @return array
 */
  function selectComponents2( $selectOptions ) {
    return $this->componentHolder->selectComponents2( $selectOptions );
  }
/**
 * add calendar component to container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param object $component calendar component
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return void
 */
  function setComponent( $component, $arg1=FALSE, $arg2=FALSE  ) {
    $component->setConfig( $this->getConfig(), FALSE, TRUE );
    $this->componentHolder->setComponent($component, $arg1, $arg2);
  }
/**
 * sort iCal compoments
 *
 * ascending sort on properties (if exist) x-current-dtstart, dtstart,
 * x-current-dtend, dtend, x-current-due, due, duration, created, dtstamp, uid if called without arguments, 
 * otherwise sorting on specific (argument) property values
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.13.4 - 2012-08-07
 * @param string $sortArg, optional
 * @return void
 *
 */
  function sort( $sortArg=FALSE ) {
    if( is_array( $this->components )) {
      if( $sortArg ) {
        $sortArg = strtoupper( $sortArg );
        if( !in_array( $sortArg, array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'DTSTAMP', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'URL' )))
          $sortArg = FALSE;
      }
            /* set sort parameters for each component */
      foreach( $this->components as $cix => & $c ) {
        $c->srtk = array( '0', '0', '0', '0' );
        if( 'vtimezone' == $c->objName ) {
          if( FALSE === ( $c->srtk[0] = $c->getProperty( 'tzid' )))
            $c->srtk[0] = 0;
          continue;
        }
        elseif( $sortArg ) {
          if(( 'ATTENDEE' == $sortArg ) || ( 'CATEGORIES' == $sortArg ) || ( 'CONTACT' == $sortArg ) || ( 'RELATED-TO' == $sortArg ) || ( 'RESOURCES' == $sortArg )) {
            $propValues = array();
            $c->_getProperties( $sortArg, $propValues );
            if( !empty( $propValues )) {
              $sk         = array_keys( $propValues );
              $c->srtk[0] = $sk[0];
              if( 'RELATED-TO'  == $sortArg )
                $c->srtk[0] .= $c->getProperty( 'uid' );
            }
            elseif( 'RELATED-TO'  == $sortArg )
              $c->srtk[0] = $c->getProperty( 'uid' );
          }
          elseif( FALSE !== ( $d = $c->getProperty( $sortArg )))
            $c->srtk[0] = $d;
          continue;
        }
        if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTSTART' ))) {
          $c->srtk[0] = iCalUtilityFunctions::_date_time_string( $d[1] );
          unset( $c->srtk[0]['unparsedtext'] );
        }
        elseif( FALSE === ( $c->srtk[0] = $c->getProperty( 'dtstart' )))
          $c->srtk[1] = 0;                                                  // sortkey 0 : dtstart
        if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTEND' ))) {
          $c->srtk[1] = iCalUtilityFunctions::_date_time_string( $d[1] );   // sortkey 1 : dtend/due(/dtstart+duration)
          unset( $c->srtk[1]['unparsedtext'] );
        }
        elseif( FALSE === ( $c->srtk[1] = $c->getProperty( 'dtend' ))) {
          if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DUE' ))) {
            $c->srtk[1] = iCalUtilityFunctions::_date_time_string( $d[1] );
            unset( $c->srtk[1]['unparsedtext'] );
          }
          elseif( FALSE === ( $c->srtk[1] = $c->getProperty( 'due' )))
            if( FALSE === ( $c->srtk[1] = $c->getProperty( 'duration', FALSE, FALSE, TRUE )))
              $c->srtk[1] = 0;
        }
        if( FALSE === ( $c->srtk[2] = $c->getProperty( 'created' )))      // sortkey 2 : created/dtstamp
          if( FALSE === ( $c->srtk[2] = $c->getProperty( 'dtstamp' )))
            $c->srtk[2] = 0;
        if( FALSE === ( $c->srtk[3] = $c->getProperty( 'uid' )))          // sortkey 3 : uid
          $c->srtk[3] = 0;
      } // end foreach( $this->components as & $c
            /* sort */
      usort( $this->components, array( $this, '_cmpfcn' ));
    }
  }
  function _cmpfcn( $a, $b ) {
    if(        empty( $a ))                       return -1;
    if(        empty( $b ))                       return  1;
    if( 'vtimezone' == $a->objName ) {
      if( 'vtimezone' != $b->objName )            return -1;
      elseif( $a->srtk[0] <= $b->srtk[0] )        return -1;
      else                                        return  1;
    }
    elseif( 'vtimezone' == $b->objName )          return  1;
    $sortkeys = array( 'year', 'month', 'day', 'hour', 'min', 'sec' );
    for( $k = 0; $k < 4 ; $k++ ) {
      if(        empty( $a->srtk[$k] ))           return -1;
      elseif(    empty( $b->srtk[$k] ))           return  1;
      if( is_array( $a->srtk[$k] )) {
        if( is_array( $b->srtk[$k] )) {
          foreach( $sortkeys as $key ) {
            if    ( !isset( $a->srtk[$k][$key] )) return -1;
            elseif( !isset( $b->srtk[$k][$key] )) return  1;
            if    (  empty( $a->srtk[$k][$key] )) return -1;
            elseif(  empty( $b->srtk[$k][$key] )) return  1;
            if    (         $a->srtk[$k][$key] == $b->srtk[$k][$key])
                                                  continue;
            if    ((  (int) $a->srtk[$k][$key] ) < ((int) $b->srtk[$k][$key] ))
                                                  return -1;
            elseif((  (int) $a->srtk[$k][$key] ) > ((int) $b->srtk[$k][$key] ))
                                                  return  1;
          }
        }
        else                                      return -1;
      }
      elseif( is_array( $b->srtk[$k] ))           return  1;
      elseif( $a->srtk[$k] < $b->srtk[$k] )       return -1;
      elseif( $a->srtk[$k] > $b->srtk[$k] )       return  1;
    }
    return 0;
  }
/**
 * parse iCal text/file into vcalendar, components, properties and parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.17 - 2012-07-12
 * @param mixed $unparsedtext, optional, strict rfc2445 formatted, single property string or array of property strings
 * @return bool FALSE if error occurs during parsing
 *
 */
  function parse( $unparsedtext=FALSE ) {
    $nl = $this->getConfig( 'nl' );
    if(( FALSE === $unparsedtext ) || empty( $unparsedtext )) {
            /* directory+filename is set previously via setConfig directory+filename or url */
      if( FALSE === ( $filename = $this->getConfig( 'url' )))
        $filename = $this->getConfig( 'dirfile' );
            /* READ FILE */
      if( FALSE === ( $rows = file_get_contents( $filename )))
        return FALSE;                 /* err 1 */
    }
    elseif( is_array( $unparsedtext ))
      $rows =  implode( '\n'.$nl, $unparsedtext );
    else
      $rows = & $unparsedtext;
            /* fix line folding */
    $rows = explode( $nl, iCalUtilityFunctions::convEolChar( $rows, $nl ));
            /* skip leading (empty/invalid) lines */
    foreach( $rows as $lix => $line ) {
      if( FALSE !== stripos( $line, 'BEGIN:VCALENDAR' ))
        break;
      unset( $rows[$lix] );
    }
    $rcnt = count( $rows );
    if( 3 > $rcnt )                  /* err 10 */
      return FALSE;
            /* skip trailing empty lines and ensure an end row */
    $lix  = array_keys( $rows );
    $lix  = end( $lix );
    while( 3 < $lix ) {
      $tst = trim( $rows[$lix] );
      if(( '\n' == $tst ) || empty( $tst )) {
        unset( $rows[$lix] );
        $lix--;
        continue;
      }
      if( FALSE === stripos( $rows[$lix], 'END:VCALENDAR' ))
        $rows[] = 'END:VCALENDAR';
      break;
    }
    $comp    = & $this;
    $calsync = $compsync = 0;
            /* identify components and update unparsed data within component */
    $config = $this->getConfig();
    $endtxt = array( 'END:VE', 'END:VF', 'END:VJ', 'END:VT' );
    foreach( $rows as $lix => $line ) {
      if(     'BEGIN:VCALENDAR' == strtoupper( substr( $line, 0, 15 ))) {
        $calsync++;
        continue;
      }
      elseif( 'END:VCALENDAR'   == strtoupper( substr( $line, 0, 13 ))) {
        if( 0 < $compsync )
          $this->components[] = $comp->copy();
        $compsync--;
        $calsync--;
        break;
      }
      elseif( 1 != $calsync )
        return FALSE;                 /* err 20 */
      elseif( in_array( strtoupper( substr( $line, 0, 6 )), $endtxt )) {
        $this->components[] = $comp->copy();
        $compsync--;
        continue;
      }
      if(     'BEGIN:VEVENT'    == strtoupper( substr( $line, 0, 12 ))) {
        $comp = new vevent( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VFREEBUSY' == strtoupper( substr( $line, 0, 15 ))) {
        $comp = new vfreebusy( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VJOURNAL'  == strtoupper( substr( $line, 0, 14 ))) {
        $comp = new vjournal( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VTODO'     == strtoupper( substr( $line, 0, 11 ))) {
        $comp = new vtodo( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VTIMEZONE' == strtoupper( substr( $line, 0, 15 ))) {
        $comp = new vtimezone( $config );
        $compsync++;
      }
      else { /* update component with unparsed data */
        $comp->unparsed[] = $line;
      }
    } // end foreach( $rows as $line )
    unset( $config, $endtxt );
            /* parse data for calendar (this) object */
    if( isset( $this->unparsed ) && is_array( $this->unparsed ) && ( 0 < count( $this->unparsed ))) {
            /* concatenate property values spread over several lines */
      $lastix    = -1;
      $propnames = array( 'calscale','method','prodid','version','x-' );
      $proprows  = array();
      foreach( $this->unparsed as $line ) {
        $newProp = FALSE;
        foreach ( $propnames as $propname ) {
          if( $propname == strtolower( substr( $line, 0, strlen( $propname )))) {
            $newProp = TRUE;
            break;
          }
        }
        if( $newProp ) {
          $newProp = FALSE;
          $lastix++;
          $proprows[$lastix]  = $line;
        }
        else
          $proprows[$lastix] .= '!"#¤%&/()=?'.$line;
      }
      $paramMStz   = array( 'utc-', 'utc+', 'gmt-', 'gmt+' );
      $paramProto3 = array( 'fax:', 'cid:', 'sms:', 'tel:', 'urn:' );
      $paramProto4 = array( 'crid:', 'news:', 'pres:' );
      foreach( $proprows as $line ) {
        $line = str_replace( '!"#¤%&/()=? ', '', $line );
        $line = str_replace( '!"#¤%&/()=?', '', $line );
        if( '\n' == substr( $line, -2 ))
          $line = substr( $line, 0, -2 );
            /* get property name */
        $propname  = null;
        $cix       = 0;
        while( FALSE !== ( $char = substr( $line, $cix, 1 ))) {
          if( in_array( $char, array( ':', ';' )))
            break;
          else
            $propname .= $char;
          $cix++;
        }
            /* ignore version/prodid properties */
        if( in_array( strtoupper( $propname ), array( 'VERSION', 'PRODID' )))
          continue;
        $line = substr( $line, $cix);
            /* separate attributes from value */
        $attr         = array();
        $attrix       = -1;
        $strlen       = strlen( $line );
        $WithinQuotes = FALSE;
        $cix          = 0;
        while( FALSE !== substr( $line, $cix, 1 )) {
          if(                       ( ':'  == $line[$cix] )                         &&
                                    ( substr( $line,$cix,     3 )  != '://' )       &&
             ( !in_array( strtolower( substr( $line,$cix - 6, 4 )), $paramMStz ))   &&
             ( !in_array( strtolower( substr( $line,$cix - 3, 4 )), $paramProto3 )) &&
             ( !in_array( strtolower( substr( $line,$cix - 4, 5 )), $paramProto4 )) &&
                        ( strtolower( substr( $line,$cix - 6, 7 )) != 'mailto:' )   &&
               !$WithinQuotes ) {
            $attrEnd = TRUE;
            if(( $cix < ( $strlen - 4 )) &&
                 ctype_digit( substr( $line, $cix+1, 4 ))) { // an URI with a (4pos) portnr??
              for( $c2ix = $cix; 3 < $c2ix; $c2ix-- ) {
                if( '://' == substr( $line, $c2ix - 2, 3 )) {
                  $attrEnd = FALSE;
                  break; // an URI with a portnr!!
                }
              }
            }
            if( $attrEnd) {
              $line = substr( $line, ( $cix + 1 ));
              break;
            }
          }
          if( '"' == $line[$cix] )
            $WithinQuotes = ( FALSE === $WithinQuotes ) ? TRUE : FALSE;
          if( ';' == $line[$cix] )
            $attr[++$attrix] = null;
          else
            $attr[$attrix] .= $line[$cix];
          $cix++;
        }
            /* make attributes in array format */
        $propattr = array();
        foreach( $attr as $attribute ) {
          $attrsplit = explode( '=', $attribute, 2 );
          if( 1 < count( $attrsplit ))
            $propattr[$attrsplit[0]] = $attrsplit[1];
          else
            $propattr[] = $attribute;
        }
            /* update Property */
        if( FALSE !== strpos( $line, ',' )) {
          $content  = array( 0 => '' );
          $cix = $lix = 0;
          while( FALSE !== substr( $line, $lix, 1 )) {
            if(( 0 < $lix ) && ( ',' == $line[$lix] ) && ( "\\" != $line[( $lix - 1 )])) {
              $cix++;
              $content[$cix] = '';
            }
            else
              $content[$cix] .= $line[$lix];
            $lix++;
          }
          if( 1 < count( $content )) {
            foreach( $content as $cix => $contentPart )
              $content[$cix] = calendarComponent::_strunrep( $contentPart );
            $this->setProperty( $propname, $content, $propattr );
            continue;
          }
          else
            $line = reset( $content );
          $line = calendarComponent::_strunrep( $line );
        }
        $this->setProperty( $propname, rtrim( $line, "\x00..\x1F" ), $propattr );
      } // end - foreach( $this->unparsed.. .
    } // end - if( is_array( $this->unparsed.. .
    unset( $unparsedtext, $rows, $this->unparsed, $proprows );
            /* parse Components */
    if( is_array( $this->components ) && ( 0 < count( $this->components ))) {
      $ckeys = array_keys( $this->components );
      foreach( $ckeys as $ckey ) {
        if( !empty( $this->components[$ckey] ) && !empty( $this->components[$ckey]->unparsed )) {
          $this->components[$ckey]->parse();
        }
      }
    }
    else
      return FALSE;                   /* err 91 or something.. . */
    return TRUE;
  }
/*********************************************************************************/

  function createCalendar() {
	  $class = strtolower($this->getConfig('FORMAT')).'renderer';
	  $renderer = new $class( $this );
	  return $renderer->createCalendar();
  }
}
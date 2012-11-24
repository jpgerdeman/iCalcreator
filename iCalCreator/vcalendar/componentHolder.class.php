<?php

/**
 * Encapsules components - and their functions - of a vcalendar.
 * 
 * @TODO if PHP < 5.2 is no longer supported implement using array Interface
 */
class componentHolder
{

	var $components = array();
	var $compix = null;
	
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
	function setComponent($component, $arg1 = FALSE, $arg2 = FALSE)
	{		
		if (!in_array($component->objName, array('valarm', 'vtimezone')))
		{
			/* make sure dtstamp and uid is set */
			$dummy1 = $component->getProperty('dtstamp');
			$dummy2 = $component->getProperty('uid');
		}

		// If $arg1 is false then no hint for ordering/replacing was supplied. 
		if (!$arg1)
		{
			$this->components[] = $component->copy();
			return TRUE;
		}
		
		// Now we have to somehow replace an existing component
		// a component can be identified by:
		// - its array-index
		// - its uid, which is a property of the components
		// - its type, which means we replace the $arg2-occurrence (e.g. 4th) of that componenttype
		if (ctype_digit((string) $arg1))
		{ // index insert/replace
			return $this->setComponentByIndex($component, $arg1 );
		}
		elseif (in_array(strtolower($arg1), array('vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone')))
		{
			$argType = strtolower($arg1);
			$index = ( ctype_digit((string) $arg2)) ? ((int) $arg2) - 1 : 0;

			return $this->setComponentByType($component, $argType, $index);
		}
		else
		{
			return $this->setComponentByUID($component, $arg1);
		}
	}

	/**
	 * Replace of Insert calendar component at given position.
	 * 
	 * @param object $component calendarComponent
	 * @param int $index the position number where to insert the component. Starts at 1.
	 *
	 * @return void
	 */
	function setComponentByIndex($component, $index)
	{
		$index = (int) $index - 1;
		$this->components[$index] = $component->copy();
		ksort($this->components, SORT_NUMERIC);
		return true;
	}
	
	/**
	 * Replace or Insert, replacing nth occurence of component of given type.
	 * 
	 * **Example**:
	 *     
	 *     $this->setComponentByType( $component, 'vevent', 2);
	 *     // replaces the second vevent by $component
	 * 
	 * @param object $component calendarComponent
	 * @param string $type The type of component to replace
	 * @param int $position The occurence to be replaced
	 *
	 * @return void
	 */
	function setComponentByType($component, $type, $position)
	{
		$cix1sC = 0;
		foreach ($this->components as $cix => $component2)
		{
			if (empty($component2))
				continue;
			if ($type == $component2->objName)
			{ // component Type index insert/replace
				if ($position == $cix1sC)
				{
					$this->components[$cix] = $component->copy();
					return TRUE;
				}
				$cix1sC++;
			}
		}

		/* arg1=index and not found.. . insert at index .. . */
		$this->components[] = $component->copy();
	}
	
	/**
	 * Insert component or replace existing component with uid.
	 *  
	 * @param object $component calendarComponent
	 * @param string $uid The uid of the component to be replaced
	 *
	 * @return void
	 */
	function setComponentByUID($component, $uid)
	{
		foreach ($this->components as $cix => $component2)
		{
			if (empty($component2))
				continue;
			if ($uid == $component2->getProperty('uid'))
			{ // UID insert/replace
				$this->components[$cix] = $component->copy();
				return TRUE;
			}
		}

		/* arg1=index and not found.. . insert at index .. . */
		$this->components[] = $component->copy();
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
	function getComponent($arg1 = FALSE, $arg2 = FALSE)
	{
		// We can choose a component by:
		// - picking the next (or first if no prev is set)
		// - its array-index
		// - its uid, which is a property of the components
		// - its type, which means we replace the $arg2-occurrence (e.g. 4th) of that componenttype
		$index = $argType = null;
		if (!$arg1)
		{ // first or next in component chain
			$argType = 'INDEX';
			$index = $this->compix['INDEX'] = ( isset($this->compix['INDEX'])) ? $this->compix['INDEX'] + 1 : 1;
			return $this->getComponentByIndex($index);
		}
		elseif (ctype_digit((string) $arg1))
		{ 
		    // specific component in chain
			$argType = 'INDEX';
			unset($this->compix);
			return $this->getComponentByIndex($arg1);
		}
		elseif (is_array($arg1))
		{ // array( *[propertyName => propertyValue] )
			$arg2 = implode('-', array_keys($arg1));
			$index = $this->compix[$arg2] = ( isset($this->compix[$arg2])) ? $this->compix[$arg2] + 1 : 1;
						
			return $this->getComponentByProperty( $arg1, $index );
		}
		elseif (( strlen($arg1) <= strlen('vfreebusy')) && ( FALSE === strpos($arg1, '@')))
		{ // object class name
			unset($this->compix['INDEX']);
			$argType = strtolower($arg1);
			if (!$arg2)
				$index = $this->compix[$argType] = ( isset($this->compix[$argType])) ? $this->compix[$argType] + 1 : 1;
			elseif (isset($arg2) && ctype_digit((string) $arg2))
				$index = (int) $arg2;
			return $this->getComponentByType($argType, $index);
		}
		elseif (( strlen($arg1) > strlen('vfreebusy')) && ( FALSE !== strpos($arg1, '@')))
		{ // UID as 1st argument
			if (!$arg2)
				$index = $this->compix[$arg1] = ( isset($this->compix[$arg1])) ? $this->compix[$arg1] + 1 : 1;
			elseif (isset($arg2) && ctype_digit((string) $arg2))
				$index = (int) $arg2;
			return $this->getComponentByUid($arg1, $index);
		}
				
		unset($this->compix);
		return FALSE;
	}
		
	/**
	 * Return the component at given position.
	 *  
	 * @param int $index The position of the component
	 *
	 * @return calendarComponent|bool Return the component or False if no component was found
	 */
	function getComponentByIndex( $index )
	{
		$index = (int) $index - 1;
		if( isset($this->components[$index]) && !empty($this->components[$index]))
		{
			$c = $this->components[$index];		
			return $c->copy();
		}
		else
		{
			return FALSE;
		}		
	}
	
	/**
	 * Return the component with uid.
	 *  
	 * @param string $uid The uid of the component to be returned
	 *
	 * @return calendarComponent|bool Return the component or False if no matching component was found
	 */
	function getComponentByUid($uid, $index)
	{
		$index--;
		$cix1gC = 0;		
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;
			
			if ( $uid == $component->getProperty('uid') && $cix1gC = $index )
			{				
				return $component->copy();				
			}
			$cix1gC++;
		}
		return FALSE;
	}
	
	/**
	 * Return nth occurence of component of given type.
	 * 
	 * **Example**:
	 *     
	 *     $this->getComponentByType( 'vevent', 2);
	 *     // returns the second vevent
	 * 
	 * @param string $type The type of component
	 * @param int $position The occurence 
	 *
	 * @return calendarComponent|bool Return the component or False if no matching component was found
	 */
	public function getComponentByType( $type, $position )
	{			
		$position --;
		$cix1dC = 0;
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;
			if ($type == $component->objName)
			{				
				if ($position == $cix1dC)
				{
					return $component->copy();
				}
				$cix1dC++;
			}
		}
		return FALSE;
	}	
	
	/**
	 * Return nth occurence of component with given properties.
	 * 
	 * **Example**:
	 *     
	 *     $this->getComponentByType( array('LOCATION' => 'Tampa'), 2);
	 *     // returns the second vevent at Tampa
	 * 
	 * @param array $properties The properties of the saught after component
	 * @param int $position The occurence 
	 *
	 * @return calendarComponent|bool Return the component or False if no matching component was found
	 */
	public function getComponentByProperty( $properties, $position )
	{
		$dateProps = array('DTSTART', 'DTEND', 'DUE', 'CREATED', 'COMPLETED', 'DTSTAMP', 'LAST-MODIFIED', 'RECURRENCE-ID');
		$otherProps = array('ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL');
		$mProps = array('ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO', 'RESOURCES');
		
		$position--;
		
		$ckeys = array_keys($this->components);
		if (!empty($position) && ( $position > end($ckeys)))
			return FALSE;
		
		$cix1gC = 0;
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;
			if (is_array($properties))
			{ // array( *[propertyName => propertyValue] )
				$hit = array();
				foreach ($properties as $pName => $pValue)
				{
					$pName = strtoupper($pName);
					if (!in_array($pName, $dateProps) && !in_array($pName, $otherProps))
						continue;
					if (in_array($pName, $mProps))
					{ // multiple occurrence
						$propValues = array();
						$component->_getProperties($pName, $propValues);
						$propValues = array_keys($propValues);
						$hit[] = ( in_array($pValue, $propValues)) ? TRUE : FALSE;
						continue;
					} // end   if(.. .// multiple occurrence
					if (FALSE === ( $value = $component->getProperty($pName)))
					{ // single occurrence
						$hit[] = FALSE; // missing property
						continue;
					}
					if ('SUMMARY' == $pName)
					{ // exists within (any case)
						$hit[] = ( FALSE !== stripos($value, $pValue)) ? TRUE : FALSE;
						continue;
					}
					if (in_array(strtoupper($pName), $dateProps))
					{
						$valuedate = sprintf('%04d%02d%02d', $value['year'], $value['month'], $value['day']);
						if (8 < strlen($pValue))
						{
							if (isset($value['hour']))
							{
								if ('T' == substr($pValue, 8, 1))
									$pValue = str_replace('T', '', $pValue);
								$valuedate .= sprintf('%02d%02d%02d', $value['hour'], $value['min'], $value['sec']);
							}
							else
								$pValue = substr($pValue, 0, 8);
						}
						$hit[] = ( $pValue == $valuedate ) ? TRUE : FALSE;
						continue;
					}
					elseif (!is_array($value))
						$value = array($value);
					foreach ($value as $part)
					{
						$part = ( FALSE !== strpos($part, ',')) ? explode(',', $part) : array($part);
						foreach ($part as $subPart)
						{
							if ($pValue == $subPart)
							{
								$hit[] = TRUE;
								continue 3;
							}
						}
					} // end foreach( $value as $part )
					$hit[] = FALSE; // no hit in property
				} // end  foreach( $arg1 as $pName => $pValue )
				if (in_array(TRUE, $hit))
				{
					if ($position == $cix1gC)
						return $component->copy();
					$cix1gC++;
				}
			} // end elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
		} // end foreach ( $this->components.. .
		/* not found.. . */
		return FALSE;
	}
	
	function & newComponent($compType, $config)
	{
		$keys = array_keys($this->components);
		$ix = end($keys) + 1;
		switch (strtoupper($compType))
		{
			case 'EVENT':
			case 'VEVENT':
				$this->components[$ix] = new vevent($config);
				break;
			case 'TODO':
			case 'VTODO':
				$this->components[$ix] = new vtodo($config);
				break;
			case 'JOURNAL':
			case 'VJOURNAL':
				$this->components[$ix] = new vjournal($config);
				break;
			case 'FREEBUSY':
			case 'VFREEBUSY':
				$this->components[$ix] = new vfreebusy($config);
				break;
			case 'TIMEZONE':
			case 'VTIMEZONE':
				array_unshift($this->components, new vtimezone($config));
				$ix = 0;
				break;
			default:
				return FALSE;
		}
		return $this->components[$ix];
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
	function selectComponents($startY = FALSE, $startM = FALSE, $startD = FALSE, $endY = FALSE, $endM = FALSE, $endD = FALSE, $cType = FALSE, $flat = FALSE, $any = TRUE, $split = TRUE)
	{
		// all utc strings are named *W*
		// end and start refer to an array representation
		
		/* check  if empty calendar */
		if (0 >= count($this->components))
			return FALSE;
		if (is_array($startY))
			return $this->selectComponents2($startY);
	
		list( $startDate, $endDate ) = $this->makeDates($startY, $startM, $startD, $endY, $endM, $endD);
		$cType = $this->intersectValidTypes($cType);
				
		// check option combinations
		if (( FALSE === $flat ) && ( FALSE === $any )) // invalid combination
			$split = FALSE;
		if (( TRUE === $flat ) && ( TRUE === $split )) // invalid combination
			$split = FALSE;

		
		foreach ($this->components as $cix => $component)
		{
			$compTimes = new Component_Decorator_TimeSelector($component);
			if (empty($component))
				continue;
			
			/* deselect unvalid type components */
			if (!in_array($component->objName, $cType))
				continue;
			
			unset($start);
			$start = $component->getProperty('dtstart');
			/* select due when dtstart is missing */
			if (empty($start) && ( $component->objName == 'vtodo' ) && ( FALSE === ( $start = $component->getProperty('due'))))
				continue;
			if (empty($start))
				continue;
			
			$dtendExist = $dueExist = $durationExist = $endAllDayEvent = $recurrid = FALSE;
			unset($end, $startWdate, $endWdate, $rdurWsecs, $rdur, $exdatelist, $workstart, $workend, $endDateFormat); // clean up
			$startWdate = iCalUtilityFunctions::_date2timestamp($start);

			$startDateFormat = $compTimes->computeStartDateFormat();
			$endDateFormat = $compTimes->computeEndDateFormat();
			$dtendExist = $compTimes->hasEndDate();
			$dueExist = $compTimes->hasDueDate();
			$durationExist = $compTimes->hasDuration();
			$isRecurring = $compTimes->isRecurring();
			$end = $compTimes->computeEndDate();
			$endAllDayEvent = $compTimes->isAllDayEvent();
			$compUID = $component->getProperty('UID');
			
			$endWdate = iCalUtilityFunctions::_date2timestamp($end);
			
			$rdurWsecs = $endWdate - $startWdate; // compute event (component) duration in seconds
			/* make a list of optional exclude dates for component occurence from exrule and exdate */
			$exdatelist = $compTimes->computeExcludeDates();
			$workstart = iCalUtilityFunctions::_timestamp2date(( $startDate - $rdurWsecs), 6);
			$workend = iCalUtilityFunctions::_timestamp2date(( $endDate + $rdurWsecs), 6);
							
			/* select only components with.. . */
			if ( $compTimes->isWithinPeriod($startDate, $endDate, !$any) )
			{	// occurs within the period
				/* add the selected component (WITHIN valid dates) to output array */
				if ($flat)
				{ // any=true/false, ignores split
					if (!$recurrid)
						$result[$compUID] = $component->copy(); // copy original to output (but not anyone with recurrence-id)
				}
				elseif ($split)
				{ // split the original component
					if ($endWdate > $endDate)
						$endWdate = $endDate;	 // use period end date
					$rstart = $startWdate;
					if ($rstart < $startDate)
						$rstart = $startDate; // use period start date
					$startYMD = date('Ymd', $rstart);
					$endYMD = date('Ymd', $endWdate);
					$checkDate = mktime(0, 0, 0, date('m', $rstart), date('d', $rstart), date('Y', $rstart)); // on a day-basis !!!
					
					while (date('Ymd', $rstart) <= $endYMD)
					{ // iterate
						
						$checkDate = mktime(0, 0, 0, date('m', $rstart), date('d', $rstart), date('Y', $rstart)); // on a day-basis !!!
						if (isset($exdatelist[$checkDate]))
						{ // exclude any recurrence date, found in exdatelist
							$rstart = mktime(date('H', $rstart), date('i', $rstart), date('s', $rstart), date('m', $rstart), date('d', $rstart) + 1, date('Y', $rstart)); // step one day
							continue;
						}
						
						// Update start properties
						if (date('Ymd', $rstart) > $startYMD) // date after dtstart
							$datestring = date($startDateFormat, mktime(0, 0, 0, date('m', $rstart), date('d', $rstart), date('Y', $rstart)));
						else
							$datestring = date($startDateFormat, $rstart);
						if (isset($start['tz']))
							$datestring .= ' ' . $start['tz'];
// echo "X-CURRENT-DTSTART 3 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br />";$component->setProperty( 'X-CNT', $tcnt ); // test ###
						$component->setProperty('X-CURRENT-DTSTART', $datestring);
						
						// Update end properties if applicable
						if ($dtendExist || $dueExist || $durationExist)
						{
							if (date('Ymd', $rstart) < $endYMD) // not the last day
								$tend = mktime(23, 59, 59, date('m', $rstart), date('d', $rstart), date('Y', $rstart));
							else
								$tend = mktime(date('H', $endWdate), date('i', $endWdate), date('s', $endWdate), date('m', $rstart), date('d', $rstart), date('Y', $rstart)); // on a day-basis !!!
							if ($endAllDayEvent && $dtendExist)
								$tend += ( 24 * 3600 ); // alldaysevents has an end date 'day after' meaning this day
							$datestring = date($endDateFormat, $tend);
							if (isset($end['tz']))
								$datestring .= ' ' . $end['tz'];
							$propName = (!$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
							$component->setProperty($propName, $datestring);
						} // end if( $dtendExist || $dueExist || $durationExist )
						
						// prepare next iteration step
						$wd = getdate($rstart);
						$result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component->copy(); // copy to output
						$rstart = mktime(date('H', $rstart), date('i', $rstart), date('s', $rstart), date('m', $rstart), date('d', $rstart) + 1, date('Y', $rstart)); // step one day
					} // end while( $rstart <= $endWdate )
				
					
					
					
				} // end if( $split )   -  else use component date
				elseif ($recurrid && !$flat && !$any && !$split)
					$continue = TRUE;
				else
				{ // !$flat && !$split, i.e. no flat array and DTSTART within period
					$checkDate = mktime(0, 0, 0, date('m', $startWdate), date('d', $startWdate), date('Y', $startWdate)); // on a day-basis !!!
					if (!$any || !isset($exdatelist[$checkDate]))
					{ // exclude any recurrence date, found in exdatelist
						$wd = getdate($startWdate);
						$result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component->copy(); // copy to output
					}
				}
			} // end if(( $startWdate >= $startDate ) && ( $startWdate <= $endDate ))

			/* if 'any' components, check components with reccurrence rules, removing all excluding dates */
			if (TRUE === $any)
			{
				/* make a list of optional repeating dates for component occurence, rrule, rdate */
				$recurlist = array();
				while (FALSE !== ( $rrule = $component->getProperty('rrule')))	// check rrule
					iCalUtilityFunctions::_recur2date($recurlist, $rrule, $start, $workstart, $workend);
				foreach ($recurlist as $recurkey => $recurvalue) // key=match date as timestamp
					$recurlist[$recurkey] = $rdurWsecs; // add duration in seconds
				while (FALSE !== ( $rdate = $component->getProperty('rdate')))
				{  // check rdate
					foreach ($rdate as $theRdate)
					{
						if (is_array($theRdate) && ( 2 == count($theRdate)) && // all days within PERIOD
								array_key_exists('0', $theRdate) && array_key_exists('1', $theRdate))
						{
							$rstart = iCalUtilityFunctions::_date2timestamp($theRdate[0]);
							if (( $rstart < ( $startDate - $rdurWsecs )) || ( $rstart > $endDate ))
								continue;
							if (isset($theRdate[1]['year'])) // date-date period
								$rend = iCalUtilityFunctions::_date2timestamp($theRdate[1]);
							else
							{							 // date-duration period
								$rend = iCalUtilityFunctions::_duration2date($theRdate[0], $theRdate[1]);
								$rend = iCalUtilityFunctions::_date2timestamp($rend);
							}
							while ($rstart < $rend)
							{
								$recurlist[$rstart] = $rdurWsecs; // set start date for recurrence instance + rdate duration in seconds
								$rstart = mktime(date('H', $rstart), date('i', $rstart), date('s', $rstart), date('m', $rstart), date('d', $rstart) + 1, date('Y', $rstart)); // step one day
							}
						} // PERIOD end
						else
						{ // single date
							$theRdate = iCalUtilityFunctions::_date2timestamp($theRdate);
							if ((( $startDate - $rdurWsecs ) <= $theRdate ) && ( $endDate >= $theRdate ))
								$recurlist[$theRdate] = $rdurWsecs; // set start date for recurrence instance + event duration in seconds
						}
					}
				}  // end - check rdate
				if (0 < count($recurlist))
				{
					ksort($recurlist);
					$xRecurrence = 1;
					$component2 = $component->copy();
					$compUID = $component2->getProperty('UID');
					foreach ($recurlist as $recurkey => $durvalue)
					{
// echo "recurKey=".date( 'Y-m-d H:i:s', $recurkey ).' dur='.iCalUtilityFunctions::offsetSec2His( $durvalue )."<br />\n"; // test ###;
						if ((( $startDate - $rdurWsecs ) > $recurkey ) || ( $endDate < $recurkey )) // not within period
							continue;
						$checkDate = mktime(0, 0, 0, date('m', $recurkey), date('d', $recurkey), date('Y', $recurkey)); // on a day-basis !!!
						if (isset($exdatelist[$checkDate])) // check excluded dates
							continue;
						if ($startWdate >= $recurkey) // exclude component start date
							continue;
						$rstart = $recurkey;
						$rend = $recurkey + $durvalue;
						/* add repeating components within valid dates to output array, only start date set */
						if ($flat)
						{
							if (!isset($result[$compUID])) // only one comp
								$result[$compUID] = $component2->copy(); // copy to output
						}
						/* add repeating components within valid dates to output array, one each day */
						elseif ($split)
						{
							if ($rend > $endDate)
								$rend = $endDate;
							$startYMD = date('Ymd', $rstart);
							$endYMD = date('Ymd', $rend);
// echo "splitStart=".date( 'Y-m-d H:i:s', $rstart ).' end='.date( 'Y-m-d H:i:s', $rend )."<br />\n"; // test ###;
							while ($rstart <= $rend)
							{ // iterate.. .
								$checkDate = mktime(0, 0, 0, date('m', $rstart), date('d', $rstart), date('Y', $rstart)); // on a day-basis !!!
								if (isset($exdatelist[$checkDate]))  // exclude any recurrence START date, found in exdatelist
									break;
// echo "checking date after startdate=".date( 'Y-m-d H:i:s', $rstart ).' mot '.date( 'Y-m-d H:i:s', $startDate )."<br />"; // test ###;
								if ($rstart >= $startDate)
								{	// date after dtstart
									if (date('Ymd', $rstart) > $startYMD) // date after dtstart
										$datestring = date($startDateFormat, $checkDate);
									else
										$datestring = date($startDateFormat, $rstart);
									if (isset($start['tz']))
										$datestring .= ' ' . $start['tz'];
//echo "X-CURRENT-DTSTART 1 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br />";$component2->setProperty( 'X-CNT', $tcnt ); // test ###
									$component2->setProperty('X-CURRENT-DTSTART', $datestring);
									if ($dtendExist || $dueExist || $durationExist)
									{
										if (date('Ymd', $rstart) < $endYMD) // not the last day
											$tend = mktime(23, 59, 59, date('m', $rstart), date('d', $rstart), date('Y', $rstart));
										else
											$tend = mktime(date('H', $endWdate), date('i', $endWdate), date('s', $endWdate), date('m', $rstart), date('d', $rstart), date('Y', $rstart)); // on a day-basis !!!
										if ($endAllDayEvent && $dtendExist)
											$tend += ( 24 * 3600 ); // alldaysevents has an end date 'day after' meaning this day
										$datestring = date($endDateFormat, $tend);
										if (isset($end['tz']))
											$datestring .= ' ' . $end['tz'];
										$propName = (!$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
										$component2->setProperty($propName, $datestring);
									} // end if( $dtendExist || $dueExist || $durationExist )
									$component2->setProperty('X-RECURRENCE', $xRecurrence);
									$wd = getdate($rstart);
									$result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component2->copy(); // copy to output
								} // end if( $checkDate > $startYMD ) {    // date after dtstart
								$rstart = mktime(date('H', $rstart), date('i', $rstart), date('s', $rstart), date('m', $rstart), date('d', $rstart) + 1, date('Y', $rstart)); // step one day
							} // end while( $rstart <= $rend )
							$xRecurrence += 1;
						} // end elseif( $split )
						elseif ($rstart >= $startDate)
						{	 // date within period   //* flat=FALSE && split=FALSE => one comp every recur startdate *//
							$checkDate = mktime(0, 0, 0, date('m', $rstart), date('d', $rstart), date('Y', $rstart)); // on a day-basis !!!
							if (!isset($exdatelist[$checkDate]))
							{ // exclude any recurrence START date, found in exdatelist
								$xRecurrence += 1;
								$datestring = date($startDateFormat, $rstart);
								if (isset($start['tz']))
									$datestring .= ' ' . $start['tz'];
//echo "X-CURRENT-DTSTART 2 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br />";$component2->setProperty( 'X-CNT', $tcnt ); // test ###
								$component2->setProperty('X-CURRENT-DTSTART', $datestring);
								if ($dtendExist || $dueExist || $durationExist)
								{
									$tend = $rstart + $rdurWsecs;
									if (date('Ymd', $tend) < date('Ymd', $endWdate))
										$tend = mktime(23, 59, 59, date('m', $tend), date('d', $tend), date('Y', $tend));
									else
										$tend = mktime(date('H', $endWdate), date('i', $endWdate), date('s', $endWdate), date('m', $tend), date('d', $tend), date('Y', $tend)); // on a day-basis !!!
									if ($endAllDayEvent && $dtendExist)
										$tend += ( 24 * 3600 ); // alldaysevents has an end date 'day after' meaning this day
									$datestring = date($endDateFormat, $tend);
									if (isset($end['tz']))
										$datestring .= ' ' . $end['tz'];
									$propName = (!$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
									$component2->setProperty($propName, $datestring);
								} // end if( $dtendExist || $dueExist || $durationExist )
								$component2->setProperty('X-RECURRENCE', $xRecurrence);
								$wd = getdate($rstart);
								$result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component2->copy(); // copy to output
							} // end if( !isset( $exdatelist[$checkDate] ))
						} // end elseif( $rstart >= $startDate )
					} // end foreach( $recurlist as $recurkey => $durvalue )
				} // end if( 0 < count( $recurlist ))
				/* deselect components with startdate/enddate not within period */
				if (( $endWdate < $startDate ) || ( $startWdate > $endDate ))
					continue;
			} // end if( TRUE === $any )
		}
		
		$result = $this->sortNestedDates($result);
		if (0 >= count($result))
			return FALSE;
		return $result;
	}

	/**
	 * Create Time strings from given Parameters.
	 * 
	 * Startdate defaults to today. Enddate defaults to startdate.
	 * 
	 * @param int $startY
	 * @param int $startM
	 * @param int $startD
	 * @param int $endY
	 * @param int $endM
	 * @param int $endD
	 * 
	 * @return string[] startdate and enddate
	 */
	function makeDates($startY = FALSE, $startM = FALSE, $startD = FALSE, $endY = FALSE, $endM = FALSE, $endD = FALSE)
	{
		/* check default dates */
		if (!$startY)
			$startY = date('Y');
		if (!$startM)
			$startM = date('m');
		if (!$startD)
			$startD = date('d');
		$startDate = mktime(0, 0, 0, $startM, $startD, $startY);
		if (!$endY)
			$endY = $startY;
		if (!$endM)
			$endM = $startM;
		if (!$endD)
			$endD = $startD;
		$endDate = mktime(23, 59, 59, $endM, $endD, $endY);
		
		return array( $startDate, $endDate );
	}
	
	/**
	 * Intersect the given (array of) component type with allowed types.
	 * 
	 * @param mixed $cType ComponentType string or Array thereof
	 * @return mixed
	 */
	function intersectValidTypes( $cType )
	{
		/* intersect cTypes with valid types */
		$validTypes = array('vevent', 'vtodo', 'vjournal', 'vfreebusy');
		if (is_array($cType))
		{
			foreach ($cType as $cix => $theType)
			{
				$cType[$cix] = $theType = strtolower($theType);
				if (!in_array($theType, $validTypes))
					$cType[$cix] = 'vevent';
			}
			$cType = array_unique($cType);
		}
		elseif (!empty($cType))
		{
			$cType = strtolower($cType);
			if (!in_array($cType, $validTypes))
				$cType = array('vevent');
			else
				$cType = array($cType);
		}
		else
			$cType = $validTypes;
		if (0 >= count($cType))
			$cType = $validTypes;
		
		return $cType;
	}
	
	/**
	 * Sort a (unique) nested set of dates.
	 * 
	 * Sorts a list that is sorted by years, then months and then days
	 *  * removing empty entries
	 *  * uid keys
	 *  
	 * @param array $dateList YEAR[] => MONTH[] =>  DAY[] => components[]
	 * @return array the sorted array
	 */
	function sortNestedDates( $dateList )
	{
		foreach ($dateList as $y => $yeararr)
		{
			foreach ($yeararr as $m => $montharr)
			{
				foreach ($montharr as $d => $dayarr)
				{
					if (empty($dateList[$y][$m][$d]))
						unset($dateList[$y][$m][$d]);
					else
						$dateList[$y][$m][$d] = array_values($dayarr); // skip tricky UID-index, hoping they are in hour order.. .
				}
				if (empty($dateList[$y][$m]))
					unset($dateList[$y][$m]);
				else
					ksort($dateList[$y][$m]);
			}
			if (empty($dateList[$y]))
				unset($dateList[$y]);
			else
				ksort($dateList[$y]);
		}
		
		ksort($dateList);
		
		return $dateList;
	}

	/**
	 * select components from calendar on based on specific property value(-s)
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.13.4 - 2012-08-07
	 * @param array $selectOptions, (string) key => (mixed) value, (key=propertyName)
	 * @return array
	 */
	function selectComponents2($selectOptions)
	{
		$output = array();
		$allowedProperties = array('ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL');
		foreach ($this->components as $cix => $component3)
		{
			if (!in_array($component3->objName, array('vevent', 'vtodo', 'vjournal', 'vfreebusy')))
				continue;
			$uid = $component3->getProperty('UID');
			foreach ($selectOptions as $propName => $pvalue)
			{
				$propName = strtoupper($propName);
				if (!in_array($propName, $allowedProperties))
					continue;
				if (!is_array($pvalue))
					$pvalue = array($pvalue);
				if (( 'UID' == $propName ) && in_array($uid, $pvalue))
				{
					$output[] = $component3->copy();
					continue;
				}
				elseif (( 'ATTENDEE' == $propName ) || ( 'CATEGORIES' == $propName ) || ( 'CONTACT' == $propName ) || ( 'RELATED-TO' == $propName ) || ( 'RESOURCES' == $propName ))
				{ // multiple occurrence?
					$propValues = array();
					$component3->_getProperties($propName, $propValues);
					$propValues = array_keys($propValues);
					foreach ($pvalue as $theValue)
					{
						if (in_array($theValue, $propValues) && !isset($output[$uid]))
						{
							$output[$uid] = $component3->copy();
							break;
						}
					}
					continue;
				} // end   elseif( // multiple occurrence?
				elseif (FALSE === ( $d = $component3->getProperty($propName))) // single occurrence
					continue;
				if (is_array($d))
				{
					foreach ($d as $part)
					{
						if (in_array($part, $pvalue) && !isset($output[$uid]))
							$output[$uid] = $component3->copy();
					}
				}
				elseif (( 'SUMMARY' == $propName ) && !isset($output[$uid]))
				{
					foreach ($pvalue as $pval)
					{
						if (FALSE !== stripos($d, $pval))
						{
							$output[$uid] = $component3->copy();
							break;
						}
					}
				}
				elseif (in_array($d, $pvalue) && !isset($output[$uid]))
					$output[$uid] = $component3->copy();
			} // end foreach( $selectOptions as $propName => $pvalue ) {
		} // end foreach( $this->components as $cix => $component3 ) {
		if (!empty($output))
		{
			ksort($output);
			$output = array_values($output);
		}
		return $output;
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
	function deleteComponent($arg1, $arg2 = FALSE)
	{
		$argType = $index = null;
		if (ctype_digit((string) $arg1))
		{
			return $this->deleteComponentByIndex($arg1);			
		}
		elseif (( strlen($arg1) <= strlen('vfreebusy')) && ( FALSE === strpos($arg1, '@')))
		{
			$argType = strtolower($arg1);
			$index = (!empty($arg2) && ctype_digit((string) $arg2)) ? ((int) $arg2 - 1 ) : 0;
			return $this->deleteComponentByType($argType, $index);
		}
		else
		{
			return $this->deleteComponentByUid($arg1);
		}
		return FALSE;
	}
	
	/**
	 * Delete calendar component at given position.
	 * 
	 * @param int $index the position number where to delete the component. Starts at 1.
	 *
	 * @return bool
	 */
	public function deleteComponentByIndex( $index )
	{
		$index = (int) $index - 1;
		
		if( isset($this->components[$index]) && !empty($this->components[$index]))
		{
			unset($this->components[$index]);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Delete nth occurence of component of given type.
	 * 
	 * **Example**:
	 *     
	 *     $this->deleteComponentByType( 'vevent', 2);
	 *     // deletes the second vevent
	 * 
	 * @param string $type The type of component to delete
	 * @param int $position The occurence to be deleted
	 *
	 * @return bool
	 */
	public function deleteComponentByType( $type, $position )
	{		
		$cix1dC = 0;
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;
			if ($type == $component->objName)
			{
				if ($position == $cix1dC)
				{
					unset($this->components[$cix]);
					return TRUE;
				}
				$cix1dC++;
			}
		}
		return FALSE;
	}

	/**
	 * Delete component with uid.
	 *  
	 * @param string $uid The uid of the component to be deleted
	 *
	 * @return bool
	 */
	function deleteComponentByUid($uid)
	{
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;
			if ( $uid == $component->getProperty('uid') )
			{
				unset($this->components[$cix]);
				return TRUE;
			}
		}
		return FALSE;
	}
}

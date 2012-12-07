<?php

/**
 * Holds the components of a vcalendar.
 * 
 * An elaborate search mechanism makes deleting, adding, selection certan 
 * components easier. The implemented interfaces allow for array-like behaviour.
 */
class ComponentHolder implements ArrayAccess, Iterator, Countable
{

	protected $components = array();
	private $compix = null;
	protected $currentKey = 0;

	/**
	 * Search for the nth component with the given type.
	 * 
	 * @param string $type a valid componenttype
	 * @param int $index
	 * 
	 * @return int|bool position of the compoennt or FALSE if not found
	 */
	private function searchByType($type, $index = null)
	{
		$cix1sC = 0;
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;

			if ($type == $component->objName)
			{
				if (is_null($index))
				{
					// if no pos is given take the first
					return $cix;
				}
				elseif ($index == $cix1sC)
				{
					return $cix;
				}
				$cix1sC++;
			}
		}

		return FALSE;
	}

	/**
	 * Search for the nth component with the given uid.
	 * 
	 * @param string $uid
	 * @param int $index
	 * 
	 * @return int|bool position of the compoennt or FALSE if not found
	 */
	private function searchByUid($uid, $index = null)
	{
		$cix1sC = 0;
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;

			if ($uid == $component->getProperty('uid'))
			{
				if (is_null($index))
				{
					// if no pos is given take the first
					return $cix;
				}
				elseif ($index == $cix1sC)
				{
					return $cix;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Return position of nth occurence of component with given properties.
	 * 
	 * **Example**:
	 *     
	 *     $this->getComponentByType( array('LOCATION' => 'Tampa'), 2);
	 *     // returns the position of the second vevent at Tampa in the 
	 *     // components-array
	 * 
	 * @param array $properties The properties of the saught after component
	 * @param int $position The occurence 
	 *
	 * @return int|bool position of the compoennt or FALSE if not found
	 */
	private function searchByProperties($properties, $position)
	{
		$dateProps = array('DTSTART', 'DTEND', 'DUE', 'CREATED', 'COMPLETED', 'DTSTAMP', 'LAST-MODIFIED', 'RECURRENCE-ID');
		$otherProps = array('ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL');
		$mProps = array('ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO', 'RESOURCES');

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
						return $cix;
					$cix1gC++;
				}
			} // end elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
		} // end foreach ( $this->components.. .
		/* not found.. . */
		return FALSE;
	}

	/**
	 * Search for a component in the Holder.
	 * 
	 * If $arg1 is a integer starting at 1, the corresponding position (starting 
	 * at 0) in the component-array is returned.
	 * 
	 * If $arg1 is an array it is assumed to be an array of propertynames and 
	 * values ( < PROPERTYNAME => VALUE > ), the positione of the first component 
	 * with those properties is returned.
	 * 
	 * If $arg1 is a componentname, the position of the nth ($arg2 starting at 1) 
	 * occurence in the component-array is returned. 
	 * 
	 * If a uid is supplied, the position of the nth ($arg2 starting at 1) 
	 * occurence in the component-array is returned. 
	 * 
	 * @param mixed $arg1
	 * @param mixed $arg2
	 * @return mixed An int if the component was found, FALSE otherwise
	 */
	private function search($arg1 = FALSE, $arg2 = FALSE)
	{
		// search for a component. We have the following possibilities
		// - its array-index 
		//		=> identified by an int
		// - its properties
		//		=> identified by an array of 'PROPERTYNAME' => 'VALUE'
		// - its type
		//		=> identified by a valid componentname
		// - its uid, which is a property of the components
		//		=> everything else
		// - If no arguments were set, then no search was executed
		$index = FALSE;
		if (self::isPosition($arg1))
		{
			$index = (int) $arg1 - 1;
		}
		elseif (is_array($arg1))
		{
			$pos = self::isPosition($arg2) ? ((int) $arg2) - 1 : 0;
			$index = $this->searchByProperties($arg1, $pos);
		}
		elseif (self::isComponentName($arg1))
		{
			$argType = strtolower($arg1);
			$pos = self::isPosition($arg2) ? ((int) $arg2) - 1 : 0;
			$index = $this->searchByType($argType, $pos);
		}
		elseif ($arg1)
		{
			$pos = self::isPosition($arg2) ? ((int) $arg2) - 1 : 0;
			$index = $this->searchByUid($arg1, $pos);
		}

		return $index;
	}

	/**
	 * Intersect the given (array of) component type with allowed types.
	 * 
	 * @param mixed $cType ComponentType string or Array thereof
	 * @return mixed
	 */
	private function intersectValidTypes($cType)
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
	 * add calendar component to container
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.8.8 - 2011-03-15
	 * @param object $component calendar component
	 * @param mixed $arg1 optional, ordno/component type/ component uid
	 * @param mixed $arg2 optional, ordno if arg1 = component type
	 * @return void
	 */
	function set($component, $arg1 = FALSE, $arg2 = FALSE)
	{
		if (!in_array($component->objName, array('valarm', 'vtimezone')))
		{
			/* make sure dtstamp and uid is set */
			$dummy1 = $component->getProperty('dtstamp');
			$dummy2 = $component->getProperty('uid');
		}

		$index = $this->search($arg1, $arg2);

		if (is_int($index))
		{
			$this->components[$index] = $component->copy();
			ksort($this->components, SORT_NUMERIC);
		}
		else
		{
			/* not found.. . insert last in chain anyway .. . */
			$this->components[] = $component->copy();
		}
		return TRUE;
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
	function delete($arg1, $arg2 = FALSE)
	{
		$index = $this->search($arg1, $arg2);

		if (is_int($index))
		{
			unset($this->components[$index]);
			return TRUE;
		}
		return FALSE;
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
	function get($arg1 = FALSE, $arg2 = FALSE)
	{
		if (self::isPosition($arg1) || self::isComponentName($arg1))
		{
			unset($this->compix);
		}
		elseif (is_array($arg1))
		{
			$arg2 = implode('-', array_keys($arg1));
			$arg2 = $this->compix[$arg2] = ( isset($this->compix[$arg2])) ? $this->compix[$arg2] + 1 : 1;
		}
		elseif ($arg1 === FALSE)
		{
			// first or next in component chain			
			$arg1 = $this->compix['INDEX'] = ( isset($this->compix['INDEX'])) ? $this->compix['INDEX'] + 1 : 1;
		}

		$pos = FALSE;
		if ($arg2 === FALSE)
		{
			$arg1 = self::isComponentName($arg1) ? strtolower($arg1) : $arg1;
			$pos = $this->compix[$arg1] = ( isset($this->compix[$arg1])) ? $this->compix[$arg1] + 1 : 1;
		}
		elseif (self::isPosition($arg2))
		{
			$pos = (int) $arg2;
		}

		$index = $this->search($arg1, $pos);

		if (is_int($index))
		{
			$component = $this->components[$index];
			return $component->copy();
		}
		else
		{
			unset($this->compix);
			return FALSE;
		}
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
	public function selectComponents($startY = FALSE, $startM = FALSE, $startD = FALSE, $endY = FALSE, $endM = FALSE, $endD = FALSE, $cType = FALSE, $flat = FALSE, $any = TRUE, $split = TRUE)
	{
		/* check  if empty calendar */
		if (0 >= count($this->components))
			return FALSE;
		if (is_array($startY))
			return $this->selectComponents2($startY);
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
//echo 'selectComp arg='.date( 'Y-m-d H:i:s', $startDate).' -- '.date( 'Y-m-d H:i:s', $endDate)."<br />\n"; $tcnt = 0;// test ###
		/* check component types */
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
		if (( FALSE === $flat ) && ( FALSE === $any )) // invalid combination
			$split = FALSE;
		if (( TRUE === $flat ) && ( TRUE === $split )) // invalid combination
			$split = FALSE;
		/* iterate components */
		$result = array();
		foreach ($this->components as $cix => $component)
		{
			if (empty($component))
				continue;
			unset($start);
			/* deselect unvalid type components */
			if (!in_array($component->objName, $cType))
				continue;
			$start = $component->getProperty('dtstart');
			/* select due when dtstart is missing */
			if (empty($start) && ( $component->objName == 'vtodo' ) && ( FALSE === ( $start = $component->getProperty('due'))))
				continue;
			if (empty($start))
				continue;
			$dtendExist = $dueExist = $durationExist = $endAllDayEvent = $recurrid = FALSE;
			unset($end, $startWdate, $endWdate, $rdurWsecs, $rdur, $exdatelist, $workstart, $workend, $endDateFormat); // clean up
			$startWdate = iCalUtilityFunctions::_date2timestamp($start);
			$startDateFormat = ( isset($start['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
			/* get end date from dtend/due/duration properties */
			$end = $component->getProperty('dtend');
			if (!empty($end))
			{
				$dtendExist = TRUE;
				$endDateFormat = ( isset($end['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
			}
			if (empty($end) && ( $component->objName == 'vtodo' ))
			{
				$end = $component->getProperty('due');
				if (!empty($end))
				{
					$dueExist = TRUE;
					$endDateFormat = ( isset($end['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
				}
			}
			if (!empty($end) && !isset($end['hour']))
			{
				/* a DTEND without time part regards an event that ends the day before,
				  for an all-day event DTSTART=20071201 DTEND=20071202 (taking place 20071201!!! */
				$endAllDayEvent = TRUE;
				$endWdate = mktime(23, 59, 59, $end['month'], ($end['day'] - 1), $end['year']);
				$end['year'] = date('Y', $endWdate);
				$end['month'] = date('m', $endWdate);
				$end['day'] = date('d', $endWdate);
				$end['hour'] = 23;
				$end['min'] = $end['sec'] = 59;
			}
			if (empty($end))
			{
				$end = $component->getProperty('duration', FALSE, FALSE, TRUE); // in dtend (array) format
				if (!empty($end))
					$durationExist = TRUE;
				$endDateFormat = ( isset($start['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
// if( !empty($end))  echo 'selectComp 4 start='.implode('-',$start).' end='.implode('-',$end)."<br />\n"; // test ###
			}
			if (empty($end))
			{ // assume one day duration if missing end date
				$end = array('year'		 => $start['year'], 'month'		 => $start['month'], 'day'		 => $start['day'], 'hour'		 => 23, 'min'		 => 59, 'sec'		 => 59);
			}
// if( isset($end))  echo 'selectComp 5 start='.implode('-',$start).' end='.implode('-',$end)."<br />\n"; // test ###
			$endWdate = iCalUtilityFunctions::_date2timestamp($end);
			if ($endWdate < $startWdate)
			{ // MUST be after start date!!
				$end = array('year'		 => $start['year'], 'month'		 => $start['month'], 'day'		 => $start['day'], 'hour'		 => 23, 'min'		 => 59, 'sec'		 => 59);
				$endWdate = iCalUtilityFunctions::_date2timestamp($end);
			}
			$rdurWsecs = $endWdate - $startWdate; // compute event (component) duration in seconds
			/* make a list of optional exclude dates for component occurence from exrule and exdate */
			$exdatelist = array();
			$workstart = iCalUtilityFunctions::_timestamp2date(( $startDate - $rdurWsecs), 6);
			$workend = iCalUtilityFunctions::_timestamp2date(( $endDate + $rdurWsecs), 6);
			while (FALSE !== ( $exrule = $component->getProperty('exrule')))	// check exrule
				iCalUtilityFunctions::_recur2date($exdatelist, $exrule, $start, $workstart, $workend);
			while (FALSE !== ( $exdate = $component->getProperty('exdate')))
			{  // check exdate
				foreach ($exdate as $theExdate)
				{
					$exWdate = iCalUtilityFunctions::_date2timestamp($theExdate);
					$exWdate = mktime(0, 0, 0, date('m', $exWdate), date('d', $exWdate), date('Y', $exWdate)); // on a day-basis !!!
					if ((( $startDate - $rdurWsecs ) <= $exWdate ) && ( $endDate >= $exWdate ))
						$exdatelist[$exWdate] = TRUE;
				} // end - foreach( $exdate as $theExdate )
			}  // end - check exdate
			$compUID = $component->getProperty('UID');
			/* check recurrence-id (with sequence), remove hit with reccurr-id date */
			if (( FALSE !== ( $recurrid = $component->getProperty('recurrence-id'))) &&
					( FALSE !== ( $sequence = $component->getProperty('sequence'))))
			{
				$recurrid = iCalUtilityFunctions::_date2timestamp($recurrid);
				$recurrid = mktime(0, 0, 0, date('m', $recurrid), date('d', $recurrid), date('Y', $recurrid)); // on a day-basis !!!
				$endD = $recurrid + $rdurWsecs;
				do
				{
					if (date('Ymd', $startWdate) != date('Ymd', $recurrid))
						$exdatelist[$recurrid] = TRUE; // exclude all other days than startdate
					$wd = getdate($recurrid);
					if (isset($result[$wd['year']][$wd['mon']][$wd['mday']][$compUID]))
						unset($result[$wd['year']][$wd['mon']][$wd['mday']][$compUID]); // remove from output, dtstart etc added below
					if ($split && ( $recurrid <= $endD ))
						$recurrid = mktime(0, 0, 0, date('m', $recurrid), date('d', $recurrid) + 1, date('Y', $recurrid)); // step one day
					else
						break;
				} while (TRUE);
			} // end recurrence-id/sequence test
			/* select only components with.. . */
			if ((!$any && ( $startWdate >= $startDate ) && ( $startWdate <= $endDate )) || // (dt)start within the period
					( $any && ( $startWdate < $endDate ) && ( $endWdate >= $startDate )))
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
						if (date('Ymd', $rstart) > $startYMD) // date after dtstart
							$datestring = date($startDateFormat, mktime(0, 0, 0, date('m', $rstart), date('d', $rstart), date('Y', $rstart)));
						else
							$datestring = date($startDateFormat, $rstart);
						if (isset($start['tz']))
							$datestring .= ' ' . $start['tz'];
// echo "X-CURRENT-DTSTART 3 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br />";$component->setProperty( 'X-CNT', $tcnt ); // test ###
						$component->setProperty('X-CURRENT-DTSTART', $datestring);
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
		} // end foreach ( $this->components as $cix => $component )
		if (0 >= count($result))
			return FALSE;
		elseif (!$flat)
		{
			foreach ($result as $y => $yeararr)
			{
				foreach ($yeararr as $m => $montharr)
				{
					foreach ($montharr as $d => $dayarr)
					{
						if (empty($result[$y][$m][$d]))
							unset($result[$y][$m][$d]);
						else
							$result[$y][$m][$d] = array_values($dayarr); // skip tricky UID-index, hoping they are in hour order.. .
					}
					if (empty($result[$y][$m]))
						unset($result[$y][$m]);
					else
						ksort($result[$y][$m]);
				}
				if (empty($result[$y]))
					unset($result[$y]);
				else
					ksort($result[$y]);
			}
			if (empty($result))
				unset($result);
			else
				ksort($result);
		} // end elseif( !$flat )
		if (0 >= count($result))
			return FALSE;
		return $result;
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
		$allowedComps = array('vevent', 'vtodo', 'vjournal', 'vfreebusy');
		$allowedProperties = array('ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL');
		foreach ($this->components as $cix => $component3)
		{
			if (!in_array($component3->objName, $allowedComps))
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
	 * create new calendar component, already included within calendar
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.6.33 - 2011-01-03
	 * @param string $compType component type
	 * @param array $config the configuration for the new component
	 * @return object (reference)
	 */
	function & newComponent($compType, $config)
	{
		// We add and then return the new component. Explicit array index needed.
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
	 * Wether the passed argument is a valid position key (int).
	 * 
	 * @param mixed $arg
	 * @return bool
	 */
	static protected function isPosition($arg)
	{
		return ctype_digit((string) $arg);
	}

	/**
	 * Wether the passed argument is a valid componentname.
	 * 
	 * @param string $arg
	 * @return bool
	 */
	static protected function isComponentName($arg)
	{
		// This evaluates freebusy, todo, and so forth as valid component names
		// strlen($arg1) <= strlen('vfreebusy')) && ( FALSE === strpos($arg1, '@')


		$componentNames = array('vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone');
		return in_array(strtolower($arg), $componentNames);
	}

	/**
	 * Returns wether the instance has an element at the given offset.
	 * 
	 * @implements ArrayAccess
	 * 
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->components[$offset]);
	}

	/**
	 * Returns the element at the given offset.
	 * 
	 * @implements ArrayAccess
	 * 
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->components[$offset];
	}

	/**
	 * Sets the given value at the offset.
	 * 
	 * @implements ArrayAccess
	 * 
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset != null)
		{
			return $this->components[$offset] = $value;
		}
		else
		{
			return $this->components[] = $value;
		}
	}

	/**
	 * Remove the value at the given offset, and remove te offset.
	 * 
	 * @implements ArrayAccess
	 * 
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->components[$offset]);
	}

	/**
	 * Returns the value at the current position.
	 * 
	 * @implements Iterator
	 * 
	 * @return mixed
	 */
	public function current()
	{
		return $this->components[$this->currentKey];
	}

	/**
	 * Returns the offet of the current position.
	 * 
	 * @implements Iterator
	 * 
	 * @return mixed
	 */
	public function key()
	{
		return $this->currentKey;
	}

	/**
	 * Sets the curent offset to the next in line.
	 * 
	 * @implements Iterator
	 * 
	 * @return mixed
	 */
	public function next()
	{
		return $this->currentKey++;
	}

	/**
	 * Sets the current offset to the first element.
	 * 
	 * @implements Iterator
	 * 
	 * @return void
	 */
	public function rewind()
	{
		return $this->currentKey = 0;
	}

	/**
	 * Wether the current offset is valid (set).
	 * 
	 * @implements Iterator
	 * 
	 * @return bool
	 */
	public function valid()
	{
		return isset($this->components[$this->currentKey]);
	}

	/**
	 * Returns the number of components.
	 * 
	 * @implements Countable
	 * 
	 * @return int
	 */
	public function count()
	{
		return count($this->components);
	}

}

?>

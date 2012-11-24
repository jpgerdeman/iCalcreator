<?php

class Component_Decorator_TimeSelector extends Component_Decorator {
	
	var $endDateStamp = null;	
	var $startDateStamp = null;	
		
	/**
	 * Compute the date, when this component starts or is due.
	 * 
	 * @return array
	 */
	function computeStartDate()
	{		
		$start = $this->component->getProperty('dtstart');
		$due = $this->component->getProperty('due');

		if( empty($start) )
		{
			$start = $due;
		}

		return $start;
	}
	
	/**
	 * Return the starting date as timestamp.
	 * 
	 * @see computeStartDate
	 * 
	 * @return string a unix timestamp
	 */
	function computeStartDateStamp()
	{
		if( is_null($this->startDateStamp) )
		{
			$start = $this->computeStartDate();
			$this->startDateStamp = iCalUtilityFunctions::_date2timestamp($start);
		}
		
		return $this->startDateStamp;
	}
	
	/**
	 * Returns the format of the start date.
	 * 
	 * If no startdate is given, a default is returned. Using the format
	 * without a start date is nonsense however. Please check the 
	 * existence of startdate prior to using this format;
	 * 
	 * @return string
	 */
	function computeStartDateFormat()
	{
		
			$start = $this->component->getProperty('dtstart');
			$due = $this->component->getProperty('due');
								
			if( empty($start) )
			{
				$start = $due;
			}
			
			return ( isset($start['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
	}
	
	/**
	 * Returns the enddate.
	 * 
	 * The enddate will be a day after the startdate, if it is not set or 
	 * incorrect.
	 * 
	 * @return array
	 */
	function computeEndDate()
	{		
		$end = $this->component->getProperty('dtend');
		if (!empty($end) && !isset($end['hour']))
		{
			/* a DTEND without time part regards an event that ends the day before,
			  for an all-day event DTSTART=20071201 DTEND=20071202 (taking place 20071201!!! */			
			$end['year'] = date('Y', $endWdate);
			$end['month'] = date('m', $endWdate);
			$end['day'] = date('d', $endWdate);
			$end['hour'] = 23;
			$end['min'] = $end['sec'] = 59;
		}
		if (empty($end))
		{ 
			$start = $this->computeStartDate();
			// assume one day duration if missing end date
			$end = array('year'		 => $start['year'], 'month'		 => $start['month'], 'day'		 => $start['day'], 'hour'		 => 23, 'min'		 => 59, 'sec'		 => 59);
		}
		
		$startWdate = $this->computeStartDateStamp();
		$endWdate = iCalUtilityFunctions::_date2timestamp($end);
		if ($endWdate < $startWdate)
		{ // MUST be after start date!!
			$end = array('year'		 => $start['year'], 'month'		 => $start['month'], 'day'		 => $start['day'], 'hour'		 => 23, 'min'		 => 59, 'sec'		 => 59);			
		}
		
		return $end;
	}
	
	/**
	 * Return the ending date as timestamp.
	 * 
	 * @see computeEndDate
	 * 
	 * @return string a unix timestamp
	 */
	function computeEndDateStamp()
	{
		if( is_null($this->endDateStamp) )
		{
			$start = $this->computeEndDate();
			$this->endDateStamp = iCalUtilityFunctions::_date2timestamp($start);
		}
		
		return $this->endDateStamp;
	}
	
	/**
	 * Compute needed dateformat for enddate from component properties.
	 *  
	 * @return string
	 */
	function computeEndDateFormat()
	{	
		$end = $this->component->getProperty('dtend');
		if (!empty($end))
		{				
			$endDateFormat = ( isset($end['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
		}
		if (empty($end) && ( $this->component->objName == 'vtodo' ))
		{
			$end = $this->component->getProperty('due');
			if (!empty($end))
			{
				$endDateFormat = ( isset($end['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
			}
		}
		if (empty($end))
		{				
			$endDateFormat = ( isset($start['hour'])) ? 'Y-m-d H:i:s' : 'Y-m-d';
		}
		
		return $endDateFormat;	
	}
	
	function hasEndDate()
	{
		$end = $this->component->getProperty('dtend');
		return !empty($end);
	}
	
	function hasDueDate()
	{		
		$end = $this->component->getProperty('dtend');
		if (empty($end) && ( $this->component->objName == 'vtodo' ))
		{
			$end = $this->component->getProperty('due');
			if (!empty($end))
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	function hasDuration()
	{
		$end = $this->component->getProperty('dtend');
		if (empty($end))
		{
			$end = $this->component->getProperty('duration', FALSE, FALSE, TRUE); // in dtend (array) format
			if (!empty($end))
				return TRUE;
		}			
		return FALSE;
	}
	
	function isAllDayEvent()
	{
		$end = $this->component->getProperty('dtend');
			
		return (!empty($end) && !isset($end['hour']));
	}
	
	function isRecurring()
	{
		$recurrid = $this->getProperty('recurrence-id');
		
		return $recurrid !== FALSE;
	}
	
	/**
	 * Indicates wether this component lies between the given start and end dates.
	 * 
	 * If $startsonly is TRUE then the return value indicates wether this 
	 * components starts in the given period, otherwise it indicates that
	 * the component start and end dates overlap with the indicated period.
	 * 
	 * @param string $startstamp a unix timestamp
	 * @param string $endstamp a unix timestamp
	 * @param bool $startsonly
	 * 
	 * @return bool
	 */
	function isWithinPeriod( $startstamp, $endstamp, $startsonly = FALSE )
	{
		
		$compstart = $this->computeStartDateStamp();
		$compend = $this->computeEndDateStamp();
		
		$startswithin = (( $compstart >= $startstamp ) && ( $compstart <= $endstamp ));
		$lieswithin = (( $compstart < $endstamp ) && ( $compend >= $startstamp ));
	
		return ( $startsonly && $startswithin ) || (!$startsonly && $lieswithin );
	}
	
	function computeExcludeDates( $recurrenceAsSingleEvents = TRUE )
	{
		$start = $this->computeStartDate();
		$end = $this->computeEndDate();
		$startWdate = $this->computeEndDateStamp();
		$endWdate = $this->computeEndDateStamp();
		$rdurWsecs = $endWdate - $startWdate; // compute event (component) duration in seconds
		/* make a list of optional exclude dates for component occurence from exrule and exdate */
		$exdatelist = array();
		$workstart = iCalUtilityFunctions::_timestamp2date(( $startWdate - $rdurWsecs), 6);
		$workend = iCalUtilityFunctions::_timestamp2date(( $endWdate + $rdurWsecs), 6);
		while (FALSE !== ( $exrule = $this->getProperty('exrule')))	// check exrule
			iCalUtilityFunctions::_recur2date($exdatelist, $exrule, $start, $workstart, $workend);
		while (FALSE !== ( $exdate = $this->getProperty('exdate')))
		{  // check exdate
			foreach ($exdate as $theExdate)
			{
				$exWdate = iCalUtilityFunctions::_date2timestamp($theExdate);
				$exWdate = mktime(0, 0, 0, date('m', $exWdate), date('d', $exWdate), date('Y', $exWdate)); // on a day-basis !!!
				if ((( $startWdate - $rdurWsecs ) <= $exWdate ) && ( $endWdate >= $exWdate ))
					$exdatelist[$exWdate] = TRUE;
			} // end - foreach( $exdate as $theExdate )
		}  // end - check exdate
		
		
		// if the startdate is a recurring event it can't be excluded
		if (( FALSE !== ( $recurrid = $this->getProperty('recurrence-id'))) &&
					( FALSE !== ( $sequence = $this->getProperty('sequence'))))
		{
			$recurrid = iCalUtilityFunctions::_date2timestamp($recurrid);
			if( $recurrenceAsSingleEvents )
			{
				// Every single day of recurrence is treated as separate event
				// So range has to include all the recurring days
				$range = $this->range($recurrid);
			}
			else
			{
				// the recurring event is treated as a single event
				// So only the first day is releant
				$recurrday = mktime(0, 0, 0, date('m', $recurrid), date('d', $recurrid), date('Y', $recurrid));
				$range = array($recurrday);
			}
			
			foreach( $range as $daystamp )
			{
				if (date('Ymd', $startWdate) != date('Ymd', $daystamp))
					$exdatelist[$daystamp] = TRUE; // exclude all other days than startdate
			}

		} 
		
		return $exdatelist;
	}	
	
	/**
	 * Returns an array of days starting from starttimestamp to the components enddate.
	 * 
	 * if no starttimestamp is supplied the components starting time is used.
	 * 
	 * @param string $starttimestamp a unix timestamp
	 * @return string[]
	 */
	function range( $starttimestamp = null )
	{
		if( is_null($starttimestamp) )
		{
			$starttimestamp = $this->computeStartDateStamp();
		}
		
		// Only day is relevant
		$curdate = mktime(0, 0, 0, date('m', $starttimestamp), date('d', $starttimestamp), date('Y', $starttimestamp));
		$endDate = $this->computeEndDateFormat();
		
		$dayrange = array();
		while( $curdate < $endDate )
		{
			$dayrange[] = $curdate;
			// step one day
			$curdate = mktime(0, 0, 0, date('m', $curdate), date('d', $curdate) + 1, date('Y', $curdate)); 
		}
		
		return $dayrange;
	}
}
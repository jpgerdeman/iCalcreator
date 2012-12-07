<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ComponentCollection
 *
 * @author epjg
 */
class ComponentCollection implements ArrayAccess, Iterator, Countable
{

	protected $components = array();
	private $compix = null;
	protected $currentKey = 0;

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
	public function sort($sortArg = FALSE)
	{
		if (is_array($this->components))
		{
			if ($sortArg)
			{
				$sortArg = strtoupper($sortArg);
				if (!in_array($sortArg, array('ATTENDEE', 'CATEGORIES', 'CONTACT', 'DTSTAMP', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'URL')))
					$sortArg = FALSE;
			}
			/* set sort parameters for each component */
			foreach ($this->components as $cix => & $c)
			{
				$c->srtk = array('0', '0', '0', '0');
				if ('vtimezone' == $c->objName)
				{
					if (FALSE === ( $c->srtk[0] = $c->getProperty('tzid')))
						$c->srtk[0] = 0;
					continue;
				}
				elseif ($sortArg)
				{
					if (( 'ATTENDEE' == $sortArg ) || ( 'CATEGORIES' == $sortArg ) || ( 'CONTACT' == $sortArg ) || ( 'RELATED-TO' == $sortArg ) || ( 'RESOURCES' == $sortArg ))
					{
						$propValues = array();
						$c->_getProperties($sortArg, $propValues);
						if (!empty($propValues))
						{
							$sk = array_keys($propValues);
							$c->srtk[0] = $sk[0];
							if ('RELATED-TO' == $sortArg)
								$c->srtk[0] .= $c->getProperty('uid');
						}
						elseif ('RELATED-TO' == $sortArg)
							$c->srtk[0] = $c->getProperty('uid');
					}
					elseif (FALSE !== ( $d = $c->getProperty($sortArg)))
						$c->srtk[0] = $d;
					continue;
				}
				if (FALSE !== ( $d = $c->getProperty('X-CURRENT-DTSTART')))
				{
					$c->srtk[0] = iCalUtilityFunctions::_date_time_string($d[1]);
					unset($c->srtk[0]['unparsedtext']);
				}
				elseif (FALSE === ( $c->srtk[0] = $c->getProperty('dtstart')))
					$c->srtk[1] = 0;												  // sortkey 0 : dtstart
				if (FALSE !== ( $d = $c->getProperty('X-CURRENT-DTEND')))
				{
					$c->srtk[1] = iCalUtilityFunctions::_date_time_string($d[1]);   // sortkey 1 : dtend/due(/dtstart+duration)
					unset($c->srtk[1]['unparsedtext']);
				}
				elseif (FALSE === ( $c->srtk[1] = $c->getProperty('dtend')))
				{
					if (FALSE !== ( $d = $c->getProperty('X-CURRENT-DUE')))
					{
						$c->srtk[1] = iCalUtilityFunctions::_date_time_string($d[1]);
						unset($c->srtk[1]['unparsedtext']);
					}
					elseif (FALSE === ( $c->srtk[1] = $c->getProperty('due')))
						if (FALSE === ( $c->srtk[1] = $c->getProperty('duration', FALSE, FALSE, TRUE)))
							$c->srtk[1] = 0;
				}
				if (FALSE === ( $c->srtk[2] = $c->getProperty('created')))	  // sortkey 2 : created/dtstamp
					if (FALSE === ( $c->srtk[2] = $c->getProperty('dtstamp')))
						$c->srtk[2] = 0;
				if (FALSE === ( $c->srtk[3] = $c->getProperty('uid')))		  // sortkey 3 : uid
					$c->srtk[3] = 0;
			} // end foreach( $this->components as & $c
			/* sort */
			usort($this->components, array($this, '_cmpfcn'));
		}
	}

	protected function _cmpfcn($a, $b)
	{
		if (empty($a))
			return -1;
		if (empty($b))
			return 1;
		if ('vtimezone' == $a->objName)
		{
			if ('vtimezone' != $b->objName)
				return -1;
			elseif ($a->srtk[0] <= $b->srtk[0])
				return -1;
			else
				return 1;
		}
		elseif ('vtimezone' == $b->objName)
			return 1;
		$sortkeys = array('year', 'month', 'day', 'hour', 'min', 'sec');
		for ($k = 0; $k < 4; $k++)
		{
			if (empty($a->srtk[$k]))
				return -1;
			elseif (empty($b->srtk[$k]))
				return 1;
			if (is_array($a->srtk[$k]))
			{
				if (is_array($b->srtk[$k]))
				{
					foreach ($sortkeys as $key)
					{
						if (!isset($a->srtk[$k][$key]))
							return -1;
						elseif (!isset($b->srtk[$k][$key]))
							return 1;
						if (empty($a->srtk[$k][$key]))
							return -1;
						elseif (empty($b->srtk[$k][$key]))
							return 1;
						if ($a->srtk[$k][$key] == $b->srtk[$k][$key])
							continue;
						if (( (int) $a->srtk[$k][$key] ) < ((int) $b->srtk[$k][$key] ))
							return -1;
						elseif (( (int) $a->srtk[$k][$key] ) > ((int) $b->srtk[$k][$key] ))
							return 1;
					}
				}
				else
					return -1;
			}
			elseif (is_array($b->srtk[$k]))
				return 1;
			elseif ($a->srtk[$k] < $b->srtk[$k])
				return -1;
			elseif ($a->srtk[$k] > $b->srtk[$k])
				return 1;
		}
		return 0;
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

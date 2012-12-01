<?php
/**
 * Takes care of setting the correct HTTP-Headers for the vcalendar.
 * 
 */
class HTTPvcalendar extends vcalendar
{

	/**
	 * a HTTP redirect header is sent with created, updated and/or parsed calendar
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.10.24 - 2011-12-23
	 * @param bool $utf8Encode
	 * @param bool $gzip
	 * @return redirect
	 */
	function returnCalendar($utf8Encode = FALSE, $gzip = FALSE)
	{
		$filename = $this->getConfig('filename');
		$output = $this->createCalendar();
		if ($utf8Encode)
			$output = utf8_encode($output);
		if ($gzip)
		{
			$output = gzencode($output, 9);
			header('Content-Encoding: gzip');
			header('Vary: *');
			header('Content-Length: ' . strlen($output));
		}
		if ('xcal' == $this->format)
			header('Content-Type: application/calendar+xml; charset=utf-8');
		else
			header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: max-age=10');
		die($output);
	}

	/**
	 * save content in a file
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.2.12 - 2007-12-30
	 * @param string $directory optional
	 * @param string $filename optional
	 * @param string $delimiter optional
	 * @return bool
	 */
	function saveCalendar($directory = FALSE, $filename = FALSE, $delimiter = FALSE)
	{
		if ($directory)
			$this->setConfig('directory', $directory);
		if ($filename)
			$this->setConfig('filename', $filename);
		if ($delimiter && ($delimiter != DIRECTORY_SEPARATOR ))
			$this->setConfig('delimiter', $delimiter);
		if (FALSE === ( $dirfile = $this->getConfig('url')))
			$dirfile = $this->getConfig('dirfile');
		$iCalFile = @fopen($dirfile, 'w');
		if ($iCalFile)
		{
			if (FALSE === fwrite($iCalFile, $this->createCalendar()))
				return FALSE;
			fclose($iCalFile);
			return TRUE;
		}
		else
			return FALSE;
	}

	/**
	 * if recent version of calendar file exists (default one hour), an HTTP redirect header is sent
	 * else FALSE is returned
	 *
	 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
	 * @since 2.2.12 - 2007-10-28
	 * @param string $directory optional alt. int timeout
	 * @param string $filename optional
	 * @param string $delimiter optional
	 * @param int timeout optional, default 3600 sec
	 * @return redirect/FALSE
	 */
	function useCachedCalendar($directory = FALSE, $filename = FALSE, $delimiter = FALSE, $timeout = 3600)
	{
		if ($directory && ctype_digit((string) $directory) && !$filename)
		{
			$timeout = (int) $directory;
			$directory = FALSE;
		}
		if ($directory)
			$this->setConfig('directory', $directory);
		if ($filename)
			$this->setConfig('filename', $filename);
		if ($delimiter && ( $delimiter != DIRECTORY_SEPARATOR ))
			$this->setConfig('delimiter', $delimiter);
		$filesize = $this->getConfig('filesize');
		if (0 >= $filesize)
			return FALSE;
		$dirfile = $this->getConfig('dirfile');
		if (time() - filemtime($dirfile) < $timeout)
		{
			clearstatcache();
			$dirfile = $this->getConfig('dirfile');
			$filename = $this->getConfig('filename');
//    if( headers_sent( $filename, $linenum ))
//      die( "Headers already sent in $filename on line $linenum\n" );
			if ('xcal' == $this->format)
				header('Content-Type: application/calendar+xml; charset=utf-8');
			else
				header('Content-Type: text/calendar; charset=utf-8');
			header('Content-Length: ' . $filesize);
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Cache-Control: max-age=10');
			$fp = @fopen($dirfile, 'r');
			if ($fp)
			{
				fpassthru($fp);
				fclose($fp);
			}
			die();
		}
		else
			return FALSE;
	}

}
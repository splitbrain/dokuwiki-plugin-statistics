<?php

/**
 *    pChart - a PHP class to build charts!
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 1,2,3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Provides static helper methods for converting values from one
 * format to another for display.
 *
 * These methods don't have state, they're only in a class for
 * purposes of namespacing.
 */
class ConversionHelpers {
	/**
	 * Convert seconds to a time format string 
	 */
	static public function ToTime($Value) {
		$Hour = floor ( $Value / 3600 );
		$Minute = floor ( ($Value - $Hour * 3600) / 60 );
		$Second = floor ( $Value - $Hour * 3600 - $Minute * 60 );
		
		if (strlen ( $Hour ) == 1) {
			$Hour = "0" . $Hour;
		}
		if (strlen ( $Minute ) == 1) {
			$Minute = "0" . $Minute;
		}
		if (strlen ( $Second ) == 1) {
			$Second = "0" . $Second;
		}
		
		return ($Hour . ":" . $Minute . ":" . $Second);
	}

	/**
	 * Convert to metric system 
	 */
	static public function ToMetric($Value) {
		$Go = floor ( $Value / 1000000000 );
		$Mo = floor ( ($Value - $Go * 1000000000) / 1000000 );
		$Ko = floor ( ($Value - $Go * 1000000000 - $Mo * 1000000) / 1000 );
		$o = floor ( $Value - $Go * 1000000000 - $Mo * 1000000 - $Ko * 1000 );
		
		if ($Go != 0) {
			return ($Go . "." . $Mo . "g");
		}
		if ($Mo != 0) {
			return ($Mo . "." . $Ko . "m");
		}
		if ($Ko != 0) {
			return ($Ko . "." . $o) . "k";
		}
		return ($o);
	}
	
	/**
	 * Convert to curency 
	 */
	static public function ToCurrency($Value) {
		$Go = floor ( $Value / 1000000000 );
		$Mo = floor ( ($Value - $Go * 1000000000) / 1000000 );
		$Ko = floor ( ($Value - $Go * 1000000000 - $Mo * 1000000) / 1000 );
		$o = floor ( $Value - $Go * 1000000000 - $Mo * 1000000 - $Ko * 1000 );
		
		if (strlen ( $o ) == 1) {
			$o = "00" . $o;
		}
		if (strlen ( $o ) == 2) {
			$o = "0" . $o;
		}
		
		$ResultString = $o;
		if ($Ko != 0) {
			$ResultString = $Ko . "." . $ResultString;
		}
		if ($Mo != 0) {
			$ResultString = $Mo . "." . $ResultString;
		}
		if ($Go != 0) {
			$ResultString = $Go . "." . $ResultString;
		}
		
		$ResultString = $this->Currency . $ResultString;
		return ($ResultString);
	}
}
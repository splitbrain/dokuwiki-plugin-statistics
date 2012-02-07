<?php
/**
 * pData - Simplifying data population for pChart
 * @copyright 2008 Jean-Damien POGOLOTTI
 * @version 2.0
 *
 * http://pchart.sourceforge.net
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 1,2,3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__).'/DataDescription.php';
require_once dirname(__FILE__).'/CSVImporter.php';

class pData {
	private $Data = array();
	private $dataDescription = array();

	/**
	 * An entry for each series giving the maximum value in that
	 * series, if we've previously calculated it
	 */
	private $seriesMinimums = array();

	/**
	 * An entry for each series giving the minimum value in that
	 * series, if we've previously calculated it
	 */
	private $seriesMaximums = array();
	
	public function __construct() {
		$this->dataDescription = new DataDescription('Name', 
													 'number', 'number', 
													 null, null);
	}
		
	/**
	 * Add a single point to the data set
	 */
	public function addPoint($value, $series = "Series1", $Description = "") {
		if (is_array($value)) {
			throw new InvalidArgumentException("Can't pass an array to addPoint()");
		}

		return $this->addPoints(array($value),
								$series,
								$Description);
	}

	/**
	 * Add an array of one or more points to the data set.
	 *
	 * @param $Value If this is an associative array the key values
	 * are ignored. The members of the array are added sequentially to
	 * the data set, taking on auto-incremented ID values based on the
	 * current state of the data set.
	 */
	public function addPoints(array $Value, $Serie = "Series1", $Description = "") {
		$ID = 0;
		for($i = 0; $i < count ( $this->Data ); $i ++) {
			if (isset ( $this->Data [$i] [$Serie] )) {
				$ID = $i + 1;
			}
		}

		foreach ( $Value as $Val ) {
			$this->Data [$ID] [$Serie] = $Val;
			if ($Description != "") {
				$this->Data[$ID]["Name"] = $Description;
			}
			elseif (! isset ( $this->Data [$ID] ["Name"] )) {
				$this->Data [$ID] ["Name"] = $ID;
			}
			$ID ++;
		}
	}
	
	public function addSeries($SerieName = "Series1") {
		if (!isset($this->dataDescription->values)) {
			$this->dataDescription->values[] = $SerieName;
		} else {
			$Found = FALSE;
			foreach ( $this->dataDescription->values as $Value )
				if ($Value == $SerieName) {
					$Found = TRUE;
				}
			
			if (! $Found)
				$this->dataDescription->values[] = $SerieName;
		}
	}
	
	public function addAllSeries() {
		unset($this->dataDescription->values);
		
		if (isset ( $this->Data [0] )) {
			foreach (array_keys($this->Data [0]) as $Key) {
				if ($Key != "Name") {
					$this->dataDescription->values[] = $Key;
				}
			}
		}
	}
	
	public function removeSeries($SerieName = "Series1") {
		if (! isset($this->dataDescription->values))
			return;
		
		foreach ( $this->dataDescription->values as $key => $Value ) {
			if ($Value == $SerieName)
				unset ( $this->dataDescription->values[$key] );
		}
	}
	
	public function setAbscissaLabelSeries($seriesName = "Name") {
		$this->dataDescription->setPosition($seriesName);
	}
	
	public function setSeriesName($Name, $SeriesName = "Series1") {
		$this->dataDescription->description[$SeriesName] = $Name;
	}
	
	public function setXAxisName($Name) {
		$this->dataDescription->setXAxisName($Name);
	}
	
	public function setYAxisName($Name) {
		$this->dataDescription->setYAxisName($Name);
	}
	
	public function setSeriesSymbol($Name, $Symbol) {
		$this->dataDescription->seriesSymbols[$Name] = $Symbol;
	}
	
	/**
	 * @param unknown_type $SerieName
	 */
	public function removeSeriesName($SerieName) {
		if (isset ( $this->dataDescription->description[$SerieName] ))
			unset ( $this->dataDescription->description[$SerieName] );
	}
	
	public function removeAllSeries() {
		$this->dataDescription->values = array();
	}
	
	public function getData() {
		return $this->Data;
	}
	
	public function getDataDescription() {
		return $this->dataDescription;
	}

	/**
	 * @brief Get the minimum data value in the specified series
	 */
	public function getSeriesMin($seriesName) {
		if (isset($this->seriesMinimums[$seriesName])) {
			return $this->seriesMinimums[$seriesName];
		}

		/**
		 * @todo This algorithm assumes that the data set contains a
		 * value at index 0 for the specified series - but this is the
		 * way it's always worked.
		 */
		$this->seriesMinimums[$seriesName] = $this->Data[0][$seriesName];

		foreach ($this->Data as $valueSet) {
			if (isset($valueSet[$seriesName])) {
				$this->seriesMinimums[$seriesName] = 
					min($this->seriesMinimums[$seriesName],
						$valueSet[$seriesName]);
			}
		}

		return $this->seriesMinimums[$seriesName];
	}

	/**
	 * @brief Get the maximum data value in the specified series
	 */
	public function getSeriesMax($seriesName) {
		$this->seriesMaximums[$seriesName] = $this->Data[0][$seriesName];

		foreach ($this->Data as $valueSet) {
			if (isset($valueSet[$seriesName])) {
				$this->seriesMaximums[$seriesName] = 
					max($this->seriesMaximums[$seriesName],
						$valueSet[$seriesName]);
			}
		}

		return $this->seriesMaximums[$seriesName];		
	}

	/**
	 * Get the numeric X and Y values, for a given series.
	 * 
	 * Ugly interface, but this is a step towards refactoring
	 * duplicated code
	 *
	 * For some reason, the data set is assumed to start at (0, 0).
	 *
	 * @param[out] $xIn   Returns an array of numeric X values
	 * @param[out] $yIn   Returns an array of Y values, corresponding
	 *   to the array of X values. Non-numeric values are omitted
	 *
	 * @param $index Returns the number of values in the specified
	 *   data set, including any non-numeric values (thus this is not
	 *   necessarily equal to the size of the $xIn or $yIn arrays), minus
	 *   one (to account for the bogus (0, 0) value added to the X and Y
	 *   arrays?)
	 *
	 * @param $missing  Returns the X values for which no Y value is
	 *   available. The missing keys form the keys of the $missing array,
	 *   and the corresponding value in the associative array is always
	 *   true
	 *
	 * @return Null
	 */
	public function getXYMap($colName, array &$xIn, array & $yIn, array & $missing, & $index) {
		$xIn [0] = 0;
		$yIn [0] = 0;

		$index = 1;

		foreach (array_keys($this->Data) as $Key) {
			if (isset ( $this->Data[$Key] [$colName] )) {
				$Value = $this->Data[$Key] [$colName];
				$xIn [$index] = $index;
				$yIn [$index] = $Value;
				if (! is_numeric ( $Value )) {
					$missing [$index] = TRUE;
				}
				$index ++;
			}
		}
		$index --;
	}
}
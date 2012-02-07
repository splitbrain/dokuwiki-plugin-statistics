<?php
/**
 * pData - Simplifying data population for pChart
 * @copyright 2008 Jean-Damien POGOLOTTI
 * @version 2.0
 * @copyright 2010 Tim Martin
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

require_once(dirname(__FILE__).'/pData.php');

/**
 * @brief Imports data from CSV files into a pData object
 *
 * All the methods on this class are static, it's debatable whether it
 * really needs to be a class, or whether it should be a namespace.
 */
class CSVImporter {
	/**
	 * @brief Import data into the specified dataobject from a CSV
	 * file
	 *
	 * @param $FileName  Name of the file (with path if necessary)
	 * @param $DataColumns If specified, this should be an array of 
	 * strings that give the names that will be suffixed to "Serie"
	 * to name the series of data. If this is left to the default,
	 * numbers will be used instead.
	 */
	static public function importFromCSV(pData $data, $FileName, $Delimiter = ",", $DataColumns = -1, $HasHeader = FALSE, $DataName = -1) {
		$handle = @fopen ( $FileName, "r" );

		if ($handle == null) {
			throw new Exception("Failed to open file");
		}

		$HeaderParsed = FALSE;
		while ( ! feof ( $handle ) ) {
			$buffer = fgets ( $handle, 4096 );
			
			if ($buffer != "") {
				if ($HasHeader && !$HeaderParsed) {
					self::importHeaderFromCSV($data, $buffer, $Delimiter, $DataColumns);
					$HeaderParsed = true;
				}
				else {
					self::importChunkFromCSV($data, $buffer, $Delimiter, $DataColumns, $DataName);
				}
			}
		}
		fclose ( $handle );
	}
	
	static private function importHeaderFromCSV(pData $data, $buffer, $Delimiter, $DataColumns) {
		$buffer = str_replace ( chr ( 10 ), "", $buffer );
		$buffer = str_replace ( chr ( 13 ), "", $buffer );
		$Values = explode ( $Delimiter, $buffer );
		
		if ($DataColumns == - 1) {
			$ID = 1;
			foreach ( $Values as $key => $Value ) {
				$data->SetSeriesName ( $Value, "Serie" . $ID );
				$ID ++;
			}
		} else {
			foreach ( $DataColumns as $key => $Value )
				$data->SetSeriesName ( $Values [$Value], "Serie" . $Value );
		}
	}
	
	/**
	 * @brief Import CSV data from a partial file chunk
	 */
	static private function importChunkFromCSV(pData $data, $buffer, $Delimiter, $DataColumns, $DataName) {
		$buffer = str_replace ( chr ( 10 ), "", $buffer );
		$buffer = str_replace ( chr ( 13 ), "", $buffer );
		$Values = explode ( $Delimiter, $buffer );

		if ($DataColumns == - 1) {
			$ID = 1;
			foreach ( $Values as $key => $Value ) {
				$data->AddPoint ( intval ( $Value ), "Serie" . $ID );
				$ID ++;
			}
		} else {
			$SerieName = "";
			if ($DataName != - 1)
				$SerieName = $Values [$DataName];
						
			foreach ( $DataColumns as $key => $Value )
				$data->AddPoint ( $Values [$Value], "Serie" . $Value, $SerieName );
		}
	}
}
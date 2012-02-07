<?php

/**
 *    pChart - a PHP class to build charts!
 *
 *    http://pchart.sourceforge.net
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

require_once(dirname(__FILE__).'/Color.php');

class GridStyle {
	public function __construct($lineWidth, $mosaic = true, Color $color = null, $alpha = 100) {
		if ($color == null) {
			$color = new Color(220, 220, 220);
		}

		if ($alpha > 100 || $alpha < 0) {
			throw new InvalidArgumentException("Bad alpha argument specified to ".__METHOD__);
		}

		$this->lineWidth = $lineWidth;
		$this->mosaic = $mosaic;
		$this->color = $color;
		$this->alpha = $alpha;
	}

	public function getLineWidth() {
		return $this->lineWidth;
	}

	public function getMosaic() {
		return $this->mosaic;
	}

	public function getColor() {
		return $this->color;
	}

	public function getAlpha() {
		return $this->alpha;
	}

	private $lineWidth;
	private $mosaic;
	private $color;
	private $alpha;
}
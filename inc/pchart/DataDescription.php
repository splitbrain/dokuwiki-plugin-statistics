<?php

/**
 * @brief Holds Metadata for the data set (name, unit, type etc.)
 *
 * The data description assumes we have X and Y axes, which isn't
 * necessarily the case (e.g. if we are generating a pie chart). This
 * is probably a design flaw.
 */
class DataDescription {
	public function __construct($position, $xFormat, $yFormat, $xUnit, $yUnit) {
		$this->position = $position;
		$this->xFormat = $xFormat;
		$this->yFormat = $yFormat;
		$this->xUnit = $xUnit;
		$this->yUnit = $yUnit;

		$this->xAxisName = '';
		$this->yAxisName = '';
	}

	/**
	 * @todo I don't know exactly what the Position does
	 */
	public function setPosition($position) {
		if (!is_string($position)) {
			throw new InvalidArgumentException("Non-string argument passed to setPosition");
		}

		$this->position = $position;
	}

	public function getPosition() {
		return $this->position;
	}

	public function setXAxisName($name) {
		if (!is_string($name)) {
			throw new InvalidArgumentException("Non-string argument passed to DataDescription::setXAxisName()");
		}

		$this->xAxisName = $name;
	}
	
	public function getXAxisName() {
		return $this->xAxisName;
	}

	public function setYAxisName($name) {
		if (!is_string($name)) {
			throw new InvalidArgumentException("Non-string argument passed to DataDescription::setYAxisName()");
		}
		$this->yAxisName = $name;
	}

	public function getYAxisName() {
		return $this->yAxisName;
	}

	/**
	 * @todo Not sure I'm happy with the name of this - should it be
	 * setXAxisFormat()?
	 */
	public function setXFormat($format) {
		/** @todo Check that $format is a recognised format value here */
		$this->xFormat = $format;
	}

	public function getXFormat() {
		return $this->xFormat;
	}

	public function setYFormat($format) {
		$this->yFormat = $format;
	}

	public function getYFormat() {
		return $this->yFormat;
	}

	public function setXUnit($unit) {
		$this->xUnit = $unit;
	}

	public function getXUnit() {
		return $this->xUnit;
	}

	public function setYUnit($unit) {
		$this->yUnit = $unit;
	}

	public function getYUnit() {
		return $this->yUnit;
	}

	public function getColumnIndex($columnName) {
		$ID = 0;
		foreach (array_keys($this->description) as $keyI) {
			if ($keyI == $columnName) {
				return $ID;
			}

			$ID ++;
		}
	}

	private $position;
	private $xFormat;
	private $yFormat;
	private $xUnit;
	private $yUnit;
	private $xAxisName;
	private $yAxisName;

	/**
	 * @todo This shouldn't be a public member, this is a transitional
	 * step while refactoring
	 */
	public $values = array();

	public $description;

	public $seriesSymbols = array();
}
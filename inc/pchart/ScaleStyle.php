<?php

require_once dirname(__FILE__).'/Color.php';

class ScaleStyle {
	/**
	 * @todo The color, lineWidth and lineDotSize variables could all
	 * be combined into a single LineStyle class
	 */
	public function __construct($scaleMode, Color $color, $drawTicks = true, $lineWidth = 1, $lineDotSize = 0) {
		$this->scaleMode = $scaleMode;
		$this->color = $color;
		$this->drawTicks = $drawTicks;
		$this->lineWidth = $lineWidth;
		$this->lineDotSize = $lineDotSize;
	}

	static public function DefaultStyle() {
		return new ScaleStyle(SCALE_NORMAL,
							  new Color(150, 150, 150));
	}

	public function getScaleMode() {
		return $this->scaleMode;
	}

	public function getColor() {
		return $this->color;
	}

	public function getDrawTicks() {
		return $this->drawTicks;
	}

	public function getLineWidth() {
		return $this->lineWidth;
	}

	public function getLineDotSize() {
		return $this->lineDotSize;
	}

	private $scaleMode;

	private $color;

	private $drawTicks;

	private $lineWidth;

	private $lineDotSize;
}
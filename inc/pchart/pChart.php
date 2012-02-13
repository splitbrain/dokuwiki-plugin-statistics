<?php
/**
 *    pChart - a PHP class to build charts!
 *    Copyright (C) 2008 Jean-Damien POGOLOTTI
 *    Version 2.0
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

require_once(dirname(__FILE__).'/ConversionHelpers.php');
require_once(dirname(__FILE__).'/ShadowProperties.php');
require_once(dirname(__FILE__).'/Color.php');
require_once(dirname(__FILE__).'/Palette.php');
require_once(dirname(__FILE__).'/ICanvas.php');
require_once(dirname(__FILE__).'/GridStyle.php');
require_once(dirname(__FILE__).'/ScaleStyle.php');

/* Declare some script wide constants */
define ( "SCALE_NORMAL", 1 );
define ( "SCALE_ADDALL", 2 );
define ( "SCALE_START0", 3 );
define ( "SCALE_ADDALLSTART0", 4 );
define ( "TARGET_GRAPHAREA", 1 );
define ( "TARGET_BACKGROUND", 2 );
define ( "ALIGN_TOP_LEFT", 1 );
define ( "ALIGN_TOP_CENTER", 2 );
define ( "ALIGN_TOP_RIGHT", 3 );
define ( "ALIGN_LEFT", 4 );
define ( "ALIGN_CENTER", 5 );
define ( "ALIGN_RIGHT", 6 );
define ( "ALIGN_BOTTOM_LEFT", 7 );
define ( "ALIGN_BOTTOM_CENTER", 8 );
define ( "ALIGN_BOTTOM_RIGHT", 9 );

/** 
 * pChart class definition 
 */
class pChart {
	protected $palette;
	
	/* Some static vars used in the class */
	protected $XSize = NULL;
	protected $YSize = NULL;
	protected $Picture = NULL;
	protected $ImageMap = NULL;
	
	/* Error management */
	protected $ErrorReporting = FALSE;
	protected $ErrorInterface = "CLI";
	protected $Errors = NULL;
	protected $ErrorFontName = "Fonts/pf_arma_five.ttf";
	protected $ErrorFontSize = 6;
	
	/* vars related to the graphing area */
	protected $GArea_X1 = NULL;
	protected $GArea_Y1 = NULL;
	protected $GArea_X2 = NULL;
	protected $GArea_Y2 = NULL;
	protected $GAreaXOffset = NULL;
	protected $VMax = NULL;
	protected $VMin = NULL;
	protected $VXMax = NULL;
	protected $VXMin = NULL;
	protected $Divisions = NULL;
	protected $XDivisions = NULL;
	protected $DivisionHeight = NULL;
	protected $XDivisionHeight = NULL;
	protected $DivisionCount = NULL;
	protected $XDivisionCount = NULL;
	protected $DivisionRatio = NULL;
	protected $XDivisionRatio = NULL;
	protected $DivisionWidth = NULL;
	protected $DataCount = NULL;
	
	/* Text format related vars */
	protected $FontName = NULL;
	protected $FontSize = NULL;
	protected $DateFormat = "d/m/Y";
	
	/* Lines format related vars */
	protected $LineWidth = 1;
	protected $LineDotSize = 0;
	
	/* Shadow settings */
	private $shadowProperties;
	
	/* Image Map settings */
	protected $BuildMap = FALSE;
	protected $MapFunction = NULL;
	protected $tmpFolder = "tmp/";
	protected $MapID = NULL;

	/**
	 * @brief An abstract ICanvas onto which we draw the chart
	 *
	 * @todo This probably shouldn't be protected, I'm still working
	 * on how the modules are going to break down between the various
	 * chart types.
	 */
	protected $canvas = null;
	
	/**
	 * This function create the background picture 
	 */
	function __construct($XSize, $YSize, ICanvas $canvas) {
		$this->palette = Palette::defaultPalette();

		$this->XSize = $XSize;
		$this->YSize = $YSize;
				
		$this->setFontProperties ( "tahoma.ttf", 8 );

		$this->shadowProperties = ShadowProperties::FromDefaults();

		$this->canvas = $canvas;
	}
	
	/**
	 * Set if warnings should be reported 
	 */
	function reportWarnings($Interface = "CLI") {
		$this->ErrorReporting = TRUE;
		$this->ErrorInterface = $Interface;
	}
	
	/**
	 * Set the font properties 
	 */
	function setFontProperties($FontName, $FontSize) {
		$this->FontName = $FontName;
		$this->FontSize = $FontSize;
	}
	
	public function setPalette(Palette $newPalette) {
		$this->palette = $newPalette;
	}

	/**
	 * Set the shadow properties 
	 */
	function setShadowProperties($XDistance = 1, $YDistance = 1, Color $color = null, $Alpha = 50, $Blur = 0) {
		if ($color == null) {
			$color = new Color(60, 60, 60);
		}

		$this->shadowProperties = ShadowProperties::FromSettings($XDistance,
																 $YDistance,
																 $color,
																 $Alpha,
																 $Blur);
	}
	
	/**
	 * Remove shadow option 
	 */
	function clearShadow() {
		$this->shadowProperties = ShadowProperties::FromDefaults();
	}
	
	/**
	 * Load Color Palette from file 
	 */
	function loadColorPalette($FileName, $Delimiter = ",") {
		$handle = @fopen ( $FileName, "r" );

		if ($handle == null) {
			throw new Exception("Failed to open file in loadColorPalette");
		}
		
		$ColorID = 0;
		if ($handle) {
			while ( ! feof ( $handle ) ) {
				$buffer = fgets ( $handle, 4096 );
				$buffer = str_replace ( chr ( 10 ), "", $buffer );
				$buffer = str_replace ( chr ( 13 ), "", $buffer );
				$Values = explode ( $Delimiter, $buffer );
				if (count ( $Values ) == 3) {
					$this->palette->colors[$ColorID] = new Color($Values[0],
																 $Values[1],
																 $Values[2]);
					$ColorID ++;
				}
			}
		}
	}
	
	/**
	 * Set line style 
	 */
	function setLineStyle($Width = 1, $DotSize = 0) {
		$this->LineWidth = $Width;
		$this->LineDotSize = $DotSize;
	}
	
	/**
	 * Set the graph area location 
	 */
	function setGraphArea($X1, $Y1, $X2, $Y2) {
		$this->GArea_X1 = $X1;
		$this->GArea_Y1 = $Y1;
		$this->GArea_X2 = $X2;
		$this->GArea_Y2 = $Y2;
	}
	
	/**
	 * Prepare the graph area 
	 */
	private function drawGraphArea(BackgroundStyle $style) {
		$this->canvas->drawFilledRectangle(new Point($this->GArea_X1, $this->GArea_Y1),
										   new Point($this->GArea_X2, $this->GArea_Y2),
										   $style->getBackgroundColor(),
										   $this->shadowProperties, FALSE );
		$this->canvas->drawRectangle(new Point($this->GArea_X1, $this->GArea_Y1),
									 new Point($this->GArea_X2, $this->GArea_Y2),
									 $style->getBackgroundColor()->addRGBIncrement(-40),
									 $style->getBorderWidth(),
									 $style->getBorderDotSize(),
									 $this->shadowProperties);
		
		if ($style->useStripe()) {
			$color2 = $style->getBackgroundColor()->addRGBIncrement(-15);

			$SkewWidth = $this->GArea_Y2 - $this->GArea_Y1 - 1;
			
			for($i = $this->GArea_X1 - $SkewWidth; $i <= $this->GArea_X2; $i = $i + 4) {
				$X1 = $i;
				$Y1 = $this->GArea_Y2;
				$X2 = $i + $SkewWidth;
				$Y2 = $this->GArea_Y1;
				
				if ($X1 < $this->GArea_X1) {
					$X1 = $this->GArea_X1;
					$Y1 = $this->GArea_Y1 + $X2 - $this->GArea_X1 + 1;
				}
				
				if ($X2 >= $this->GArea_X2) {
					$Y2 = $this->GArea_Y1 + $X2 - $this->GArea_X2 + 1;
					$X2 = $this->GArea_X2 - 1;
				}
				
				$this->canvas->drawLine(new Point($X1, $Y1),
										new Point($X2, $Y2 + 1),
										$color2,
										1,
										0,
										ShadowProperties::NoShadow());
			}
		}
	}

	public function drawGraphBackground(BackgroundStyle $style) {
		$this->drawGraphArea($style);
		$this->drawGraphAreaGradient($style);
	}
	
	/**
	 * Allow you to clear the scale : used if drawing multiple charts
	 */
	function clearScale() {
		$this->VMin = NULL;
		$this->VMax = NULL;
		$this->VXMin = NULL;
		$this->VXMax = NULL;
		$this->Divisions = NULL;
		$this->XDivisions = NULL;
	}
	
	/**
	 * Allow you to fix the scale, use this to bypass the automatic scaling 
	 */
	function setFixedScale($VMin, $VMax, $Divisions = 5, $VXMin = 0, $VXMax = 0, $XDivisions = 5) {
		$this->VMin = $VMin;
		$this->VMax = $VMax;
		$this->Divisions = $Divisions;
		
		if (! $VXMin == 0) {
			$this->VXMin = $VXMin;
			$this->VXMax = $VXMax;
			$this->XDivisions = $XDivisions;
		}
	}
	
	/**
	 * Wrapper to the drawScale() function allowing a second scale to
	 * be drawn
	 */
	function drawRightScale(pData $data, ScaleStyle $style, $Angle = 0, $Decimals = 1, $WithMargin = FALSE, $SkipLabels = 1) {
		$this->drawScale($data, $style, $Angle, $Decimals, $WithMargin, $SkipLabels, TRUE );
	}
	
	/**
	 * Compute and draw the scale 
	 */
	function drawScale(pData $Data, ScaleStyle $style, $Angle = 0, $Decimals = 1, $WithMargin = FALSE, $SkipLabels = 1, $RightScale = FALSE) {
		/* Validate the Data and DataDescription array */
		$this->validateData ( "drawScale", $Data->getData() );
		
		$this->canvas->drawLine(new Point($this->GArea_X1, $this->GArea_Y1),
								new Point($this->GArea_X1, $this->GArea_Y2),
								$style->getColor(),
								$style->getLineWidth(),
								$style->getLineDotSize(),
								$this->shadowProperties);
		$this->canvas->drawLine(new Point($this->GArea_X1, $this->GArea_Y2), 
								new Point($this->GArea_X2, $this->GArea_Y2),
								$style->getColor(),
								$style->getLineWidth(),
								$style->getLineDotSize(),
								$this->shadowProperties);
		
		if ($this->VMin == NULL && $this->VMax == NULL) {
			$Divisions = $this->calculateDivisions($Data, $style);			
		} else
			$Divisions = $this->Divisions;
		
		$this->DivisionCount = $Divisions;
		
		$DataRange = $this->VMax - $this->VMin;
		if ($DataRange == 0) {
			$DataRange = .1;
		}
		
		$this->DivisionHeight = ($this->GArea_Y2 - $this->GArea_Y1) / $Divisions;
		$this->DivisionRatio = ($this->GArea_Y2 - $this->GArea_Y1) / $DataRange;
		
		$this->GAreaXOffset = 0;
		if (count ( $Data->getData() ) > 1) {
			if ($WithMargin == FALSE)
				$this->DivisionWidth = ($this->GArea_X2 - $this->GArea_X1) / (count ( $Data->getData() ) - 1);
			else {
				$this->DivisionWidth = ($this->GArea_X2 - $this->GArea_X1) / (count ( $Data->getData() ));
				$this->GAreaXOffset = $this->DivisionWidth / 2;
			}
		} else {
			$this->DivisionWidth = $this->GArea_X2 - $this->GArea_X1;
			$this->GAreaXOffset = $this->DivisionWidth / 2;
		}
		
		$this->DataCount = count ( $Data->getData() );
		
		if ($style->getDrawTicks() == FALSE)
			return (0);
		
		$YPos = $this->GArea_Y2;
		$XMin = NULL;
		for($i = 1; $i <= $Divisions + 1; $i ++) {
			if ($RightScale)
				$this->canvas->drawLine(new Point($this->GArea_X2, $YPos),
										new Point($this->GArea_X2 + 5, $YPos),
										$style->getColor(),
										$style->getLineWidth(),
										$style->getLineDotSize(), 
										$this->shadowProperties);
			else
				$this->canvas->drawLine(new Point($this->GArea_X1, $YPos),
										new Point($this->GArea_X1 - 5, $YPos),
										$style->getColor(),
										$style->getLineWidth(),
										$style->getLineDotSize(),
										$this->shadowProperties);
			
			$Value = $this->VMin + ($i - 1) * (($this->VMax - $this->VMin) / $Divisions);
			$Value = round ( $Value * pow ( 10, $Decimals ) ) / pow ( 10, $Decimals );
			$Value = $this->convertValueForDisplay($Value,
												   $Data->getDataDescription()->getYFormat(),
												   $Data->getDataDescription()->getYUnit());

			$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Value );
			$TextWidth = $Position [2] - $Position [0];
			
			if ($RightScale) {
				$this->canvas->drawText($this->FontSize, 0,
										new Point($this->GArea_X2 + 10,
												  $YPos + ($this->FontSize / 2)),
										$style->getColor(),
										$this->FontName, 
										$Value,
										ShadowProperties::NoShadow());
				if ($XMin < $this->GArea_X2 + 15 + $TextWidth || $XMin == NULL) {
					$XMin = $this->GArea_X2 + 15 + $TextWidth;
				}
			} else {
				$this->canvas->drawText($this->FontSize,
										0,
										new Point($this->GArea_X1 - 10 - $TextWidth,
												  $YPos + ($this->FontSize / 2)),
										$style->getColor(), 
										$this->FontName,
										$Value,
										ShadowProperties::NoShadow());
				if ($XMin > $this->GArea_X1 - 10 - $TextWidth || $XMin == NULL) {
					$XMin = $this->GArea_X1 - 10 - $TextWidth;
				}
			}
			
			$YPos = $YPos - $this->DivisionHeight;
		}
		
		/* Write the Y Axis caption if set */
		if ($Data->getDataDescription()->getYAxisName() != '') {
			$Position = imageftbbox ( $this->FontSize, 90, $this->FontName, $Data->getDataDescription()->getYAxisName() );
			$TextHeight = abs ( $Position [1] ) + abs ( $Position [3] );
			$TextTop = (($this->GArea_Y2 - $this->GArea_Y1) / 2) + $this->GArea_Y1 + ($TextHeight / 2);
			
			if ($RightScale) {
				$this->canvas->drawText($this->FontSize, 90,
										new Point($XMin + $this->FontSize, 
												  $TextTop), 
										$style->getColor(), $this->FontName,
										$Data->getDataDescription()->getYAxisName(),
										ShadowProperties::NoShadow());
			}
			else {
				$this->canvas->drawText($this->FontSize, 90,
										new Point($XMin - $this->FontSize,
												  $TextTop),
										$style->getColor(), $this->FontName,
										$Data->getDataDescription()->getYAxisName(),
										ShadowProperties::NoShadow());
			}
		}
		
		/* Horizontal Axis */
		$XPos = $this->GArea_X1 + $this->GAreaXOffset;
		$ID = 1;
		$YMax = NULL;
		foreach ( $Data->getData() as $Values ) {
			if ($ID % $SkipLabels == 0) {
				$this->canvas->drawLine(new Point(floor($XPos), $this->GArea_Y2),
										new Point(floor($XPos), $this->GArea_Y2 + 5),
										$style->getColor(),
										$style->getLineWidth(),
										$style->getLineDotSize(),
										$this->shadowProperties);
				$Value = $Values[$Data->getDataDescription()->getPosition()];
				$Value = $this->convertValueForDisplay($Value,
													   $Data->getDataDescription()->getXFormat(),
													   $Data->getDataDescription()->getXUnit());
				
				$Position = imageftbbox ( $this->FontSize, $Angle, $this->FontName, $Value );
				$TextWidth = abs ( $Position [2] ) + abs ( $Position [0] );
				$TextHeight = abs ( $Position [1] ) + abs ( $Position [3] );
				
				if ($Angle == 0) {
					$YPos = $this->GArea_Y2 + 18;
					$this->canvas->drawText($this->FontSize,
											$Angle,
											new Point(floor ( $XPos ) - floor ( $TextWidth / 2 ),
													  $YPos),
											$style->getColor(),
											$this->FontName,
											$Value,
											ShadowProperties::NoShadow());
				} else {
					$YPos = $this->GArea_Y2 + 10 + $TextHeight;
					if ($Angle <= 90) {
						$this->canvas->drawText($this->FontSize,
												$Angle,
												new Point(floor($XPos) - $TextWidth + 5,
														  $YPos),
												$style->getColor(),
												$this->FontName,
												$Value,
												ShadowProperties::NoShadow());
					}
					else {
						$this->canvas->drawText($this->FontSize,
												$Angle,
												new Point(floor ( $XPos ) + $TextWidth + 5,
														  $YPos),
												$style->getColor(),
												$this->FontName,
												$Value,
												ShadowProperties::NoShadow());
					}
				}
				if ($YMax < $YPos || $YMax == NULL) {
					$YMax = $YPos;
				}
			}
			
			$XPos = $XPos + $this->DivisionWidth;
			$ID ++;
		}
		
		/* Write the X Axis caption if set */
		if ($Data->getDataDescription()->getXAxisName() != '') {
			$Position = imageftbbox ( $this->FontSize, 90,
									  $this->FontName,
									  $Data->getDataDescription()->getXAxisName());
			$TextWidth = abs ( $Position [2] ) + abs ( $Position [0] );
			$TextLeft = (($this->GArea_X2 - $this->GArea_X1) / 2) + $this->GArea_X1 + ($TextWidth / 2);
			$this->canvas->drawText($this->FontSize, 0,
									new Point($TextLeft,
											  $YMax + $this->FontSize + 5),
									$style->getColor(), $this->FontName, 
									$Data->getDataDescription()->getXAxisName(),
									ShadowProperties::NoShadow());
		}
	}

	/**
	 * Calculate the number of divisions that the Y axis will be
	 * divided into. This is a function of the range of Y values the
	 * data covers, as well as the scale style. Divisions should have
	 * some minimum size in screen coordinates in order that the
	 * divisions are clearly visible, so this is also a function of
	 * the graph size in screen coordinates.
	 *
	 * This method returns the number of divisions, but it also has
	 * side-effects on some class data members. This needs to be
	 * refactored to make it clearer what is and isn't affected.
	 */
	private function calculateDivisions(pData $Data, ScaleStyle $style) {
		if (isset ( $Data->getDataDescription()->values[0] )) {
			/* Pointless temporary is necessary because you can't
			 * directly apply an array index to the return value
			 * of a function in PHP */
			$dataArray = $Data->getData();
			$this->VMin = $dataArray[0] [$Data->getDataDescription()->values[0]];
			$this->VMax = $dataArray[0] [$Data->getDataDescription()->values[0]];
		} else {
			$this->VMin = 2147483647;
			$this->VMax = - 2147483647;
		}

		/* Compute Min and Max values */
		if ($style->getScaleMode() == SCALE_NORMAL
			|| $style->getScaleMode() == SCALE_START0) {
			if ($style->getScaleMode() == SCALE_START0) {
				$this->VMin = 0;
			}
				
			foreach ( $Data->getData() as $Values ) {
				foreach ( $Data->getDataDescription()->values as $ColName ) {
					if (isset ( $Values[$ColName] )) {
						$Value = $Values[$ColName];
							
						if (is_numeric ( $Value )) {
							if ($this->VMax < $Value) {
								$this->VMax = $Value;
							}
							if ($this->VMin > $Value) {
								$this->VMin = $Value;
							}
						}
					}
				}
			}
		} elseif ($style->getScaleMode() == SCALE_ADDALL || $style->getScaleMode() == SCALE_ADDALLSTART0 ) /* Experimental */ {
			if ($style->getScaleMode() == SCALE_ADDALLSTART0) {
				$this->VMin = 0;
			}
				
			foreach ( $Data->getData() as $Values ) {
				$Sum = 0;
				foreach ( $Data->getDataDescription()->values as $ColName ) {
					$dataArray = $Data->getData();
					if (isset ( $Values[$ColName] )) {
						$Value = $Values[$ColName];
						if (is_numeric ( $Value ))
							$Sum += $Value;
					}
				}
				if ($this->VMax < $Sum) {
					$this->VMax = $Sum;
				}
				if ($this->VMin > $Sum) {
					$this->VMin = $Sum;
				}
			}
		}
			
		$this->VMax = ceil($this->VMax);
				
		/* If all values are the same */
		if ($this->VMax == $this->VMin) {
			if ($this->VMax >= 0) {
				$this->VMax ++;
			} else {
				$this->VMin --;
			}
		}
			
		$DataRange = $this->VMax - $this->VMin;
		if ($DataRange == 0) {
			$DataRange = .1;
		}

		$this->calculateScales($Scale, $Divisions);
			
		if (! isset ( $Divisions ))
			$Divisions = 2;
			
		if ($Scale == 1 && $Divisions % 2 == 1)
			$Divisions --;

		return $Divisions;
	}
	
	/**
	 * Compute and draw the scale for X/Y charts 
	 */
	function drawXYScale(pData $Data, ScaleStyle $style, $YSerieName, $XSerieName, $Angle = 0, $Decimals = 1) {
		/* Validate the Data and DataDescription array */
		$this->validateData ( "drawScale", $Data->getData());
		
		$this->canvas->drawLine(new Point($this->GArea_X1, $this->GArea_Y1),
								new Point($this->GArea_X1, $this->GArea_Y2),
								$style->getColor(),
								$style->getLineWidth(),
								$style->getLineDotSize(),
								$this->shadowProperties);
		$this->canvas->drawLine(new Point($this->GArea_X1, $this->GArea_Y2),
								new Point($this->GArea_X2, $this->GArea_Y2),
								$style->getColor(),
								$style->getLineWidth(),
								$style->getLineDotSize(),
								$this->shadowProperties);
		
		/* Process Y scale */
		if ($this->VMin == NULL && $this->VMax == NULL) {
			$this->VMin = $Data->getSeriesMin($YSerieName);
			$this->VMax = $Data->getSeriesMax($YSerieName);

			/** @todo The use of ceil() here is questionable if all
			 * the values are much less than 1, AIUI */
			$this->VMax = ceil($this->VMax);
			
			$DataRange = $this->VMax - $this->VMin;
			if ($DataRange == 0) {
				$DataRange = .1;
			}
			
			self::computeAutomaticScaling($this->GArea_Y1,
										  $this->GArea_Y2,
										  $this->VMin,
										  $this->VMax,
										  $Divisions);
		} else
			$Divisions = $this->Divisions;
		
		$this->DivisionCount = $Divisions;
		
		$DataRange = $this->VMax - $this->VMin;
		if ($DataRange == 0) {
			$DataRange = .1;
		}
		
		$this->DivisionHeight = ($this->GArea_Y2 - $this->GArea_Y1) / $Divisions;
		$this->DivisionRatio = ($this->GArea_Y2 - $this->GArea_Y1) / $DataRange;
		
		$YPos = $this->GArea_Y2;
		$XMin = NULL;
		for($i = 1; $i <= $Divisions + 1; $i ++) {
			$this->canvas->drawLine(new Point($this->GArea_X1, $YPos),
									new Point($this->GArea_X1 - 5, $YPos),
									$style->getColor(),
									$style->getLineWidth(),
									$style->getLineDotSize(),
									$this->shadowProperties);
			$Value = $this->VMin + ($i - 1) * (($this->VMax - $this->VMin) / $Divisions);
			$Value = round ( $Value * pow ( 10, $Decimals ) ) / pow ( 10, $Decimals );
			$Value = $this->convertValueForDisplay($Value,
												   $Data->getDataDescription()->getYFormat(),
												   $Data->getDataDescription()->getYUnit());
			
			$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Value );
			$TextWidth = $Position [2] - $Position [0];
			$this->canvas->drawText($this->FontSize,
									0,
									new Point($this->GArea_X1 - 10 - $TextWidth,
											  $YPos + ($this->FontSize / 2)),
									$style->getColor(),
									$this->FontName,
									$Value,
									$this->shadowProperties);
			
			if ($XMin > $this->GArea_X1 - 10 - $TextWidth || $XMin == NULL) {
				$XMin = $this->GArea_X1 - 10 - $TextWidth;
			}
			
			$YPos = $YPos - $this->DivisionHeight;
		}
		
		/* Process X scale */
		if ($this->VXMin == NULL && $this->VXMax == NULL) {
			$this->VXMax = $Data->getSeriesMax($XSerieName);
			$this->VXMin = $Data->getSeriesMin($XSerieName);

			$this->VXMax = ceil($this->VXMax);
			
			$DataRange = $this->VMax - $this->VMin;
			if ($DataRange == 0) {
				$DataRange = .1;
			}
			
			/* Compute automatic scaling */
			self::computeAutomaticScaling($this->GArea_X1, $this->GArea_X2,
										  $this->VXMin, $this->VXMax,
										  $XDivisions);
		} else
			$XDivisions = $this->XDivisions;
		
		$this->XDivisionCount = $Divisions;
		$this->DataCount = $Divisions + 2;
		
		$XDataRange = $this->VXMax - $this->VXMin;
		if ($XDataRange == 0) {
			$XDataRange = .1;
		}
		
		$this->DivisionWidth = ($this->GArea_X2 - $this->GArea_X1) / $XDivisions;
		$this->XDivisionRatio = ($this->GArea_X2 - $this->GArea_X1) / $XDataRange;
		
		$XPos = $this->GArea_X1;
		$YMax = NULL;
		for($i = 1; $i <= $XDivisions + 1; $i ++) {
			$this->canvas->drawLine(new Point($XPos, $this->GArea_Y2),
									new Point($XPos, $this->GArea_Y2 + 5),
									$style->getColor(),
									$style->getLineWidth(),
									$style->getLineDotSize(),
									$this->shadowProperties);
			
			$Value = $this->VXMin + ($i - 1) * (($this->VXMax - $this->VXMin) / $XDivisions);
			$Value = round ( $Value * pow ( 10, $Decimals ) ) / pow ( 10, $Decimals );

			$Value = $this->convertValueForDisplay($Value,
												   $Data->getDataDescription()->getYFormat(),
												   $Data->getDataDescription()->getYUnit());
			
			$Position = imageftbbox ( $this->FontSize, $Angle, $this->FontName, $Value );
			$TextWidth = abs ( $Position [2] ) + abs ( $Position [0] );
			$TextHeight = abs ( $Position [1] ) + abs ( $Position [3] );
			
			if ($Angle == 0) {
				$YPos = $this->GArea_Y2 + 18;
				$this->canvas->drawText($this->FontSize,
										$Angle,
										new Point(floor ( $XPos ) - floor ( $TextWidth / 2 ),
												  $YPos),
										$style->getColor(),
										$this->FontName,
										$Value,
										$this->shadowProperties);
			} else {
				$YPos = $this->GArea_Y2 + 10 + $TextHeight;
				if ($Angle <= 90) {
					$this->canvas->drawText($this->FontSize,
											$Angle,
											new Point(floor ( $XPos ) - $TextWidth + 5,
													  $YPos),
											$style->getColor(),
											$this->FontName,
											$Value,
											$this->shadowProperties);
				}
				else {
					$this->canvas->drawText($this->FontSize,
											$Angle,
											new Point(floor ( $XPos ) + $TextWidth + 5,
													  $YPos),
											$style->getColor(),
											$this->FontName,
											$Value,
											$this->shadowProperties);
				}
			}
			
			if ($YMax < $YPos || $YMax == NULL) {
				$YMax = $YPos;
			}
			
			$XPos = $XPos + $this->DivisionWidth;
		}
		
		/* Write the Y Axis caption if set */
		if ($Data->getDataDescription()->getYAxisName() != '') {
			$Position = imageftbbox ( $this->FontSize, 90, $this->FontName,
									  $Data->getDataDescription()->getYAxisName());
			$TextHeight = abs ( $Position [1] ) + abs ( $Position [3] );
			$TextTop = (($this->GArea_Y2 - $this->GArea_Y1) / 2) + $this->GArea_Y1 + ($TextHeight / 2);
			$this->canvas->drawText($this->FontSize,
									90,
									new Point($XMin - $this->FontSize, 
											  $TextTop),
									$style->getColor(), 
									$this->FontName,
									$Data->getDataDescription()->getYAxisName(),
									$this->shadowProperties);
		}
		
		/* Write the X Axis caption if set */
		$this->writeScaleXAxisCaption($Data, $style, $YMax);
	}

	private function drawGridMosaic(GridStyle $style, $divisionCount, $divisionHeight) {
		$LayerHeight = $this->GArea_Y2 - $this->GArea_Y1;
			
		$YPos = $LayerHeight - 1; //$this->GArea_Y2-1;
		$LastY = $YPos;

		for($i = 0; $i < $divisionCount; $i ++) {
			$LastY = $YPos;
			$YPos = $YPos - $divisionHeight;
				
			if ($YPos <= 0) {
				$YPos = 1;
			}
				
			if ($i % 2 == 0) {
				$this->canvas->drawFilledRectangle(new Point($this->GArea_X1 + 1,
															 $this->GArea_Y1 + $YPos),
												   new Point($this->GArea_X2 - 1,
															 $this->GArea_Y1 + $LastY),
												   new Color(250, 250, 250),
												   ShadowProperties::NoShadow(),
												   false,
												   $style->getAlpha());
			}
		}
	}
	
	/**
	 * Write the X Axis caption on the scale, if set
	 */
	private function writeScaleXAxisCaption(pData $data, ScaleStyle $style, $YMax) {
		if ($data->getDataDescription()->getXAxisName() != '') {
			$Position = imageftbbox ( $this->FontSize, 90, $this->FontName, $data->getDataDescription()->getXAxisName());
			$TextWidth = abs ( $Position [2] ) + abs ( $Position [0] );
			$TextLeft = (($this->GArea_X2 - $this->GArea_X1) / 2) + $this->GArea_X1 + ($TextWidth / 2);
			$this->canvas->drawText($this->FontSize,
									0,
									new Point($TextLeft,
											  $YMax + $this->FontSize + 5),
									$style->getColor(),
									$this->FontName,
									$data->getDataDescription()->getXAxisName(),
									$this->shadowProperties);
		}

	}

	/**
	 * Compute and draw the scale 
	 */
	function drawGrid(GridStyle $style) {
		/* Draw mosaic */
		if ($style->getMosaic()) {
			$this->drawGridMosaic($style, $this->DivisionCount, $this->DivisionHeight);
		}
		
		/* Horizontal lines */
		$YPos = $this->GArea_Y2 - $this->DivisionHeight;
		for($i = 1; $i <= $this->DivisionCount; $i ++) {
			if ($YPos > $this->GArea_Y1 && $YPos < $this->GArea_Y2)
				$this->canvas->drawDottedLine(new Point($this->GArea_X1, $YPos),
											  new Point($this->GArea_X2, $YPos),
											  $style->getLineWidth(),
											  $this->LineWidth,
											  $style->getColor(), 
											  ShadowProperties::NoShadow());
			/** @todo There's some inconsistency here. The parameter
			 * $lineWidth appears to be used to control the dot size,
			 * not the line width?  This is the same way it's always
			 * been done, although now it's more obvious that there's
			 * a problem. */

			$YPos = $YPos - $this->DivisionHeight;
		}
		
		/* Vertical lines */
		if ($this->GAreaXOffset == 0) {
			$XPos = $this->GArea_X1 + $this->DivisionWidth + $this->GAreaXOffset;
			$ColCount = $this->DataCount - 2;
		} else {
			$XPos = $this->GArea_X1 + $this->GAreaXOffset;
			$ColCount = floor ( ($this->GArea_X2 - $this->GArea_X1) / $this->DivisionWidth );
		}
		
		for($i = 1; $i <= $ColCount; $i ++) {
			if ($XPos > $this->GArea_X1 && $XPos < $this->GArea_X2)
				$this->canvas->drawDottedLine(new Point(floor($XPos), $this->GArea_Y1),
											  new Point(floor($XPos), $this->GArea_Y2),
											  $style->getLineWidth(),
											  $this->LineWidth,
											  $style->getcolor(),
											  $this->shadowProperties);
			$XPos = $XPos + $this->DivisionWidth;
		}
	}
	
	/**
	 * retrieve the legends size 
	 */
	public function getLegendBoxSize($DataDescription) {
		if (! isset ( $DataDescription->description))
			return (- 1);
			
		/* <-10->[8]<-4->Text<-10-> */
		$MaxWidth = 0;
		$MaxHeight = 8;
		foreach ( $DataDescription->description as $Value ) {
			$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Value );
			$TextWidth = $Position [2] - $Position [0];
			$TextHeight = $Position [1] - $Position [7];
			if ($TextWidth > $MaxWidth) {
				$MaxWidth = $TextWidth;
			}
			$MaxHeight = $MaxHeight + $TextHeight + 4;
		}
		$MaxHeight = $MaxHeight - 3;
		$MaxWidth = $MaxWidth + 32;
		
		return (array ($MaxWidth, $MaxHeight ));
	}
	
	/**
	 * Draw the data legends 
	 */
	public function drawLegend($XPos, $YPos, $DataDescription, Color $color, Color $color2 = null, Color $color3 = null, $Border = TRUE) {
		if ($color2 == null) {
			$color2 = $color->addRGBIncrement(-30);
		}

		if ($color3 == null) {
			$color3 = new Color(0, 0, 0);
		}

		/* Validate the Data and DataDescription array */
		$this->validateDataDescription("drawLegend", $DataDescription);
		
		if (! isset ( $DataDescription->description))
			return (- 1);
		
		/* <-10->[8]<-4->Text<-10-> */
		$MaxWidth = 0;
		$MaxHeight = 8;
		foreach ( $DataDescription->description as $Key => $Value ) {
			$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Value );
			$TextWidth = $Position [2] - $Position [0];
			$TextHeight = $Position [1] - $Position [7];
			if ($TextWidth > $MaxWidth) {
				$MaxWidth = $TextWidth;
			}
			$MaxHeight = $MaxHeight + $TextHeight + 4;
		}
		$MaxHeight = $MaxHeight - 5;
		$MaxWidth = $MaxWidth + 32;
		
		if ($Border) {
			$this->canvas->drawFilledRoundedRectangle(new Point($XPos + 1, $YPos + 1),
													  new Point($XPos + $MaxWidth + 1,
																$YPos + $MaxHeight + 1),
													  5, $color2,
													  $this->LineWidth,
													  $this->LineDotSize,
													  $this->shadowProperties);
			
			$this->canvas->drawFilledRoundedRectangle(new Point($XPos, $YPos),
													  new Point($XPos + $MaxWidth,
																$YPos + $MaxHeight), 
													  5, $color,
													  $this->LineWidth,
													  $this->LineDotSize,
													  $this->shadowProperties);
		}
		
		$YOffset = 4 + $this->FontSize;
		$ID = 0;
		foreach ( $DataDescription->description as $Key => $Value ) {
			$this->canvas->drawFilledRoundedRectangle(new Point($XPos + 10,
																$YPos + $YOffset - 4),
													  new Point($XPos + 14,
																$YPos + $YOffset - 4),
													  2,
													  $this->palette->colors[$ID],
													  $this->LineWidth,
													  $this->LineDotSize,
													  $this->shadowProperties);
			$this->canvas->drawText($this->FontSize, 
									0,
									new Point($XPos + 22,
											  $YPos + $YOffset),
									$color3,
									$this->FontName,
									$Value,
									$this->shadowProperties);
			
			$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Value );
			$TextHeight = $Position [1] - $Position [7];
			
			$YOffset = $YOffset + $TextHeight + 4;
			$ID ++;
		}
	}
		
	/**
	 * Draw the graph title 
	 *
	 * @todo Should we pass in a ShadowProperties object here? Or is
	 * this a public function?
	 */
	public function drawTitle($XPos, $YPos, $Value, Color $color, $XPos2 = -1, $YPos2 = -1, ShadowProperties $shadowProperties = null) {
		if ($shadowProperties == null) {
			$shadowProperties = ShadowProperties::NoShadow();
		}

		if ($XPos2 != - 1) {
			$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Value );
			$TextWidth = $Position [2] - $Position [0];
			$XPos = floor ( ($XPos2 - $XPos - $TextWidth) / 2 ) + $XPos;
		}
		
		if ($YPos2 != - 1) {
			$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Value );
			$TextHeight = $Position [5] - $Position [3];
			$YPos = floor ( ($YPos2 - $YPos - $TextHeight) / 2 ) + $YPos;
		}
		
		$this->canvas->drawText($this->FontSize,
								0,
								new Point($XPos, $YPos),
								$color,
								$this->FontName,
								$Value,
								$shadowProperties);
	}
	
	/**
	 * Draw a text box with text align & alpha properties
	 *
	 * @param $point1 Minimum corner of the box
	 * @param $point2 Maximum corner of the box
	 *
	 * @todo This should probably be a method on the ICanvas
	 * interface, since it doesn't have anything specifically to do
	 * with graphs
	 */
	public function drawTextBox(Point $point1, Point $point2, $Text, $Angle = 0, Color $color = null, $Align = ALIGN_LEFT, ShadowProperties $shadowProperties = null, Color $backgroundColor = null, $Alpha = 100) {
		if ($color == null) {
			$color = new Color(255, 255, 255);
		}

		if ($shadowProperties == null) {
			$shadowProperties = ShadowProperties::NoShadow();
		}

		$Position = imageftbbox ( $this->FontSize, $Angle, $this->FontName, $Text );
		$TextWidth = $Position [2] - $Position [0];
		$TextHeight = $Position [5] - $Position [3];
		$AreaWidth = $point2->getX() - $point1->getX();
		$AreaHeight = $point2->getY() - $point1->getY();
		
		if ($backgroundColor != null)
			$this->canvas->drawFilledRectangle($point1,
											   $point2,
											   $backgroundColor,
											   $shadowProperties, FALSE, $Alpha );
		
		if ($Align == ALIGN_TOP_LEFT) {
			$newPosition = $point1->addIncrement(1, $this->FontSize + 1);
		}
		if ($Align == ALIGN_TOP_CENTER) {
			$newPosition = $point1->addIncrement(($AreaWidth / 2) - ($TextWidth / 2),
												 $this->FontSize + 1);
		}
		if ($Align == ALIGN_TOP_RIGHT) {
			$newPosition = new Point($point2->getX() - $TextWidth - 1,
									 $point1->getY() + $this->FontSize + 1);
		}
		if ($Align == ALIGN_LEFT) {
			$newPosition = $point1->addIncrement(1,
												 ($AreaHeight / 2) - ($TextHeight / 2));
		}
		if ($Align == ALIGN_CENTER) {
			$newPosition = $point1->addIncrement(($AreaWidth / 2) - ($TextWidth / 2),
												 ($AreaHeight / 2) - ($TextHeight / 2));
		}
		if ($Align == ALIGN_RIGHT) {
			$newPosition = new Point($point2->getX() - $TextWidth - 1,
									 $point1->getY() + ($AreaHeight / 2) - ($TextHeight / 2));
		}
		if ($Align == ALIGN_BOTTOM_LEFT) {
			$newPosition = new Point($point1->getX() + 1,
									 $point2->getY() - 1);
		}
		if ($Align == ALIGN_BOTTOM_CENTER) {
			$newPosition = new Point($point1->getX() + ($AreaWidth / 2) - ($TextWidth / 2),
									 $point2->getY() - 1);
		}
		if ($Align == ALIGN_BOTTOM_RIGHT) {
			$newPosition = $point2->addIncrement(- $TextWidth - 1,
												 -1);
		}
		
		$this->canvas->drawText($this->FontSize, $Angle, $newPosition, $color, $this->FontName, $Text, $shadowProperties);
	}
	
	/**
	 * Compute and draw the scale
	 *
	 * @todo What is the method name a typo for? Threshold?
	 */
	function drawTreshold($Value, Color $color, $ShowLabel = FALSE, $ShowOnRight = FALSE, $TickWidth = 4, $FreeText = NULL) {
		$Y = $this->GArea_Y2 - ($Value - $this->VMin) * $this->DivisionRatio;
		
		if ($Y <= $this->GArea_Y1 || $Y >= $this->GArea_Y2)
			return (- 1);
		
		if ($TickWidth == 0)
			$this->canvas->drawLine(new Point($this->GArea_X1, $Y),
									new Point($this->GArea_X2, $Y),
									$color,
									$this->LineWidth,
									$this->LineDotSize,
									$this->shadowProperties);
		else
			$this->canvas->drawDottedLine(new Point($this->GArea_X1, $Y),
										  new Point($this->GArea_X2, $Y),
										  $TickWidth, 
										  $this->LineWidth,
										  $color,
										  $this->shadowProperties);
		
		if ($ShowLabel) {
			if ($FreeText == NULL) {
				$Label = $Value;
			} else {
				$Label = $FreeText;
			}
			
			if ($ShowOnRight) {
				$position = new Point($this->GArea_X2 + 2,
									  $Y + ($this->FontSize/ 2));
			} 
			else {
				$position = new Point($this->GArea_X1 + 2,
									  $Y - ($this->FontSize / 2));
			}

			$this->canvas->drawText($this->FontSize, 0,
									$position,
									$color,
									$this->FontName,
									$Label,
									ShadowProperties::NoShadow());
		}
	}
	
	/**
	 * This function put a label on a specific point 
	 */
	function setLabel($Data, $DataDescription, $SerieName, $ValueName, $Caption, Color $color = null) {
		if ($color == null) {
			$color = new Color(210, 210, 210);
		}

		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "setLabel", $DataDescription );
		$this->validateData ( "setLabel", $Data );
		$ShadowFactor = 100;
		
		$Cp = 0;
		$Found = FALSE;
		foreach ( $Data as $Value ) {
			if ($Value[$DataDescription->getPosition()] == $ValueName) {
				$NumericalValue = $Value[$SerieName];
				$Found = TRUE;
			}
			if (! $Found)
				$Cp ++;
		}
		
		$XPos = $this->GArea_X1 + $this->GAreaXOffset + ($this->DivisionWidth * $Cp) + 2;
		$YPos = $this->GArea_Y2 - ($NumericalValue - $this->VMin) * $this->DivisionRatio;
		
		$Position = imageftbbox ( $this->FontSize, 0, $this->FontName, $Caption );
		$TextHeight = $Position [3] - $Position [5];
		$TextWidth = $Position [2] - $Position [0] + 2;
		$TextOffset = floor ( $TextHeight / 2 );
		
		// Shadow
		$Poly = array ($XPos + 1, $YPos + 1, $XPos + 9, $YPos - $TextOffset, $XPos + 8, $YPos + $TextOffset + 2 );

		$this->canvas->drawFilledPolygon($Poly, 
										 3,
										 $color->addRGBIncrement(-$ShadowFactor));

		$this->canvas->drawLine(new Point($XPos, $YPos + 1), 
								new Point($XPos + 9, $YPos - $TextOffset - .2),
								$color->addRGBIncrement(-$ShadowFactor),
								$this->LineWidth,
								$this->LineDotSize,
								$this->shadowProperties);

		$this->canvas->drawLine(new Point($XPos, $YPos + 1),
								new Point($XPos + 9, $YPos + $TextOffset + 2.2),
								$color->addRGBIncrement(-$ShadowFactor),
								$this->LineWidth,
								$this->LineDotSize,
								$this->shadowProperties);

		$this->canvas->drawFilledRectangle(new Point($XPos + 9,
													 $YPos - $TextOffset - .2),
										   new Point($XPos + 13 + $TextWidth,
													 $YPos + $TextOffset + 2.2),
									 $color->addRGBIncrement(-$ShadowFactor),
									 $this->shadowProperties);
		
		// Label background
		$Poly = array ($XPos, $YPos, $XPos + 8, $YPos - $TextOffset - 1, $XPos + 8, $YPos + $TextOffset + 1 );

		$this->canvas->drawFilledPolygon($Poly, 3, $color);

		/** @todo We draw exactly the same line twice, with the same settings.
		 * Surely this is pointless? */
		$this->canvas->drawLine(new Point($XPos - 1, $YPos), 
								new Point($XPos + 8, $YPos - $TextOffset - 1.2),
								$color,
								$this->LineWidth,
								$this->LineDotSize,
								$this->shadowProperties);

		$this->canvas->drawLine(new Point($XPos - 1, $YPos), 
								new Point($XPos + 8, $YPos + $TextOffset + 1.2),
								$color,
								$this->LineWidth,
								$this->LineDotSize,
								$this->shadowProperties);
		$this->canvas->drawFilledRectangle(new Point($XPos + 8,
													 $YPos - $TextOffset - 1.2),
										   new Point($XPos + 12 + $TextWidth,
													 $YPos + $TextOffset + 1.2),
										   $color, 
										   $this->shadowProperties);
		
		$this->canvas->drawText($this->FontSize,
								0,
								new Point($XPos + 10, $YPos + $TextOffset),
								new Color(0, 0, 0),
								$this->FontName,
								$Caption,
								ShadowProperties::NoShadow());
	}
	
	/**
	 * This function draw a plot graph 
	 */
	function drawPlotGraph($Data, $DataDescription, $BigRadius = 5, $SmallRadius = 2, Color $color2 = null, $Shadow = FALSE) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawPlotGraph", $DataDescription );
		$this->validateData ( "drawPlotGraph", $Data );
		
		$GraphID = 0;
		$colorO = $color2;
		
		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);
			
			$color = $this->palette->colors[$ColorID];
			$color2 = $colorO;
			
			if (isset ( $DataDescription->seriesSymbols[$ColName] )) {
				$Infos = getimagesize ( $DataDescription->seriesSymbols[$ColName] );
				$ImageWidth = $Infos [0];
				$ImageHeight = $Infos [1];
				$Symbol = imagecreatefromgif ( $DataDescription->seriesSymbols[$ColName] );
			}
			
			$XPos = $this->GArea_X1 + $this->GAreaXOffset;
			$Hsize = round ( $BigRadius / 2 );

			$color3 = null;
			foreach ( $Data as $Values ) {
				$Value = $Values[$ColName];
				$YPos = $this->GArea_Y2 - (($Value - $this->VMin) * $this->DivisionRatio);
				
				/* Save point into the image map if option activated */
				if ($this->BuildMap)
					$this->addToImageMap ( $XPos - $Hsize, $YPos - $Hsize, $XPos + 1 + $Hsize, $YPos + $Hsize + 1, $DataDescription->description[$ColName], $Values[$ColName] . $DataDescription->getYUnit(), "Plot" );
				
				if (is_numeric ( $Value )) {
					if (! isset ( $DataDescription->seriesSymbols[$ColName] )) {
						
						if ($Shadow) {
							if ($color3 != null) {
								$this->canvas->drawFilledCircle(new Point($XPos + 2,
																		  $YPos + 2),
																$BigRadius,
																$color3,
																$this->shadowProperties);
							}
							else {
								$color3 = $this->palette->colors[$ColorID]->addRGBIncrement(-20);

								$this->canvas->drawFilledCircle(new Point($XPos + 2,
																		  $YPos + 2),
																$BigRadius,
																$color3,
																$this->shadowProperties);
							}
						}
						
						$this->canvas->drawFilledCircle(new Point($XPos + 1,
																  $YPos + 1),
														$BigRadius,
														$color,
														$this->shadowProperties);
						
						if ($SmallRadius != 0) {
							if ($color2 != null) {
								$this->canvas->drawFilledCircle(new Point($XPos + 1,
																		  $YPos + 1),
																$SmallRadius,
																$color2,
																$this->shadowProperties);
							}
							else {
								$color2 = $this->palette->colors[$ColorID]->addRGBIncrement(-15);
								
								$this->canvas->drawFilledCircle(new Point($XPos + 1,
																		  $YPos + 1),
																$SmallRadius,
																$color2,
																$this->shadowProperties);
							}
						}
					} else {
						imagecopymerge ( $this->canvas->getPicture(), $Symbol, $XPos + 1 - $ImageWidth / 2, $YPos + 1 - $ImageHeight / 2, 0, 0, $ImageWidth, $ImageHeight, 100 );
					}
				}
				
				$XPos = $XPos + $this->DivisionWidth;
			}
			$GraphID ++;
		}
	}
	

    /**
     * Linearly Scale a given value
     *
     * using it's own minima/maxima and the desired output minima/maxima
     */
    function linearScale($value, $istart, $istop, $ostart, $ostop){
        $div = ($istop - $istart);
        if($div == 0.0) $div = 1;
        return $ostart + ($ostop - $ostart) * (($value - $istart) / $div);
    }

	/**
	 * @brief This function draw a plot graph in an X/Y space 
	 */
	function drawXYPlotGraph(pData $DataSet, $YSerieName, $XSerieName, $PaletteID = 0, $BigRadius = 5, $SmallRadius = 2, Color $color2 = null, $Shadow = TRUE, $SizeSerieName = '') {
		$color = $this->palette->colors[$PaletteID];
		
		$color3 = null;

        $Data = $DataSet->getData();
		foreach ( $Data as $Values ) {
			if (isset($Values[$YSerieName]) && isset ($Values[$XSerieName])) {
				$X = $Values[$XSerieName];
				$Y = $Values[$YSerieName];
				
				$Y = $this->GArea_Y2 - (($Y - $this->VMin) * $this->DivisionRatio);
				$X = $this->GArea_X1 + (($X - $this->VXMin) * $this->XDivisionRatio);
				
                if(isset($Values[$SizeSerieName])){
                    $br = $this->linearScale(
                            $Values[$SizeSerieName],
                            $DataSet->getSeriesMin($SizeSerieName),
                            $DataSet->getSeriesMax($SizeSerieName),
                            $SmallRadius,
                            $BigRadius
                    );
                    $sr = $br;
                }else{
                    $br = $BigRadius;
                    $sr = $SmallRadius;
                }

				if ($Shadow) {
					if ($color3 != null) {
						$this->canvas->drawFilledCircle(new Point($X + 2, $Y + 2),
														$br, 
														$color3,
														$this->shadowProperties);
					}
					else {
						$color3 = $this->palette->colors[$PaletteID]->addRGBIncrement(-20);
						$this->canvas->drawFilledCircle(new Point($X + 2, $Y + 2),
														$br,
														$color3,
														$this->shadowProperties);
					}
				}
				
				$this->canvas->drawFilledCircle(new Point($X + 1, $Y + 1),
												$br,
												$color,
												$this->shadowProperties);
				
				if ($color2 != null) {
					$this->canvas->drawFilledCircle(new Point($X + 1, $Y + 1),
													$sr,
													$color2,
													$this->shadowProperties);
				}
				else {
					$color2 = $this->palette->colors[$PaletteID]->addRGBIncrement(20);

					$this->canvas->drawFilledCircle(new Point($X + 1, $Y + 1),
													$sr,
													$color2,
													$this->shadowProperties);
				}
			}
		}
	
	}
	
	/**
	 * This function draw an area between two series 
	 */
	function drawArea($Data, $Serie1, $Serie2, Color $color, $Alpha = 50) {
		/* Validate the Data and DataDescription array */
		$this->validateData ( "drawArea", $Data );
		
		$LayerHeight = $this->GArea_Y2 - $this->GArea_Y1;
		
		$XPos = $this->GAreaXOffset;
		$LastXPos = - 1;
		foreach ( $Data as $Values ) {
			$Value1 = $Values[$Serie1];
			$Value2 = $Values[$Serie2];
			$YPos1 = $LayerHeight - (($Value1 - $this->VMin) * $this->DivisionRatio);
			$YPos2 = $LayerHeight - (($Value2 - $this->VMin) * $this->DivisionRatio);
			
			if ($LastXPos != - 1) {
				$Points = array();
				$Points [] = $LastXPos + $this->GArea_X1;
				$Points [] = $LastYPos1 + $this->GArea_Y1;
				$Points [] = $LastXPos + $this->GArea_X1;
				$Points [] = $LastYPos2 + $this->GArea_Y1;
				$Points [] = $XPos + $this->GArea_X1;
				$Points [] = $YPos2 + $this->GArea_Y1;
				$Points [] = $XPos + $this->GArea_X1;
				$Points [] = $YPos1 + $this->GArea_Y1;
				
				$this->canvas->drawFilledPolygon($Points,
												 4,
												 $color,
												 $Alpha);
			}
			
			$LastYPos1 = $YPos1;
			$LastYPos2 = $YPos2;
			$LastXPos = $XPos;
			
			$XPos = $XPos + $this->DivisionWidth;
		}
	}
	
	/**
	 * This function write the values of the specified series 
	 */
	function writeValues($Data, $DataDescription, $Series) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "writeValues", $DataDescription );
		$this->validateData ( "writeValues", $Data );
		
		if (! is_array ( $Series )) {
			$Series = array ($Series );
		}
		
		foreach ( $Series as $Serie ) {
			$ColorID = $DataDescription->getColumnIndex($Serie);
			
			$XPos = $this->GArea_X1 + $this->GAreaXOffset;

			foreach ( $Data as $Values ) {
				if (isset ( $Values[$Serie] ) && is_numeric ( $Values[$Serie] )) {
					$Value = $Values[$Serie];
					$YPos = $this->GArea_Y2 - (($Value - $this->VMin) * $this->DivisionRatio);
					
					$Positions = imagettfbbox ( $this->FontSize, 0, $this->FontName, $Value );
					$Width = $Positions [2] - $Positions [6];
					$XOffset = $XPos - ($Width / 2);
					$YOffset = $YPos - 4;
					
					$this->canvas->drawText($this->FontSize,
											0,
											new Point($XOffset, $YOffset),
											$this->palette->colors[$ColorID],
											$this->FontName,
											$Value,
											ShadowProperties::NoShadow());
				}
				$XPos = $XPos + $this->DivisionWidth;
			}
		
		}
	}
	
	/**
	 * @brief Draws a line graph where the data gives Y values for a
	 * series of regular positions along the X axis
	 */
	function drawLineGraph($Data, $DataDescription, $SerieName = "") {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawLineGraph", $DataDescription );
		$this->validateData ( "drawLineGraph", $Data );
		
		$GraphID = 0;
		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);
			
			if ($SerieName == "" || $SerieName == $ColName) {
				$XPos = $this->GArea_X1 + $this->GAreaXOffset;
				$XLast = - 1;
				foreach ( $Data as $Values ) {
					if (isset ( $Values[$ColName] )) {
						$Value = $Values[$ColName];
						$YPos = $this->GArea_Y2 - (($Value - $this->VMin) * $this->DivisionRatio);
						
						/* Save point into the image map if option activated */
						if ($this->BuildMap)
							$this->addToImageMap ( $XPos - 3, $YPos - 3, $XPos + 3, $YPos + 3, $DataDescription->description[$ColName], $Values[$ColName] . $DataDescription->getYUnit(), "Line" );
						
						if (! is_numeric ( $Value )) {
							$XLast = - 1;
						}
						if ($XLast != - 1)
							$this->canvas->drawLine(new Point($XLast, $YLast), 
													new Point($XPos, $YPos),
													$this->palette->colors[$ColorID],
													$this->LineWidth,
													$this->LineDotSize,
													$this->shadowProperties,
													new Point($this->GArea_X1,
															  $this->GArea_Y1),
													new Point($this->GArea_X2,
															  $this->GArea_Y2));
						
						$XLast = $XPos;
						$YLast = $YPos;
						if (! is_numeric ( $Value )) {
							$XLast = - 1;
						}
					}
					$XPos = $XPos + $this->DivisionWidth;
				}
				$GraphID ++;
			}
		}
	}
	
	/**
	 * @brief Draws a line graph where one series of data defines the
	 * X position and another the Y position
	 */
	function drawXYGraph($Data, $YSerieName, $XSerieName, $PaletteID = 0) {
		$graphAreaMin = new Point($this->GArea_X1, $this->GArea_Y1);
		$graphAreaMax = new Point($this->GArea_X2, $this->GArea_Y2);

		$lastPoint = null;

		foreach ($Data as $Values) {
			if (isset ( $Values[$YSerieName] ) && isset ( $Values[$XSerieName] )) {
				$X = $Values[$XSerieName];
				$Y = $Values[$YSerieName];
				
				$Y = $this->GArea_Y2 - (($Y - $this->VMin) * $this->DivisionRatio);
				$X = $this->GArea_X1 + (($X - $this->VXMin) * $this->XDivisionRatio);

				$currentPoint = new Point($X, $Y);
				
				if ($lastPoint != null) {
					$this->canvas->drawLine($lastPoint,
											$currentPoint,
											$this->palette->colors[$PaletteID],
											$this->LineWidth,
											$this->LineDotSize,
											$this->shadowProperties,
											$graphAreaMin,
											$graphAreaMax);
				}
				
				$lastPoint = $currentPoint;
			}
		}
	}
	
	/**
	 * This function draw a cubic curve 
	 */
	function drawCubicCurve(pData $data, $Accuracy = .1, $SerieName = "") {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription("drawCubicCurve",
									   $data->getDataDescription());
		$this->validateData ( "drawCubicCurve", $data->getData() );
		
		$graphAreaMin = new Point($this->GArea_X1, $this->GArea_Y1);
		$graphAreaMax = new Point($this->GArea_X2, $this->GArea_Y2);

		$GraphID = 0;
		foreach ( $data->getDataDescription()->values as $ColName ) {
			if ($SerieName == "" || $SerieName == $ColName) {
				/** @todo The next section of code has been duplicated by 
				 * copy & paste */
				$XIn = array();
				$YIn = array();
				$Yt = "";
				$U = "";
				
				$ColorID = $data->getDataDescription()->getColumnIndex($ColName);
				
				$Index = 1;
				$XLast = - 1;
				$Missing = array();

				$data->getXYMap($ColName, $XIn, $YIn, $Missing, $Index);
				
				assert(count($XIn) == count($YIn));
				assert($Index + 1 >= count($XIn));
				
				$Yt [0] = 0;
				$Yt [1] = 0;
				$U [1] = 0;

				$this->calculateCubicCurve($Yt, $XIn, $YIn, $U, $Index);

				$Yt [$Index] = 0;
				
				for($k = $Index - 1; $k >= 1; $k --)
					$Yt [$k] = $Yt [$k] * $Yt [$k + 1] + $U [$k];
				
				$XPos = $this->GArea_X1 + $this->GAreaXOffset;
				for($X = 1; $X <= $Index; $X = $X + $Accuracy) {

					/* I believe here we're searching for the integral
					 * value k such that $X lies between $XIn[k] and
					 * $XIn[k-1] */
					$klo = 1;
					$khi = $Index;
					$k = $khi - $klo;
					while ( $k > 1 ) {
						$k = $khi - $klo;
						If ($XIn [$k] >= $X)
							$khi = $k;
						else
							$klo = $k;
					}
					$klo = $khi - 1;

					/* These assertions are to check my understanding
					 * of the code. If they fail, it is my fault and
					 * not a bug */
					assert($khi = $klo + 1);
					assert($XIn[$klo] < $X);
					assert($X <= $XIn[$khi]);

					$h = $XIn [$khi] - $XIn [$klo];
					$a = ($XIn [$khi] - $X) / $h;
					$b = ($X - $XIn [$klo]) / $h;

					/**
					 * I believe this is the actual cubic Bezier
					 * calculation. In parametric form:
					 *
					 * B(t) = (1-t)^3 * B0 
					 *        + 3 (1-t)^2 * t * B1
					 *        + 3 (1-t) * t^2 * B2
					 *        + t^3 B3
					 */
					$Value = $a * $YIn [$klo] + $b * $YIn [$khi] + (($a * $a * $a - $a) * $Yt [$klo] + ($b * $b * $b - $b) * $Yt [$khi]) * ($h * $h) / 6;
					
					$YPos = $this->GArea_Y2 - (($Value - $this->VMin) * $this->DivisionRatio);
					
					if ($XLast != - 1 && ! isset ( $Missing [floor ( $X )] ) && ! isset ( $Missing [floor ( $X + 1 )] ))
						$this->canvas->drawLine(new Point($XLast,
														  $YLast),
												new Point($XPos,
														  $YPos),
												$this->palette->colors[$ColorID],
												$this->LineWidth,
												$this->LineDotSize,
												$this->shadowProperties,
												$graphAreaMin,
												$graphAreaMax);
					
					$XLast = $XPos;
					$YLast = $YPos;
					$XPos = $XPos + $this->DivisionWidth * $Accuracy;
				}
				
				// Add potentialy missing values
				$XPos = $XPos - $this->DivisionWidth * $Accuracy;
				if ($XPos < ($this->GArea_X2 - $this->GAreaXOffset)) {
					$YPos = $this->GArea_Y2 - (($YIn [$Index] - $this->VMin) * $this->DivisionRatio);
					$this->canvas->drawLine(new Point($XLast,
													  $YLast),
											new Point($this->GArea_X2 - $this->GAreaXOffset, 
													  $YPos), 
											$this->palette->colors[$ColorID],
											$this->LineWidth,
											$this->LineDotSize,
											$this->shadowProperties,
											$graphAreaMin,
											$graphAreaMax);
				}
				
				$GraphID ++;
			}
		}
	}

	/**
	 * @todo I haven't figured out exactly what this bit of code does,
	 * it's just an attempt to reduce code duplication
	 */
	private function calculateCubicCurve(array & $Yt, array $XIn, array $YIn, array & $U, $Index) {
		for($i = 2; $i <= $Index - 1; $i ++) {
			/* Typically $Sig will be 0.5, since each X value will be
			 * one unit past the last. If there is missing data then
			 * this ratio will change */
			$Sig = ($XIn [$i] - $XIn [$i - 1]) / ($XIn [$i + 1] - $XIn [$i - 1]);
			
			$p = $Sig * $Yt [$i - 1] + 2;

			/* This Y value will nearly always be negative, thanks to
			 * $Sig being 0.5 */
			$Yt [$i] = ($Sig - 1) / $p;

			/** @todo No idea what the following code is doing */
			$U [$i] = ($YIn [$i + 1] - $YIn [$i]) / ($XIn [$i + 1] - $XIn [$i])
				- ($YIn [$i] - $YIn [$i - 1]) / ($XIn [$i] - $XIn [$i - 1]);
			$U [$i] = (6 * $U [$i] / ($XIn [$i + 1] - $XIn [$i - 1]) - $Sig * $U [$i - 1]) / $p;
		}
	}
	
	/**
	 * This function draw a filled cubic curve
	 */
	function drawFilledCubicCurve(pData $data, $Accuracy = .1, $Alpha = 100, $AroundZero = FALSE) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription("drawFilledCubicCurve",
									   $data->getDataDescription());
		$this->validateData ( "drawFilledCubicCurve", $data->getData() );
		
		$LayerWidth = $this->GArea_X2 - $this->GArea_X1;
		$LayerHeight = $this->GArea_Y2 - $this->GArea_Y1;
		$YZero = $LayerHeight - ((0 - $this->VMin) * $this->DivisionRatio);
		if ($YZero > $LayerHeight) {
			$YZero = $LayerHeight;
		}
		
		$GraphID = 0;
		foreach ($data->getDataDescription()->values as $ColName) {
			$XIn = array();
			$YIn = array();
			$Yt = array();
			$U = array();
			
			$ColorID = $data->getDataDescription()->getColumnIndex($ColName);
			
			$numElements = 1;
			$XLast = - 1;
			$Missing = array();

			$data->getXYMap($ColName, $XIn, $YIn, $Missing, $numElements);
			
			$Yt [0] = 0;
			$Yt [1] = 0;
			$U [1] = 0;

			$this->calculateCubicCurve($Yt, $XIn, $YIn, $U, $numElements);
			
			$Yt [$numElements] = 0;
			
			for($k = $numElements - 1; $k >= 1; $k --)
				$Yt [$k] = $Yt [$k] * $Yt [$k + 1] + $U [$k];
			
			$Points = "";
			$Points [] = $this->GAreaXOffset + $this->GArea_X1;
			$Points [] = $LayerHeight + $this->GArea_Y1;
			
			$YLast = NULL;
			$XPos = $this->GAreaXOffset;
			$PointsCount = 2;
			for($X = 1; $X <= $numElements; $X = $X + $Accuracy) {
				$klo = 1;
				$khi = $numElements;
				$k = $khi - $klo;
				while ( $k > 1 ) {
					$k = $khi - $klo;
					If ($XIn [$k] >= $X)
						$khi = $k;
					else
						$klo = $k;
				}
				$klo = $khi - 1;
				
				$h = $XIn [$khi] - $XIn [$klo];
				$a = ($XIn [$khi] - $X) / $h;
				$b = ($X - $XIn [$klo]) / $h;
				$Value = $a * $YIn [$klo] + $b * $YIn [$khi] + (($a * $a * $a - $a) * $Yt [$klo] + ($b * $b * $b - $b) * $Yt [$khi]) * ($h * $h) / 6;
				
				$YPos = $LayerHeight - (($Value - $this->VMin) * $this->DivisionRatio);
				
				if ($YLast != NULL && $AroundZero && ! isset ( $Missing [floor ( $X )] ) && ! isset ( $Missing [floor ( $X + 1 )] )) {
					$aPoints = "";

					$aPoints [] = $XLast + $this->GArea_X1;
					$aPoints [] = min($YLast + $this->GArea_Y1, $this->GArea_Y2);
					$aPoints [] = $XPos + $this->GArea_X1;
					$aPoints [] = min($YPos + $this->GArea_Y1, $this->GArea_Y2);
					$aPoints [] = $XPos + $this->GArea_X1;
					$aPoints [] = $YZero + $this->GArea_Y1;
					$aPoints [] = $XLast + $this->GArea_X1;
					$aPoints [] = $YZero + $this->GArea_Y1;
					
					$this->canvas->drawFilledPolygon($aPoints,
													 4,
													 $this->palette->colors[$ColorID],
													 $alpha);
				}
				
				if (! isset ( $Missing [floor ( $X )] ) || $YLast == NULL) {
					$PointsCount ++;
					$Points [] = $XPos + $this->GArea_X1;
					$Points [] = min($YPos + $this->GArea_Y1, $this->GArea_Y2);
				} else {
					$PointsCount ++;
					$Points [] = $XLast + $this->GArea_X1;
					$Points [] = min($LayerHeight + $this->GArea_Y1,
									 $this->GArea_Y2);;
				}
				
				$YLast = $YPos;
				$XLast = $XPos;
				$XPos = $XPos + $this->DivisionWidth * $Accuracy;
			}
			
			// Add potentialy missing values
			$XPos = $XPos - $this->DivisionWidth * $Accuracy;
			if ($XPos < ($LayerWidth - $this->GAreaXOffset)) {
				$YPos = $LayerHeight - (($YIn [$numElements] - $this->VMin) * $this->DivisionRatio);
				if ($YLast != NULL && $AroundZero) {
					$aPoints = "";
					$aPoints [] = $XLast + $this->GArea_X1;
					$aPoints [] = max($YLast + $this->GArea_Y1, $this->GArea_Y1);
					$aPoints [] = $LayerWidth - $this->GAreaXOffset + $this->GArea_X1;
					$aPoints [] = max($YPos + $this->GArea_Y1, $this->GArea_Y1);
					$aPoints [] = $LayerWidth - $this->GAreaXOffset + $this->GArea_X1;
					$aPoints [] = max($YZero + $this->GArea_Y1, $this->GArea_Y1);
					$aPoints [] = $XLast + $this->GArea_X1;
					$aPoints [] = max($YZero + $this->GArea_Y1, $this->GArea_Y1);
					
					$this->canvas->drawFilledPolygon($aPoints,
													 4,
													 $this->palette->colors[$ColorID],
													 $alpha);
				}
				
				if ($YIn [$klo] != "" && $YIn [$khi] != "" || $YLast == NULL) {
					$PointsCount ++;
					$Points [] = $LayerWidth 
						- $this->GAreaXOffset 
						+ $this->GArea_X1;
					$Points [] = $YPos + $this->GArea_Y1;
				}
			}
			
			$Points [] = $LayerWidth - $this->GAreaXOffset + $this->GArea_X1;
			$Points [] = $LayerHeight + $this->GArea_Y1;
			
			if (! $AroundZero) {
				$this->canvas->drawFilledPolygon($Points,
												 $PointsCount,
												 $this->palette->colors[$ColorID],
												 $Alpha);
			}
			
			$this->drawCubicCurve($data, $Accuracy, $ColName );
			
			$GraphID ++;
		}
	}
	
	/**
	 * This function draw a filled line graph 
	 */
	function drawFilledLineGraph($Data, $DataDescription, $Alpha = 100, $AroundZero = FALSE) {
		$Empty = - 2147483647;
		
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawFilledLineGraph", $DataDescription );
		$this->validateData ( "drawFilledLineGraph", $Data );
		
		$LayerWidth = $this->GArea_X2 - $this->GArea_X1;
		$LayerHeight = $this->GArea_Y2 - $this->GArea_Y1;
		
		$GraphID = 0;
		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);
			
			$aPoints = array();
			$aPoints [] = $this->GAreaXOffset + $this->GArea_X1;
			$aPoints [] = $LayerHeight + $this->GArea_Y1;
			
			$XPos = $this->GAreaXOffset;
			$XLast = - 1;
			$PointsCount = 2;
			$YZero = $LayerHeight - ((0 - $this->VMin) * $this->DivisionRatio);
			if ($YZero > $LayerHeight) {
				$YZero = $LayerHeight;
			}
			
			$YLast = $Empty;
			foreach (array_keys($Data) as $Key) {
				$Value = $Data [$Key] [$ColName];
				$YPos = $LayerHeight - (($Value - $this->VMin) * $this->DivisionRatio);
				
				/* Save point into the image map if option activated */
				if ($this->BuildMap)
					$this->addToImageMap ( $XPos - 3, $YPos - 3, $XPos + 3, $YPos + 3, $DataDescription->description[$ColName], $Data [$Key] [$ColName] . $DataDescription->getYUnit(), "FLine" );
				
				if (! is_numeric ( $Value )) {
					$PointsCount ++;
					$aPoints [] = $XLast + $this->GArea_X1;
					$aPoints [] = $LayerHeight + $this->GArea_Y1;
					
					$YLast = $Empty;
				} else {
					$PointsCount ++;
					if ($YLast != $Empty) {
						$aPoints [] = $XPos + $this->GArea_X1;
						$aPoints [] = $YPos + $this->GArea_Y1;
					} else {
						$PointsCount ++;

						$aPoints [] = $XPos + $this->GArea_X1;
						$aPoints [] = $LayerHeight + $this->GArea_Y1;
						$aPoints [] = $XPos + $this->GArea_X1;
						$aPoints [] = $YPos + $this->GArea_Y1;
					}
					
					if ($YLast != $Empty && $AroundZero) {
						$Points = "";
						$Points [] = $XLast + $this->GArea_X1;
						$Points [] = $YLast + $this->GArea_Y1;
						$Points [] = $XPos + $this->GArea_X1;
						$Points [] = $YPos + $this->GArea_Y1;
						$Points [] = $XPos + $this->GArea_X1;
						$Points [] = $YZero + $this->GArea_Y1;
						$Points [] = $XLast + $this->GArea_X1;
						$Points [] = $YZero + $this->GArea_Y1;
						
						$this->canvas->drawFilledPolygon($Points,
														 4,
														 $this->palette->colors[$ColorID],
														 $Alpha);
					}
					$YLast = $YPos;
				}
				
				$XLast = $XPos;
				$XPos = $XPos + $this->DivisionWidth;
			}
			$aPoints [] = $LayerWidth - $this->GAreaXOffset + $this->GArea_X1;
			$aPoints [] = $LayerHeight + $this->GArea_Y1;
			
			if ($AroundZero == FALSE) {
				$this->canvas->drawFilledPolygon($aPoints,
												 $PointsCount,
												 $this->palette->colors[$ColorID],
												 $Alpha);
			}
			
			$GraphID ++;
			$this->drawLineGraph ( $Data, $DataDescription, $ColName );
		}
	}
	
	/**
	 * This function draws a bar graph 
	 */
	function drawOverlayBarGraph($Data, $DataDescription, $Alpha = 50) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawOverlayBarGraph", $DataDescription );
		$this->validateData ( "drawOverlayBarGraph", $Data );
		
		$LayerHeight = $this->GArea_Y2 - $this->GArea_Y1;
		
		$GraphID = 0;
		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);
			
			$XWidth = $this->DivisionWidth / 4;
			$XPos = $this->GAreaXOffset;
			$YZero = $LayerHeight - ((0 - $this->VMin) * $this->DivisionRatio);
			foreach (array_keys($Data) as $Key) {
				if (isset ( $Data [$Key] [$ColName] )) {
					$Value = $Data [$Key] [$ColName];
					if (is_numeric ( $Value )) {
						$YPos = $LayerHeight - (($Value - $this->VMin) * $this->DivisionRatio);

						$this->canvas->drawFilledRectangle(new Point(floor($XPos - $XWidth + $this->GArea_X1),
																	 floor($YPos + $this->GArea_Y1)),
														   new Point(floor($XPos + $XWidth + $this->GArea_X1),
																	 floor($YZero + $this->GArea_Y1)),
														   $this->palette->colors[$GraphID],
														   ShadowProperties::NoShadow(),
														   false,
														   $Alpha);
						
						$X1 = floor ( $XPos - $XWidth + $this->GArea_X1 );
						$Y1 = floor ( $YPos + $this->GArea_Y1 ) + .2;
						$X2 = floor ( $XPos + $XWidth + $this->GArea_X1 );
						$Y2 = $this->GArea_Y2 - ((0 - $this->VMin) * $this->DivisionRatio);
						if ($X1 <= $this->GArea_X1) {
							$X1 = $this->GArea_X1 + 1;
						}
						if ($X2 >= $this->GArea_X2) {
							$X2 = $this->GArea_X2 - 1;
						}
						
						/* Save point into the image map if option activated */
						if ($this->BuildMap)
							$this->addToImageMap ( $X1, min ( $Y1, $Y2 ), $X2, max ( $Y1, $Y2 ), $DataDescription->description[$ColName], $Data [$Key] [$ColName] . $DataDescription->getYUnit(), "oBar" );
						
						$this->canvas->drawLine(new Point($X1,
														  $Y1),
												new Point($X2,
														  $Y1),
												$this->palette->colors[$ColorID],
												$this->LineWidth,
												$this->LineDotSize,
												$this->shadowProperties,
												new Point($this->GArea_X1,
														  $this->GArea_Y1),
												new Point($this->GArea_X2,
														  $this->GArea_Y2));
					}
				}
				$XPos = $XPos + $this->DivisionWidth;
			}
			
			$GraphID ++;
		}
	}
	
	/**
	 * This function draw a bar graph 
	 */
	function drawBarGraph($Data, $DataDescription, $Alpha = 100) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawBarGraph", $DataDescription );
		$this->validateData ( "drawBarGraph", $Data );
		
		$Series = count ( $DataDescription->values);
		$SeriesWidth = $this->DivisionWidth / ($Series + 1);
		$SerieXOffset = $this->DivisionWidth / 2 - $SeriesWidth / 2;
		
		$YZero = $this->GArea_Y2 - ((0 - $this->VMin) * $this->DivisionRatio);
		if ($YZero > $this->GArea_Y2) {
			$YZero = $this->GArea_Y2;
		}
		
		$SerieID = 0;

		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);

			$XPos = $this->GArea_X1 + $this->GAreaXOffset - $SerieXOffset + $SeriesWidth * $SerieID;
			foreach (array_keys($Data) as $Key) {
				if (isset ( $Data [$Key] [$ColName] )) {
					if (is_numeric ( $Data [$Key] [$ColName] )) {
						$Value = $Data [$Key] [$ColName];
						$YPos = $this->GArea_Y2 - (($Value - $this->VMin) * $this->DivisionRatio);
						
						/* Save point into the image map if option activated */
						if ($this->BuildMap) {
							$this->addToImageMap ( $XPos + 1, min ( $YZero, $YPos ), $XPos + $SeriesWidth - 1, max ( $YZero, $YPos ), $DataDescription->description[$ColName], $Data [$Key] [$ColName] . $DataDescription->getYUnit(), "Bar" );
						}
						
						if ($Alpha == 100) {
							$this->canvas->drawRectangle(new Point($XPos + 1, $YZero),
														 new Point($XPos + $SeriesWidth - 1,
																   $YPos),
														 new Color(25, 25, 25),
														 $this->LineWidth,
														 $this->LineDotSize,
														 $this->shadowProperties);
						}
						
						$this->canvas->drawFilledRectangle(new Point($XPos + 1,
																	 $YZero),
														   new Point($XPos + $SeriesWidth - 1,
																	 $YPos),
														   $this->palette->colors[$ColorID],
														   $this->shadowProperties,
														   TRUE, $Alpha);
					}
				}
				$XPos = $XPos + $this->DivisionWidth;
			}
			$SerieID ++;
		}
	}
	
	/**
	 * This function draw a stacked bar graph 
	 */
	function drawStackedBarGraph($Data, $DataDescription, $Alpha = 50, $Contiguous = FALSE) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawBarGraph", $DataDescription );
		$this->validateData ( "drawBarGraph", $Data );
		
		if ($Contiguous)
			$SeriesWidth = $this->DivisionWidth;
		else
			$SeriesWidth = $this->DivisionWidth * .8;
		
		$YZero = $this->GArea_Y2 - ((0 - $this->VMin) * $this->DivisionRatio);
		if ($YZero > $this->GArea_Y2) {
			$YZero = $this->GArea_Y2;
		}
		
		$SerieID = 0;
		$LastValue = "";
		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);
			
			$XPos = $this->GArea_X1 + $this->GAreaXOffset - $SeriesWidth / 2;
			foreach (array_keys($Data) as $Key) {
				if (isset ( $Data [$Key] [$ColName] )) {
					if (is_numeric ( $Data [$Key] [$ColName] )) {
						$Value = $Data [$Key] [$ColName];
						
						if (isset ( $LastValue [$Key] )) {
							$YPos = $this->GArea_Y2 - ((($Value + $LastValue [$Key]) - $this->VMin) * $this->DivisionRatio);
							$YBottom = $this->GArea_Y2 - (($LastValue [$Key] - $this->VMin) * $this->DivisionRatio);
							$LastValue [$Key] += $Value;
						} else {
							$YPos = $this->GArea_Y2 - (($Value - $this->VMin) * $this->DivisionRatio);
							$YBottom = $YZero;
							$LastValue [$Key] = $Value;
						}
						
						/* Save point into the image map if option activated */
						if ($this->BuildMap)
							$this->addToImageMap ( $XPos + 1, min ( $YBottom, $YPos ), $XPos + $SeriesWidth - 1, max ( $YBottom, $YPos ), $DataDescription->description[$ColName], $Data [$Key] [$ColName] . $DataDescription->getYUnit(), "sBar" );
						
						$this->canvas->drawFilledRectangle(new Point($XPos + 1,
																	 $YBottom),
														   new Point($XPos + $SeriesWidth - 1,
																	 $YPos),
														   $this->palette->colors[$ColorID],
														   $this->shadowProperties, 
														   TRUE,
														   $Alpha);
					}
				}
				$XPos = $XPos + $this->DivisionWidth;
			}
			$SerieID ++;
		}
	}
	
	/**
	 * This function draw a limits bar graphs 
	 */
	function drawLimitsGraph($Data, $DataDescription, Color $color = null) {
		if ($color == null) {
			$color = new Color(0, 0, 0);
		}

		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawLimitsGraph", $DataDescription );
		$this->validateData ( "drawLimitsGraph", $Data );
		
		$XWidth = $this->DivisionWidth / 4;
		$XPos = $this->GArea_X1 + $this->GAreaXOffset;
		
		$graphAreaMin = new Point($this->GArea_X1, $this->GArea_Y1);
		$graphAreaMax = new Point($this->GArea_X2, $this->GArea_Y2);

		foreach (array_keys($Data) as $Key) {
			$Min = $Data [$Key] [$DataDescription->values[0]];
			$Max = $Data [$Key] [$DataDescription->values[0]];
			$GraphID = 0;
			$MaxID = 0;
			$MinID = 0;
			foreach ( $DataDescription->values as $ColName ) {
				if (isset ( $Data [$Key] [$ColName] )) {
					if ($Data [$Key] [$ColName] > $Max && is_numeric ( $Data [$Key] [$ColName] )) {
						$Max = $Data [$Key] [$ColName];
						$MaxID = $GraphID;
					}
				}
				if (isset ( $Data [$Key] [$ColName] ) && is_numeric ( $Data [$Key] [$ColName] )) {
					if ($Data [$Key] [$ColName] < $Min) {
						$Min = $Data [$Key] [$ColName];
						$MinID = $GraphID;
					}
					$GraphID ++;
				}
			}
			
			$YPos = $this->GArea_Y2 - (($Max - $this->VMin) * $this->DivisionRatio);
			$X1 = floor ( $XPos - $XWidth );
			$Y1 = floor ( $YPos ) - .2;
			$X2 = floor ( $XPos + $XWidth );
			if ($X1 <= $this->GArea_X1) {
				$X1 = $this->GArea_X1 + 1;
			}
			if ($X2 >= $this->GArea_X2) {
				$X2 = $this->GArea_X2 - 1;
			}
			
			$YPos = $this->GArea_Y2 - (($Min - $this->VMin) * $this->DivisionRatio);
			$Y2 = floor ( $YPos ) + .2;
			
			$this->canvas->drawLine(new Point(floor ( $XPos ) - .2, $Y1 + 1),
									new Point(floor ( $XPos ) - .2, $Y2 - 1),
									$color,
									$this->LineWidth,
									$this->LineDotSize,
									$this->shadowProperties,
									$graphAreaMin,
									$graphAreaMax);
			$this->canvas->drawLine(new Point(floor ( $XPos ) + .2, $Y1 + 1),
									new Point(floor ( $XPos ) + .2, $Y2 - 1),
									$color,
									$this->LineWidth,
									$this->LineDotSize,
									$this->shadowProperties,
									$graphAreaMin,
									$graphAreaMax);
			$this->canvas->drawLine(new Point($X1,
											  $Y1),
									new Point($X2,
											  $Y1),
									$this->palette->colors[$MaxID],
									$this->LineWidth,
									$this->LineDotSize,
									$this->shadowProperties);
			$this->canvas->drawLine(new Point($X1,
											  $Y2),
									new Point($X2,
											  $Y2),
									$this->palette->colors[$MinID],
									$this->LineWidth,
									$this->LineDotSize,
									$this->shadowProperties);
			
			$XPos = $XPos + $this->DivisionWidth;
		}
	}
	
	/**
	 * This function draw radar axis centered on the graph area 
	 */
	function drawRadarAxis($Data, $DataDescription, $Mosaic = TRUE, $BorderOffset = 10, Color $colorA = null, Color $colorS = null, $MaxValue = -1) {
		if ($colorA == null) {
			$colorA = new Color(60, 60, 60);
		}

		if ($colorS == null) {
			$colorS = new Color(200, 200, 200);
		}

		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawRadarAxis", $DataDescription );
		$this->validateData ( "drawRadarAxis", $Data );
		
		/* Draw radar axis */
		$Points = count ( $Data );
		$Radius = ($this->GArea_Y2 - $this->GArea_Y1) / 2 - $BorderOffset;
		$XCenter = ($this->GArea_X2 - $this->GArea_X1) / 2 + $this->GArea_X1;
		$YCenter = ($this->GArea_Y2 - $this->GArea_Y1) / 2 + $this->GArea_Y1;
		
		/* Search for the max value */
		if ($MaxValue == - 1) {
			foreach ( $DataDescription->values as $ColName ) {
				foreach (array_keys($Data) as $Key) {
					if (isset ( $Data [$Key] [$ColName] ))
						if ($Data [$Key] [$ColName] > $MaxValue) {
							$MaxValue = $Data [$Key] [$ColName];
						}
				}
			}
		}
		
		/* Draw the mosaic */
		if ($Mosaic) {
			$RadiusScale = $Radius / $MaxValue;
			for($t = 1; $t <= $MaxValue - 1; $t ++) {
				$TRadius = $RadiusScale * $t;
				$LastX1 = - 1;
				
				for($i = 0; $i <= $Points; $i ++) {
					$Angle = - 90 + $i * 360 / $Points;
					$X1 = cos ( $Angle * M_PI / 180 ) * $TRadius + $XCenter;
					$Y1 = sin ( $Angle * M_PI / 180 ) * $TRadius + $YCenter;
					$X2 = cos ( $Angle * M_PI / 180 ) * ($TRadius + $RadiusScale) + $XCenter;
					$Y2 = sin ( $Angle * M_PI / 180 ) * ($TRadius + $RadiusScale) + $YCenter;
					
					if ($t % 2 == 1 && $LastX1 != - 1) {
						$Plots = "";
						$Plots [] = $X1;
						$Plots [] = $Y1;
						$Plots [] = $X2;
						$Plots [] = $Y2;
						$Plots [] = $LastX2;
						$Plots [] = $LastY2;
						$Plots [] = $LastX1;
						$Plots [] = $LastY1;
						
						$this->canvas->drawFilledPolygon($Plots,
														 (count ( $Plots ) + 1) / 2,
														 new Color(250, 250, 250));
					}
					
					$LastX1 = $X1;
					$LastY1 = $Y1;
					$LastX2 = $X2;
					$LastY2 = $Y2;
				}
			}
		}
		
		/* Draw the spider web */
		for($t = 1; $t <= $MaxValue; $t ++) {
			$TRadius = ($Radius / $MaxValue) * $t;
			$LastX = - 1;
			
			for($i = 0; $i <= $Points; $i ++) {
				$Angle = - 90 + $i * 360 / $Points;
				$X = cos ( $Angle * M_PI / 180 ) * $TRadius + $XCenter;
				$Y = sin ( $Angle * M_PI / 180 ) * $TRadius + $YCenter;
				
				if ($LastX != - 1)
					$this->canvas->drawDottedLine(new Point($LastX, $LastY),
												  new Point($X, $Y),
												  4, 1, $colorS,
												  $this->shadowProperties);
				
				$LastX = $X;
				$LastY = $Y;
			}
		}
		
		/* Draw the axis */
		for($i = 0; $i <= $Points; $i ++) {
			$Angle = - 90 + $i * 360 / $Points;
			$X = cos ( $Angle * M_PI / 180 ) * $Radius + $XCenter;
			$Y = sin ( $Angle * M_PI / 180 ) * $Radius + $YCenter;
			
			$this->canvas->drawLine(new Point($XCenter, $YCenter),
									new Point($X, $Y),
									$colorA,
									$this->LineWidth,
									$this->LineDotSize,
									$this->shadowProperties);
			
			$XOffset = 0;
			$YOffset = 0;
			if (isset ( $Data [$i] [$DataDescription->getPosition()] )) {
				$Label = $Data [$i] [$DataDescription->getPosition()];
				
				$Positions = imagettfbbox ( $this->FontSize, 0, $this->FontName, $Label );
				$Width = $Positions [2] - $Positions [6];
				$Height = $Positions [3] - $Positions [7];
				
				if ($Angle >= 0 && $Angle <= 90)
					$YOffset = $Height;
				
				if ($Angle > 90 && $Angle <= 180) {
					$YOffset = $Height;
					$XOffset = - $Width;
				}
				
				if ($Angle > 180 && $Angle <= 270) {
					$XOffset = - $Width;
				}
				
				$this->canvas->drawText($this->FontSize,
										0,
										new Point($X + $XOffset, $Y + $YOffset),
										$colorA,
										$this->FontName,
										$Label,
										ShadowProperties::NoShadow());
			}
		}
		
		/* Write the values */
		for($t = 1; $t <= $MaxValue; $t ++) {
			$TRadius = ($Radius / $MaxValue) * $t;
			
			$Angle = - 90 + 360 / $Points;
			$X1 = $XCenter;
			$Y1 = $YCenter - $TRadius;
			$X2 = cos ( $Angle * M_PI / 180 ) * $TRadius + $XCenter;
			$Y2 = sin ( $Angle * M_PI / 180 ) * $TRadius + $YCenter;
			
			$XPos = floor ( ($X2 - $X1) / 2 ) + $X1;
			$YPos = floor ( ($Y2 - $Y1) / 2 ) + $Y1;
			
			$Positions = imagettfbbox ( $this->FontSize, 0, $this->FontName, $t );
			$X = $XPos - ($X + $Positions [2] - $X + $Positions [6]) / 2;
			$Y = $YPos + $this->FontSize;
			
			$this->canvas->drawFilledRoundedRectangle(new Point($X + $Positions [6] - 2,
																$Y + $Positions [7] - 1), 
													  new Point($X + $Positions [2] + 4, 
																$Y + $Positions [3] + 1),
													  2,
													  new Color(240, 240, 240),
													  $this->LineWidth,
													  $this->LineDotSize,
													  $this->shadowProperties);

			$this->canvas->drawRoundedRectangle(new Point($X + $Positions [6] - 2,
														  $Y + $Positions [7] - 1),
												new Point($X + $Positions [2] + 4,
														  $Y + $Positions [3] + 1),
												2,
												new Color(220, 220, 220),
												$this->LineWidth,
												$this->LineDotSize,
												$this->shadowProperties);
			$this->canvas->drawText($this->FontSize,
									0,
									new Point($X, $Y),
									$colorA,
									$this->FontName,
									$t,
									ShadowProperties::NoShadow());
		}
	}
	
	private function calculateMaxValue($Data, $DataDescription) {
		$MaxValue = -1;
		foreach ( $DataDescription->values as $ColName ) {
			foreach (array_keys($Data) as $Key) {
				if (isset ( $Data [$Key] [$ColName] ))
					if ($Data [$Key] [$ColName] > $MaxValue && is_numeric($Data[$Key][$ColName])) {
						$MaxValue = $Data [$Key] [$ColName];
					}
			}
		}
		return $MaxValue;
	}

	/**
	 * This function draw a radar graph centered on the graph area
	 */
	function drawRadar($Data, $DataDescription, $BorderOffset = 10, $MaxValue = -1) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawRadar", $DataDescription );
		$this->validateData ( "drawRadar", $Data );
		
		$Points = count ( $Data );
		$Radius = ($this->GArea_Y2 - $this->GArea_Y1) / 2 - $BorderOffset;
		$XCenter = ($this->GArea_X2 - $this->GArea_X1) / 2 + $this->GArea_X1;
		$YCenter = ($this->GArea_Y2 - $this->GArea_Y1) / 2 + $this->GArea_Y1;
		
		/* Search for the max value */
		if ($MaxValue == - 1) {
			$MaxValue = $this->calculateMaxValue($Data, $DataDescription);
		}
		
		$GraphID = 0;
		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);

			$Angle = - 90;
			$XLast = - 1;
			foreach (array_keys($Data) as $Key) {
				if (isset ( $Data [$Key] [$ColName] )) {
					$Value = $Data [$Key] [$ColName];
					$Strength = ($Radius / $MaxValue) * $Value;
					
					$XPos = cos ( $Angle * M_PI / 180 ) * $Strength + $XCenter;
					$YPos = sin ( $Angle * M_PI / 180 ) * $Strength + $YCenter;
					
					if ($XLast != - 1)
						$this->canvas->drawLine(new Point($XLast,
														  $YLast),
												new Point($XPos,
														  $YPos),
												$this->palette->colors[$ColorID],
												$this->LineWidth,
												$this->LineDotSize,
												$this->shadowProperties);
					
					if ($XLast == - 1) {
						$FirstX = $XPos;
						$FirstY = $YPos;
					}
					
					$Angle = $Angle + (360 / $Points);
					$XLast = $XPos;
					$YLast = $YPos;
				}
			}
			$this->canvas->drawLine(new Point($XPos,
											  $YPos),
									new Point($FirstX,
											  $FirstY),
									$this->palette->colors[$ColorID],
									$this->LineWidth,
									$this->LineDotSize,
									$this->shadowProperties);
			$GraphID ++;
		}
	}
	
	/**
	 * This function draw a radar graph centered on the graph area 
	 */
	function drawFilledRadar($Data, $DataDescription, $Alpha = 50, $BorderOffset = 10, $MaxValue = -1) {
		/* Validate the Data and DataDescription array */
		$this->validateDataDescription ( "drawFilledRadar", $DataDescription );
		$this->validateData ( "drawFilledRadar", $Data );
		
		$Points = count ( $Data );
		$Radius = ($this->GArea_Y2 - $this->GArea_Y1) / 2 - $BorderOffset;
		$XCenter = ($this->GArea_X2 - $this->GArea_X1) / 2;
		$YCenter = ($this->GArea_Y2 - $this->GArea_Y1) / 2;
		
		/* Search for the max value */
		if ($MaxValue == - 1) {
			$MaxValue = $this->calculateMaxValue($Data, $DataDescription);
		}
		
		$GraphID = 0;
		foreach ( $DataDescription->values as $ColName ) {
			$ColorID = $DataDescription->getColumnIndex($ColName);
			
			$Angle = - 90;
			$XLast = - 1;
			$Plots = array();
			foreach (array_keys($Data) as $Key) {
				if (isset ( $Data [$Key] [$ColName] )) {
					$Value = $Data [$Key] [$ColName];
					if (! is_numeric ( $Value )) {
						$Value = 0;
					}
					$Strength = ($Radius / $MaxValue) * $Value;
					
					$XPos = cos ( $Angle * M_PI / 180 ) * $Strength + $XCenter;
					$YPos = sin ( $Angle * M_PI / 180 ) * $Strength + $YCenter;
					
					$Plots [] = $XPos + $this->GArea_X1;
					$Plots [] = $YPos + $this->GArea_Y1;
					
					$Angle = $Angle + (360 / $Points);
					$XLast = $XPos;
				}
			}
			
			if (isset ( $Plots [0] )) {
				$Plots [] = $Plots [0];
				$Plots [] = $Plots [1];
				
				$this->canvas->drawFilledPolygon($Plots,
												 (count ( $Plots ) + 1) / 2,
												 $this->palette->colors[$ColorID],
												 $Alpha);
								
				for($i = 0; $i <= count ( $Plots ) - 4; $i = $i + 2)
					$this->canvas->drawLine(new Point($Plots [$i],
													  $Plots [$i + 1]),
											new Point($Plots [$i + 2],
													  $Plots [$i + 3]),
											$this->palette->colors[$ColorID],
											$this->LineWidth,
											$this->LineDotSize,
											$this->shadowProperties);
			}
			
			$GraphID ++;
		}
	}
	
	/**
	 * This function can be used to set the background color 
	 */
	function drawBackground(Color $color) {
		$C_Background = $this->canvas->allocateColor($color);
		imagefilledrectangle ( $this->canvas->getPicture(), 0, 0, $this->XSize, $this->YSize, $C_Background );
	}
	
	private function drawGradient(Point $point1, Point $point2, Color $color, $decay) {
		/* Positive gradient */
		if ($decay > 0) {
			$YStep = ($point2->getY() - $point1->getY() - 2) / $decay;
			for($i = 0; $i <= $decay; $i ++) {
				$color = $color->addRGBIncrement(-1);
				$Yi1 = $point1->getY() + ($i * $YStep);
				$Yi2 = ceil ( $Yi1 + ($i * $YStep) + $YStep );
				if ($Yi2 >= $Yi2) {
					$Yi2 = $point2->getY() - 1;
				}
				
				$this->canvas->drawFilledRectangle(new Point($point1->getX(), $Yi1),
												   new Point($point2->getX(), $Yi2),
												   $color,
												   ShadowProperties::NoShadow());
			}
		}
		
		/* Negative gradient */
		if ($decay < 0) {
			$YStep = ($point2->getY() - $point1->getY() - 2) / - $decay;
			$Yi1 = $point1->getY();
			$Yi2 = $point1->getY() + $YStep;
			for($i = - $decay; $i >= 0; $i --) {
				$color = $color->addRGBIncrement(1);

				$this->canvas->drawFilledRectangle(new Point($point1->getX(), $Yi1),
												   new Point($point2->getX(), $Yi2),
												   $color,
												   ShadowProperties::NoShadow());
				
				$Yi1 += $YStep;
				$Yi2 += $YStep;
				if ($Yi2 >= $Yi2) {
					$Yi2 = $point2->getY() - 1;
				}
			}
		}		
	}

	/**
	 * This function can be used to set the background color 
	 */
	private function drawGraphAreaGradient(BackgroundStyle $style) {
		if (!$style->useGradient()) {
			return;
		}

		$this->drawGradient(new Point($this->GArea_X1 + 1, $this->GArea_Y1 + 1),
							new Point($this->GArea_X2 - 1, $this->GArea_Y2),
							$style->getGradientStartColor(),
							$style->getGradientDecay());
	}

	public function drawBackgroundGradient(Color $color, $decay) {
		$this->drawGradient(new Point(0, 0),
							new Point($this->XSize, $this->YSize),
							$color,
							$decay);
	}
		
	/**
	 * This function will draw an ellipse 
	 */
	function drawEllipse($Xc, $Yc, $Height, $Width, Color $color) {
		$this->canvas->drawCircle(new Point($Xc, $Yc), $Height, $color, $this->shadowProperties, $Width );
	}
	
	/**
	 * This function will draw a filled ellipse 
	 */
	function drawFilledEllipse($Xc, $Yc, $Height, $Width, Color $color) {
		$this->canvas->drawFilledCircle(new Point($Xc, $Yc), $Height, $color, $this->shadowProperties, $Width );
	}
	
	/**
	 * Load a PNG file and draw it over the chart 
	 */
	function drawFromPNG($FileName, $X, $Y, $Alpha = 100) {
		$this->drawFromPicture ( 1, $FileName, $X, $Y, $Alpha );
	}
	
	/**
	 * Load a GIF file and draw it over the chart 
	 */
	function drawFromGIF($FileName, $X, $Y, $Alpha = 100) {
		$this->drawFromPicture ( 2, $FileName, $X, $Y, $Alpha );
	}
	
	/**
	 * Load a JPEG file and draw it over the chart
	 */
	function drawFromJPG($FileName, $X, $Y, $Alpha = 100) {
		$this->drawFromPicture ( 3, $FileName, $X, $Y, $Alpha );
	}
	
	/**
	 * Generic loader function for external pictures 
	 */
	function drawFromPicture($PicType, $FileName, $X, $Y, $Alpha = 100) {
		if (file_exists ( $FileName )) {
			$Infos = getimagesize ( $FileName );
			$Width = $Infos [0];
			$Height = $Infos [1];
			if ($PicType == 1) {
				$Raster = imagecreatefrompng ( $FileName );
			}
			if ($PicType == 2) {
				$Raster = imagecreatefromgif ( $FileName );
			}
			if ($PicType == 3) {
				$Raster = imagecreatefromjpeg ( $FileName );
			}
			
			imagecopymerge ( $this->canvas->getPicture(), $Raster, $X, $Y, 0, 0, $Width, $Height, $Alpha );
			imagedestroy ( $Raster );
		}
	}
	
	/**
	 * Add a border to the picture 
	 *
	 * @todo This hasn't been updated to the new API yet
	 */
	function addBorder($Size = 3, $R = 0, $G = 0, $B = 0) {
		$Width = $this->XSize + 2 * $Size;
		$Height = $this->YSize + 2 * $Size;
		
		$Resampled = imagecreatetruecolor ( $Width, $Height );
		$C_Background = imagecolorallocate($Resampled, $R, $G, $B);
		imagefilledrectangle ( $Resampled, 0, 0, $Width, $Height, $C_Background );
		
		imagecopy ( $Resampled, $this->canvas->getPicture(), $Size, $Size, 0, 0, $this->XSize, $this->YSize );
		imagedestroy ( $this->canvas->getPicture() );
		
		$this->XSize = $Width;
		$this->YSize = $Height;
		
		$this->canvas->setPicture(imagecreatetruecolor ( $this->XSize, $this->YSize ));
		$C_White = $this->canvas->allocate(new Color(255, 255, 255));
		imagefilledrectangle ( $this->canvas->getPicture(), 0, 0, $this->XSize, $this->YSize, $C_White );
		imagecolortransparent ( $this->canvas->getPicture(), $C_White );
		imagecopy ( $this->canvas->getPicture(), $Resampled, 0, 0, 0, 0, $this->XSize, $this->YSize );
	}
	
	/**
	 * Render the current picture to a file 
	 */
	function Render($FileName) {
		if ($this->ErrorReporting)
			$this->printErrors ( $this->ErrorInterface );
			
		/* Save image map if requested */
		if ($this->BuildMap)
			$this->SaveImageMap ();
		
		imagepng ( $this->canvas->getPicture(), $FileName );
	}
	
	/**
	 * Render the current picture to STDOUT 
	 */
	function Stroke() {
		if ($this->ErrorReporting)
			$this->printErrors ( "GD" );
			
		/* Save image map if requested */
		if ($this->BuildMap)
			$this->SaveImageMap ();
		
		header ( 'Content-type: image/png' );
		imagepng ( $this->canvas->getPicture() );
	}
		
	/**
	 * Validate data contained in the description array 
	 *
	 * @todo Should this be a method on DataDescription?
	 */
	protected function validateDataDescription($FunctionName, DataDescription &$DataDescription, $DescriptionRequired = TRUE) {
		if ($DataDescription->getPosition() == '') {
			$this->Errors [] = "[Warning] " . $FunctionName . " - Y Labels are not set.";
			$DataDescription->setPosition("Name");
		}
		
		if ($DescriptionRequired) {
			if (! isset ( $DataDescription->description)) {
				$this->Errors [] = "[Warning] " . $FunctionName . " - Series descriptions are not set.";
				foreach ( $DataDescription->values as $key => $Value ) {
					$DataDescription->description[$Value] = $Value;
				}
			}
			
			if (count ( $DataDescription->description) < count ( $DataDescription->values )) {
				$this->Errors [] = "[Warning] " . $FunctionName . " - Some series descriptions are not set.";
				foreach ( $DataDescription->values as $key => $Value ) {
					if (! isset ( $DataDescription->description[$Value] ))
						$DataDescription->description[$Value] = $Value;
				}
			}
		}
	}
	
	/**
	 * Validate data contained in the data array 
	 */
	protected function validateData($FunctionName, &$Data) {
		$DataSummary = array ();
		
		foreach ( $Data as $key => $Values ) {
			foreach ( $Values as $key2 => $Value ) {
				if (! isset ( $DataSummary [$key2] ))
					$DataSummary [$key2] = 1;
				else
					$DataSummary [$key2] ++;
			}
		}
		
		if (empty($DataSummary))
			$this->Errors [] = "[Warning] " . $FunctionName . " - No data set.";
		
		foreach ( $DataSummary as $key => $Value ) {
			if ($Value < max ( $DataSummary )) {
				$this->Errors [] = "[Warning] " . $FunctionName . " - Missing data in serie " . $key . ".";
			}
		}
	}
	
	/**
	 * Print all error messages on the CLI or graphically 
	 */
	function printErrors($Mode = "CLI") {
		if (count ( $this->Errors ) == 0)
			return (0);
		
		if ($Mode == "CLI") {
			foreach ( $this->Errors as $key => $Value )
				echo $Value . "\r\n";
		} elseif ($Mode == "GD") {
			$MaxWidth = 0;
			foreach ( $this->Errors as $key => $Value ) {
				$Position = imageftbbox ( $this->ErrorFontSize, 0, $this->ErrorFontName, $Value );
				$TextWidth = $Position [2] - $Position [0];
				if ($TextWidth > $MaxWidth) {
					$MaxWidth = $TextWidth;
				}
			}
			$this->canvas->drawFilledRoundedRectangle(new Point($this->XSize - ($MaxWidth + 20),
																$this->YSize - (20 + (($this->ErrorFontSize + 4) * count ( $this->Errors )))),
													  new Point($this->XSize - 10,
																$this->YSize - 10),
													  6,
													  new Color(233, 185, 185),
													  $this->lineWidth,
													  $this->lineDotSize,
													  $this->shadowProperties);

			$this->canvas->drawRoundedRectangle(new Point($this->XSize - ($MaxWidth + 20),
														  $this->YSize - (20 + (($this->ErrorFontSize + 4) * count ( $this->Errors )))),
												new Point($this->XSize - 10,
														  $this->YSize - 10),
												6,
												new Color(193, 145, 145),
												$this->LineWidth,
												$this->LineDotSize,
												$this->shadowProperties);
			
			$YPos = $this->YSize - (18 + (count ( $this->Errors ) - 1) * ($this->ErrorFontSize + 4));
			foreach ( $this->Errors as $key => $Value ) {
				$this->canvas->drawText($this->ErrorFontSize,
										0,
										new Point($this->XSize - ($MaxWidth + 15),
												  $YPos),
										new Color(133, 85, 85),
										$this->ErrorFontName,
										$Value,
										ShadowProperties::NoShadow());
				$YPos = $YPos + ($this->ErrorFontSize + 4);
			}
		}
	}
	
	/**
	 * Activate the image map creation process 
	 */
	function setImageMap($Mode = TRUE, $GraphID = "MyGraph") {
		$this->BuildMap = $Mode;
		$this->MapID = $GraphID;
	}
	
	/**
	 * Add a box into the image map 
	 */
	function addToImageMap($X1, $Y1, $X2, $Y2, $SerieName, $Value, $CallerFunction) {
		if ($this->MapFunction == NULL || $this->MapFunction == $CallerFunction) {
			$this->ImageMap [] = round ( $X1 ) . "," . round ( $Y1 ) . "," . round ( $X2 ) . "," . round ( $Y2 ) . "," . $SerieName . "," . $Value;
			$this->MapFunction = $CallerFunction;
		}
	}
	
	/**
	 * Load and cleanup the image map from disk 
	 */
	function getImageMap($MapName, $Flush = TRUE) {
		/* Strip HTML query strings */
		$Values = $this->tmpFolder . $MapName;
		$Value = explode ( "\?", $Values );
		$FileName = $Value [0];
		
		if (file_exists ( $FileName )) {
			$Handle = fopen ( $FileName, "r" );
			$MapContent = fread ( $Handle, filesize ( $FileName ) );
			fclose ( $Handle );
			echo $MapContent;
			
			if ($Flush)
				unlink ( $FileName );
			
			exit ();
		} else {
			header ( "HTTP/1.0 404 Not Found" );
			exit ();
		}
	}
	
	/**
	 * Save the image map to the disk 
	 */
	function SaveImageMap() {
		if (! $this->BuildMap) {
			return (- 1);
		}
		
		if ($this->ImageMap == NULL) {
			$this->Errors [] = "[Warning] SaveImageMap - Image map is empty.";
			return (- 1);
		}
		
		$Handle = fopen ( $this->tmpFolder . $this->MapID, 'w' );
		if (! $Handle) {
			$this->Errors [] = "[Warning] SaveImageMap - Cannot save the image map.";
			return (- 1);
		} else {
			foreach ( $this->ImageMap as $Value )
				fwrite ( $Handle, htmlentities ( $Value ) . "\r" );
		}
		fclose ( $Handle );
	}
			
	/**
	 * Set date format for axis labels 
	 */
	function setDateFormat($Format) {
		$this->DateFormat = $Format;
	}
	
	/**
	 * Convert TS to a date format string 
	 */
	function ToDate($Value) {
		return (date ( $this->DateFormat, $Value ));
	}
	
	/**
	 * Check if a number is a full integer (for scaling) 
	 */
	static private function isRealInt($Value) {
		if ($Value == floor ( $Value ))
			return (TRUE);
		return (FALSE);
	}

	/**
	 * @todo I don't know what this does yet, I'm refactoring...
	 */
	public function calculateScales(& $Scale, & $Divisions) {
		/* Compute automatic scaling */
		$ScaleOk = FALSE;
		$Factor = 1;
		$MinDivHeight = 25;
		$MaxDivs = ($this->GArea_Y2 - $this->GArea_Y1) / $MinDivHeight;
		
		if ($this->VMax <= $this->VMin) {
			throw new Exception("Impossible to calculate scales when VMax <= VMin");
		}

		if ($this->VMin == 0 && $this->VMax == 0) {
			$this->VMin = 0;
			$this->VMax = 2;
			$Scale = 1;
			$Divisions = 2;
		} elseif ($MaxDivs > 1) {
			while ( ! $ScaleOk ) {
				$Scale1 = ($this->VMax - $this->VMin) / $Factor;
				$Scale2 = ($this->VMax - $this->VMin) / $Factor / 2;

				if ($Scale1 > 1 && $Scale1 <= $MaxDivs && ! $ScaleOk) {
					$ScaleOk = TRUE;
					$Divisions = floor ( $Scale1 );
					$Scale = 1;
				}
				if ($Scale2 > 1 && $Scale2 <= $MaxDivs && ! $ScaleOk) {
					$ScaleOk = TRUE;
					$Divisions = floor ( $Scale2 );
					$Scale = 2;
				}
				if (! $ScaleOk) {
					if ($Scale2 > 1) {
						$Factor = $Factor * 10;
					}
					if ($Scale2 < 1) {
						$Factor = $Factor / 10;
					}
				}
			}
			
			if (floor ( $this->VMax / $Scale / $Factor ) != $this->VMax / $Scale / $Factor) {
				$GridID = floor ( $this->VMax / $Scale / $Factor ) + 1;
				$this->VMax = $GridID * $Scale * $Factor;
				$Divisions ++;
			}
			
			if (floor ( $this->VMin / $Scale / $Factor ) != $this->VMin / $Scale / $Factor) {
				$GridID = floor ( $this->VMin / $Scale / $Factor );
				$this->VMin = $GridID * $Scale * $Factor;
				$Divisions ++;
			}
		} else /* Can occur for small graphs */
			  $Scale = 1;
	}
        
        /*
         * Returns the resource
         */
        public function getPicture() {
            return $this->canvas->getPicture();
        }

	static private function computeAutomaticScaling($minCoord, $maxCoord, &$minVal, &$maxVal, &$Divisions) {
		$ScaleOk = FALSE;
		$Factor = 1.0;
		$MinDivHeight = 25;
		$MaxDivs = ($maxCoord - $minCoord) / $MinDivHeight;
			
		if ($minVal == 0 && $maxVal == 0){
			$minVal = 0;
			$maxVal = 2;
			$Scale = 1;
			$Divisions = 2;
		}elseif ($minVal == $maxVal){
			$minVal = 0;
			$maxVal = $maxVal*2;
			$Scale = 1;
			$Divisions = 2;
		} elseif ($MaxDivs > 1) {
			while ( ! $ScaleOk ) {
                if($Factor == 0) { die(); }
				$Scale1 = ($maxVal - $minVal) / $Factor;
				$Scale2 = ($maxVal - $minVal) / $Factor / 2;
					
				if ($Scale1 > 1 && $Scale1 <= $MaxDivs && ! $ScaleOk) {
					$ScaleOk = TRUE;
					$Divisions = floor ( $Scale1 );
					$Scale = 1;
				}
				if ($Scale2 > 1 && $Scale2 <= $MaxDivs && ! $ScaleOk) {
					$ScaleOk = TRUE;
					$Divisions = floor ( $Scale2 );
					$Scale = 2;
				}
				if (! $ScaleOk) {
					if ($Scale2 > 1) {
						$Factor = $Factor * 10;
					}
					if ($Scale2 < 1) {
						$Factor = $Factor / 10;
					}
				}
			}
				
			if (floor ( $maxVal / $Scale / $Factor ) != $maxVal / $Scale / $Factor) {
				$GridID = floor ( $maxVal / $Scale / $Factor ) + 1;
				$maxVal = $GridID * $Scale * $Factor;
				$Divisions ++;
			}
				
			if (floor ( $minVal / $Scale / $Factor ) != $minVal / $Scale / $Factor) {
				$GridID = floor ( $minVal / $Scale / $Factor );
				$minVal = $GridID * $Scale * $Factor;
				$Divisions ++;
			}
		} else /* Can occurs for small graphs */
			  $Scale = 1;
			
		if (! isset ( $Divisions ))
			$Divisions = 2;
			
		if (self::isRealInt ( ($maxVal - $minVal) / ($Divisions - 1) ))
			$Divisions --;
		elseif (self::isRealInt ( ($maxVal - $minVal) / ($Divisions + 1) ))
			$Divisions ++;
	}

	static private function convertValueForDisplay($value, $format, $unit) {
		if ($format == "number")
			return $value . $unit;
		if ($format == "time")
			return ConversionHelpers::ToTime ( $value );
		if ($format == "date")
			return $this->ToDate ( $value );
		if ($format == "metric")
			return ConversionHelpers::ToMetric ( $value );
		if ($format == "currency")
			return ConversionHelpers::ToCurrency ( $value );
	}
}

/**
 * 
 * @param $Message
 */
function RaiseFatal($Message) {
	echo "[FATAL] " . $Message . "\r\n";
	exit ();
}
?>

<?php

require_once dirname(__FILE__).'/ICanvas.php';

class GDCanvas implements ICanvas {
	public function __construct($xSize, $ySize, $transparent=true) {
		$this->picture = imagecreatetruecolor($xSize, $ySize);

		$C_White = $this->allocateColor(new Color(255, 255, 255));
		imagefilledrectangle($this->picture, 0, 0, $xSize, $ySize, $C_White);
		if($transparent) imagecolortransparent($this->picture, $C_White);

		$this->antialiasQuality = 0;
	}

	function drawFilledRectangle(Point $corner1, Point $corner2, Color $color,
								 ShadowProperties $shadowProperties, $drawBorder = false,
								 $alpha = 100, $lineWidth = 1, $lineDotSize = 0) {
		if ($corner2->getX() < $corner1->getX()) {
			$newCorner1 = new Point($corner2->getX(), $corner1->getY());
			$newCorner2 = new Point($corner1->getX(), $corner2->getY());

			$corner1 = $newCorner1;
			$corner2 = $newCorner2;
		}

		if ($corner2->getY() < $corner1->getY()) {
			$newCorner1 = new Point($corner1->getX(), $corner2->getY());
			$newCorner2 = new Point($corner2->getX(), $corner1->getY());

			$corner1 = $newCorner1;
			$corner2 = $newCorner2;
		}

		$X1 = $corner1->getX();
		$Y1 = $corner1->getY();

		$X2 = $corner2->getX();
		$Y2 = $corner2->getY();
		
		if ($alpha == 100) {
			/* Process shadows */
			if ($shadowProperties->active) {
				$this->drawFilledRectangle(new Point($X1 + $shadowProperties->xDistance,
													 $Y1 + $shadowProperties->yDistance),
										   new Point($X2 + $shadowProperties->xDistance,
													 $Y2 + $shadowProperties->yDistance),
										   $shadowProperties->color,
										   ShadowProperties::NoShadow(),
										   FALSE,
										   $shadowProperties->alpha);
				if ($shadowProperties->blur != 0) {
					$AlphaDecay = ($shadowProperties->alpha / $shadowProperties->blur);
					
					for($i = 1; $i <= $shadowProperties->blur; $i ++)
						$this->drawFilledRectangle(new Point($X1 + $shadowProperties->xDistance - $i / 2,
															 $Y1 + $shadowProperties->yDistance - $i / 2),
												   new Point($X2 + $shadowProperties->xDistance - $i / 2,
															 $Y2 + $shadowProperties->yDistance - $i / 2),
												   $shadowProperties->color,
												   ShadowProperties::NoShadow(),
												   FALSE,
												   $shadowProperties->alpha - $AlphaDecay * $i);
					for($i = 1; $i <= $shadowProperties->blur; $i ++)
						$this->drawFilledRectangle(new Point($X1 + $shadowProperties->xDistance + $i / 2,
															 $Y1 + $shadowProperties->yDistance + $i / 2),
												   new Point($X2 + $shadowProperties->xDistance + $i / 2,
															 $Y2 + $shadowProperties->xDistance + $i / 2),
												   $shadowProperties->color,
												   ShadowProperties::NoShadow(),
												   FALSE, 
												   $shadowProperties->alpha - $AlphaDecay * $i);
				}
			}
			
			$C_Rectangle = $this->allocateColor($color);
			imagefilledrectangle($this->picture, round ( $X1 ), round ( $Y1 ), round ( $X2 ), round ( $Y2 ), $C_Rectangle );
		} else {
			$LayerWidth = abs ( $X2 - $X1 ) + 2;
			$LayerHeight = abs ( $Y2 - $Y1 ) + 2;
			
			$this->Layers [0] = imagecreatetruecolor ( $LayerWidth, $LayerHeight );
			$C_White = imagecolorallocate( $this->Layers [0], 255, 255, 255);
			imagefilledrectangle ( $this->Layers [0], 0, 0, $LayerWidth, $LayerHeight, $C_White );
			imagecolortransparent ( $this->Layers [0], $C_White );
			
			$C_Rectangle = imagecolorallocate( $this->Layers [0], $color->r, $color->g, $color->b);
			imagefilledrectangle ( $this->Layers [0], round ( 1 ), round ( 1 ), round ( $LayerWidth - 1 ), round ( $LayerHeight - 1 ), $C_Rectangle );
			
			imagecopymerge ($this->picture, $this->Layers [0], round ( min ( $X1, $X2 ) - 1 ), round ( min ( $Y1, $Y2 ) - 1 ), 0, 0, $LayerWidth, $LayerHeight, $alpha);
			imagedestroy ( $this->Layers [0] );
		}
		
		if ($drawBorder) {
			$this->drawRectangle(new Point($X1, $Y1),
								 new Point($X2, $Y2),
								 $color,
								 $lineWidth,
								 $lineDotSize,
								 ShadowProperties::NoShadow());
		}
	}

	public function drawRectangle(Point $corner1, Point $corner2, Color $color, $lineWidth, $lineDotSize, ShadowProperties $shadowProperties) {
		$X1 = $corner1->getX() - .2;
		$Y1 = $corner1->getY() - .2;
		$X2 = $corner2->getX() + .2;
		$Y2 = $corner2->getY() + .2;
		$this->drawLine(new Point($X1, $Y1),
						new Point($X2, $Y1),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);

		$this->drawLine(new Point($X2, $Y1),
						new Point($X2, $Y2),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);

		$this->drawLine(new Point($X2, $Y2),
						new Point($X1, $Y2),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);
		
		$this->drawLine(new Point($X1, $Y2),
						new Point($X1, $Y1),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);

	}

	public function drawRoundedRectangle(Point $point1, Point $point2, $radius, Color $color, $lineWidth, $lineDotSize, ShadowProperties $shadowProperties) {
		$Step = 90 / ((M_PI * $radius) / 2);
		
		for($i = 0; $i <= 90; $i = $i + $Step) {
			$X = cos ( ($i + 180) * M_PI / 180 ) * $radius + $point1->getX() + $radius;
			$Y = sin ( ($i + 180) * M_PI / 180 ) * $radius + $point1->getY() + $radius;
			$this->drawAntialiasPixel(new Point($X, $Y), $color, $shadowProperties);
			
			$X = cos ( ($i - 90) * M_PI / 180 ) * $radius + $point2->getX() - $radius;
			$Y = sin ( ($i - 90) * M_PI / 180 ) * $radius + $point1->getY() + $radius;
			$this->drawAntialiasPixel(new Point($X, $Y), $color, $shadowProperties);
			
			$X = cos ( ($i) * M_PI / 180 ) * $radius + $point2->getX() - $radius;
			$Y = sin ( ($i) * M_PI / 180 ) * $radius + $point2->getY() - $radius;
			$this->drawAntialiasPixel(new Point($X, $Y), $color, $shadowProperties);
			
			$X = cos ( ($i + 90) * M_PI / 180 ) * $radius + $point1->getX() + $radius;
			$Y = sin ( ($i + 90) * M_PI / 180 ) * $radius + $point2->getY() - $radius;
			$this->drawAntialiasPixel(new Point($X, $Y), $color, $shadowProperties);
		}
		
		$X1 = $point1->getX() - .2;
		$Y1 = $point1->getY() - .2;
		$X2 = $point2->getX() + .2;
		$Y2 = $point2->getY() + .2;
		$this->drawLine(new Point($X1 + $radius, $Y1),
						new Point($X2 - $radius, $Y1),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);

		$this->drawLine(new Point($X2, $Y1 + $radius),
						new Point($X2, $Y2 - $radius),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);

		$this->drawLine(new Point($X2 - $radius, $Y2),
						new Point($X1 + $radius, $Y2),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);

		$this->drawLine(new Point($X1, $Y2 - $radius),
						new Point($X1, $Y1 + $radius),
						$color,
						$lineWidth,
						$lineDotSize,
						$shadowProperties);		
	}

	/**
	 * This function creates a filled rectangle with rounded corners
	 * and antialiasing
	 */
	function drawFilledRoundedRectangle(Point $point1, Point $point2, $radius,
										Color $color, $lineWidth, $lineDotSize,
										ShadowProperties $shadowProperties) {
		$C_Rectangle = $this->allocateColor($color);
		
		$Step = 90 / ((M_PI * $radius) / 2);
		
		for($i = 0; $i <= 90; $i = $i + $Step) {
			$Xi1 = cos ( ($i + 180) * M_PI / 180 ) * $radius 
				+ $point1->getX() 
				+ $radius;

			$Yi1 = sin ( ($i + 180) * M_PI / 180 ) * $radius 
				+ $point1->getY() 
				+ $radius;
			
			$Xi2 = cos ( ($i - 90) * M_PI / 180 ) * $radius 
				+ $point2->getX() 
				- $radius;

			$Yi2 = sin ( ($i - 90) * M_PI / 180 ) * $radius 
				+ $point1->getY()
				+ $radius;
			
			$Xi3 = cos ( ($i) * M_PI / 180 ) * $radius 
				+ $point2->getX() 
				- $radius;

			$Yi3 = sin ( ($i) * M_PI / 180 ) * $radius 
				+ $point2->getY() 
				- $radius;
			
			$Xi4 = cos ( ($i + 90) * M_PI / 180 ) * $radius
				+ $point1->getX()
				+ $radius;

			$Yi4 = sin ( ($i + 90) * M_PI / 180 ) * $radius
				+ $point2->getY() 
				- $radius;
			
			imageline($this->picture,
					  $Xi1, $Yi1, 
					  $point1->getX() + $radius, $Yi1,
					  $C_Rectangle);

			imageline($this->picture, $point2->getX() - $radius, $Yi2,
					  $Xi2, $Yi2,
					  $C_Rectangle);

			imageline($this->picture,
					  $point2->getX() - $radius, $Yi3,
					  $Xi3, $Yi3,
					  $C_Rectangle);

			imageline($this->picture,
					  $Xi4, $Yi4,
					  $point1->getX() + $radius, $Yi4,
					  $C_Rectangle );
			
			$this->drawAntialiasPixel(new Point($Xi1, $Yi1),
									  $color,
									  $shadowProperties);
			$this->drawAntialiasPixel(new Point($Xi2, $Yi2),
									  $color,
									  $shadowProperties);
			$this->drawAntialiasPixel(new Point($Xi3, $Yi3),
									  $color,
									  $shadowProperties);
			$this->drawAntialiasPixel(new Point($Xi4, $Yi4),
									  $color,
									  $shadowProperties);
		}
		
		imagefilledrectangle($this->picture,
							 $point1->getX(), $point1->getY() + $radius,
							 $point2->getX(), $point2->getY() - $radius,
							 $C_Rectangle);

		imagefilledrectangle($this->picture,
							 $point1->getX() + $radius, $point1->getY(),
							 $point2->getX() - $radius, $point2->getY(),
							 $C_Rectangle);
		
		$X1 = $point1->getX() - .2;
		$Y1 = $point1->getY() - .2;
		$X2 = $point2->getX() + .2;
		$Y2 = $point2->getY() + .2;
		$this->drawLine(new Point($X1 + $radius, $Y1),
						new Point($X2 - $radius, $Y1),
						$color,
						$lineWidth, $lineDotSize,
						$shadowProperties);

		$this->drawLine(new Point($X2, $Y1 + $radius),
						new Point($X2, $Y2 - $radius),
						$color,
						$lineWidth, $lineDotSize,
						$shadowProperties);
		
		$this->drawLine(new Point($X2 - $radius, $Y2),
						new Point($X1 + $radius, $Y2),
						$color,
						$lineWidth, $lineDotSize,
						$shadowProperties);

		$this->drawLine(new Point($X1, $Y2 - $radius),
						new Point($X1, $Y1 + $radius),
						$color,
						$lineWidth, $lineDotSize,
						$shadowProperties);

	}


	public function drawLine(Point $point1, Point $point2, Color $color, $lineWidth, $lineDotSize, ShadowProperties $shadowProperties, Point $boundingBoxMin = null, Point $boundingBoxMax = null) {
		if ($lineDotSize > 1) {
			$this->drawDottedLine($point1,
								  $point2,
								  $lineDotSize, $lineWidth,
								  $color, $shadowProperties,
								  $boundingBoxMin,
								  $boundingBoxMax);
			return;
		}
		
		$Distance = $point1->distanceFrom($point2);
		if ($Distance == 0)
			return;
		$XStep = ($point2->getX() - $point1->getX()) / $Distance;
		$YStep = ($point2->getY() - $point1->getY()) / $Distance;
		
		for($i = 0; $i <= $Distance; $i ++) {
			$X = $i * $XStep + $point1->getX();
			$Y = $i * $YStep + $point1->getY();
			
			if ((($boundingBoxMin == null) || (($X >= $boundingBoxMin->getX())
											   && ($Y >= $boundingBoxMin->getY())))
				&& (($boundingBoxMax == null) || (($X <= $boundingBoxMax->getX())
												  && ($Y <= $boundingBoxMax->getY())))) {
				if ($lineWidth == 1)
					$this->drawAntialiasPixel(new Point($X, $Y), $color, $shadowProperties);
				else {
					$StartOffset = - ($lineWidth / 2);
					$EndOffset = ($lineWidth / 2);
					for($j = $StartOffset; $j <= $EndOffset; $j ++)
						$this->drawAntialiasPixel(new Point($X + $j, $Y + $j),
												  $color, $shadowProperties);
				}
			}
		}
	}

	public function drawDottedLine(Point $point1, Point $point2, $dotSize, $lineWidth, Color $color, ShadowProperties $shadowProperties, Point $boundingBoxMin = null, Point $boundingBoxMax = null) {
		$Distance = $point1->distanceFrom($point2);

		$XStep = ($point2->getX() - $point1->getX()) / $Distance;
		$YStep = ($point2->getY() - $point1->getY()) / $Distance;
		
		$DotIndex = 0;
		for($i = 0; $i <= $Distance; $i ++) {
			$X = $i * $XStep + $point1->getX();
			$Y = $i * $YStep + $point1->getY();
			
			if ($DotIndex <= $dotSize) {
				if (($boundingBoxMin == null || (($X >= $boundingBoxMin->getX())
												 && ($Y >= $boundingBoxMin->getY())))
					&& ($boundingBoxMax == null || (($X <= $boundingBoxMax->getX())
													&& ($Y <= $boundingBoxMax->getY())))) {
					if ($lineWidth == 1)
						$this->drawAntialiasPixel(new Point($X, $Y),
												  $color, $shadowProperties);
					else {
						$StartOffset = - ($lineWidth / 2);
						$EndOffset = ($lineWidth / 2);
						for($j = $StartOffset; $j <= $EndOffset; $j ++) {
							$this->drawAntialiasPixel(new Point($X + $j,
																$Y + $j),
													  $color, $shadowProperties);
						}
					}
				}
			}
			
			$DotIndex ++;
			if ($DotIndex == $dotSize * 2)
				$DotIndex = 0;
		}		
	}

	public function drawAntialiasPixel(Point $point, Color $color, ShadowProperties $shadowProperties, $alpha = 100) {
		/* Process shadows */
		if ($shadowProperties->active) {
			$this->drawAntialiasPixel(new Point($point->getX() + $shadowProperties->xDistance,
												$point->getY() + $shadowProperties->yDistance),
									  $shadowProperties->color,
									  ShadowProperties::NoShadow(),
									  $shadowProperties->alpha);
			if ($shadowProperties->blur != 0) {
				$AlphaDecay = ($shadowProperties->alpha / $shadowProperties->blur);
				
				for($i = 1; $i <= $shadowProperties->blur; $i ++)
					$this->drawAntialiasPixel(new Point($point->getX() + $shadowProperties->xDistance - $i / 2,
														$point->getY() + $shadowProperties->yDistance - $i / 2),
											  $shadowProperties->color,
											  ShadowProperties::NoShadow(),
											  $shadowProperties->alpha - $AlphaDecay * $i);
				for($i = 1; $i <= $shadowProperties->blur; $i ++)
					$this->drawAntialiasPixel(new Point($point->getX() + $shadowProperties->xDistance + $i / 2,
														$point->getY() + $shadowProperties->yDistance + $i / 2),
											  $shadowProperties->color, 
											  ShadowProperties::NoShadow(),
											  $shadowProperties->alpha - $AlphaDecay * $i);
			}
		}
		
		$Xi = floor ( $point->getX() );
		$Yi = floor ( $point->getY() );
		
		if ($Xi == $point->getX() && $Yi == $point->getY()) {
			if ($alpha == 100) {
				$C_Aliased = $this->allocateColor($color);
				imagesetpixel ( $this->picture, 
								$point->getX(), $point->getY(),
								$C_Aliased );
			} else
				$this->drawAlphaPixel($point, $alpha, $color);
		} else {
			$Alpha1 = (((1 - ($point->getX() - $Xi)) * (1 - ($point->getY() - $Yi)) * 100) / 100) * $alpha;
			if ($Alpha1 > $this->antialiasQuality) {
				$this->drawAlphaPixel(new Point($Xi, $Yi), $Alpha1, $color);
			}
			
			$Alpha2 = ((($point->getX() - $Xi) * (1 - ($point->getY() - $Yi)) * 100) / 100) * $alpha;
			if ($Alpha2 > $this->antialiasQuality) {
				$this->drawAlphaPixel (new Point($Xi + 1, $Yi), $Alpha2, $color);
			}
			
			$Alpha3 = (((1 - ($point->getX() - $Xi)) * ($point->getY() - $Yi) * 100) / 100) 
				* $alpha;
			if ($Alpha3 > $this->antialiasQuality) {
				$this->drawAlphaPixel (new Point($Xi, $Yi + 1), $Alpha3, $color);
			}
			
			$Alpha4 = ((($point->getX() - $Xi) * ($point->getY() - $Yi) * 100) / 100) 
				* $alpha;
			if ($Alpha4 > $this->antialiasQuality) {
				$this->drawAlphaPixel (new Point($Xi + 1, $Yi + 1), $Alpha4, $color);
			}
		}
	}

	public function drawAlphaPixel(Point $point, $alpha, Color $color) {
		/** @todo Check that the point is within the bounds of the
		 * canvas */

		$RGB2 = imagecolorat ( $this->picture, $point->getX(), $point->getY());
		$R2 = ($RGB2 >> 16) & 0xFF;
		$G2 = ($RGB2 >> 8) & 0xFF;
		$B2 = $RGB2 & 0xFF;
		
		$iAlpha = (100 - $alpha) / 100;
		$alpha = $alpha / 100;
		
		$Ra = floor ( $color->r * $alpha + $R2 * $iAlpha );
		$Ga = floor ( $color->g * $alpha + $G2 * $iAlpha );
		$Ba = floor ( $color->b * $alpha + $B2 * $iAlpha );
		
		$C_Aliased = $this->allocateColor (new Color($Ra, $Ga, $Ba));
		imagesetpixel($this->picture, $point->getX(), $point->getY(), $C_Aliased );
	}

	/**
	 * Color helper 
	 *
	 * @todo This shouldn't need to be public, it's only a temporary
	 * step while refactoring
	 */
	public function allocateColor(Color $color, $Factor = 0, $alpha = 100) {
		if ($Factor != 0) {
			$color = $color->addRGBIncrement($Factor);
		}
		
		if ($alpha == 100) {
			return (imagecolorallocate ($this->picture, $color->r, $color->g, $color->b ));
		}
		else {
			return imagecolorallocatealpha($this->picture,
										   $color->r,
										   $color->g,
										   $color->b,
										   127 * (1 - $alpha / 100));
		}
	}

	/**
	 * @todo This is only a temporary interface while I'm
	 * refactoring. This should eventually be removed.
	 */
	public function getPicture() {
		return $this->picture;
	}

	public function getAntialiasQuality() {
		return $this->antialiasQuality;
	}

	public function setAntialiasQuality($newQuality) {
		if (!is_numeric($newQuality)
			|| $newQuality < 0
			|| $newQuality > 100) {
			throw new InvalidArgumentException("Invalid argument to GDCanvas::setAntialiasQuality()");
		}

		$this->antialiasQuality = $newQuality;
	}

	function drawText($fontSize, $angle, Point $point, Color $color, $fontName, $text, ShadowProperties $shadowProperties) {
		if ($shadowProperties->active) {
			$gdShadowColor = $this->allocateColor($shadowProperties->color);

			imagettftext($this->picture, $fontSize, $angle,
						 $point->getX() + $shadowProperties->xDistance,
						 $point->getY() + $shadowProperties->yDistance,
						 $gdShadowColor,
						 $fontName, $text);
		}

		$gdColor = $this->allocateColor($color);

		imagettftext($this->picture, $fontSize, $angle, 
					 $point->getX(), $point->getY(),
					 $gdColor, $fontName, $text);
	}

	function drawCircle(Point $center, $height, Color $color, ShadowProperties $shadowProperties, $width = null) {
		if ($width == null) {
			$width = $height;
		}

		$Step = 360 / (2 * M_PI * max ( $width, $height ));
		
		for($i = 0; $i <= 360; $i = $i + $Step) {
			$X = cos ( $i * M_PI / 180 ) * $height + $center->getX();
			$Y = sin ( $i * M_PI / 180 ) * $width + $center->getY();
			$this->drawAntialiasPixel(new Point($X, $Y),
									  $color,
									  $shadowProperties);
		}

	}

	public function drawFilledCircle(Point $center, $height, Color $color, ShadowProperties $shadowProperties, $width = null) {
		if ($width == null) {
			$width = $height;
		}
		
		$C_Circle = $this->allocateColor($color);
		$Step = 360 / (2 * M_PI * max ( $width, $height ));
		
		for($i = 90; $i <= 270; $i = $i + $Step) {
			$X1 = cos ( $i * M_PI / 180 ) * $height + $center->getX();
			$Y1 = sin ( $i * M_PI / 180 ) * $width + $center->getY();
			$X2 = cos ( (180 - $i) * M_PI / 180 ) * $height + $center->getX();
			$Y2 = sin ( (180 - $i) * M_PI / 180 ) * $width + $center->getY();
			
			$this->drawAntialiasPixel(new Point($X1 - 1, $Y1 - 1),
									  $color,
									  $shadowProperties);
			$this->drawAntialiasPixel(new Point($X2 - 1, $Y2 - 1),
									  $color,
									  $shadowProperties);
			
			if (($Y1 - 1) > $center->getY() - max ( $width, $height )) {
				imageline ( $this->picture, $X1, $Y1 - 1, $X2 - 1, $Y2 - 1, $C_Circle );
			}
		}
	}

	function drawFilledPolygon(array $points, $numPoints, Color $color, $alpha = 100) {
		$gdColor = $this->allocateColor($color, 0, $alpha);

		imagefilledpolygon($this->picture,
						   $points,
						   $numPoints,
						   $gdColor);
	}

	private $picture;

	/**
	 * Quality of the antialiasing we do: 0 is maximum, 100 is minimum
	 */
	private $antialiasQuality;
}

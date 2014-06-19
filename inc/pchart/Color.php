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

/**
 * Color is an immutable class, so all mutator methods return a new
 * Color instance rather than modifying this instance.
 *
 * The immutability is in practice undermined by the fact that the RGB
 * components are public. This is a transitional detail that should
 * eventually be done away with.
 */
class Color {
    /**
     * The members r, g and b are still public since they are used
     * within GDCanvas. Since we don't have any GDCanvas unit tests
     * yet, we can't safely make these private at the moment.
     */
    public $r;
    public $g;
    public $b;

    /**
     * Initializes a new RGB color
     *
     * @param int|string $red  either red channel or the whole color in hex
     * @param int $green
     * @param int $blue
     * @throws InvalidArgumentException
     */
    public function __construct($red, $green = null, $blue = null) {
        if(!is_numeric($red)) {
            // we assume it's hex
            list($red, $green, $blue) = $this->Hex2RGB($red);
        }
        if(is_null($green)) $green = $red;
        if(is_null($blue)) $blue = $red;

        if($red < 0 || $red > 255) {
            throw new InvalidArgumentException("Invalid Red component");
        }

        if($green < 0 || $green > 255) {
            throw new InvalidArgumentException("Invalid Green component");
        }

        if($blue < 0 || $blue > 255) {
            throw new InvalidArgumentException("Invalid Blue component");
        }

        $this->r = $red;
        $this->g = $green;
        $this->b = $blue;
    }

    /**
     * Creates a new random color
     *
     * @static
     * @todo make sure it's a visible color
     * @param mixed $rand optional externally created random value
     * @return Color
     */
    public static function random($rand = null) {
        if(!$rand) $rand = rand();

        return new Color('#'.substr(md5($rand),0,6));
    }

    /**
     * Return the color as a HTML hex color
     *
     * @return string
     */
    public function getHex() {
        return sprintf('#%02x%02x%02x', $this->r, $this->g, $this->b);
    }

    /**
     * Return RGB values of a hex color
     *
     * @param $color
     * @return array
     * @throws InvalidArgumentException
     */
    private function Hex2RGB($color) {
        if(substr($color,0,1) == '#') $color = substr($color, 1);

        if(strlen($color) == 6) {
            list($r, $g, $b) = array(
                $color[0].$color[1],
                $color[2].$color[3],
                $color[4].$color[5]
            );
        } elseif(strlen($color) == 3) {
            list($r, $g, $b) = array(
                $color[0].$color[0],
                $color[1].$color[1],
                $color[2].$color[2]
            );
        } else {
            throw new InvalidArgumentException("Invalid hex color: ".$color);
        }

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);

        return array($r, $g, $b);
    }

    /**
     * Return a new color formed by adding the specified increment to
     * the R, G and B values
     */
    public function addRGBIncrement($increment) {
        $incremented = new Color($this->r, $this->g, $this->b);

        $incremented->r = $this->truncateColorComponentRange($incremented->r + $increment);
        $incremented->g = $this->truncateColorComponentRange($incremented->g + $increment);
        $incremented->b = $this->truncateColorComponentRange($incremented->b + $increment);

        return $incremented;
    }

    /**
     * Returns a string representation of the color
     *
     * @return string
     */
    public function __toString() {
        return sprintf("Color<%d, %d, %d>", $this->r, $this->g, $this->b);
    }

    /**
     * Makes sure the input is a valid color range (0-255)
     *
     * @param $input
     * @return int
     */
    private function truncateColorComponentRange($input) {
        if($input > 255) {
            return 255;
        } elseif($input < 0) {
            return 0;
        } else {
            return $input;
        }
    }

    /**
     * Get the red channel
     *
     * @return int
     */
    public function getR() {
        return $this->r;
    }

    /**
     * Get the green channel
     *
     * @return int
     */
    public function getG() {
        return $this->g;
    }

    /**
     * Get the blue channel
     *
     * @return int
     */
    public function getB() {
        return $this->b;
    }
}
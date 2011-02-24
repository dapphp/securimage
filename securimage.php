<?php

/**
 * Project:     Securimage: A PHP class for creating and managing form CAPTCHA images<br />
 * File:        securimage.php<br />
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or any later version.<br /><br />
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.<br /><br />
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA<br /><br />
 *
 * Any modifications to the library should be indicated clearly in the source code
 * to inform users that the changes are not a part of the original software.<br /><br />
 *
 * If you found this script useful, please take a quick moment to rate it.<br />
 * http://www.hotscripts.com/rate/49400.html  Thanks.
 *
 * @link http://www.phpcaptcha.org Securimage PHP CAPTCHA
 * @link http://www.phpcaptcha.org/latest.zip Download Latest Version
 * @link http://www.phpcaptcha.org/Securimage_Docs/ Online Documentation
 * @copyright 2009 Drew Phillips
 * @author Drew Phillips <drew@drew-phillips.com>
 * @version 2.0.1 BETA (December 6th, 2009)
 * @package Securimage
 *
 */

/**
 ChangeLog
 
 3.0
 - Convert to PHP5
 - Remove support for GD fonts, require FreeType
 - Remove support for multi-color codes
 - Add option to make codes case-sensitive
 - Add namespaces to support multiple captchas on a single page or page specific captchas

 2.0.2
 - Fix pathing to make integration into libraries easier (Nathan Phillip Brink ohnobinki@ohnopublishing.net)

 2.0.1
 - Add support for browsers with cookies disabled (requires php5, sqlite) maps users to md5 hashed ip addresses and md5 hashed codes for security
 - Add fallback to gd fonts if ttf support is not enabled or font file not found (Mike Challis http://www.642weather.com/weather/scripts.php)
 - Check for previous definition of image type constants (Mike Challis)
 - Fix mime type settings for audio output
 - Fixed color allocation issues with multiple colors and background images, consolidate allocation to one function
 - Ability to let codes expire after a given length of time
 - Allow HTML color codes to be passed to Securimage_Color (suggested by Mike Challis)

 2.0.0
 - Add mathematical distortion to characters (using code from HKCaptcha)
 - Improved session support
 - Added Securimage_Color class for easier color definitions
 - Add distortion to audio output to prevent binary comparison attack (proposed by Sven "SavageTiger" Hagemann [insecurity.nl])
 - Flash button to stream mp3 audio (Douglas Walsh www.douglaswalsh.net)
 - Audio output is mp3 format by default
 - Change font to AlteHaasGrotesk by yann le coroller
 - Some code cleanup 

 1.0.4 (unreleased)
 - Ability to output audible codes in mp3 format to stream from flash

 1.0.3.1
 - Error reading from wordlist in some cases caused words to be cut off 1 letter short

 1.0.3
 - Removed shadow_text from code which could cause an undefined property error due to removal from previous version

 1.0.2
 - Audible CAPTCHA Code wav files
 - Create codes from a word list instead of random strings

 1.0
 - Added the ability to use a selected character set, rather than a-z0-9 only.
 - Added the multi-color text option to use different colors for each letter.
 - Switched to automatic session handling instead of using files for code storage
 - Added GD Font support if ttf support is not available.  Can use internal GD fonts or load new ones.
 - Added the ability to set line thickness
 - Added option for drawing arced lines over letters
 - Added ability to choose image type for output

 */


/**
 * Securimage CAPTCHA Class.
 *
 * @version    3.0
 * @package    Securimage
 * @subpackage classes
 * @author     Drew Phillips <drew@drew-phillips.com>
 *
 */
class Securimage
{
    const SI_IMAGE_JPEG = 1;
    const SI_IMAGE_PNG  = 2;
    const SI_IMAGE_GIF  = 3;
    
    public $image_width;
    public $image_height;
    
    public $background_directory;
    public $use_wordlist;
    public $wordlist_file;
    
    public $use_sqlite_db;
    public $sqlite_database;
    
    public $namespace;
    
    protected $im;
    protected $tmpimg;
    protected $bgimg;
    protected $iscale;
    
    protected $captcha_code;
    protected $sqlite_handle;
    
    protected $gdbgcolor;
    protected $gdtextcolor;
    protected $gdlinecolor;
    protected $gdsignaturecolor;
    
    public function __construct()
    {
        
    }
    
	public function show($background_image = '')
	{
        if($background_image != '' && is_readable($background_image)) {
            $this->bgimg = $background_image;
        }

        $this->doImage();
	}
	
	public function check($code)
	{
	    
	}
	
	public function outputAudioFile()
	{
	    
	}
	
	protected function doImage()
	{
        if( ($this->use_transparent_text == true || $this->bgimg != '') && function_exists('imagecreatetruecolor')) {
            $imagecreate = 'imagecreatetruecolor';
        } else {
            $imagecreate = 'imagecreate';
        }
        
        $this->im     = $imagecreate($this->image_width, $this->image_height);
        $this->tmpimg = $imagecreate($this->image_width * $this->iscale, $this->image_height * $this->iscale);
        
        $this->allocateColors();
        imagepalettecopy($this->tmpimg, $this->im);

        $this->setBackground();

        $this->createCode();

        if (!$this->draw_lines_over_text && $this->num_lines > 0) $this->drawLines();

        $this->drawWord();
        if ($this->use_gd_font == false && is_readable($this->ttf_file)) $this->distortedCopy();

        if ($this->draw_lines_over_text && $this->num_lines > 0) $this->drawLines();

        if (trim($this->image_signature) != '')    $this->addSignature();

        $this->output();
	}
	
	protected function allocateColors()
	{
	    // allocate bg color first for imagecreate
        $this->gdbgcolor = imagecolorallocate($this->im,
                                              $this->image_bg_color->r,
                                              $this->image_bg_color->g,
                                              $this->image_bg_color->b);
        
        $alpha = intval($this->text_transparency_percentage / 100 * 127);
        
        if ($this->use_transparent_text == true) {
            $this->gdtextcolor = imagecolorallocatealpha($this->im,
                                                         $this->text_color->r,
                                                         $this->text_color->g,
                                                         $this->text_color->b,
                                                         $alpha);
            $this->gdlinecolor = imagecolorallocatealpha($this->im,
                                                         $this->line_color->r,
                                                         $this->line_color->g,
                                                         $this->line_color->b,
                                                         $alpha);
        } else {
            $this->gdtextcolor = imagecolorallocate($this->im,
                                                    $this->text_color->r,
                                                    $this->text_color->g,
                                                    $this->text_color->b);
            $this->gdlinecolor = imagecolorallocate($this->im,
                                                    $this->line_color->r,
                                                    $this->line_color->g,
                                                    $this->line_color->b);
        }
    
        $this->gdsignaturecolor = imagecolorallocate($this->im,
                                                     $this->signature_color->r,
                                                     $this->signature_color->g,
                                                     $this->signature_color->b);

	}
	
	protected function setBackground()
    {
        // set background color of image by drawing a rectangle since imagecreatetruecolor doesn't set a bg color
        imagefilledrectangle($this->im, 0, 0,
                             $this->image_width, $this->image_height,
                             $this->gdbgcolor);
        imagefilledrectangle($this->tmpimg, 0, 0,
                             $this->image_width * $this->iscale, $this->image_height * $this->iscale,
                             $this->gdbgcolor);
    
        if ($this->bgimg == '') {
            if ($this->background_directory != null && 
                is_dir($this->background_directory) &&
                is_readable($this->background_directory))
            {
                $img = $this->getBackgroundFromDirectory();
                if ($img != false) {
                    $this->bgimg = $img;
                }
            }
        }
        
        if ($this->bgimg == '') {
            return;
        }

        $dat = @getimagesize($this->bgimg);
        if($dat == false) { 
            return;
        }

        switch($dat[2]) {
            case 1:  $newim = @imagecreatefromgif($this->bgimg); break;
            case 2:  $newim = @imagecreatefromjpeg($this->bgimg); break;
            case 3:  $newim = @imagecreatefrompng($this->bgimg); break;
            default: return;
        }

        if(!$newim) return;

        imagecopyresized($this->im, $newim, 0, 0, 0, 0,
                         $this->image_width, $this->image_height,
                         imagesx($newim), imagesy($newim));
    }
    
    protected function getBackgroundFromDirectory()
    {
        $images = array();

        if ($dh = opendir($this->background_directory)) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/(jpg|gif|png)$/i', $file)) $images[] = $file;
            }

            closedir($dh);

            if (sizeof($images) > 0) {
                return rtrim($this->background_directory, '/') . '/' . $images[rand(0, sizeof($images)-1)];
            }
        }

        return false;
    }
    
    protected function createCode()
    {
        $this->captcha_code = false;

        if ($this->use_wordlist && is_readable($this->wordlist_file)) {
            $this->captcha_code = $this->readCodeFromFile();
        }

        if ($this->captcha_code == false) {
            $this->captcha_code = $this->generateCode($this->code_length);
        }
        
        $this->saveData();
    }
    
    protected function readCodeFromFile()
    {
        $fp = @fopen($this->wordlist_file, 'rb');
        if (!$fp) return false;

        $fsize = filesize($this->wordlist_file);
        if ($fsize < 128) return false; // too small of a list to be effective

        fseek($fp, rand(0, $fsize - 16), SEEK_SET); // seek to a random position of file from 0 to filesize-16
        $data = fread($fp, 64); // read a chunk from our random position
        fclose($fp);
        $data = preg_replace("/\r?\n/", "\n", $data);

        $start = @strpos($data, "\n", rand(0, 56)) + 1; // random start position
        $end   = @strpos($data, "\n", $start);          // find end of word
        
        if ($start === false) {
            return false;
        } else if ($end === false) {
            $end = strlen($data);
        }

        return strtolower(substr($data, $start, $end - $start)); // return a line of the file
    }
    
    protected function generateCode()
    {
        $code = '';

        for($i = 1, $cslen = strlen($this->charset); $i <= $len; ++$i) {
            $code .= $this->charset{rand(0, $cslen - 1)};
        }
        return $code;
    }
    
    protected function saveData()
    {
        $_SESSION['securimage_code_value'][$this->namespace] = $this->code;
        $_SESSION['securimage_code_ctime'][$this->namespace] = time();
        
        $this->saveCodeToDatabase();
    }
    
    protected function saveCodeToDatabase()
    {
        $success = false;
        
        $this->openDatabase();
        
        if ($this->use_sqlite_db && $this->sqlite_handle !== false) {
            $ip      = $_SERVER['REMOTE_ADDR'];
            $time    = time();
            $code    = $_SESSION['securimage_code_value'][$this->namespace]; // hash code for security - if cookies are disabled the session still exists at this point
            $success = sqlite_query($this->sqlite_handle,
                                    "INSERT OR REPLACE INTO codes(ip, code, namespace created)
                                    VALUES('$ip', '$code', '{$this->namespace}', $time)");
        }
        
        return $success !== false;
    }
}


/**
 * Color object for Securimage CAPTCHA
 *
 * @version 3.0
 * @since 2.0
 * @package Securimage
 * @subpackage classes
 *
 */
class Securimage_Color
{
	public $r;
	public $g;
	public $b;

	/**
	 * Create a new Securimage_Color object.<br />
	 * Constructor expects 1 or 3 arguments.<br />
	 * When passing a single argument, specify the color using HTML hex format,<br />
	 * when passing 3 arguments, specify each RGB component (from 0-255) individually.<br />
	 * $color = new Securimage_Color('#0080FF') or <br />
	 * $color = new Securimage_Color(0, 128, 255)
	 * 
	 * @param string $color
	 * @throws Exception
	 */
	public function __construct($color = '#ffffff')
	{
	    $args = func_get_args();
	    
	    if (sizeof($args) == 0) {
	        $this->r = 255;
	        $this->g = 255;
	        $this->b = 255;
	    } else if (sizeof($args) == 1) {
	        // set based on html code
	        if (substr($color, 0, 1) == '#') {
	            $color = substr($color, 1);
	        }
	        
	        if (strlen($color) != 3 && strlen($color) != 6) {
	            throw new InvalidArgumentException(
	              'Invalid HTML color code passed to Securimage_Color'
	            );
	        }
	        
	        $this->constructHTML($color);
	    } else if (sizeof($args) == 3) {
	        $this->constructRGB($args[0], $args[1], $args[2]);
	    } else {
	        throw new InvalidArgumentException(
	          'Securimage_Color constructor expects 0, 1 or 3 arguments; ' . sizeof($args) . ' given'
	        );
	    }
	}
	
	protected function constructRGB($red, $green, $blue)
	{
	    if ($red < 0)     $red   = 0;
		if ($red > 255)   $red   = 255;
		if ($green < 0)   $green = 0;
		if ($green > 255) $green = 255;
		if ($blue < 0)    $blue  = 0;
		if ($blue > 255)  $blue  = 255;
		
		$this->r = $red;
		$this->g = $green;
		$this->b = $blue;
	}
	
	protected function constructHTML($color)
	{
	    if (strlen($color) == 3) {
	        $red   = str_repeat(substr($color, 0, 1), 2);
			$green = str_repeat(substr($color, 1, 1), 2);
			$blue  = str_repeat(substr($color, 2, 1), 2);
		} else {
			$red   = substr($color, 0, 2);
			$green = substr($color, 2, 2);
			$blue  = substr($color, 4, 2); 
		}
		
		$this->r = hexdec($red);
		$this->g = hexdec($green);
		$this->b = hexdec($blue);
	}
}

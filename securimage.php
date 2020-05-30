<?php

 //error_reporting(E_ALL); ini_set('display_errors', 1); // uncomment this line for debugging

/**
 * Project:  Securimage: A PHP class dealing with CAPTCHA images, audio, and validation
 * File:     securimage.php
 *
 * Copyright (c) 2018, Drew Phillips
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Any modifications to the library should be indicated clearly in the source code
 * to inform users that the changes are not a part of the original software.
 *
 * @link https://www.phpcaptcha.org Securimage Homepage
 * @link https://www.phpcaptcha.org/latest.zip Download Latest Version
 * @link https://github.com/dapphp/securimage GitHub page
 * @link https://www.phpcaptcha.org/Securimage_Docs/ Online Documentation
 * @copyright 2018 Drew Phillips
 * @author Drew Phillips <drew@drew-phillips.com>
 * @version 4.0.2 (March 2018)
 * @package Securimage
 *
 * See the file CHANGES for changelog
 *
 */


/**
 * Securimage CAPTCHA Class.
 *
 * A class for creating and validating secure CAPTCHA images and audio.
 *
 * The class contains many options regarding appearance, security, storage of
 * captcha data and image/audio generation options.
 *
 * @package    Securimage
 * @subpackage classes
 * @author     Drew Phillips <drew@drew-phillips.com>
 *
 */
class Securimage
{
    // All of the public variables below are securimage options
    // They can be passed as an array to the Securimage constructor, set below,
    // or set from securimage_show.php and securimage_play.php

    /**
     * Constant for rendering captcha as a JPEG image
     * @var int
     */
    const SI_IMAGE_JPEG = 1;

    /**
     * Constant for rendering captcha as a PNG image (default)
     * @var int
     */

    const SI_IMAGE_PNG  = 2;
    /**
     * Constant for rendering captcha as a GIF image
     * @var int
     */
    const SI_IMAGE_GIF  = 3;

    /**
     * Constant for generating a normal alphanumeric captcha based on the
     * character set
     *
     * @see Securimage::$charset charset property
     * @var int
     */
    const SI_CAPTCHA_STRING     = 0;

    /**
     * Constant for generating a captcha consisting of a simple math problem
     *
     * @var int
     */
    const SI_CAPTCHA_MATHEMATIC = 1;

    /**
     * Constant for generating a word based captcha using 2 words from a list
     *
     * @var int
     */
    const SI_CAPTCHA_WORDS      = 2;

    /**
     * MySQL option identifier for database storage option
     *
     * @var string
     */
    const SI_DRIVER_MYSQL   = 'mysql';

    /**
     * PostgreSQL option identifier for database storage option
     *
     * @var string
     */
    const SI_DRIVER_PGSQL   = 'pgsql';

    /**
     * SQLite option identifier for database storage option
     *
     * @var string
     */
    const SI_DRIVER_SQLITE3 = 'sqlite';

    /**
     * getCaptchaHtml() display constant for HTML Captcha Image
     *
     * @var integer
     */
    const HTML_IMG   = 1;

    /**
     * getCaptchaHtml() display constant for HTML5 Audio code
     *
     * @var integer
     */
    const HTML_AUDIO = 2;

    /**
     * getCaptchaHtml() display constant for Captcha Input text box
     *
     * @var integer
     */
    const HTML_INPUT = 4;

    /**
     * getCaptchaHtml() display constant for Captcha Text HTML label
     *
     * @var integer
     */
    const HTML_INPUT_LABEL = 8;

    /**
     * getCaptchaHtml() display constant for HTML Refresh button
     *
     * @var integer
     */
    const HTML_ICON_REFRESH = 16;

    /**
     * getCaptchaHtml() display constant for all HTML elements (default)
     *
     * @var integer
     */
    const HTML_ALL = 0xffffffff;

    /*%*********************************************************************%*/
    // Properties

    /**
     * The width of the captcha image
     * @var int
     */
    public $image_width = 215;

    /**
     * The height of the captcha image
     * @var int
     */
    public $image_height = 80;

    /**
     * Font size is calculated by image height and this ratio.  Leave blank for
     * default ratio of 0.4.
     *
     * Valid range: 0.1 - 0.99.
     *
     * Depending on image_width, values > 0.6 are probably too large and
     * values < 0.3 are too small.
     *
     * @var float
     */
    public $font_ratio;

    /**
     * The type of the image, default = png
     *
     * @see Securimage::SI_IMAGE_PNG SI_IMAGE_PNG
     * @see Securimage::SI_IMAGE_JPEG SI_IMAGE_JPEG
     * @see Securimage::SI_IMAGE_GIF SI_IMAGE_GIF
     * @var int
     */
    public $image_type   = self::SI_IMAGE_PNG;

    /**
     * The background color of the captcha
     * @var Securimage_Color|string
     */
    public $image_bg_color = '#ffffff';

    /**
     * The color of the captcha text
     * @var Securimage_Color|string
     */
    public $text_color     = '#707070';

    /**
     * The color of the lines over the captcha
     * @var Securimage_Color|string
     */
    public $line_color     = '#707070';

    /**
     * The color of the noise that is drawn
     * @var Securimage_Color|string
     */
    public $noise_color    = '#707070';

    /**
     * Deprecated. How transparent to make the text.
     *
     * 0 = completely opaque, 100 = invisible
     *
     * @var int
     * @deprecated 4.0.0
     */
    public $text_transparency_percentage = 0;

    /**
     * Deprecated. Whether or not to draw the text transparently.
     *
     * true = use transparency, false = no transparency
     *
     * @var bool
     * @deprecated 4.0.0
     */
    public $use_transparent_text         = false;

    /**
     * The length of the captcha code
     * @var int
     */
    public $code_length    = 6;

    /**
     * Display random spaces in the captcha text on the image
     *
     * @var bool true to insert random spacing between groups of letters
     */
    public $use_random_spaces  = false;

    /**
     * Draw each character at an angle with random starting angle and increase/decrease per character
     * @var bool true to use random angles, false to draw each character normally
     */
    public $use_text_angles = false;

    /**
     * Instead of centering text vertically in the image, the baseline of each character is
     * randomized in such a way that the next character is drawn slightly higher or lower than
     * the previous in a step-like fashion.
     *
     * @var bool true to use random baselines, false to center text in image
     */
    public $use_random_baseline = false;

    /**
     * Draw a bounding box around some characters at random.  20% of the time, random boxes
     * may be drawn around 0 or more characters on the image.
     *
     * @var bool  true to randomly draw boxes around letters, false not to
     */
    public $use_random_boxes = false;

    /**
     * Whether the captcha should be case sensitive or not.
     *
     * Not recommended, use only for maximum protection.
     *
     * @var bool
     */
    public $case_sensitive = false;

    /**
     * The character set to use for generating the captcha code
     * @var string
     */
    public $charset        = 'abcdefghijkmnopqrstuvwxzyABCDEFGHJKLMNPQRSTUVWXZY0123456789';

    /**
     * How long in seconds a captcha remains valid, after this time it will be
     * considered incorrect.
     *
     * @var int
     */
    public $expiry_time    = 900;

    /**
     * true to use the wordlist file, false to generate random captcha codes
     * @var bool
     */
    public $use_wordlist   = false;

    /**
     * The level of distortion.
     *
     * 0.75 = normal, 1.0 = very high distortion
     *
     * @var double
     */
    public $perturbation = 0.85;

    /**
     * How many lines to draw over the captcha code to increase security
     * @var int
     */
    public $num_lines    = 5;

    /**
     * The level of noise (random dots) to place on the image, 0-10
     * @var int
     */
    public $noise_level  = 2;

    /**
     * The signature text to draw on the bottom corner of the image
     * @var string
     */
    public $image_signature = '';

    /**
     * The color of the signature text
     * @var Securimage_Color|string
     */
    public $signature_color = '#707070';

    /**
     * The path to the ttf font file to use for the signature text.
     * Defaults to $ttf_file (AHGBold.ttf)
     *
     * @see Securimage::$ttf_file
     * @var string
     */
    public $signature_font;

    /**
     * The type of captcha to create.
     *
     * Either alphanumeric based on *charset*, a simple math problem, or an
     * image consisting of 2 words from the word list.
     *
     * @see Securimage::SI_CAPTCHA_STRING SI_CAPTCHA_STRING
     * @see Securimage::SI_CAPTCHA_MATHEMATIC SI_CAPTCHA_MATHEMATIC
     * @see Securimage::SI_CAPTCHA_WORDS SI_CAPTCHA_WORDS
     * @see Securimage::$charset charset property
     * @see Securimage::$wordlist_file wordlist_file property
     * @var int
     */
    public $captcha_type  = self::SI_CAPTCHA_STRING; // or self::SI_CAPTCHA_MATHEMATIC, or self::SI_CAPTCHA_WORDS;

    /**
     * The TTF font file to use to draw the captcha code.
     *
     * Leave blank for default font AHGBold.ttf
     *
     * @var string
     */
    public $ttf_file;

    /**
     * The path to the wordlist file to use.
     *
     * Leave blank for default words/words.txt
     *
     * @var string
     */
    public $wordlist_file;

    /**
     * Character encoding of the wordlist file.
     * Requires PHP Multibyte String (mbstring) support.
     * Allows word list to contain characters other than US-ASCII (requires compatible TTF font).
     *
     * @var string The character encoding (e.g. UTF-8, UTF-7, EUC-JP, GB2312)
     * @see http://php.net/manual/en/mbstring.supported-encodings.php
     * @since 3.6.3
     */
    public $wordlist_file_encoding = null;

    /**
     * The directory to scan for background images, if set a random background
     * will be chosen from this folder
     *
     * @var string
     */
    public $background_directory;

    /**
     * The path to the audio files to be used for audio captchas.
     *
     * Can also be set in securimage_play.php
     *
     * Example:
     *
     *     $img->audio_path = '/home/yoursite/public_html/securimage/audio/en/';
     *
     * @var string
     */
    public $audio_path;

    /**
     * The path to the lame (mp3 encoder) binary on your system
     * Static so that Securimage::getCaptchaHtml() has access to this value.
     *
     * @since 3.6
     * @var string
     */
    public static $lame_binary_path = '/usr/bin/lame';

    /**
     * The path to the directory containing audio files that will be selected
     * randomly and mixed with the captcha audio.
     *
     * @var string
     */
    public $audio_noise_path;

    /**
     * Whether or not to mix background noise files into captcha audio
     *
     * Mixing random background audio with noise can help improve security of
     * audio captcha.
     *
     * Default: securimage/audio/noise
     *
     * @since 3.0.3
     * @see Securimage::$audio_noise_path audio_noise_path property
     * @var bool true = mix, false = no
     */
    public $audio_use_noise;

    /**
     * The method and threshold (or gain factor) used to normalize the mixing
     * with background noise.
     *
     * See http://www.voegler.eu/pub/audio/ for more information.
     *
     * Default: 0.6
     *
     * Valid:
     *     >= 1
     *     Normalize by multiplying by the threshold (boost - positive gain).
     *     A value of 1 in effect means no normalization (and results in clipping).
     *
     *     <= -1
     *     Normalize by dividing by the the absolute value of threshold (attenuate - negative gain).
     *     A factor of 2 (-2) is about 6dB reduction in volume.
     *
     *     [0, 1)  (open inverval - not including 1)
     *     The threshold above which amplitudes are comressed logarithmically.
     *     e.g. 0.6 to leave amplitudes up to 60% "as is" and compressabove.
     *
     *     (-1, 0) (open inverval - not including -1 and 0)
     *     The threshold above which amplitudes are comressed linearly.
     *     e.g. -0.6 to leave amplitudes up to 60% "as is" and compress above.
     *
     * @since 3.0.4
     * @var float
     */
    public $audio_mix_normalization = 0.8;

    /**
     * Whether or not to degrade audio by introducing random noise.
     *
     * Current research shows this may not increase the security of audible
     * captchas.
     *
     * Default: true
     *
     * @since 3.0.3
     * @var bool
     */
    public $degrade_audio;

    /**
     * Minimum delay to insert between captcha audio letters in milliseconds
     *
     * @since 3.0.3
     * @var float
     */
    public $audio_gap_min = 0;

    /**
     * Maximum delay to insert between captcha audio letters in milliseconds
     *
     * @since 3.0.3
     * @var float
     */
    public $audio_gap_max = 3000;

    /**
     * The file path for logging errors from audio (default __DIR__)
     *
     * @var string|null
     */
    public $log_path = null;

    /**
     * The name of the log file for logging audio errors
     *
     * @var string|null (defualt si_error.log)
     */
    public $log_file = null;

    /**
     * Captcha ID if using static captcha
     * @var string Unique captcha id
     */
    protected static $_captchaId = null;

    /**
     * The GD image resource of the captcha image
     *
     * @var resource
     */
    protected $im;

    /**
     * A temporary GD image resource of the captcha image for distortion
     *
     * @var resource
     */
    protected $tmpimg;

    /**
     * The background image GD resource
     * @var string
     */
    protected $bgimg;

    /**
     * Scale factor for magnification of distorted captcha image
     *
     * @var int
     */
    protected $iscale = 2;

    /**
     * Absolute path to securimage directory.
     *
     * This is calculated at runtime
     *
     * @var string
     */
    public $securimage_path = null;

    /**
     * The captcha challenge value.
     *
     * Either the case-sensitive/insensitive word captcha, or the solution to
     * the math captcha.
     *
     * @var string|bool Captcha challenge value
     */
    protected $code;

    /**
     * The display value of the captcha to draw on the image
     *
     * Either the word captcha or the math equation to present to the user
     *
     * @var string Captcha display value to draw on the image
     */
    protected $code_display;

    /**
     * Alternate text to draw as the captcha image text
     *
     * A value that can be passed to the constructor that can be used to
     * generate a captcha image with a given value.
     *
     * This value does not get stored in the session or database and is only
     * used when calling Securimage::show().
     *
     * If a display_value was passed to the constructor and the captcha image
     * is generated, the display_value will be used as the string to draw on
     * the captcha image.
     *
     * Used only if captcha codes are generated and managed by a 3rd party
     * app/library
     *
     * @var string Captcha code value to display on the image
     */
    public $display_value;

    /**
     * Captcha code supplied by user [set from Securimage::check()]
     *
     * @var string
     */
    protected $captcha_code;

    /**
     * Time (in seconds) that the captcha was solved in (correctly or incorrectly).
     *
     * This is from the time of code creation, to when validation was attempted.
     *
     * @var int
     */
    protected $_timeToSolve = 0;

    /**
     * Flag that can be specified telling securimage not to call exit after
     * generating a captcha image or audio file
     *
     * @var bool If true, script will not terminate; if false script will terminate (default)
     */
    protected $no_exit;

    /**
     * Flag indicating whether or not a PHP session should be started and used
     *
     * @var bool If true, no session will be started; if false, session will be started and used to store data (default)
     */
    protected $no_session;

    /**
     * List of storage adapters for persisting captcha data
     *
     * @var array Array of one or more storage adapters
     */
    protected $storage_adapters = array();

    /**
     * Flag indicating whether or not HTTP headers will be sent when outputting
     * captcha image/audio
     *
     * @var bool If true (default) headers will be sent, if false, no headers are sent
     */
    protected $send_headers;

    /**
     * The GD color for the background color
     *
     * @var int
     */
    protected $gdbgcolor;

    /**
     * The GD color for the text color
     *
     * @var int
     */
    protected $gdtextcolor;

    /**
     * The GD color for the line color
     *
     * @var int
     */
    protected $gdlinecolor;

    /**
     * The GD color for the signature text color
     *
     * @var int
     */
    protected $gdsignaturecolor;

    /**
     * Create a new securimage object, pass options to set in the constructor.
     *
     * The object can then be used to display a captcha, play an audible captcha, or validate a submission.
     *
     * @param array $options  Options to initialize the class.  May be any class property.
     *
     *     $options = array(
     *         'text_color' => new Securimage_Color('#013020'),
     *         'code_length' => 5,
     *         'num_lines' => 5,
     *         'noise_level' => 3,
     *         'font_file' => Securimage::getPath() . '/custom.ttf'
     *     );
     *
     *     $img = new Securimage($options);
     *
     */
    public function __construct($options = array())
    {
        $this->securimage_path = dirname(__FILE__);

        if (!is_array($options)) {
            trigger_error(
                    '$options passed to Securimage::__construct() must be an array.  ' .
                    gettype($options) . ' given',
                    E_USER_WARNING
            );
            $options = array();
        }

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        // check for and load settings from custom config file
        $config_file = null;

        if (file_exists(dirname(__FILE__) . '/config.inc.php')) {
            $config_file = dirname(__FILE__) . '/config.inc.php';
        }
        if (isset($options['config_file']) && file_exists($options['config_file'])) {
            $config_file = $options['config_file'];
        }

        if ($config_file) {
            $settings = include $config_file;

            if (is_array($settings)) {
                $options = array_merge($settings, $options);
            }
        }

        if (!class_exists(\Securimage\CaptchaObject::class)) {
            // not using Composer autoloader
            require_once __DIR__ . '/CaptchaObject.php';
            require_once __DIR__ . '/StorageAdapter/AdapterInterface.php';
        }

        if (is_array($options) && sizeof($options) > 0) {
            foreach($options as $prop => $val) {
                if ($prop == 'captchaId') {
                    Securimage::$_captchaId = $val;
                } else if ($prop == 'storage_adapters') {
                    if (!is_array($val)) {
                        $val = array($val);
                    }

                    foreach($val as $adapter) {
                        $this->addStorageAdapter($adapter);
                    }
                } else {
                    $this->$prop = $val;
                }
            }
        }

        $this->image_bg_color  = $this->initColor($this->image_bg_color,  '#ffffff');
        $this->text_color      = $this->initColor($this->text_color,      '#616161');
        $this->line_color      = $this->initColor($this->line_color,      '#616161');
        $this->noise_color     = $this->initColor($this->noise_color,     '#616161');
        $this->signature_color = $this->initColor($this->signature_color, '#616161');

        if (is_null($this->ttf_file)) {
            $this->ttf_file = $this->securimage_path . '/AHGBold.ttf';
        }

        $this->signature_font = $this->ttf_file;

        if (is_null($this->wordlist_file)) {
            $this->wordlist_file = $this->securimage_path . '/words/words.txt';
        }

        if (is_null($this->audio_path)) {
            $this->audio_path = $this->securimage_path . '/audio/en/';
        }

        if (is_null($this->audio_noise_path)) {
            $this->audio_noise_path = $this->securimage_path . '/audio/noise/';
        }

        if (is_null($this->audio_use_noise)) {
            $this->audio_use_noise = true;
        }

        if (is_null($this->degrade_audio)) {
            $this->degrade_audio = true;
        }

        if (is_null($this->code_length) || (int)$this->code_length < 1) {
            $this->code_length = 6;
        }

        if (is_null($this->perturbation) || !is_numeric($this->perturbation)) {
            $this->perturbation = 0.75;
        }

        if (is_null($this->no_exit)) {
            $this->no_exit = false;
        }

        if (is_null($this->no_session)) {
            $this->no_session = false;
        }

        if (is_null($this->send_headers)) {
            $this->send_headers = true;
        }

        // Set up storage adapters if none defined

        // PHP Session storage adapter
        if (sizeof($this->storage_adapters) < 1 && $this->no_session != true) {
            if (!class_exists('Securimage\StorageAdapter\Session')) {
                require_once __DIR__ . '/StorageAdapter/Session.php';
            }

            $sessionOpts = array(
                'session_name' => (isset($options['session_name']) ?
                                   $options['session_name'] :
                                   null),
            );

            $defaultAdapter = new \Securimage\StorageAdapter\Session($sessionOpts);

            $this->addStorageAdapter($defaultAdapter);
        }

        // PDO database storage adapter
        if (isset($options['use_database']) && $options['use_database']) {
            if (!isset($options['database_driver']) ||
                !in_array($options['database_driver'], array(
                    self::SI_DRIVER_MYSQL,
                    self::SI_DRIVER_PGSQL,
                    self::SI_DRIVER_SQLITE3)
                )
            ) {
                throw new \Exception(
                    "Invalid database driver supplied to Securimage. '" .
                    htmlspecialchars($options['database_driver']) . "' is not a valid driver"
                );
            }

            if (!class_exists('Securimage\StorageAdapter\PDO')) {
                require_once __DIR__ . '/StorageAdapter/PDO.php';
            }

            $dbOpts = array(
                'database_driver'  => $options['database_driver'],
                'database_table'   => $options['database_table'],
                'skip_table_check' => @$options['skip_table_check'],
                'expiry_time'      => $this->expiry_time,
            );

            if ($options['database_driver'] == self::SI_DRIVER_SQLITE3) {
                $dbOpts['database_file'] = (isset($options['database_file']) ?
                                           $options['database_file'] :
                                           $this->securimage_path . '/database/securimage.sq3');
            } else {
                $dbOpts['database_host'] = @$options['database_host'];
                $dbOpts['database_name'] = @$options['database_name'];
                $dbOpts['database_user'] = @$options['database_user'];
                $dbOpts['database_pass'] = @$options['database_pass'];
            }

            $dbAdapter = new \Securimage\StorageAdapter\PDO($dbOpts);

            $this->addStorageAdapter($dbAdapter);
        }

        if (isset($options['use_mysqli']) && $options['use_mysqli']) {
            if (!class_exists('Securimage\StorageAdapter\Mysqli')) {
                require_once __DIR__ . '/StorageAdapter/Mysqli.php';
            }

            $mysqliOpts = array(
                'database_host'    => $options['database_host'],
                'database_name'    => $options['database_name'],
                'database_user'    => $options['database_user'],
                'database_pass'    => $options['database_pass'],
                'expiry_time'      => $this->expiry_time,
                'database_table'   => $options['database_table'],
                'skip_table_check' => @$options['skip_table_check'],
                'mysqli_conn'      => @$options['mysqli_conn'],
            );

            $mysqliAdapter = new \Securimage\StorageAdapter\Mysqli($mysqliOpts);

            $this->addStorageAdapter($mysqliAdapter);
        }

        if (isset($options['use_memcached']) && $options['use_memcached']) {
            $mcopts = array(
                'memcached_servers' => $options['memcached_servers'],
                'persistent'        => @$options['memcached_persistent'],
                'expiration'        => $this->expiry_time,
            );

            if (!class_exists('Securimage\StorageAdapter\Memcached')) {
                require_once __DIR__ . '/StorageAdapter/Memcached.php';
            }

            $mcAdapter = new \Securimage\StorageAdapter\Memcached($mcopts);

            $this->addStorageAdapter($mcAdapter);
        }

        if (isset($options['use_redis']) && $options['use_redis']) {
            $redisOpts = array(
                'redis_server'  => $options['redis_server'],
                'expiration'    => $this->expiry_time,
                'redis_dbindex' => @$options['redis_dbindex'],
            );

            if (!class_exists('Securimage\StorageAdapter\Redis')) {
                require_once __DIR__ . '/StorageAdapter/Redis.php';
            }

            $redisAdapter = new \Securimage\StorageAdapter\Redis($redisOpts);

            $this->addStorageAdapter($redisAdapter);
        }

        if (Securimage::$_captchaId) {
            // populated by securimage_show.php and securimage_play.php
            $this->getCode();
        }
    }

    /**
     * Return the absolute path to the Securimage directory.
     *
     * @return string The path to the securimage base directory
     */
    public static function getPath()
    {
        return dirname(__FILE__);
    }

    public static function generateCaptchaId()
    {
        $data = sprintf('%s:%d-%s', $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], microtime(true));
        $hash = sha1(uniqid($data, true));

        return $hash;
    }

    /**
     * Add a storage adapter for persisting captcha data
     *
     * @param Securimage\StorageAdapter\AdapterInterface $adapter  The storage adapter to add
     * @return bool true if adapter added, false otherwise
     */
    public function addStorageAdapter($adapter)
    {
        if (! ($adapter instanceof Securimage\StorageAdapter\AdapterInterface) ) {
            trigger_error(
                "Adapter supplied to addStorageAdapter is not an instance " .
                "of Securimage AdapterInterface, cannot add",
                E_USER_WARNING
            );
            return false;
        }

        $this->storage_adapters[] = $adapter;

        return true;
    }

    /**
     * Generates a new challenge and serves a captcha image.
     *
     * Appropriate headers will be sent to the browser unless the *send_headers* option is false.
     *
     * @param string $background_image The absolute or relative path to the background image to use as the background of the captcha image.
     *
     *     $img = new Securimage();
     *     $img->code_length = 6;
     *     $img->num_lines   = 5;
     *     $img->noise_level = 5;
     *
     *     $img->show(); // sends the image and appropriate headers to browser
     *     exit;
     */
    public function show($background_image = '')
    {
        set_error_handler(array(&$this, 'errorHandler'));

        if(!empty($background_image) && is_readable($background_image)) {
            $this->bgimg = $background_image;
        }

        $this->doImage();
    }

    /**
     * Checks a given code against the correct value from the session and/or database.
     *
     * @param string $code      The captcha code to check
     * @param string $captchaId The ID of the captcha being checked
     * @param bool   $alwaysDelete True to delete captcha data after a failed guess.
     *   Useful if the form post results in a full page reload and the old captcha
     *   ID is never used again. Not so useful on Ajax forms.  (default false)
     *
     * <code>
     *     $code = $_POST['code'];
     *     $img  = new Securimage();
     *     if ($img->check($code) == true) {
     *         $captcha_valid = true;
     *     } else {
     *         $captcha_valid = false;
     *     }
     * </code>
     *
     * @return bool true if the given code was correct, false if not.
     */
    public function check($code, $captchaId = null, $alwaysDelete = false)
    {
        if (!is_string($code)) {
            trigger_error("The \$code parameter passed to Securimage::check() must be a string, " . gettype($code) . " given", E_USER_NOTICE);
            $code = '';
        }

        $this->code_entered = $code;
        $this->correct_code = false;
        $this->code         = null;

        if (empty($captchaId)) {
            // Attempt backwards compatibility with pre 4.0 for those who may
            // have forgotten to modify the call to Securimage::check()
            if (isset($_POST['captcha_id'])) {
                $captchaId = $_POST['captcha_id'];
            }
        }

        if (!empty($captchaId)) {
            if (sizeof($this->storage_adapters) < 1) {
                trigger_error(
                    "No storage adapters are enabled, captcha will not validate",
                    E_USER_WARNING
                );
            }

            foreach($this->storage_adapters as $adapter) {
                $info = $adapter->get($captchaId);

                if ($info) {
                    $this->code = $info;
                    break;
                }
            }

            $this->validate($captchaId);
        } else {
            trigger_error(
                'No captcha ID supplied to Securimage::check(). ' .
                'Captcha code cannot be validated',
                E_USER_WARNING
            );
        }

        if ($this->correct_code === false && $alwaysDelete) {
            // clear code from storage after use
            // if correct_code === true, it has already been deleted
            $this->deleteData($captchaId);
        }

        return $this->correct_code;
    }

    /**
     * Returns HTML code for displaying the captcha image, audio button, and form text input.
     *
     * Options can be specified to modify the output of the HTML.  Accepted options:
     *
     *     'securimage_path':
     *         Optional: The URI to where securimage is installed (e.g. /securimage)
     *     'show_image_url':
     *         Path to the securimage_show.php script (useful when integrating with a framework or moving outside the securimage directory)
     *         This will be passed as a urlencoded string to the <img> tag for outputting the captcha image
     *     'audio_play_url':
     *         Same as show_image_url, except this indicates the URL of the audio playback script
     *     'image_id':
     *          A string that sets the "id" attribute of the captcha image (default: captcha_image)
     *     'image_alt_text':
     *         The alt text of the captcha image (default: CAPTCHA Image)
     *     'show_audio_button':
     *         true/false  Whether or not to show the audio button (default: true)
     *     'show_refresh_button':
     *         true/false  Whether or not to show a button to refresh the image (default: true)
     *     'audio_icon_url':
     *         URL to the image used for showing the HTML5 audio icon
     *     'js_url':
     *         URL to the javascript file
     *     'icon_size':
     *         Size (for both height & width) in pixels of the audio and refresh buttons
     *     'show_text_input':
     *         true/false  Whether or not to show the text input for the captcha (default: true)
     *     'refresh_alt_text':
     *         Alt text for the refresh image (default: Refresh Image)
     *     'refresh_title_text':
     *         Title text for the refresh image link (default: Refresh Image)
     *     'input_id':
     *         A string that sets the "id" attribute of the captcha text input (default: captcha_code)
     *     'input_name':
     *         A string that sets the "name" attribute of the captcha text input (default: same as input_id)
     *     'input_text':
     *         A string that sets the text of the label for the captcha text input (default: Type the text:)
     *     'input_attributes':
     *         An array of additional HTML tag attributes to pass to the text input tag (default: empty)
     *     'image_attributes':
     *         An array of additional HTML tag attributes to pass to the captcha image tag (default: empty)
     *     'error_html':
     *         Optional HTML markup to be shown above the text input field
     *     'namespace':
     *         The optional captcha namespace to use for showing the image and playing back the audio. Namespaces are for using multiple captchas on the same page.
     *
     * @param array $options Array of options for modifying the HTML code.
     * @param int   $parts Securiage::HTML_* constant controlling what component of the captcha HTML to display
     *
     * @return string  The generated HTML code for displaying the captcha
     */
    public static function getCaptchaHtml($options = array(), $parts = Securimage::HTML_ALL)
    {
        static $javascript_init = false;

        if (!isset($options['securimage_path'])) {
            $docroot = (isset($_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT'] : substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['SCRIPT_NAME']));
            $docroot = realpath($docroot);
            $sipath  = dirname(__FILE__);
            $securimage_path = str_replace($docroot, '', $sipath);
        } else {
            $securimage_path = $options['securimage_path'];
        }

        $show_image_url    = (isset($options['show_image_url'])) ? $options['show_image_url'] : null;
        $image_id          = (isset($options['image_id'])) ? $options['image_id'] : 'captcha_image';
        $image_alt         = (isset($options['image_alt_text'])) ? $options['image_alt_text'] : 'CAPTCHA Image';
        $show_audio_btn    = (isset($options['show_audio_button'])) ? (bool)$options['show_audio_button'] : true;
        $show_refresh_btn  = (isset($options['show_refresh_button'])) ? (bool)$options['show_refresh_button'] : true;
        $refresh_icon_url  = (isset($options['refresh_icon_url'])) ? $options['refresh_icon_url'] : null;
        $audio_but_bg_col  = (isset($options['audio_button_bgcol'])) ? $options['audio_button_bgcol'] : '#ffffff';
        $audio_icon_url    = (isset($options['audio_icon_url'])) ? $options['audio_icon_url'] : null;
        $loading_icon_url  = (isset($options['loading_icon_url'])) ? $options['loading_icon_url'] : null;
        $icon_size         = (isset($options['icon_size'])) ? $options['icon_size'] : 32;
        $audio_play_url    = (isset($options['audio_play_url'])) ? $options['audio_play_url'] : null;
        $audio_swf_url     = (isset($options['audio_swf_url'])) ? $options['audio_swf_url'] : null;
        $js_url            = (isset($options['js_url'])) ? $options['js_url'] : null;
        $show_input        = (isset($options['show_text_input'])) ? (bool)$options['show_text_input'] : true;
        $refresh_alt       = (isset($options['refresh_alt_text'])) ? $options['refresh_alt_text'] : 'Refresh Image';
        $refresh_title     = (isset($options['refresh_title_text'])) ? $options['refresh_title_text'] : 'Refresh Image';
        $input_text        = (isset($options['input_text'])) ? $options['input_text'] : 'Type the text:';
        $input_id          = (isset($options['input_id'])) ? $options['input_id'] : 'captcha_code';
        $input_name        = (isset($options['input_name'])) ? $options['input_name'] :  $input_id;
        $input_required    = (isset($options['input_required'])) ? (bool)$options['input_required'] : true;
        $input_attrs       = (isset($options['input_attributes'])) ? $options['input_attributes'] : array();
        $image_attrs       = (isset($options['image_attributes'])) ? $options['image_attributes'] : array();
        $error_html        = (isset($options['error_html'])) ? $options['error_html'] : null;
        $captcha_id        = (isset($options['captcha_id'])) ? $options['captcha_id'] : self::generateCaptchaId();

        $rand              = md5(uniqid($_SERVER['REMOTE_PORT'], true));
        $securimage_path   = rtrim($securimage_path, '/\\');
        $securimage_path   = str_replace('\\', '/', $securimage_path);

        $image_attr = '';
        if (!is_array($image_attrs)) $image_attrs = array();
        if (!isset($image_attrs['style'])) $image_attrs['style'] = 'float: left; padding-right: 5px';
        $image_attrs['id']  = $image_id;

        $show_path = $securimage_path . '/securimage_show.php?';
        if ($show_image_url) {
            if (parse_url($show_image_url, PHP_URL_QUERY)) {
                $show_path = "{$show_image_url}&";
            } else {
                $show_path = "{$show_image_url}?";
            }
        }
        $show_path .= sprintf('id=%s&amp;', $captcha_id);
        $image_attrs['src'] = $show_path;

        $image_attrs['alt'] = $image_alt;

        foreach($image_attrs as $name => $val) {
            $image_attr .= sprintf('%s="%s" ', $name, htmlspecialchars($val));
        }

        $swf_path  = $securimage_path . '/securimage_play.swf';
        $play_path = $securimage_path . '/securimage_play.php?';
        $icon_path = $securimage_path . '/images/audio_icon.png';
        $load_path = $securimage_path . '/images/loading.png';
        $js_path   = $securimage_path . '/securimage.js';

        if (!empty($audio_icon_url)) {
            $icon_path = $audio_icon_url;
        }

        if (!empty($loading_icon_url)) {
            $load_path = $loading_icon_url;
        }

        if (!empty($audio_play_url)) {
            if (parse_url($audio_play_url, PHP_URL_QUERY)) {
                $play_path = "{$audio_play_url}&";
            } else {
                $play_path = "{$audio_play_url}?";
            }
        }
        $play_path .= sprintf('id=%s', $captcha_id);

        if (!empty($audio_swf_url)) {
            $swf_path = $audio_swf_url;
        }

        if (!empty($js_url)) {
            $js_path = $js_url;
        }

        $audio_obj = $image_id . '_audioObj';
        $html      = '';

        if ( ($parts & Securimage::HTML_IMG) > 0) {
            $html .= sprintf('<img %s/>', $image_attr);
        }

        if ( ($parts & Securimage::HTML_AUDIO) > 0 && $show_audio_btn) {
            // html5 audio
            $html .= sprintf('<div id="%s_audio_div">', $image_id) . "\n" .
                     sprintf('<audio id="%s_audio" preload="none" style="display: none">', $image_id) . "\n";

            // check for existence and executability of LAME binary
            // prefer mp3 over wav by sourcing it first, if available
            if (is_executable(Securimage::$lame_binary_path)) {
                $html .= sprintf('<source id="%s_source_mp3" src="%s&amp;format=mp3" type="audio/mpeg">', $image_id, $play_path) . "\n";
            }

            // output wav source
            $html .= sprintf('<source id="%s_source_wav" src="%s" type="audio/wav">', $image_id, $play_path) . "\n";

            // html5 audio close
            $html .= "</audio>\n</div>\n";

            // html5 audio controls
            $html .= sprintf('<div id="%s_audio_controls">', $image_id) . "\n" .
                     sprintf('<a tabindex="-1" class="captcha_play_button" href="%s" onclick="return false">',
                             $play_path
                     ) . "\n" .
                     sprintf('<img class="captcha_play_image" height="%d" width="%d" src="%s" alt="Play CAPTCHA Audio" style="border: 0px">', $icon_size, $icon_size, htmlspecialchars($icon_path)) . "\n" .
                     sprintf('<img class="captcha_loading_image rotating" height="%d" width="%d" src="%s" alt="Loading audio" style="display: none">', $icon_size, $icon_size, htmlspecialchars($load_path)) . "\n" .
                     "</a>\n<noscript>Enable Javascript for audio controls</noscript>\n" .
                     "</div>\n";

            // html5 javascript
            if (!$javascript_init) {
                $html .= sprintf('<script type="text/javascript" src="%s"></script>', $js_path) . "\n";
                $javascript_init = true;
            }
            $html .= '<script type="text/javascript">' .
                     "$audio_obj = new SecurimageAudio({ audioElement: '{$image_id}_audio', controlsElement: '{$image_id}_audio_controls' });" .
                     "</script>\n";
        }

        if ( ($parts & Securimage::HTML_ICON_REFRESH) > 0 && $show_refresh_btn) {
            $icon_path = $securimage_path . '/images/refresh.png';
            if ($refresh_icon_url) {
                $icon_path = $refresh_icon_url;
            }
            $img_tag = sprintf('<img height="%d" width="%d" src="%s" alt="%s" onclick="this.blur()" style="border: 0px; vertical-align: bottom">',
                               $icon_size, $icon_size, htmlspecialchars($icon_path), htmlspecialchars($refresh_alt));

            $html .= sprintf('<a tabindex="-1" style="border: 0" href="#" title="%s" onclick="securimageRefreshCaptcha(\'%s\', \'%s\'); this.blur(); return false">%s</a><br />',
                    htmlspecialchars($refresh_title),
                    $image_id,
                    ($audio_obj) ? $audio_obj : '',
                    $img_tag
            );
        }

        if ($parts == Securimage::HTML_ALL) {
            $html .= '<div style="clear: both"></div>';
        }

        if ( ($parts & Securimage::HTML_INPUT_LABEL) > 0 && $show_input) {
            $html .= sprintf('<label for="%s">%s</label> ',
                    htmlspecialchars($input_id),
                    htmlspecialchars($input_text));

            if (!empty($error_html)) {
                $html .= $error_html;
            }
        }

        if ( ($parts & Securimage::HTML_INPUT) > 0 && $show_input) {
            $input_attr = '';
            if (!is_array($input_attrs)) $input_attrs = array();
            $input_attrs['type'] = 'text';
            $input_attrs['name'] = $input_name;
            $input_attrs['id']   = $input_id;
            $input_attrs['autocomplete'] = 'off';
            if ($input_required) $input_attrs['required'] = $input_required;

            foreach($input_attrs as $name => $val) {
                $input_attr .= sprintf('%s="%s" ', $name, htmlspecialchars($val));
            }

            $html .= sprintf('<input %s>', $input_attr);
            $html .= sprintf('<input type="hidden" id="%s_captcha_id" name="captcha_id" value="%s">', $image_id, $captcha_id);
        }

        return $html;
    }

    /**
     * Get the time in seconds that it took to solve the captcha.
     *
     * @return int The time in seconds from when the code was created, to when it was solved
     */
    public function getTimeToSolve()
    {
        return $this->_timeToSolve;
    }

    /**
     * Set the namespace for the captcha being stored in the session or database.
     *
     * Namespaces are useful when multiple captchas need to be displayed on a single page.
     *
     * @param string $namespace  Namespace value, String consisting of characters "a-zA-Z0-9_-"
     */
    public function setNamespace($namespace)
    {
        $namespace = preg_replace('/[^a-z0-9-_]/i', '', $namespace);
        $namespace = substr($namespace, 0, 64);

        if (!empty($namespace)) {
            $this->namespace = $namespace;
        } else {
            $this->namespace = 'default';
        }
    }

    /**
     * Generate an audible captcha in WAV format and send it to the browser with appropriate headers.
     * Example:
     *
     *     $img = new Securimage();
     *     $img->outputAudioFile(); // outputs a wav file to the browser
     *     exit;
     *
     * @param string $format
     */
    public function outputAudioFile($format = null)
    {
        set_error_handler(array(&$this, 'errorHandler'));

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range   = true;
            $rangeId = (isset($_SERVER['HTTP_X_PLAYBACK_SESSION_ID'])) ?
                       'ID' . $_SERVER['HTTP_X_PLAYBACK_SESSION_ID']   :
                       'ID' . md5($_SERVER['REQUEST_URI']);
            $uniq    = $rangeId;
        } else {
            $uniq = md5(uniqid(microtime()));
        }

        try {
            if (!($audio = $this->getAudioData(Securimage::$_captchaId))) {
                // if previously generated audio not found for current captcha
                require_once dirname(__FILE__) . '/WavFile.php';
                $audio = $this->getAudibleCode();

                if (strtolower($format) == 'mp3') {
                    $audio = $this->wavToMp3($audio);
                }

                $this->saveAudioData($audio, Securimage::$_captchaId);
            }
        } catch (Exception $ex) {
            $log_file = rtrim($this->log_path, '/\\ ') . DIRECTORY_SEPARATOR . $this->log_file;

            if (($fp = fopen($log_file, 'a+')) !== false) {
                fwrite($fp, date('Y-m-d H:i:s') . ': Securimage audio error "' . $ex->getMessage() . '"' . "\n");
                fclose($fp);
            }

            $audio = $this->audioError();
        }

        if ($this->no_session != true) {
            // close session to make it available to other requests in the event
            // streaming the audio takes sevaral seconds or more
            session_write_close();
        }

        if ($this->canSendHeaders() || $this->send_headers == false) {
            if ($this->send_headers) {
                if ($format == 'mp3') {
                    $ext  = 'mp3';
                    $type = 'audio/mpeg';
                } else {
                    $ext  = 'wav';
                    $type = 'audio/wav';
                }

                header('Accept-Ranges: bytes');
                header("Content-Disposition: attachment; filename=\"securimage_audio-{$uniq}.{$ext}\"");
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Expires: Sun, 1 Jan 2000 12:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
                header('Content-type: ' . $type);
            }

            $this->rangeDownload($audio);
        } else {
            echo '<hr /><strong>'
                .'Failed to generate audio file, content has already been '
                .'output.<br />This is most likely due to misconfiguration or '
                .'a PHP error was sent to the browser.</strong>';
        }

        restore_error_handler();

        if (!$this->no_exit) exit;
    }

    /**
     * Output audio data with http range support.  Typically this shouldn't be
     * called directly unless being used with a custom implentation.  Use
     * Securimage::outputAudioFile instead.
     *
     * @param string $audio Raw wav or mp3 audio file content
     */
    public function rangeDownload($audio)
    {
        /* Congratulations Firefox Android/Linux/Windows for being the most
         * sensible browser of all when streaming HTML5 audio!
         *
         * Chrome on Android and iOS on iPad/iPhone both make extra HTTP requests
         * for the audio whether on WiFi or the mobile network resulting in
         * multiple downloads of the audio file and wasted bandwidth.
         *
         * If I'm doing something wrong in this code or anyone knows why, I'd
         * love to hear from you.
         */
        $audioLength = $size = strlen($audio);

        if (isset($_SERVER['HTTP_RANGE'])) {
            list( , $range) = explode('=', $_SERVER['HTTP_RANGE']); // bytes=byte-range-set
            $range = trim($range);

            if (strpos($range, ',') !== false) {
                // eventually, we should handle requests with multiple ranges
                // most likely these types of requests will never be sent
                header('HTTP/1.1 416 Range Not Satisfiable');
                echo "<h1>Range Not Satisfiable</h1>";
                exit;
            } else if (preg_match('/(\d+)-(\d+)/', $range, $match)) {
                // bytes n - m
                $range = array(intval($match[1]), intval($match[2]));
            } else if (preg_match('/(\d+)-$/', $range, $match)) {
                // bytes n - last byte of file
                $range = array(intval($match[1]), null);
            } else if (preg_match('/-(\d+)/', $range, $match)) {
                // final n bytes of file
                $range = array($size - intval($match[1]), $size - 1);
            }

            if ($range[1] === null) $range[1] = $size - 1;
            $length = $range[1] - $range[0] + 1;
            $audio = substr($audio, $range[0], $length);
            $audioLength = strlen($audio);

            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes {$range[0]}-{$range[1]}/{$size}");

            if ($range[0] < 0 ||$range[1] >= $size || $range[0] >= $size || $range[0] > $range[1]) {
                header('HTTP/1.1 416 Range Not Satisfiable');
                echo "<h1>Range Not Satisfiable</h1>";
                exit;
            }
        }

        header('Content-Length: ' . $audioLength);

        echo $audio;
    }

    /**
     * Return the code from the session or database (if configured).  If none exists or was found, an empty string is returned.
     *
     * @param mixed $deprecated Parameter no longer used as of Securimage 4.0
     * @param bool $returnExisting If true, and the class property *code* is set, it will be returned instead of getting the code from the session or database.
     * @return array|string Return is an array if $array = true, otherwise a string containing the code
     */
    public function getCode($deprecated = null, $returnExisting = false)
    {
        $code = array();

        foreach($this->storage_adapters as $adapter) {
            $info = $adapter->get(Securimage::$_captchaId);
            if ($info) {
                return $info;
            }
        }

        // TODO: generate new & return

        return '';

        if ($returnExisting && strlen($this->code) > 0) {
            if ($array) {
                return array(
                    'code'         => $this->code,
                    'display'      => $this->code_display,
                    'code_display' => $this->code_display,
                    'time'         => 0);
            } else {
                return $this->code;
            }
        }

        if ($this->no_session != true) {
            if (isset($_SESSION['securimage_code_value'][$this->namespace]) &&
                    trim($_SESSION['securimage_code_value'][$this->namespace]) != '') {
                if ($this->isCodeExpired(
                        $_SESSION['securimage_code_ctime'][$this->namespace]) == false) {
                    $code['code'] = $_SESSION['securimage_code_value'][$this->namespace];
                    $code['time'] = $_SESSION['securimage_code_ctime'][$this->namespace];
                    $code['display'] = $_SESSION['securimage_code_disp'] [$this->namespace];
                }
            }
        }

        if (empty($code) && $this->use_database) {
            // no code in session - may mean user has cookies turned off
            $this->openDatabase();
            $code = $this->getCodeFromDatabase();

            if (!empty($code)) {
                $code['display'] = $code['code_disp'];
                unset($code['code_disp']);
            }
        } else { /* no code stored in session or sqlite database, validation will fail */ }

        if ($array == true) {
            return $code;
        } elseif (!empty($code['code'])) {
            return $code['code'];
        }

        return '';
    }

    /**
     * The main image drawing routing, responsible for constructing the entire image and serving it
     */
    protected function doImage()
    {
        if($this->bgimg != '' || function_exists('imagecreatetruecolor')) {
            $imagecreate = 'imagecreatetruecolor';
        } else {
            $imagecreate = 'imagecreate';
        }

        $this->im = $imagecreate($this->image_width, $this->image_height);

        if (function_exists('imageantialias')) {
            imageantialias($this->im, true);
        }

        $this->allocateColors();

        if ($this->perturbation > 0) {
            $this->tmpimg = $imagecreate($this->image_width * $this->iscale, $this->image_height * $this->iscale);
            imagepalettecopy($this->tmpimg, $this->im);
        } else {
            $this->iscale = 1;
        }

        $this->setBackground();

        $code = '';

        if (is_string($this->display_value) && strlen($this->display_value)) {
            $code = $this->code_display = $this->display_value;
        } elseif (($info = $this->getCode())) {
            $code = $this->code_display = $info->code_display;
        }

        if ($code == '') {
            // if the code was not set using display_value or was not found in
            // the database, create a new code
            $this->createCode();
        }

        if ($this->noise_level > 0) {
            $this->drawNoise();
        }

        $this->drawWord();

        if ($this->perturbation > 0) {
            $this->distortedCopy();
        }

        if ($this->num_lines > 0) {
            $this->drawLines();
        }

        if (trim($this->image_signature) != '') {
            $this->addSignature();
        }

        $this->output();
    }

    /**
     * Allocate the colors to be used for the image
     */
    protected function allocateColors()
    {
        // allocate bg color first for imagecreate
        $this->gdbgcolor = imagecolorallocate($this->im,
                                              $this->image_bg_color->r,
                                              $this->image_bg_color->g,
                                              $this->image_bg_color->b);

        $this->gdtextcolor = imagecolorallocate($this->im,
                                                $this->text_color->r,
                                                $this->text_color->g,
                                                $this->text_color->b);
        $this->gdlinecolor = imagecolorallocate($this->im,
                                                $this->line_color->r,
                                                $this->line_color->g,
                                                $this->line_color->b);
        $this->gdnoisecolor = imagecolorallocate($this->im,
                                                 $this->noise_color->r,
                                                 $this->noise_color->g,
                                                 $this->noise_color->b);

        $this->gdsignaturecolor = imagecolorallocate($this->im,
                                                     $this->signature_color->r,
                                                     $this->signature_color->g,
                                                     $this->signature_color->b);

    }

    /**
     * The the background color, or background image to be used
     */
    protected function setBackground()
    {
        // set background color of image by drawing a rectangle since imagecreatetruecolor doesn't set a bg color
        imagefilledrectangle($this->im, 0, 0,
                             $this->image_width, $this->image_height,
                             $this->gdbgcolor);

        if ($this->perturbation > 0) {
            imagefilledrectangle($this->tmpimg, 0, 0,
                                 $this->image_width * $this->iscale, $this->image_height * $this->iscale,
                                 $this->gdbgcolor);
        }

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

    /**
     * Scan the directory for a background image to use
     * @return string|bool
     */
    protected function getBackgroundFromDirectory()
    {
        $images = array();

        if ( ($dh = opendir($this->background_directory)) !== false) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/(jpg|gif|png)$/i', $file)) $images[] = $file;
            }

            closedir($dh);

            if (sizeof($images) > 0) {
                return rtrim($this->background_directory, '/') . '/' . $images[mt_rand(0, sizeof($images)-1)];
            }
        }

        return false;
    }

    /**
     * This method generates a new captcha code.
     *
     * Generates a random captcha code based on *charset*, math problem, or captcha from the wordlist and saves the value to the session and/or database.
     */
    public function createCode()
    {
        $this->code = false;

        switch($this->captcha_type) {
            case self::SI_CAPTCHA_MATHEMATIC:
            {
                do {
                    $signs = array('+', '-', 'x');
                    $left  = mt_rand(1, 10);
                    $right = mt_rand(1, 5);
                    $sign  = $signs[mt_rand(0, 2)];

                    switch($sign) {
                        case 'x': $c = $left * $right; break;
                        case '-': $c = $left - $right; break;
                        default:  $c = $left + $right; break;
                    }
                } while ($c <= 0); // no negative #'s or 0

                $this->code         = "$c";
                $this->code_display = "$left $sign $right";
                break;
            }

            case self::SI_CAPTCHA_WORDS:
                $words = $this->readCodeFromFile(2);
                $this->code = implode(' ', $words);
                $this->code_display = $this->code;
                break;

            default:
            {
                if ($this->use_wordlist && is_readable($this->wordlist_file)) {
                    $this->code = $this->readCodeFromFile();
                }

                if ($this->code == false) {
                    $this->code = $this->generateCode($this->code_length);
                }

                $this->code_display = $this->code;
                $this->code         = ($this->case_sensitive) ? $this->code : strtolower($this->code);
            } // default
        }

        $this->saveData();
    }

    /**
     * Draws the captcha code on the image
     */
    protected function drawWord()
    {
        $ratio = ($this->font_ratio) ? $this->font_ratio : 0.4;

        if ((float)$ratio < 0.1 || (float)$ratio >= 1) {
            $ratio = 0.4;
        }

        if (!is_readable($this->ttfFile())) {
            // this will not catch missing fonts after the first!
            $this->perturbation = 0;
            imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);

            return ;
        }

        if ($this->perturbation > 0) {
            $width     = $this->image_width * $this->iscale;
            $height    = $this->image_height * $this->iscale;
            $font_size = $height * $ratio;
            $im        = &$this->tmpimg;
            $scale     = $this->iscale;
        } else {
            $height    = $this->image_height;
            $width     = $this->image_width;
            $font_size = $this->image_height * $ratio;
            $im        = &$this->im;
            $scale     = 1;
        }

        $captcha_text = $this->code_display;

        if ($this->use_random_spaces && $this->strpos($captcha_text, ' ') === false) {
            if (mt_rand(1, 100) % 5 > 0) { // ~20% chance no spacing added
                $index  = mt_rand(1, $this->strlen($captcha_text) -1);
                $spaces = mt_rand(1, 3);

                // in general, we want all characters drawn close together to
                // prevent easy segmentation by solvers, but this adds random
                // spacing between two groups to make character positioning
                // less normalized.

                $captcha_text = sprintf(
                    '%s%s%s',
                    $this->substr($captcha_text, 0, $index),
                    str_repeat(' ', $spaces),
                    $this->substr($captcha_text, $index)
                );
            }
        }

        $fonts    = array();  // list of fonts corresponding to each char $i
        $angles   = array();  // angles corresponding to each char $i
        $distance = array();  // distance from current char $i to previous char
        $dims     = array();  // dimensions of each individual char $i
        $txtWid   = 0;        // width of the entire text string, including spaces and distances

        // Character positioning and angle

        $angle0 = mt_rand(10, 20);
        $angleN = mt_rand(-20, 10);

        if ($this->use_text_angles == false) {
            $angle0 = $angleN = $step = 0;
        }

        if (mt_rand(0, 99) % 2 == 0) {
            $angle0 = -$angle0;
        }
        if (mt_rand(0, 99) % 2 == 1) {
            $angleN = -$angleN;
        }

        $step   = abs($angle0 - $angleN) / ($this->strlen($captcha_text) - 1);
        $step   = ($angle0 > $angleN) ? -$step : $step;
        $angle  = $angle0;

        for ($c = 0; $c < $this->strlen($captcha_text); ++$c) {
            $font     = $this->ttfFile(); // select random font from list for this character
            $fonts[]  = $font;
            $angles[] = $angle;  // the angle of this character
            $dist     = mt_rand(-2, 0) * $scale; // random distance between this and next character
            $distance[] = $dist;
            $char     = $this->substr($captcha_text, $c, 1); // the character to draw for this sequence

            $dim = $this->getCharacterDimensions($char, $font_size, $angle, $font); // calculate dimensions of this character

            $dim[0] += $dist;   // add the distance to the dimension (negative to bring them closer)
            $txtWid += $dim[0]; // increment width based on character width

            $dims[] = $dim;

            $angle += $step; // next angle

            if ($angle > 20) {
                $angle = 20;
                $step  = $step * -1;
            } elseif ($angle < -20) {
                $angle = -20;
                $step  = -1 * $step;
            }
        }

        $nextYPos = function($y, $i, $step) use ($height, $scale, $dims) {
            static $dir = 1;

            if ($y + $step + $dims[$i][2] + (10 * $scale) > $height) {
                $dir = 0;
            } elseif ($y - $step - $dims[$i][2] < $dims[$i][1] + $dims[$i][2] + (5 * $scale)) {
                $dir = 1;
            }

            if ($dir) {
                $y += $step;
            } else {
                $y -= $step;
            }

            return $y;
        };

        $cx = floor($width / 2 - ($txtWid / 2));
        $x  = mt_rand(5 * $scale, max($cx * 2 - (5 * $scale), 5 * $scale));

        if ($this->use_random_baseline) {
            $y = mt_rand($dims[0][1], $height - 10);
        } else {
            $y = ($height / 2 + $dims[0][1] / 2 - $dims[0][2]);
        }

        $st = $scale * mt_rand(5, 10);

        for ($c = 0; $c < $this->strlen($captcha_text); ++$c) {
            $font  = $fonts[$c];
            $char  = $this->substr($captcha_text, $c, 1);
            $angle = $angles[$c];
            $dim   = $dims[$c];

            if ($this->use_random_baseline) {
                $y = $nextYPos($y, $c, $st);
            }

            imagettftext(
                $im,
                $font_size,
                $angle,
                (int)$x,
                (int)$y,
                $this->gdtextcolor,
                $font,
                $char
            );

            if ($this->use_random_boxes && strlen(trim($char)) && mt_rand(1,100) % 5 == 0) {
                imagesetthickness($im, 3);
                imagerectangle($im, $x, $y - $dim[1] + $dim[2], $x + $dim[0], $y + $dim[2], $this->gdtextcolor);
            }

            if ($c == ' ') {
                $x += $dim[0];
            } else {
                $x += $dim[0] + $distance[$c];
            }
        }

        // DEBUG
        //$this->im = $im;
        //$this->output();
    }

    /**
     * Get the width and height (in points) of a character for a given font,
     * angle, and size.
     *
     * @param string $char The character to get dimensions for
     * @param number $size The font size, in points
     * @param number $angle The angle of the text
     * @return number[] A 3-element array representing the width, height and baseline of the text
     */
    protected function getCharacterDimensions($char, $size, $angle, $font)
    {
        $box = imagettfbbox($size, $angle, $font, $char);

        return array($box[2] - $box[0], max($box[1] - $box[7], $box[5] - $box[3]), $box[1]);
    }

    /**
     * Copies the captcha image to the final image with distortion applied
     */
    protected function distortedCopy()
    {
        $numpoles = 3;       // distortion factor
        $px       = array(); // x coordinates of poles
        $py       = array(); // y coordinates of poles
        $rad      = array(); // radius of distortion from pole
        $amp      = array(); // amplitude
        $x        = ($this->image_width / 4); // lowest x coordinate of a pole
        $maxX     = $this->image_width - $x;  // maximum x coordinate of a pole
        $dx       = mt_rand($x / 10, $x);     // horizontal distance between poles
        $y        = mt_rand(20, $this->image_height - 20);  // random y coord
        $dy       = mt_rand(20, $this->image_height * 0.7); // y distance
        $minY     = 20;                                     // minimum y coordinate
        $maxY     = $this->image_height - 20;               // maximum y cooddinate

        // make array of poles AKA attractor points
        for ($i = 0; $i < $numpoles; ++ $i) {
            $px[$i]  = ($x + ($dx * $i)) % $maxX;
            $py[$i]  = ($y + ($dy * $i)) % $maxY + $minY;
            $rad[$i] = mt_rand($this->image_height * 0.4, $this->image_height * 0.8);
            $tmp     = ((- $this->frand()) * 0.15) - .15;
            $amp[$i] = $this->perturbation * $tmp;
        }

        $bgCol   = imagecolorat($this->tmpimg, 0, 0);
        $width2  = $this->iscale * $this->image_width;
        $height2 = $this->iscale * $this->image_height;
        imagepalettecopy($this->im, $this->tmpimg); // copy palette to final image so text colors come across

        // loop over $img pixels, take pixels from $tmpimg with distortion field
        for ($ix = 0; $ix < $this->image_width; ++ $ix) {
            for ($iy = 0; $iy < $this->image_height; ++ $iy) {
                $x = $ix;
                $y = $iy;
                for ($i = 0; $i < $numpoles; ++ $i) {
                    $dx = $ix - $px[$i];
                    $dy = $iy - $py[$i];
                    if ($dx == 0 && $dy == 0) {
                        continue;
                    }
                    $r = sqrt($dx * $dx + $dy * $dy);
                    if ($r > $rad[$i]) {
                        continue;
                    }
                    $rscale = $amp[$i] * sin(3.14 * $r / $rad[$i]);
                    $x += $dx * $rscale;
                    $y += $dy * $rscale;
                }
                $c = $bgCol;
                $x *= $this->iscale;
                $y *= $this->iscale;
                if ($x >= 0 && $x < $width2 && $y >= 0 && $y < $height2) {
                    $c = imagecolorat($this->tmpimg, $x, $y);
                }
                if ($c != $bgCol) { // only copy pixels of letters to preserve any background image
                    imagesetpixel($this->im, $ix, $iy, $c);
                }
            }
        }
    }

    /**
     * Draws distorted lines on the image
     */
    protected function drawLines()
    {
        for ($line = 0; $line < $this->num_lines; ++ $line) {
            $x = $this->image_width * (1 + $line) / ($this->num_lines + 1);
            $x += (0.5 - $this->frand()) * $this->image_width / $this->num_lines;
            $y = mt_rand($this->image_height * 0.1, $this->image_height * 0.9);

            $theta = ($this->frand() - 0.5) * M_PI * 0.33;
            $w = $this->image_width;
            $len = mt_rand($w * 0.4, $w * 0.7);
            $lwid = mt_rand(0, 2);

            $k = $this->frand() * 0.6 + 0.2;
            $k = $k * $k * 0.5;
            $phi = $this->frand() * 6.28;
            $step = 0.5;
            $dx = $step * cos($theta);
            $dy = $step * sin($theta);
            $n = $len / $step;
            $amp = 1.5 * $this->frand() / ($k + 5.0 / $len);
            $x0 = $x - 0.5 * $len * cos($theta);
            $y0 = $y - 0.5 * $len * sin($theta);

            $ldx = round(- $dy * $lwid);
            $ldy = round($dx * $lwid);

            for ($i = 0; $i < $n; ++ $i) {
                $x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
                $y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
                imagefilledrectangle($this->im, $x, $y, $x + $lwid, $y + $lwid, $this->gdlinecolor);
            }
        }
    }

    /**
     * Draws random noise on the image
     */
    protected function drawNoise()
    {
        if ($this->noise_level > 10) {
            $noise_level = 10;
        } else {
            $noise_level = $this->noise_level;
        }

        $t0 = microtime(true);
        $noise_level *= M_LOG2E;

        for ($x = 1; $x < $this->image_width; $x += 20) {
            for ($y = 1; $y < $this->image_height; $y += 20) {
                for ($i = 0; $i < $noise_level; ++$i) {
                    $x1 = mt_rand($x, $x + 20);
                    $y1 = mt_rand($y, $y + 20);
                    $size = mt_rand(1, 3);

                    if ($x1 - $size <= 0 && $y1 - $size <= 0) continue; // dont cover 0,0 since it is used by imagedistortedcopy
                    imagefilledarc($this->im, $x1, $y1, $size, $size, 0, mt_rand(180,360), $this->gdlinecolor, IMG_ARC_PIE);
                }
            }
        }

        $t = microtime(true) - $t0;

        /*
        // DEBUG
        imagestring($this->im, 5, 25, 30, "$t", $this->gdnoisecolor);
        header('content-type: image/png');
        imagepng($this->im);
        exit;
        */
    }

    /**
    * Print signature text on image
    */
    protected function addSignature()
    {
        $bbox = imagettfbbox(10, 0, $this->signature_font, $this->image_signature);
        $textlen = $bbox[2] - $bbox[0];
        $x = $this->image_width - $textlen - 5;
        $y = $this->image_height - 3;

        imagettftext($this->im, 10, 0, $x, $y, $this->gdsignaturecolor, $this->signature_font, $this->image_signature);
    }

    /**
     * Sends the appropriate image and cache headers and outputs image to the browser
     */
    protected function output()
    {
        if ($this->canSendHeaders() || $this->send_headers == false) {
            if ($this->send_headers) {
                // only send the content-type headers if no headers have been output
                // this will ease debugging on misconfigured servers where warnings
                // may have been output which break the image and prevent easily viewing
                // source to see the error.
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
            }

            switch ($this->image_type) {
                case self::SI_IMAGE_JPEG:
                    if ($this->send_headers) header("Content-Type: image/jpeg");
                    imagejpeg($this->im, null, 90);
                    break;
                case self::SI_IMAGE_GIF:
                    if ($this->send_headers) header("Content-Type: image/gif");
                    imagegif($this->im);
                    break;
                default:
                    if ($this->send_headers) header("Content-Type: image/png");
                    imagepng($this->im);
                    break;
            }
        } else {
            echo '<hr /><strong>'
                .'Failed to generate captcha image, content has already been '
                .'output.<br />This is most likely due to misconfiguration or '
                .'a PHP error was sent to the browser.</strong>';
        }

        imagedestroy($this->im);
        restore_error_handler();

        if (!$this->no_exit) exit;
    }

    /**
     * Generates an audio captcha in WAV format
     *
     * @return string The audio representation of the captcha in Wav format
     */
    protected function getAudibleCode()
    {
        $letters = array();
        $code    = $this->getCode(null, true);

        if (empty($code) || empty($code->code)) {
            if ($this->strlen($this->display_value) > 0) {
                $code = new \Securimage\CaptchaObject;
                $code->code         = $this->display_value;
                $code->code_display = $this->display_value;
            } else {
                $this->createCode();
                $code = $this->getCode();
            }
        }

        if (empty($code)) {
            $error = 'Failed to get audible code (are database settings correct?).  Check the error log for details';
            trigger_error($error, E_USER_WARNING);
            throw new \Exception($error);
        }

        if (preg_match('/(\d+) (\+|-|x) (\d+)/i', $code->code_display, $eq)) {
            $math = true;

            $left  = $eq[1];
            $sign  = str_replace(array('+', '-', 'x'), array('plus', 'minus', 'times'), $eq[2]);
            $right = $eq[3];

            $letters = array($left, $sign, $right);
        } else {
            $math = false;

            $length = $this->strlen($code->code_display);

            for($i = 0; $i < $length; ++$i) {
                $letter    = $this->substr($code->code_display, $i, 1);
                $letters[] = $letter;
            }
        }

        try {
            return $this->generateWAV($letters);
        } catch(\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Gets a captcha code from a file containing a list of words.
     *
     * Seek to a random offset in the file and reads a block of data and returns a line from the file.
     *
     * @param int $numWords Number of words (lines) to read from the file
     * @return string|array|bool  Returns a string if only one word is to be read, or an array of words
     */
    protected function readCodeFromFile($numWords = 1)
    {
        $strtolower_func = 'strtolower';
        $mb_support      = false;

        if (!empty($this->wordlist_file_encoding)) {
            if (!extension_loaded('mbstring')) {
                trigger_error("wordlist_file_encoding option set, but PHP does not have mbstring support", E_USER_WARNING);
                return false;
            }

            // emits PHP warning if not supported
            $mb_support = mb_internal_encoding($this->wordlist_file_encoding);

            if (!$mb_support) {
                return false;
            }

            $strtolower_func = 'mb_strtolower';
        }

        $fp = fopen($this->wordlist_file, 'rb');
        if (!$fp) return false;

        $fsize = filesize($this->wordlist_file);
        if ($fsize < 512) return false; // too small of a list to be effective

        if ((int)$numWords < 1 || (int)$numWords > 5) $numWords = 1;

        $words = array();
        $w     = 0;
        $tries = 0;
        do {
            fseek($fp, mt_rand(0, $fsize - 512), SEEK_SET); // seek to a random position of file from 0 to filesize - 512 bytes
            $data = fread($fp, 512); // read a chunk from our random position

            if ( ($p = $this->strpos($data, "\n")) !== false) {
                $data = $this->substr($data, $p + 1);
            }

            if ( ($start = @$this->strpos($data, "\n", mt_rand(0, $this->strlen($data) / 2))) === false) {
                continue;
            }

            $data = $this->substr($data,$start + 1);
            $word = '';

            for ($i = 0; $i < $this->strlen($data); ++$i) {
                $c = $this->substr($data, $i, 1);
                if ($c == "\r") continue;
                if ($c == "\n") break;

                $word .= $c;
            }

            $word = trim($word);

            if (empty($word)) {
                continue;
            }

            $word = $strtolower_func($word);

            if ($mb_support) {
                // convert to UTF-8 for imagettftext
                $word = mb_convert_encoding($word, 'UTF-8', $this->wordlist_file_encoding);
            }

            $words[] = $word;
        } while (++$w < $numWords && $tries++ < $numWords * 2);

        fclose($fp);

        if (count($words) < $numWords) {
            return false;
        }

        if ($numWords == 1) {
            return $words[0];
        } else {
            return $words;
        }
    }

    /**
     * Generates a random captcha code from the set character set
     *
     * @see Securimage::$charset  Charset option
     * @return string A randomly generated CAPTCHA code
     */
    protected function generateCode()
    {
        $code = '';

        for($i = 1, $cslen = $this->strlen($this->charset); $i <= $this->code_length; ++$i) {
            $code .= $this->substr($this->charset, mt_rand(0, $cslen - 1), 1);
        }

        return $code;
    }

    /**
     * Validate a code supplied by the user
     *
     * Checks the entered code against the value stored in the session and/or database (if configured).  Handles case sensitivity.
     * Also removes the code from session/database if the code was entered correctly to prevent re-use attack.
     *
     * This function does not return a value.
     *
     * @see Securimage::$correct_code 'correct_code' property
     */
    protected function validate($captchaId)
    {
        $code = null;

        if (!$this->code) {
            $code = $this->getCode();
            // returns stored code, or an empty string if no stored code was found
            // checks the session and database if enabled

            if ($code && false == $this->isCodeExpired($code->creationTime)) {
                $code = null;

                $this->deleteData($captchaId);
            }
        } else {
            $code = $this->code;
        }

        if ($code) {
            $ctime = $code->creationTime;
            $code  = $code->code;

            $this->_timeToSolve = time() - $ctime;
        } else {
            $code = '';
        }

        if ($this->case_sensitive == false && preg_match('/[A-Z]/', $code)) {
            // case sensitive was set from securimage_show.php but not in class
            // the code saved in the session has capitals so set case sensitive to true
            $this->case_sensitive = true;
        }

        $code_entered = trim( (($this->case_sensitive) ? $this->code_entered
                                                       : strtolower($this->code_entered))
                        );
        $this->correct_code = false;

        if ($code != '') {
            if (strpos($code, ' ') !== false) {
                // for multi word captchas, remove more than once space from input
                $code_entered = preg_replace('/\s+/', ' ', $code_entered);
                $code_entered = strtolower($code_entered);
            }

            if ((string)$code === (string)$code_entered) {
                $this->correct_code = true;

                $this->deleteData($captchaId);
            }
        }
    }

    /**
     * Save CAPTCHA data to session and database (if configured)
     */
    protected function saveData()
    {
        $captchaObj = new Securimage\CaptchaObject;
        $captchaObj->captchaId    = Securimage::$_captchaId;
        $captchaObj->code         = $this->code;
        $captchaObj->code_display = $this->code_display;
        $captchaObj->creationTime = time();

        /** @var Securimage\StorageAdapter\AdapterInterface $adapter */
        foreach($this->storage_adapters as $adapter) {
            $adapter->store($captchaObj->captchaId, $captchaObj);
        }

        /*
        if ($this->no_session != true) {
            if (isset($_SESSION['securimage_code_value']) && is_scalar($_SESSION['securimage_code_value'])) {
                // fix for migration from v2 - v3
                unset($_SESSION['securimage_code_value']);
                unset($_SESSION['securimage_code_ctime']);
            }

            $_SESSION['securimage_code_disp'] [$this->namespace] = $this->code_display;
            $_SESSION['securimage_code_value'][$this->namespace] = $this->code;
            $_SESSION['securimage_code_ctime'][$this->namespace] = time();
            $_SESSION['securimage_code_audio'][$this->namespace] = null; // clear previous audio, if set
        }

        if ($this->use_database) {
            $this->saveCodeToDatabase();
        }
        */
    }

    /**
     * Delete a saved captcha from all configured storage adapters
     *
     * @param string $captchaId  The captcha ID to delete from storage
     */
    protected function deleteData($captchaId)
    {
        foreach($this->storage_adapters as $adapter) {
            $adapter->delete($captchaId);
        }
    }

    /**
     * Save audio data to session and/or the configured database
     *
     * @param string $data The CAPTCHA audio data
     */
    protected function saveAudioData($data, $captchaId)
    {
        $return = false;

        foreach($this->storage_adapters as $adapter) {
            if ($adapter->storeAudioData($captchaId, $data)) {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Gets audio file contents from the session or database
     *
     * @return string|boolean Audio contents on success, or false if no audio found in session or DB
     */
    protected function getAudioData($captchaId)
    {
        foreach($this->storage_adapters as $adapter) {
            $info = $adapter->get($captchaId);
            if ($info && !empty($info->captchaImageAudio)) {
                return $info->captchaImageAudio;
            }
        }

        return false;
    }

    /**
     * Checks to see if the captcha code has expired and can no longer be used.
     *
     * @see Securimage::$expiry_time expiry_time
     * @param int $creation_time  The Unix timestamp of when the captcha code was created
     * @return bool true if the code is expired, false if it is still valid
     */
    protected function isCodeExpired($creation_time)
    {
        $expired = true;

        if (!is_numeric($this->expiry_time) || $this->expiry_time < 1) {
            $expired = false;
        } else if (time() - $creation_time < $this->expiry_time) {
            $expired = false;
        }

        return $expired;
    }

    /**
     * Generate a wav file given the $letters in the code
     *
     * @param array $letters  The letters making up the captcha
     * @return string The audio content in WAV format
     */
    protected function generateWAV($letters)
    {
        $wavCaptcha = new WavFile();
        $first      = true;     // reading first wav file

        foreach ($letters as $letter) {
            $letter = strtoupper($letter);

            try {
                $letter_file = realpath($this->audio_path) . DIRECTORY_SEPARATOR . $letter . '.wav';
                $l = new WavFile($letter_file);

                if ($first) {
                    // set sample rate, bits/sample, and # of channels for file based on first letter
                    $wavCaptcha->setSampleRate($l->getSampleRate())
                               ->setBitsPerSample($l->getBitsPerSample())
                               ->setNumChannels($l->getNumChannels());
                    $first = false;
                }

                // append letter to the captcha audio
                $wavCaptcha->appendWav($l);

                // random length of silence between $audio_gap_min and $audio_gap_max
                if ($this->audio_gap_max > 0 && $this->audio_gap_max > $this->audio_gap_min) {
                    $wavCaptcha->insertSilence( mt_rand($this->audio_gap_min, $this->audio_gap_max) / 1000.0 );
                }
            } catch (Exception $ex) {
                // failed to open file, or the wav file is broken or not supported
                // 2 wav files were not compatible, different # channels, bits/sample, or sample rate
                throw new Exception("Error generating audio captcha on letter '$letter': " . $ex->getMessage());
            }
        }

        /********* Set up audio filters *****************************/
        $filters = array();

        if ($this->audio_use_noise == true) {
            // use background audio - find random file
            $wavNoise   = false;
            $randOffset = 0;

            if ( ($noiseFile = $this->getRandomNoiseFile()) !== false) {
                try {
                    $wavNoise = new WavFile($noiseFile, false);
                } catch(Exception $ex) {
                    throw $ex;
                }

                // start at a random offset from the beginning of the wavfile
                // in order to add more randomness

                $randOffset = 0;

                if ($wavNoise->getNumBlocks() > 2 * $wavCaptcha->getNumBlocks()) {
                    $randBlock = mt_rand(0, $wavNoise->getNumBlocks() - $wavCaptcha->getNumBlocks());
                    $wavNoise->readWavData($randBlock * $wavNoise->getBlockAlign(), $wavCaptcha->getNumBlocks() * $wavNoise->getBlockAlign());
                } else {
                    $wavNoise->readWavData();
                    $randOffset = mt_rand(0, $wavNoise->getNumBlocks() - 1);
                }
            }

            if ($wavNoise !== false) {
                $mixOpts = array('wav'  => $wavNoise,
                                 'loop' => true,
                                 'blockOffset' => $randOffset);

                $filters[WavFile::FILTER_MIX]       = $mixOpts;
                $filters[WavFile::FILTER_NORMALIZE] = $this->audio_mix_normalization;
            }
        }

        if ($this->degrade_audio == true) {
            // add random noise.
            // any noise level below 95% is intensely distorted and not pleasant to the ear
            $filters[WavFile::FILTER_DEGRADE] = mt_rand(95, 98) / 100.0;
        }

        if (!empty($filters)) {
            $wavCaptcha->filter($filters);  // apply filters to captcha audio
        }

        return $wavCaptcha->__toString();
    }

    /**
     * Gets and returns the path to a random noise file from the audio noise directory.
     *
     * @return bool|string  false if a file could not be found, or a string containing the path to the file.
     */
    public function getRandomNoiseFile()
    {
        $return = false;

        if ( ($dh = opendir($this->audio_noise_path)) !== false ) {
            $list = array();

            while ( ($file = readdir($dh)) !== false ) {
                if ($file == '.' || $file == '..') continue;
                if (strtolower(substr($file, -4)) != '.wav') continue;

                $list[] = $file;
            }

            closedir($dh);

            if (sizeof($list) > 0) {
                $file   = $list[array_rand($list, 1)];
                $return = $this->audio_noise_path . DIRECTORY_SEPARATOR . $file;

                if (!is_readable($return)) $return = false;
            }
        }

        return $return;
    }

    /**
     * Convert WAV data to MP3 using the Lame MP3 encoder binary
     *
     * @param string $data  Contents of the WAV file to convert
     * @return string       MP3 file data
     */
    protected function wavToMp3($data)
    {
        if (!file_exists(self::$lame_binary_path) || !is_executable(self::$lame_binary_path)) {
            throw new Exception('Lame binary "' . $this->lame_binary_path . '" does not exist or is not executable');
        }

        // size of wav data input
        $size = strlen($data);

        // file descriptors for reading and writing to the Lame process
        $descriptors = array(
                0 => array('pipe', 'r'), // stdin
                1 => array('pipe', 'w'), // stdout
                2 => array('pipe', 'a'), // stderr
        );

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // workaround for Windows conversion
            // writing to STDIN seems to hang indefinitely after writing approximately 0xC400 bytes
            $wavinput = tempnam(sys_get_temp_dir(), 'wav');
            if (!$wavinput) {
                throw new Exception('Failed to create temporary file for WAV to MP3 conversion');
            }
            file_put_contents($wavinput, $data);
            $size = 0;
        } else {
            $wavinput = '-'; // stdin
        }

        // Mono, variable bit rate, 32 kHz sampling rate, read WAV from stdin, write MP3 to stdout
        $cmd  = sprintf("%s -m m -v -b 32 %s -", self::$lame_binary_path, $wavinput);
        $proc = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($proc)) {
            throw new Exception('Failed to open process for MP3 encoding');
        }

        stream_set_blocking($pipes[0], 0); // set stdin to be non-blocking

        for ($written = 0; $written < $size; $written += $len) {
            // write to stdin until all WAV data is written
            $len = fwrite($pipes[0], substr($data, $written, 0x20000));

            if ($len === 0) {
                // fwrite wrote no data, make sure process is still alive, otherwise wait for it to process
                $status = proc_get_status($proc);
                if ($status['running'] === false) break;
                usleep(25000);
            } else if ($written < $size) {
                // couldn't write all data, small pause and try again
                usleep(10000);
            } else if ($len === false) {
                // fwrite failed, should not happen
                break;
            }
        }

        fclose($pipes[0]);

        $data = stream_get_contents($pipes[1]);
        $err  = trim(stream_get_contents($pipes[2]));

        fclose($pipes[1]);
        fclose($pipes[2]);

        $return = proc_close($proc);

        if ($wavinput != '-') unlink($wavinput); // delete temp file on Windows

        if ($return !== 0) {
            throw new Exception("Failed to convert WAV to MP3.  Shell returned ({$return}): {$err}");
        } else if ($written < $size) {
            throw new Exception('Failed to convert WAV to MP3.  Failed to write all data to encoder');
        }

        return $data;
    }

    /**
     * Return a wav file saying there was an error generating file
     *
     * @return string The binary audio contents
     */
    protected function audioError()
    {
        return @file_get_contents(dirname(__FILE__) . '/audio/en/error.wav');
    }

    /**
     * Checks to see if headers can be sent and if any error has been output
     * to the browser
     *
     * @return bool true if it is safe to send headers, false if not
     */
    protected function canSendHeaders()
    {
        if (headers_sent()) {
            // output has been flushed and headers have already been sent
            return false;
        } else if (strlen((string)ob_get_contents()) > 0) {
            // headers haven't been sent, but there is data in the buffer that will break image and audio data
            return false;
        }

        return true;
    }

    /**
     * Return a random float between 0 and 0.9999
     *
     * @return float Random float between 0 and 0.9999
     */
    protected function frand()
    {
        return 0.0001 * mt_rand(0,9999);
    }

    protected function strlen($string)
    {
        $strlen= 'strlen';

        if (function_exists('mb_strlen')) {
            $strlen= 'mb_strlen';
        }

        return $strlen($string);
    }

    protected function substr($string, $start, $length = null)
    {
        $substr= 'substr';

        if (function_exists('mb_substr')) {
            $substr = 'mb_substr';
        }

        if ($length === null) {
            return $substr($string, $start);
        } else {
            return $substr($string, $start, $length);
        }
    }

    protected function strpos($haystack, $needle, $offset = 0)
    {
        $strpos = 'strpos';

        if (function_exists('mb_strpos')) {
            $strpos = 'mb_strpos';
        }

        return $strpos($haystack, $needle, $offset);
    }

    /**
     * Convert an html color code to a Securimage_Color
     * @param string $color
     * @param Securimage_Color|string $default The defalt color to use if $color is invalid
     */
    protected function initColor($color, $default)
    {
        if ($color == null) {
            return new Securimage_Color($default);
        } else if (is_string($color)) {
            try {
                return new Securimage_Color($color);
            } catch(Exception $e) {
                return new Securimage_Color($default);
            }
        } else if (is_array($color) && sizeof($color) == 3) {
            return new Securimage_Color($color[0], $color[1], $color[2]);
        } else {
            return new Securimage_Color($default);
        }
    }

    protected function ttfFile()
    {
        if (is_string($this->ttf_file)) {
            return $this->ttf_file;
        } elseif (is_array($this->ttf_file)) {
            return $this->ttf_file[mt_rand(0, sizeof($this->ttf_file)-1)];
        } else {
            throw new \Exception('ttf_file is not a string or array');
        }
    }

    /**
     * The error handling function used when outputting captcha image or audio.
     *
     * This error handler helps determine if any errors raised would
     * prevent captcha image or audio from displaying.  If they have
     * no effect on the output buffer or headers, true is returned so
     * the script can continue processing.
     *
     * See https://github.com/dapphp/securimage/issues/15
     *
     * @param int $errno  PHP error number
     * @param string $errstr  String description of the error
     * @param string $errfile  File error occurred in
     * @param int $errline  Line the error occurred on in file
     * @param array $errcontext  Additional context information
     * @return boolean true if the error was handled, false if PHP should handle the error
     */
    public function errorHandler($errno, $errstr, $errfile = '', $errline = 0, $errcontext = array())
    {
        // get the current error reporting level
        $level = error_reporting();

        // if error was supressed or $errno not set in current error level
        if ($level == 0 || ($level & $errno) == 0) {
            return true;
        }

        return false;
    }
}


/**
 * Color object for Securimage CAPTCHA
 *
* @since 2.0
 * @package Securimage
 * @subpackage classes
 *
 */
class Securimage_Color
{
    /**
     * Red value (0-255)
     * @var int
     */
    public $r;

    /**
     * Gree value (0-255)
     * @var int
     */
    public $g;

    /**
     * Blue value (0-255)
     * @var int
     */
    public $b;

    /**
     * Create a new Securimage_Color object.
     *
     * Constructor expects 1 or 3 arguments.
     *
     * When passing a single argument, specify the color using HTML hex format.
     *
     * When passing 3 arguments, specify each RGB component (from 0-255)
     * individually.
     *
     * Examples:
     *
     *     $color = new Securimage_Color('#0080FF');
     *     $color = new Securimage_Color(0, 128, 255);
     *
     * @param string $color  The html color code to use
     * @throws Exception  If any color value is not valid
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

    public function toLongColor()
    {
        return ($this->r << 16) + ($this->g << 8) + $this->b;
    }

    public function fromLongColor($color)
    {
        $this->r = ($color >> 16) & 0xff;
        $this->g = ($color >>  8) & 0xff;
        $this->b =  $color        & 0xff;

        return $this;
    }

    /**
     * Construct from an rgb triplet
     *
     * @param int $red The red component, 0-255
     * @param int $green The green component, 0-255
     * @param int $blue The blue component, 0-255
     */
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

    /**
     * Construct from an html hex color code
     *
     * @param string $color
     */
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

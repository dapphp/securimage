<?php

/**
 * Project:     Securimage: A PHP class for creating and managing form CAPTCHA images<br />
 * File:        securimage_play.php<br />
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
 * @copyright 2018 Drew Phillips
 * @author Drew Phillips <drew@drew-phillips.com>
 * @version 4.0.2 (May 2020)
 * @package Securimage
 *
 */

if (!class_exists('Securimage')) {
    require_once __DIR__ . '/securimage.php';
}

$options = array();

// set id if supplied to script via HTTP GET
if (!empty($_GET['id'])) {
    $options['captchaId'] = $_GET['id'];
}

$img = new Securimage($options);

// Other audio settings
//$img->audio_use_noise = true;
//$img->degrade_audio   = false;
//Securimage::$lame_binary_path = '/usr/bin/lame'; // for mp3 audio support

// To use an alternate language, uncomment the following and download the files from phpcaptcha.org
// $img->audio_path = $img->securimage_path . '/audio/es/';

// mp3 or wav format
$format = (isset($_GET['format']) && strtolower($_GET['format']) == 'mp3') ? 'mp3' : null;

$img->outputAudioFile($format);

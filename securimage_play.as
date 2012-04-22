/**
* Project: Securimage: A PHP class for creating and managing form CAPTCHA images<br />
* File: securimage_play.fla<br />
*
* Copyright (c) 2012, Drew Phillips
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without modification,
* are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
* this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright notice,
* this list of conditions and the following disclaimer in the documentation
* and/or other materials provided with the distribution.
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
* to inform users that the changes are not a part of the original software.<br /><br />
*
* @link http://www.phpcaptcha.org Securimage PHP CAPTCHA
* @link http://www.phpcaptcha.org/latest.zip Download Latest Version
* @copyright 2012 Drew Phillips
* @author Drew Phillips <drew@drew-phillips.com>
* @version 3.0.3Beta (April 2012)
* @package Securimage
*
*
* @revision 2012-04-13
*
* Current Flash with custom audio icon and image loader by Age Bosma (agebosma -at- gmail -dot- com)
* Accessibility code by Ryan Chan (ryanchan -at- live -dot- com)
* Original flash movie and graphics by Mario Romero (animario -at- hotmail), ActionScript by Drew Phillips
*
*/


package {
	// IMPORTS
	import flash.events.*;
	import flash.net.*;
	import flash.media.*;
	import flash.display.*;
	import flash.utils.*;
	import flash.geom.Point;
	import flash.accessibility.Accessibility;
	import flash.accessibility.AccessibilityProperties;
	import de.popforge.audio.*;
	import de.popforge.format.wav.WavFormat;
	import de.popforge.audio.output.SoundFactory;

	public class securimage_play {

		// MOVIE VARS
		private var _mainMovie:MovieClip;
		private var _stage:Stage;
		private var _flashVars:Object;
		private var _iconArea:MovieClip;
		private var _accessProps:AccessibilityProperties;
		private var _isPlaying:Boolean = false; // is the sound playing or not
		private var _isLoading:Boolean = false; // is the in a loading state
		private var _soundChnl:SoundChannel;    // soundchannel for popforge
		private var _urlLoader:URLLoader;       // for loading wav file data
		private var _iconLoader:Loader;         // for loading user defined icon
		private var _loadingTimer:Timer;        // timer for loading sound
		private var _indicatorCenter:Point;     // center point for sound loading throbber
		private var _lastClickTime:Number;      // time the start/stop button was pressed


		// USER PARAMETERS - unused except audio_file and icon_file
		private var audio_file:String;
		private var icon_file:String;
		private var lang:String;

		private var captchaSound:Sound;
		private var icon_color:String;
		private var border_color1:String;
		private var border_color2:String;
		private var bg_color1:String;
		private var bg_color2:String;
		private var rounded:String;
		private var border_width:String;

        /**
		 * Constructor for Securimage flash
		 *
		 * @param movieClip:MovieClip  The main movie clip of the flash object
		 * @param parameters:Object    The flash params (root.loaderInfo.parameters)
		 * @param mainStage:Stage      The stage for the flash movie
		 * @param iconArea:MovieClip   The clip for the audio play button icon
		 */
		public function securimage_play(movieClip:MovieClip,
		                                parameters:Object,
		                                mainStage:Stage,
		                                iconArea:MovieClip) {
			_mainMovie = movieClip;
			_stage     = mainStage;
			_flashVars = parameters;
			_iconArea  = iconArea;

			// Set up accessibility for screen reader support
			_accessProps      = new AccessibilityProperties();
			_accessProps.name = "Play Audio Captcha";

			_iconArea.accessibilityProperties = _accessProps;
			_iconArea.tabIndex = 1;
			setTimeout(updateAccessibility, 2000);


			// Register click event for start/stop sound
			_mainMovie.addEventListener(KeyboardEvent.KEY_DOWN, enterHandler);
			_mainMovie.addEventListener(MouseEvent.CLICK, startStopSound);


			// Construct objects and initialize values

			// default audio_file to the same working directory as securimage_play.swf on server
			audio_file     = "./securimage_play.php";
			_soundChnl     = new SoundChannel();
			_loadingTimer  = new Timer(100);
			_isPlaying     = false;
			_isLoading     = false;
			_lastClickTime = 0;

			loadParams();

			_loadingTimer.addEventListener(TimerEvent.TIMER, this.rotateLoadingIndicator);
		}

		function loadParams()
		{
			trace("Params loaded");

			if (_flashVars.audio_file != undefined) {
				audio_file = _flashVars.audio_file;
			} else {
				// Debug help - set this to your debugging url for securimage_play.php
				// or if you leave blank it defaults to ./securimage_play.php which is likely invalid
				// trace("audio_file not defined");
				// audio_file = "http://192.168.0.13/securimage-dev/securimage_play.php";
			}

			if (_flashVars.lang != undefined) {
				lang = _flashVars.lang;
			}
			
			if (_flashVars.bgcol != undefined) {
				if (_flashVars.bgcol.match(/^#[0-9a-fA-F]{6}$/) != null) {
					_flashVars.bgcol = _flashVars.bgcol.replace("#", "");
					_iconArea.opaqueBackground = parseInt(_flashVars.bgcol, 16);
				}
			}

			if (_flashVars.icon_file != undefined) {
				icon_file = _flashVars.icon_file;
				setCustomIcon();
			}

			_stage.addEventListener(MouseEvent.CLICK, startStopSound);
			_stage.addEventListener(KeyboardEvent.KEY_UP, startStopSound);
			_iconArea.alpha = 1;
			
			trace("Stage area wxh = " + _stage.scaleX + " " + _stage.scaleY);
		}

		function enterHandler(event:KeyboardEvent)
		{
			_mainMovie.dispatchEvent(new MouseEvent(MouseEvent.CLICK));	
		}

		/**
		 * Starts or stops playback of the audio based on _isPlaying
		 */
		function startStopSound(evt:MouseEvent):void
		{
			// check for click bug - maybe this only happens in flash debugger.
			// Frequently the click event would raise twice VERY FAST when trying to use
			// the button.  One click seemed to be registering two clicks and raising the
			// event two times in a row which would greatly confuse the user.
			// If a second click event is received less than 250ms from the previous, ignore
			// it as it was probably unintended or a result of this glitch.
			var d:Date = new Date();
			if (d.getTime() - _lastClickTime < 250) return ;

			_lastClickTime = d.getTime(); // update last click time

			if (_isPlaying == false && _isLoading == false)
			{
				trace("Loading sound " + audio_file);

				_isLoading       = true;
				_mainMovie.alpha = 0.85; // show loading throbber
				_iconArea.alpha  = 0;    // hide sound icon
				_loadingTimer.start();   // start the timer to rotate the loader icon

				_urlLoader = new URLLoader();
				_urlLoader.dataFormat = URLLoaderDataFormat.BINARY;
				_urlLoader.addEventListener(Event.COMPLETE, onSoundLoaded);
				_urlLoader.addEventListener(IOErrorEvent.IO_ERROR, onLoaderIOError);

			   try {
				   _urlLoader.load(new URLRequest(audio_file));
			   } catch (error:SecurityError) {
				   trace("A security error has occurred loading the audio file.");
			   }
			} else {
				if (_isLoading == true) {
					trace("Stop loading sound");
					_urlLoader.close(); // cancel loading file
				} else {
					trace("Stop playing sound");
					_soundChnl.stop();
				}

				trace("Stop handled");

				_iconArea.alpha  = 1;
				_mainMovie.alpha = 0;

				_isLoading = false;
				_isPlaying = false;
			}
		}

		/**
		 * Called when the wav file has finished loading
		 */
		function onSoundLoaded(e:Event):void
		{
			trace("Sound loaded, creating soundfactory");
			_isLoading = false;
			_isPlaying = true;
			_loadingTimer.stop();
			_iconArea.alpha  = 1.0;
			_mainMovie.alpha = 0; // hide loading indicator

			var wavformat:WavFormat = WavFormat.decode(e.target.data);

			SoundFactory.fromArray(wavformat.samples, wavformat.channels, wavformat.bits, wavformat.rate, onSoundFactoryComplete);
		}

		/**
		 * Called when the SoundFactory finishes processing the WAV and it can be played
		 */
		function onSoundFactoryComplete(sound:Sound):void
		{   
			trace("Playing sound");

			_isPlaying = true;
			_soundChnl = sound.play();
			_soundChnl.addEventListener(Event.SOUND_COMPLETE, soundFinished);
		}

		/**
		 * Called if the audio fails to load
		 */
		function onLoaderIOError(e:IOErrorEvent):void
		{
			trace("Error loading sound");

			// TODO: Play a generic audio error file that is embedded in the flash file
		}

		/**
		 * Called when the sound finishes playback
		 */
		function soundFinished(e:Event):void
		{
			trace("Sound finished playing");

			_isPlaying = false;
			_isLoading = false;
		}

		/**
		 *  Accessibility handler
		 */
		function updateAccessibility():void {
			if (Accessibility.active) {
				Accessibility.updateProperties();
			}
		}

		/**
		 * When sound is loading, this event is raised every 100ms to
		 * roate the spinner and give a loading effect
		 */
		function rotateLoadingIndicator(e:Event):void {
			//trace("Rotate indicator");
			_mainMovie.rotation += 45;
		}

		/**
		 * Called on load if a user provided icon file is used
		 */
		function setCustomIcon() {
			trace("Setting custom icon");

			_iconLoader = new Loader();
			_iconLoader.contentLoaderInfo.addEventListener(Event.COMPLETE, onIconLoaded);
			_iconLoader.load(new URLRequest(icon_file));
		}

		/**
		 * Called when the user icon has been downloaded
		 */
		function onIconLoaded(e:Event) {
			trace("Custom icon loaded");

			var image:Bitmap = Bitmap(_iconLoader.content);

			if (image.width != _stage.width || image.height != _stage.height) {
				trace("Scaling custom icon");

				image.smoothing = true;
				image.width     = _stage.width;
				image.height    = _stage.height;

				(image.scaleX < image.scaleY) ? image.scaleY = image.scaleX : image.scaleX = image.scaleY;
			}

			_iconArea.removeChildAt(0);
			_iconArea.addChild(image);
		}
	} // class securimage_play
} // package

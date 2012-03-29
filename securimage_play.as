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
* @version 3.2.0 (April 2012)
* @package Securimage
*
*
* @revision 2012-03-28
*
* Original flash movie and graphics by Mario Romero (animario -at- hotmail), ActionScript by Drew Phillips
* Accessibility code by Ryan Chan (ryanchan -at- live -dot- com)
* 
*/


package {
	// IMPORTS
	import flash.events.*;
	import flash.net.*;
	import flash.media.*;
	import flash.display.*;
	import flash.utils.*;
	import flash.accessibility.Accessibility;
	import flash.accessibility.AccessibilityProperties;
	import de.popforge.audio.*;
	import de.popforge.format.wav.WavFormat;
	import de.popforge.audio.output.SoundFactory;
	
	public class securimage_play {
		
		// MOVIE VARS
		private var _mainMovie:MovieClip;
		private var _accessProps:AccessibilityProperties;
		private var _sound:Sound;               // sound object for popforge
		private var _sChannel:SoundChannel;     // soundchannel for popforge
		private var _isPlaying:Boolean = false; // is the sound playing or not
		private var _isLoading:Boolean = false; // is the in a loading state
		
		// USER PARAMETERS - unused except audio_file
		private var audio_file:String;
		private var captchaSound:Sound;
		private var icon_color:String;
		private var border_color1:String;
		private var border_color2:String;
		private var bg_color1:String;
		private var bg_color2:String;
		private var rounded:String;
		private var border_width:String;
		private var sndChl:SoundChannel;
		private var urlLoader:URLLoader;
		
		public function securimage_play(movieClip:MovieClip, parameters:Object) {
			this._mainMovie = movieClip;
			_accessProps = new AccessibilityProperties();
			
			audio_file = "./securimage_play.php";
			bg_color1 = "#FFF";
			bg_color2 = "#CCC";
			border_color1 = "#CCC";
			border_color2 = "#000";
			border_width = "0";
			icon_color = "#000";
			
			_accessProps.name="Play Audio Captcha";
			
			_mainMovie.accessibilityProperties = _accessProps;
			_mainMovie.tabIndex = 1;
			_mainMovie.addEventListener(KeyboardEvent.KEY_DOWN, enterHandler);
			_mainMovie.addEventListener(MouseEvent.CLICK, startStopSound);
			
			trace("Parameter audio_file = " + parameters.audio_file);
			
			if (parameters.audio_file != undefined)
			{
				audio_file = parameters.audio_file;
				trace("Will load " + audio_file);
			} else {
				// Debug help
				// audio_file = "http://phpcap/securimage_play.php";
				// trace("audio_file not defined");
			}
			
			sndChl = new SoundChannel();
			setTimeout(updateAccessibility, 2000);
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
			if (_isPlaying == false && _isLoading == false)
			{
				urlLoader = new URLLoader();
				urlLoader.dataFormat = URLLoaderDataFormat.BINARY;
				urlLoader.addEventListener(Event.COMPLETE, soundLoaded);
				urlLoader.addEventListener(IOErrorEvent.IO_ERROR, onLoaderIOError);
			   
				trace("Downloading the audio file...");       
				trace(audio_file);
				
				var urlRequest:URLRequest = new URLRequest(audio_file);
			   
				_isLoading = true;
				urlLoader.load(urlRequest);
			   
				// HERE WE NEED TO SHOW THE "LOADING" SPRITE
				_mainMovie.gotoAndStop("loading");   
			} else {   
				_isPlaying = false;
				sndChl.stop();
				trace("Clicked stop");
				_mainMovie.gotoAndStop("audio");
			}
		}
		
		/**
		 * Called when the wav file has finished loading
		 */
		function soundLoaded(e:Event):void
		{
			var urlLoader:URLLoader = e.target as URLLoader;
			urlLoader.removeEventListener(Event.COMPLETE, soundLoaded);
			urlLoader.removeEventListener(IOErrorEvent.IO_ERROR, onLoaderIOError);
			_isLoading = false;
		   
			trace("Sound loaded, creating soundFactory.");
			_mainMovie.gotoAndStop("stop");
		
			var wavformat:WavFormat = WavFormat.decode(urlLoader.data);
		
			SoundFactory.fromArray(wavformat.samples, wavformat.channels, wavformat.bits, wavformat.rate, onSoundFactoryComplete);
		}
		
		/**
		 * Called when the SoundFactory finishes processing the WAV and it can be played
		 */
		function onSoundFactoryComplete(sound:Sound):void
		{   
			trace("Sound constructed, going to play");
			captchaSound = sound;
			_isPlaying   = true;
		
			sndChl     = captchaSound.play();
			sndChl.addEventListener(Event.SOUND_COMPLETE, soundFinished);
		}
		
		/**
		 * Called if the audio fails to load
		 */
		function onLoaderIOError(e:IOErrorEvent):void
		{
			var urlLoader:URLLoader = e.target as URLLoader;
			urlLoader.removeEventListener(Event.COMPLETE, soundLoaded);
			urlLoader.removeEventListener(IOErrorEvent.IO_ERROR, onLoaderIOError);
		
			trace("Error loading sound");
		}
		
		/**
		 * Called when the sound finishes playback
		 */
		function soundFinished(e:Event):void
		{
			trace("Audio finished playing");
			_mainMovie.gotoAndStop("audio");
		
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
	} // class securimage_play
} // package

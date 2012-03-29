/*
Copyright(C) 2007 Andre Michelle and Joa Ebert

PopForge is an ActionScript3 code sandbox developed by Andre Michelle and Joa Ebert
http://sandbox.popforge.de

This file is part of PopforgeAS3Audio.

PopforgeAS3Audio is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

PopforgeAS3Audio is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>
*/
package de.popforge.audio.output
{
	import flash.events.Event;
	import flash.media.Sound;
	import flash.media.SoundChannel;
	import flash.utils.ByteArray;
	import flash.utils.Endian;

	/**
	 * The class AudioBuffer creates an endless AudioStream
	 * as long as the buffer is updated with new samples
	 * 
	 * @author Andre Michelle
	 */
	public class AudioBuffer
	{
		/**
		 * The internal minimal sound buffer length in samples(PC)
		 */
		static public const UNIT_SAMPLES_NUM: uint = 2048;
		
		/**
		 * Stores a delegate function called, when the AudioBuffer is inited.
		 */
		public var onInit: Function;
		/**
		 * Stores a delegate function called, when the AudioBuffer has complete its cycle.
		 */
		public var onComplete: Function;
		/**
		 * Stores a delegate function called, when the AudioBuffer is started.
		 */
		public var onStart: Function;
		/**
		 * Stores a delegate function called, when the AudioBuffer is stopped.
		 */
		public var onStop: Function;
		
		private var multiple: uint;
		private var channels: uint;
		private var bits: uint;
		private var rate: uint;
		
		private var sync: Sound;
		private var syncChannel: SoundChannel;
		private var sound: Sound;
		private var soundChannel: SoundChannel;
		
		private var _numBytes: uint;
		private var _numSamples: uint;

		private var bytes: ByteArray;
		private var samples: Array;

		private var firstrun: Boolean;
		private var playing: Boolean;
		
		private var $isInit: Boolean;
		
		/**
		 * Creates an AudioBuffer instance.
		 * 
		 * @param multiple Defines the buffer length (4times recommended)
		 * @param channels Mono(1) or Stereo(2)
		 * @param bits 8bit(8) or 16bit(16)
		 * @param rate SamplingRate 5512Hz, 11025Hz, 22050Hz, 44100Hz
		 */
		public function AudioBuffer( multiple: uint, channels: uint, bits: uint, rate: uint )
		{
			Audio.checkAll( channels, bits, rate );
			
			this.multiple = multiple;
			this.channels = channels;
			this.bits = bits;
			this.rate = rate;
			
			firstrun = true;
			
			init();
		}

		/**
		 * Updates the AudioBuffer samples for the next playback cycle
		 * Must be called after the new samples are computed
		 */
		public function update(): ByteArray
		{
			bytes.length = 0;
			
			var i: int;
			var s: Sample;
			var l: Number;
			var r: Number;
			
			switch( channels )
			{
				case Audio.MONO:

					if( bits == Audio.BIT16 )
					{
						for( i = 0 ; i < _numSamples ; i++ )
						{
							s = samples[i];
							l = ( s.left + s.right ) / 2;
							
							if( l < -1 ) bytes.writeShort( -0x7fff );
							else if( l > 1 ) bytes.writeShort( 0x7fff );
							else bytes.writeShort( l * 0x7fff );
							
							s.left = s.right = 0;
						}
					}
					else
					{
						for( i = 0 ; i < _numSamples ; i++ )
						{
							s = samples[i];
							l = ( s.left + s.right ) / 2;
							
							if( l < -1 ) bytes.writeByte( 0 );
							else if( l > 1 ) bytes.writeByte( 0xff );
							else bytes.writeByte( 0x80 + l * 0x7f );
							
							s.left = s.right = 0;
						}
					}
					break;
					
				case Audio.STEREO:

					if( bits == Audio.BIT16 )
					{
						for( i = 0 ; i < _numSamples ; i++ )
						{
							s = samples[i];
							l = s.left;
							r = s.right;
							
							if( l < -1 ) bytes.writeShort( -0x7fff );
							else if( l > 1 ) bytes.writeShort( 0x7fff );
							else bytes.writeShort( l * 0x7fff );
			
							if( r < -1 ) bytes.writeShort( -0x7fff );
							else if( r > 1 ) bytes.writeShort( 0x7fff );
							else bytes.writeShort( r * 0x7fff );
							
							s.left = s.right = 0;
						}
					}
					else
					{
						for( i = 0 ; i < _numSamples ; i++ )
						{
							s = samples[i];
							l = s.left;
							r = s.right;
							
							if( l < -1 ) bytes.writeByte( 0 );
							else if( l > 1 ) bytes.writeByte( 0xff );
							else bytes.writeByte( 0x80 + l * 0x7f );
							if( r < -1 ) bytes.writeByte( 0 );
							else if( r > 1 ) bytes.writeByte( 0xff );
							else bytes.writeByte( 0x80 + r * 0x7f );
							
							s.left = s.right = 0;
						}
					}
					break;
			}
			
			SoundFactory.fromByteArray( bytes, channels, bits, rate, onNewBufferCreated );
			
			return bytes;
		}
		
		/**
		 * Starts the AudioBuffer playback
		 */
		public function start(): Boolean
		{
			if( playing ) return false;
			
			if( sync != null )
			{
				syncChannel = sync.play( 0, 1 );
				syncChannel.addEventListener( Event.SOUND_COMPLETE, onSyncComplete );
				
				if( soundChannel != null )
					soundChannel.stop();
				
				if( sound != null )
					soundChannel = sound.play( 0, 1 );
				
				playing = true;
			}
			
			if( onStart != null )
				onStart( this );
			
			return true;
		}

		/**
		 * Stops the AudioBuffer playback
		 */
		public function stop(): Boolean
		{
			if( !playing ) return false;
			
			if( syncChannel != null )
			{
				syncChannel.stop();
				syncChannel = null;
			}

			if( soundChannel != null )
			{
				soundChannel.stop();
				soundChannel = null;
			}
			
			playing = false;
			sound = null;
			
			var s: Sample;

			for( var i: int = 0 ; i < _numSamples ; i++ )
			{
				s = samples[i];
				s.left = s.right = 0.0;
			}
			
			if( onStop != null )
				onStop( this );
			
			return true;
		}
		
		/**
		 * Returns true, if the AudioBuffer is playing
		 */
		public function isPlaying(): Boolean
		{
			return playing;
		}

		/**
		 * Sets the number of channels. Stops AudioBuffer playback for new a init phase
		 */
		public function setChannels( channels: uint ): void
		{
			Audio.checkChannels( channels );
			
			if( channels != this.channels )
			{
				this.stop();
				this.channels = channels;
				this.init();
			}
		}

		/**
		 * Returns number of channels.
		 */
		public function getChannels(): uint
		{
			return channels;
		}

		/**
		 * Sets the number of bits. Stops AudioBuffer playback for new a init phase
		 */
		public function setBits( bits: uint ): void
		{
			Audio.checkBits( bits );
			
			if( bits != this.bits )
			{
				this.stop();
				this.bits = bits;
				this.init();
			}
		}

		/**
		 * Returns number of bits.
		 */
		public function getBits(): uint
		{
			return bits;
		}

		/**
		 * Sets the samplingRate. Stops AudioBuffer playback for new a init phase
		 */
		public function setRate( rate: uint ): void
		{
			Audio.checkRate( rate );
			
			if( rate != this.rate )
			{
				this.stop();
				this.rate = rate;
				this.init();
			}
		}

		/**
		 * Returns samplingRate in Hz
		 */
		public function getRate(): uint
		{
			return rate;
		}

		/**
		 * Sets the length of the buffer. Stops AudioBuffer playback for new a init phase
		 */
		public function setMultiple( multiple: uint ): void
		{
			if( multiple != this.multiple )
			{
				this.stop();
				this.multiple = multiple;
				this.init();
			}
		}

		/**
		 * Returns length of the buffer (sampleNum / UNIT_SAMPLES_NUM)
		 */
		public function getMultiple(): uint
		{
			return multiple;
		}

		/**
		 * Returns samples for overriding with new amplitudes
		 */
		public function getSamples(): Array
		{
			return samples;
		}

		/**
		 * Returns number of samples
		 */
		public function get numSamples(): uint	//-- read only
		{
			return _numSamples;
		}

		/**
		 * Returns current peak(left)
		 */
		public function get leftPeak(): Number
		{
			return soundChannel == null ? 0 : soundChannel.leftPeak;
		}

		/**
		 * Returns current peak(right)
		 */
		public function get rightPeak(): Number
		{
			return soundChannel == null ? 0 : soundChannel.rightPeak;
		}

		/**
		 * Returns true, if the AudioBuffer is inited
		 */
		public function get isInit(): Boolean
		{
			return $isInit;
		}

		/**
		 * Returns number of milliseconds of each buffer
		 */
		public function get millisEachBuffer(): Number
		{
			return 2048000 / rate * multiple;
		}
		
		private function init(): void
		{
			$isInit = false;
			
			if( multiple == 0 )
				throw new Error( 'Buffer must have a length greater than 0.' );
			
			var i: int;
			
			bytes = new ByteArray();
			bytes.endian = Endian.LITTLE_ENDIAN;
			
			//-- compute number of bytes
			switch( rate )
			{
				case Audio.RATE44100:
					_numSamples = ( UNIT_SAMPLES_NUM * multiple ); break;
				case Audio.RATE22050:
					_numSamples = ( UNIT_SAMPLES_NUM * multiple ) >> 1; break;
				case Audio.RATE11025:
					_numSamples = ( UNIT_SAMPLES_NUM * multiple ) >> 2; break;
				case Audio.RATE5512:
					_numSamples = ( UNIT_SAMPLES_NUM * multiple ) >> 3; break;
			}
			
			//-- compute number of bytes
			_numBytes = _numSamples;
			if( channels == Audio.STEREO ) _numBytes <<= 1;
			if( bits == Audio.BIT16 ) _numBytes <<= 1;

			samples = new Array();
			for( i = 0 ; i < _numSamples ; i++ )
				samples.push( new Sample() );

			//-- create silent bytes for sync sound
			var syncSamples: ByteArray = new ByteArray();
			
			switch( bits )
			{
				case Audio.BIT16:
					syncSamples.length = ( _numSamples - 1 ) << 1; break;
				case Audio.BIT8:
					syncSamples.length = _numSamples - 1;
					for( i = 0 ; i < syncSamples.length ; i++ )
						syncSamples[i] = 128;
					break;
			}

			SoundFactory.fromByteArray( syncSamples, 1, bits, rate, onGenerateSyncSound );
			
			playing = false;
		}
		
		private function onGenerateSyncSound( sound: Sound ): void
		{
			sync = sound;
			
			$isInit = true;
			
			if( onInit != null && firstrun )
				onInit( this );
			
			firstrun = false;
		}

		private function onNewBufferCreated( sound: Sound ): void
		{
			if( playing )
				this.sound = sound;
		}
		
		private function onSyncComplete( event: Event ): void
		{
			if( syncChannel != null )
				syncChannel.stop();

			syncChannel = sync.play( 0, 1 );
			syncChannel.addEventListener( Event.SOUND_COMPLETE, onSyncComplete );
			
			if( soundChannel != null )
				soundChannel.stop();

			if( sound != null )
			{
				soundChannel = sound.play( 0, 1 );
			}

			sound = null;
			
			onComplete( this );
		}
	}
}
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
	import flash.display.Loader;
	import flash.events.Event;
	import flash.media.Sound;
	import flash.utils.ByteArray;
	import flash.utils.Endian;
	
	/**
	 * The class SoundFactory provides creating a valid
	 * flash.media.Sound object by passing either a
	 * custom Array with de.popforge.audio.output.Sample
	 * entries or by passing an uncompressed PCM ByteArray.
	 * 
	 * @author Andre Michelle
	 */
	public class SoundFactory
	{
		[Embed(source="swf.bin", mimeType="application/octet-stream")] static private const SWF: Class;
		
		/**
		 * Creates a flash.media.Sound object from dynamic audio material
		 * 
		 * @param samples An Array of Samples (de.popforge.audio.output.Sample)
		 * @param channels Mono(1) or Stereo(2)
		 * @param bits 8bit(8) or 16bit(16)
		 * @param rate SamplingRate 5512Hz, 11025Hz, 22050Hz, 44100Hz
		 * @param onComplete Function, that will be called after the Sound object is created. The signature must accept the Sound object as a parameter!
		 * 
		 * @see http://livedocs.adobe.com/flex/2/langref/flash/media/Sound.html flash.media.Sound
		 */
		static public function fromArray( samples: Array, channels: uint, bits: uint, rate: uint, onComplete: Function ): void
		{
			var bytes: ByteArray = new ByteArray();
			bytes.endian = Endian.LITTLE_ENDIAN;
			
			var i: int;
			var s: Sample;
			var l: Number;
			var r: Number;
			
			var _numSamples: int = samples.length;
			
			switch( channels )
			{
				case Audio.MONO:

					if( bits == Audio.BIT16 )
					{
						for( i = 0 ; i < _numSamples ; i++ )
						{
							s = samples[i];
							l = s.left;
							
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
							l = s.left;
							
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
			
			SoundFactory.fromByteArray( bytes, channels, bits, rate, onComplete );
		}

		/**
		 * Creates a flash.media.Sound object from dynamic audio material
		 * 
		 * @param samples A uncompressed PCM ByteArray
		 * @param channels Mono(1) or Stereo(2)
		 * @param bits 8bit(8) or 16bit(16)
		 * @param rate SamplingRate 5512Hz, 11025Hz, 22050Hz, 44100Hz
		 * @param onComplete Function, that will be called after the Sound object is created. The signature must accept the Sound object as a parameter!
		 * 
		 * @see http://livedocs.adobe.com/flex/2/langref/flash/media/Sound.html flash.media.Sound
		 */
		static public function fromByteArray( bytes: ByteArray, channels: uint, bits: uint, rate: uint, onComplete: Function ): void
		{
			Audio.checkAll( channels, bits, rate );
			
			//-- get naked swf bytearray
			var swf: ByteArray = ByteArray( new SWF() );

			swf.endian = Endian.LITTLE_ENDIAN;
			swf.position = swf.length;

			//-- write define sound tag header
			swf.writeShort( 0x3bf );
			swf.writeUnsignedInt( bytes.length + 7 );

			//-- assemble audio property byte (uncompressed little endian)
			var byte2: uint = 3 << 4;
	
			switch( rate )
			{
				case 44100: byte2 |= 0xc; break;
				case 22050: byte2 |= 0x8; break;
				case 11025:	byte2 |= 0x4; break;
			}

			var numSamples: int = bytes.length;
			
			if( channels == 2 )
			{
				byte2 |= 1;
				numSamples >>= 1;
			}
			
			if( bits == 16 )
			{
				byte2 |= 2;
				numSamples >>= 1;
			}
	
			//-- write define sound tag
			swf.writeShort( 1 );
			swf.writeByte( byte2 );
			swf.writeUnsignedInt( numSamples );
			swf.writeBytes( bytes );

			//-- write eof tag in swf stream
			swf.writeShort( 1 << 6 );
			
			//-- overwrite swf length
			swf.position = 4;
			swf.writeUnsignedInt( swf.length );
			swf.position = 0;
			
			var onSWFLoaded: Function = function( event: Event ): void
			{
				onComplete( Sound( new ( loader.contentLoaderInfo.applicationDomain.getDefinition( 'SoundItem' ) as Class )() ) );
			}
			
			var loader: Loader = new Loader();
			loader.contentLoaderInfo.addEventListener( Event.COMPLETE, onSWFLoaded );
			loader.loadBytes( swf );
		}
	}
}
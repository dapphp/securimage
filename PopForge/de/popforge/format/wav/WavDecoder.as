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
package de.popforge.format.wav
{
	import de.popforge.audio.output.Audio;
	import de.popforge.audio.output.Sample;
	
	import flash.utils.ByteArray;
	import flash.utils.Endian;

	/**
	 * The class WavFormat interprets a ByteArray encoded in WAV-Format
	 * and creates an Array with Samples as well
	 * 
	 * @author Andre Michelle
	 */
	internal class WavDecoder
	{
		static internal function parse( bytes: ByteArray ): WavFormat
		{
			var wav: WavFormat = new WavFormat();
			
			bytes.position = 0;
			bytes.endian = Endian.LITTLE_ENDIAN;
			
			bytes.readUTFBytes( 4 ); // RIFF
			bytes.readUnsignedInt(); // entire fileLength - 8
			bytes.readUTFBytes( 4 ); // WAVE
			
			var id: String;
			var length: uint;
			var position: uint;
			
			while( bytes.position < bytes.length )
			{
				id = bytes.readUTFBytes( 4 );
				length = bytes.readUnsignedInt();
				
				position = bytes.position;
				
				switch( id )
				{
					case 'fmt ':
						trace("In case fmt ");
						wav.$compression = bytes.readUnsignedShort();
						wav.$channels = bytes.readUnsignedShort();
						wav.$rate = bytes.readUnsignedInt();
						wav.$bytesPerSecond = bytes.readUnsignedInt();
						wav.$blockAlign = bytes.readUnsignedShort();
						wav.$bits = bytes.readUnsignedShort();
						
						if (length == 18) {
							var temp:int = bytes.readUnsignedShort();
							bytes.position += temp;
						}
						
						break;

					case 'data':
						trace("In case data...");					
						var data: ByteArray = new ByteArray();
						data.endian = Endian.LITTLE_ENDIAN;
						data.writeBytes( bytes, position, length );
						data.position = 0;
						wav.$data = data;
						bytes.position = position + length;
						break;

					default:
						trace("Default case.");
						bytes.position = position + length;
						break;
				}
			}
			
			trace("Data.length = " + data.length);
			
			//-- compute samplenum
			wav.$numSamples = data.length;
			if( wav.$channels == 2 )
				wav.$numSamples >>= 1;
			if( wav.$bits == 16 )
				wav.$numSamples >>= 1;
			
			//-- create samples for audio engine
			wav.$samples = createSamples( wav );
			
			return wav;
		}
		
		static private function createSamples( wav: WavFormat ): Array
		{
			var sampleCount: uint = wav.$numSamples;
			var channels: uint = wav.$channels;
			var bits: uint = wav.$bits;
			var data: ByteArray = wav.$data;
			
			var samples: Array = new Array();
			var i: int;
			
			var value: Number;
			
			if( channels == Audio.STEREO )
			{
				if( bits == Audio.BIT16 )
				{
					for( i = 0 ; i < sampleCount ; i++ )
					{
						samples[i] = new Sample( data.readShort() / 0x7fff, data.readShort() / 0x7fff );
					}
				}
				else
				{
					for( i = 0 ; i < sampleCount ; i++ )
					{
						samples[i] = new Sample( data.readUnsignedByte() / 0x80 - 1, data.readUnsignedByte() / 0x80 - 1 );
					}
				}
			}
			else if( channels == Audio.MONO )
			{
				if( bits == Audio.BIT16 )
				{
					for( i = 0 ; i < sampleCount ; i++ )
					{
						value = data.readShort() / 0x7fff;
						
						samples[i] = new Sample( value, value );
					}
				}
				else
				{
					for( i = 0 ; i < sampleCount ; i++ )
					{
						value = data.readUnsignedByte() / 0x80 - 1;
						
						samples[i] = new Sample( value, value );
					}
				}
			}
			
			return samples;
		}
	}
}
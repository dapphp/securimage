/**
 * Copyright(C) 2007 Andre Michelle and Joa Ebert
 *
 * PopForge is an ActionScript3 code sandbox developed by Andre Michelle and Joa Ebert
 * http://sandbox.popforge.de
 * 
 * This file is part of PopforgeAS3Audio.
 * 
 * PopforgeAS3Audio is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * PopforgeAS3Audio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
package de.popforge.audio.output
{
	/**
	 * The class Audio provides all possible audio properties as constants.
	 * 
	 * @author Andre Michelle
	 */
	 
	public class Audio
	{
		static public const MONO: uint = 1;
		static public const STEREO: uint = 2;
		
		static public const BIT8: uint = 8;
		static public const BIT16: uint = 16;
		
		static public const RATE44100: uint = 44100;
		static public const RATE22050: uint = 22050;
		static public const RATE11025: uint = 11025;
		static public const RATE5512: uint = 5512;

		/**
		 * Checks all reasonable audio properties
		 * @throws Error thrown, if any property has not valid value
		 * 
		 * @param channels Mono(1) or Stereo(2)
		 * @param bits 8bit(8) or 16bit(16)
		 * @param rate SamplingRate 5512Hz, 11025Hz, 22050Hz, 44100Hz
		 */
		static public function checkAll( channels: uint, bits: uint, rate: uint ): void
		{
			checkChannels( channels );
			checkBits( bits );
			checkRate( rate );
		}
		
		/**
		 * Checks if the passed number of channels if valid
		 * @throws Error thrown, if not Mono(1) or Stereo(2)
		 * 
		 * @param channels Mono(1) or Stereo(2)
		 */
		static public function checkChannels( channels: uint ): void
		{
			switch( channels )
			{
				case MONO:
				case STEREO:
					break;

				default:
					throw new Error( 'Only mono or stereo is supported.' );
			}
		}

		/**
		 * Checks if the passed number of bits if valid
		 * @throws Error thrown, if not 8bit(8) or 16bit(16)
		 * 
		 * @param bits 8bit(8) or 16bit(16)
		 */
		static public function checkBits( bits: uint ): void
		{
			switch( bits )
			{
				case BIT8:
				case BIT16:
					break;
				
				default:
					throw new Error( 'Only 8 and 16 bit is supported.' );
			}
		}

		/**
		 * Checks if the passed number of bits if valid
		 * @throws Error thrown, if not 5512Hz, 11025Hz, 22050Hz, 44100Hz
		 * 
		 * @param rate SamplingRate 5512Hz, 11025Hz, 22050Hz, 44100Hz
		 */
		static public function checkRate( rate: uint ): void
		{
			switch( rate )
			{
				case RATE44100:
				case RATE22050:
				case RATE11025:
				case RATE5512:
					break;

				default:
					throw new Error( rate.toString() + 'is not supported.' );
			}
		}
	}
}
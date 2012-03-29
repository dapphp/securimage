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
	import flash.utils.ByteArray;

	public class WavFormat
	{
		internal var $compression: uint;
		internal var $channels: uint;
		internal var $rate: uint;
		internal var $bytesPerSecond: uint;
		internal var $blockAlign: uint;
		internal var $bits: uint;
		internal var $data: ByteArray;
		internal var $bytes: ByteArray;
		internal var $samples: Array;
		internal var $numSamples: uint;

		static public function decode( bytes: ByteArray ): WavFormat
		{
			return WavDecoder.parse( bytes );
		}
		
		static public function encode( samples: Array, channels: uint, bits: uint, rate: uint ): ByteArray
		{
			return WavEncoder.encode( samples, channels, bits, rate );
		}
		
		public function get numSamples(): uint
		{
			return $numSamples;
		}
		
		public function get channels(): uint
		{
			return $channels;
		}
		
		public function get bits(): uint
		{
			return $bits;
		}
		
		public function get rate(): uint
		{
			return $rate;
		}
		
		public function get samples(): Array
		{
			return $samples;
		}
		
		public function toString(): String
		{
			return '[WAV Header'
				+ ' compression: '+ $compression
				+ ', channels: ' + $channels
				+ ', samplingRate: ' + $rate
				+ ', bytesPerSecond: ' + $bytesPerSecond
				+ ', blockAlign: ' + $blockAlign
				+ ', bitsPerSample: ' + $bits
				+ ']';
		}
	}
}
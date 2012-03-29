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
	/**
	 * The class Sample stores 2 Numbers
	 * as a representation of the current Sample amplitudes.
	 * Their value should be computed in range[-1,1]
	 * 
	 * For Mono Samples use only left.
	 * 
	 * @author Andre Michelle
	 */
	public class Sample
	{
		/**
		 * The left amplitude of the Sample
		 */
		public var left: Number;
		/**
		 * The right amplitude of the Sample
		 */
		public var right: Number;

		/**
		 * Creates a Sample instance
		 * 
		 * @param left The left amplitude of the Sample
		 * @param right The right amplitude of the Sample
		 */
		public function Sample( left: Number = 0.0, right: Number = 0.0 )
		{
			this.left = left;
			this.right = right;
		}
		
		/**
		 * Returns a clone of the current Sample
		 */
		public function clone(): Sample
		{
			return new Sample( left, right );
		}
		
		public function toString(): String
		{
			return '{ left: ' + left + ' right: ' + right + ' }';
		}
	}
}
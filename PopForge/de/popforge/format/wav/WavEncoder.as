package de.popforge.format.wav
{
	import de.popforge.audio.output.Audio;
	import de.popforge.audio.output.Sample;
	
	import flash.utils.ByteArray;
	import flash.utils.Endian;
	
	public class WavEncoder
	{
		static public function encode( samples: Array, channels: uint, bits: uint, rate: uint ): ByteArray
		{
			var data: ByteArray = createData( samples, channels, bits, rate );
			
			var bytes: ByteArray = new ByteArray();
			bytes.endian = Endian.LITTLE_ENDIAN;
			
			bytes.writeUTFBytes( 'RIFF' );
			bytes.writeInt( uint( data.length + 44 ) );
			bytes.writeUTFBytes( 'WAVE' );
			bytes.writeUTFBytes( 'fmt ' );
			bytes.writeInt( uint( 16 ) );
			bytes.writeShort( uint( 1 ) );
			bytes.writeShort( channels );
			bytes.writeInt( rate );
			bytes.writeInt( uint( rate * channels * ( bits / 8 ) ) );
			bytes.writeShort( uint( channels * ( bits / 8 ) ) );
			bytes.writeShort( bits );
			bytes.writeUTFBytes( 'data' );
			bytes.writeInt( data.length );
			bytes.writeBytes( data );
			bytes.position = 0;
			
			return bytes;
		}
		
		static public function createWildHeader( channels: uint, bits: uint, rate: uint ): ByteArray
		{
			var bytes: ByteArray = new ByteArray();
			bytes.endian = Endian.LITTLE_ENDIAN;
			
			bytes.writeUTFBytes( 'RIFF' );
			bytes.writeInt( 0 );
			bytes.writeUTFBytes( 'WAVE' );
			bytes.writeUTFBytes( 'fmt ' );
			bytes.writeInt( uint( 16 ) );
			bytes.writeShort( uint( 1 ) );
			bytes.writeShort( channels );
			bytes.writeInt( rate );
			bytes.writeInt( uint( rate * channels * ( bits / 8 ) ) );
			bytes.writeShort( uint( channels * ( bits / 8 ) ) );
			bytes.writeShort( bits );
			bytes.writeUTFBytes( 'data' );
			bytes.writeInt( 0 );
			
			bytes.position = 0;
			
			return bytes;
		}
		
		static private function createData( samples: Array, channels: uint, bits: uint, rate: uint ): ByteArray
		{
			var bytes: ByteArray = new ByteArray();
			bytes.endian = Endian.LITTLE_ENDIAN;
			
			var i: int;
			var s: Sample;
			var l: Number;
			var r: Number;
			
			var numSamples: int = samples.length;
			
			switch( channels )
			{
				case Audio.MONO:

					if( bits == Audio.BIT16 )
					{
						for( i = 0 ; i < numSamples ; i++ )
						{
							s = samples[i];
							l = s.left;
							
							if( l < -1 ) bytes.writeShort( -0x7fff );
							else if( l > 1 ) bytes.writeShort( 0x7fff );
							else bytes.writeShort( l * 0x7fff );
						}
					}
					else
					{
						for( i = 0 ; i < numSamples ; i++ )
						{
							s = samples[i];
							l = s.left;
							
							if( l < -1 ) bytes.writeByte( 0 );
							else if( l > 1 ) bytes.writeByte( 0xff );
							else bytes.writeByte( 0x80 + l * 0x7f );
						}
					}
					break;
					
				case Audio.STEREO:

					if( bits == Audio.BIT16 )
					{
						for( i = 0 ; i < numSamples ; i++ )
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
						}
					}
					else
					{
						for( i = 0 ; i < numSamples ; i++ )
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
						}
					}
					break;
			}
			
			return bytes;
		}
	}
}
<?php

// error_reporting(E_ALL); ini_set('display_errors', 1); // uncomment this line for debugging

/**
* Project: PHPWavUtils: Classes for creating, reading, and manipulating WAV files in PHP<br />
* File: WavFile.php<br />
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
* @copyright 2012 Drew Phillips
* @author Drew Phillips <drew@drew-phillips.com>
* @version 0.5-alpha (April 2012)
* @package PHPWavUtils
* @license BSD License
* 
* Changelog:
*   0.5 (4/3/2012)
*     - Fix binary pack routine (Paul Voegler)
*     - Add improved mixing function (Paul Voegler)
*
*/

class WavFile
{
	/** @var int Filter flag for mixing two files */
	const FILTER_MIX     = 0x01;
	
	/** @var int Filter flag for degrading audio data */
	const FILTER_DEGRADE = 0x02;
	
    /** @var int The front left channel number */
    const CHANNEL_FL = 1;
    /** @var int The front right channel number */
    const CHANNEL_FR = 2;
    /** @var int The front center channel number */
    const CHANNEL_FC = 3;
    /** @var int The low frequency channel number */
    const CHANNEL_LF = 4;
    /** @var int The rear left channel number */
    const CHANNEL_BL = 5;
    /** @var int The rear right channel number */
    const CHANNEL_BR = 6;
    const MAX_CHANNEL = 6;
    
    /** @var int The size of the file in RIFF header */
    protected $_size;
    
    /** @var int The actual physical file size */
    protected $_actualSize;
    
    /** @var int The file format, supports PCM = 1 only */
    protected $_format;
    
    /** @var int The size of the fmt chunk  - 8 */
    protected $_subChunk1Size;
    
    /** @var int Number of channels in the audio file */
    protected $_numChannels;
    
    /** @var int Samples per second */
    protected $_sampleRate;
    
    /** @var int Bytes per second */
    protected $_byteRate;
    
    /** @var int NumChannels * BitsPerSample/8 */
    protected $_blockAlign;
    
    /** @var int Number of bits per sample */
    protected $_bitsPerSample;
    
    /** @var int Size of the data chunk */
    protected $_dataSize;
    
    /** @var int Starting offset of data chunk */
    protected $_dataOffset;
    
    /** @var array Array of samples */
    protected $_samples;
    
    /** @var resource The file pointer used for reading wavs from file or memory */
    protected $_fp;
    
    /**
     * WavFile Constructor<br />
     * 
     * <code>
     * $wav1 = new WavFile(2, 44100, 16); // new wav 2 channels, 44100 samples/sec, 16 bits per sample
     * $wav2 = new WavFile($wav1);        // new wavfile from existing object
     * $wav3 = new WavFile(array('numChannels' => 2, 'sampleRate' => 44100, 'bitsPerSample' => 16)); // create from array
     * $wav4 = new WavFile('./audio/sound.wav'); // create from file
     * </code>
     * 
     * @param array|WavFile|int $param  A WavFile, array of property values, or the number of channels to set
     * @param int $sampleRate           The samples per second (i.e. 44100, 22050)
     * @param int $bitsPerSample        Bits per sample - 8, 16 or 32
     * @throws InvalidArgumentException 
     */
    public function __construct($params = null)
    {
    	$this->_samples       = '';
    	$this->_blockAlign    = 0;
    	$this->_sampleRate    = 11025;
    	$this->_bitsPerSample = 8;
    	$this->_numChannels   = 1;
    	$this->_dataSize      = 0;
    	
        if ($params instanceof WavFile) {
            foreach ($params as $prop => $val) {
                $this->$prop = $val;
            }
        } else if (is_string($params)) {
            if (is_readable($params)) {
                try {
                    $this->openWav($params);
                    
                } catch(WavFormatException $wex) {
                    throw $wex;
                } catch(Exception $ex) {
                    throw $ex;
                }
            } else {
                throw new InvalidArgumentException("Cannot construct WavFile.  '" . htmlspecialchars($params) . "' is not readable.");
            }
        } else if (is_array($params)) {
            foreach ($params as $key => $val) {
                $this->$prop = $val;
            }
        } else if (is_int($params) && sizeof(func_num_args() == 3)) {
            $args = func_get_args();
            
            $numChannels   = $args[0];
            $sampleRate    = $args[1];
            $bitsPerSample = $args[2];
            
            $this->setNumChannels($numChannels)
                 ->setSampleRate($sampleRate)
                 ->setBitsPerSample($bitsPerSample)
                 ->setBlockAlign();
        }
    }
   
    public function getSize() {
        return $this->_size;
    }

    protected function setSize($size) {
        $this->_size = (int)$size;
        return $this;
    }

    public function getActualSize() {
        return $this->_actualSize;
    }

    public function setActualSize($actualSize) {
        $this->_actualSize = $actualSize;
        return $this;
    }

    public function getFormat() {
        return $this->_format;
    }

    public function setFormat($format) {
        $this->_format = $format;
        return $this;
    }
    
    public function getSubChunk1Size() {
        return $this->_subChunk1Size;
    }
    
    public function setSubChunk1Size($size) {
        $this->_subChunk1Size = $size;
        return $this;
    }

    public function getNumChannels() {
        return $this->_numChannels;
    }

    public function setNumChannels($numChannels) {
        $this->_numChannels = $numChannels;
        $this->setBlockAlign();
        return $this;
    }

    public function getSampleRate() {
        return $this->_sampleRate;
    }
    
    public function getByteRate() {
        return $this->_byteRate;
    }

    public function setByteRate($_byteRate) {
        $this->_byteRate = $_byteRate;
        return $this;
    }

    public function getBlockAlign() {
        return $this->_blockAlign;
    }

    protected function setBlockAlign() {
        $this->_blockAlign = $this->_numChannels * ($this->_bitsPerSample / 8);
        return $this;
    }

    public function setSampleRate($sampleRate) {
        $this->_sampleRate = $sampleRate;
        return $this;
    }

    public function getBitsPerSample() {
        return $this->_bitsPerSample;
    }

    public function setBitsPerSample($bitsPerSample) {
        $this->_bitsPerSample = $bitsPerSample;
        $this->setBlockAlign();
        return $this;
    }
    
    public function getDataSize() {
        return $this->_dataSize;
    }

    public function setDataSize($_dataSize) {
        $this->_dataSize = $_dataSize;
        return $this;
    }
    
    public function getDataOffset() {
        return $this->_dataOffset;
    }

    public function setDataOffset($_dataOffset) {
        $this->_dataOffset = $_dataOffset;
        return $this;
    }
    
    public function getSamples() {
        return $this->_samples;
    }

    public function setSamples($_samples) {
        $this->_samples = $_samples;
        return $this;
    }
    
    public function getNumSamples()
    {
        if ($this->_blockAlign == 0) {
    		return 0;
    	} else {
    		return strlen($this->_samples) / $this->_blockAlign;
    	}
    }
    
    public function getAmplitude()
    {
        if($this->getBitsPerSample() == 8) {
            return 255;
        } elseif($this->getBitsPerSample() == 32) {
            return 1;
        } else {
            return (pow(2, $this->getBitsPerSample()) / 2) - 1;
        }
    }
    
    public function getMinAmplitude()
    {
        if ($this->getBitsPerSample() == 8) {
            return 0;
        } elseif ($this->getBitsPerSample() == 32) {
            return -1;
        } else {
            return -$this->getAmplitude() - 1;
        }
    }
    
    /**
     * Construct a wav header from object
     * 
     * @return string The RIFF header data
     */
    public function makeHeader()
    {
        // RIFF header
        $header = pack('N', 0x52494646);
        
        $subchunk1size = 16;  // 16 byte subchunk1, PCM format
        $subchunk2size = sizeof($this->_samples) * $this->getNumChannels() * $this->getBitsPerSample() / 8;
        
        $header .= pack('V', 4 + (8 + $subchunk1size) + (8 +  $subchunk2size));
        $header .= pack('N', 0x57415645); // WAVE marker
        
        // fmt subchunk
        $header .= pack('N', 0x666d7420); // "fmt "
        $header .= pack('V', $subchunk1size);
        $header .= pack('v', 1);
        $header .= pack('v', $this->getNumChannels());
        $header .= pack('V', $this->getSampleRate());
        $header .= pack('V', $this->getSampleRate() * $this->getNumChannels() * $this->getBitsPerSample() / 8);
        $header .= pack('v', $this->getNumChannels() * $this->getBitsPerSample() / 8);
        $header .= pack('v', $this->getBitsPerSample());
        
        return $header;
    }
    
    /**
     * Construct wav DATA chunk
     * 
     * @return string The DATA header and chunk
     */
    public function getDataSubchunk()
    {
        return pack('N', 0x64617461) . // "data" chunk
               //pack('V', sizeof($this->_samples) * $this->getNumChannels() * $this->getBitsPerSample() / 8) .
        	   pack('V', strlen($this->_samples)) .
               //implode('', $this->_samples);
               $this->_samples;
    }
    
    /**
     * Reads a wav header and data from a file
     * 
     * @param string $filename  The path to the wav file to read
     * @throws InvalidArgumentException
     * @throws WavFormatException
     * @throws Exception
     */
    public function openWav($filename)
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("Failed to open $filename - no such file or directory");
        } else if (!is_readable($filename)) {
            throw new InvalidArgumentException("Failed to open $filename, access denied");
        }
        
        $this->_fp = @fopen($filename, 'rb');
        
        if (!$this->_fp) {
            $e = error_get_last();
            throw new Exception($e['message']);
        }
        
        try {
            $this->readWav();
        } catch (WavFormatException $wex) {
            throw $wex;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    /**
     * Set the wav file data and properties from a wav file in a string
     * 
     * @param string $data  The wav file data
     * @param bool $free True to free the passed $data object after copying
     * @throws Exception
     * @throws WavFormatException
     */
    public function setWavData(&$data, $free = true)
    {
        $this->_fp = @fopen('php://memory', 'w+b');
        
        if (!$this->_fp) {
            throw new Exception('Failed to open memory stream to write wav data.  Use openWav() instead');
        }
        
        fputs($this->_fp, $data);
        
        if ($free) {
            $data = null;
        }
        
        rewind($this->_fp);
        
        try {
            $this->readWav();
        } catch (WavFormatException $wex) {
            throw $wex;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    /**
     * Return a single sample from the file
     * 
     * @param int $sampleNum  The sample #
     */
    public function getSample($sampleNum)
    {
    	$offset = $sampleNum * $this->_blockAlign;
    	if ($offset + $this->_blockAlign > strlen($this->_samples)) {
    		return null;
    	} else {
    		return substr($this->_samples, $offset, $this->_blockAlign);
    	}
    	
    	/*
        if (sizeof($this->_samples) <= $sampleNum) {
            return null;
        } else {
            return $this->_samples[$sampleNum];
        }
        */
    }
    
    public function setSample($sampleNum, $sample)
    {
    	if (strlen($sample) != $this->_blockAlign) {
    		throw new Exception('Incorrect sample size.  Was ' . strlen($sample) . ' expected ' . $this->_blockAlign);
    	}
    	
    	$numSamples = strlen($this->_samples) / $this->_blockAlign;
    	$offset     = $sampleNum * $this->_blockAlign;
    	
    	if ($sampleNum > $numSamples) {
    		if ($sampleNum + 1 == $numSamples) {
    			$this->_samples .= $sample;
    		} else {
    			throw new Exception('Sample was outside the range of the wav file, use append.');
    		}
    	} else {
    		//$this->_samples = substr_replace($this->_samples, $sample, $offset, $this->_blockAlign);
    		for ($i = 0; $i < $this->_blockAlign; ++$i) {
    			$this->_samples{$offset + $i} = $sample{$i};
    		}
    	}
    }
    
    /**
     * Append a wav file to the current wav<br />
     * The wav files must have the same sample rate, # of bits per sample, and number of channels.
     * 
     * @param WavFile $wav  The wavfile to append
     * @throws Exception
     */
    public function appendWav(WavFile $wav) {
        // basic checks
        if ($wav->getSampleRate() != $this->getSampleRate()) {
            throw new Exception("Sample rate for wav files do not match");
        } else if ($wav->getBitsPerSample() != $this->getBitsPerSample()) {
            throw new Exception("Bits per sample for wav files do not match");
        } else if ($wav->getNumChannels() != $this->getNumChannels()) {
            throw new Exception("Number of channels for wav files do not match");
        }
        
        /*
        if (strlen($this->_samples) == 0) {
            $this->_samples = $wav->getSamples();
        } else {
            foreach ($wav->getSamples() as $sample) {
                $this->_samples[] = $sample;
            }
        }
        */
        $this->_samples .= $wav->getSamples();
        
        return $this;
    }
    
    public function filter($filters, $options = array())
    {
    	$filters = (int)$filters;
    	
    	if ($filters == 0) {
    		throw new Exception('No filters provided');
    	}
    	
    	$numSamples  = $this->getNumSamples();
    	$numChannels = $this->getNumChannels();
    	$bitdepth    = $this->_bitsPerSample;
    	$amplitude   = $this->getAmplitude();
    	$packFunction = "packSample{$bitdepth}bit";
    	
    	if ($bitdepth == 32) $packFunction .= 'f';
    	    	
    	for ($s = 0; $s < $numSamples; ++$s) {
    		$filtered  = '';
    		$sample    = $this->getSample($s);
    		
    		for ($ch = 1; $ch <= $numChannels; ++$ch) {
    			$smpl = $this->getChannelData($sample, $ch);
    			
    			/************* MIX FILTER ***********************/
	    		if ( ($filters & self::FILTER_MIX) > 0) {
	    			$wav  = $options['filter_mix'];
	    			$s2   = $wav->getSample($s);
	    			if ($s2 != null) {
	    				$c2   = $wav->getChannelData($s2, $ch);
	    			 
	    				$smpl = $this->mixSample($smpl, $c2, $bitdepth);
	    			}
	    			
	    		}
	    		
	    		/************* DEGRADE FILTER *******************/
	    		if ( ($filters & self::FILTER_DEGRADE) > 0) {
	    			$quality = $options['filter_degrade'];
	    			$thresh  = (int)((1 - $quality) * $amplitude);
	    			$smpl += rand(-($thresh+1), $thresh);
	    		}
	    		
	    		$filtered .= $this->$packFunction($smpl);
    		}
    		    		
    		$this->setSample($s, $filtered);
    	}
    }
    
    /**
     * Mix 2 wav files together<br />
     * Both wavs must have the same sample rate and same number of channels
     * 
     * @param WavFile $wav The WavFile to mix
     * @throws Exception
     */
    public function mergeWav(WavFile $wav) {
        if ($wav->getSampleRate() != $this->getSampleRate()) {
            throw new Exception("Sample rate for wav files do not match");
        } else if ($wav->getNumChannels() != $this->getNumChannels()) {
            throw new Exception("Number of channels for wav files does not match");
        }

        $numSamples = $this->getNumSamples();
        $numChannels = $this->getNumChannels();
        $bitDepth    = $this->getBitsPerSample();
        $packFunction = "packSample{$bitDepth}bit";
        
        if ($bitDepth == 32) $packFunction .= 'f';

        for ($s = 0; $s < $numSamples; ++$s) {
            $sample1 = $this->getSample($s);
            $sample2 = $wav->getSample($s);

            // TODO: option to extend/rewind buffer to extend to for the longest wav
            if ($sample1 == null || $sample2 == null) break;

            $sample = '';

            for ($c = 1; $c <= $numChannels; ++$c) {
                $c1 = $this->getChannelData($sample1, $c);
                $c2 = $this->getChannelData($sample2, $c);

                $smpl = $this->mixSample($c1, $c2, $bitDepth, 0.75);
                
                $sample .= $this->$packFunction($smpl);
            }

            //$this->_samples[$s] = $sample;
            $this->setSample($s, $sample);
        }

        return $this;
    }
    
    /**
    * Mixes two wav audio samples. <br />
    * The samples are signed, except for 8-Bit and below. 32-Bit samples are assumed to be floats.
    * 
    * @param int $a 1st sample
    * @param int $b 2nd sample
    * @param int $bitdepth Bit depth of $a and $b (8, 16, 24, 32)
    * @param float $threshold The threshold for normalizing the amplitude <br />
    *     null - Amplitudes are normalized by dividing by 2, i.e. loss of loudness by 6 dB for each individual sample. <br />
    *     [0, 1) - (open inverval - not including 1) - The threshold relative to the maximum amplitude of the result, <br />
    *         above which amplitudes are comressed logarithmically. <br />
    *         e.g. 0.6 to leave amplitudes up to 60% "as is" and compress above. <br />
    *     (-1, 0) - (open inverval - not including 0 and -1) - The negative of the threshold relative to the maximum amplitude of the result, <br />
    *         above which amplitudes are comressed linearly. <br />
    *         e.g. -0.6 to leave amplitudes up to 60% "as is" and compress above. <br />
    *     1 - Amplitudes above maximum range are clipped (-1 has the same effect). <br />
    *     >1 - Normalize by dividing by $threshold (<-1 has the same effect - divide by abs($threshold)).
    * @return int|float The mixed and normalized sample
    **/
    protected function mixSample($a, $b, $bitdepth, $threshold = null) {
        // log base modifier lookup table for a given threshold (in 0.05 steps)
        // adjusts the slope (1st derivative) of the log function at the threshold to 1 for a smooth transition from linear to logarithmic amplitude output.
        $lookup = array(2.513, 2.667, 2.841, 3.038, 3.262,
                        3.520, 3.819, 4.171, 4.589, 5.093,
                        5.711, 6.487, 7.483, 8.806, 10.634,
                        13.302, 17.510, 24.970, 41.155, 96.088);

        // project values into [-1, 1] - convert to normalized float
        if ($bitdepth < 32) {
            $p = (1 << $bitdepth); // 2 to the power of $bitdepth
            // convert 8-bit and below to signed values
            if ($bitdepth <= 8) {
                $a -= $p / 2;
                $b -= $p / 2;
            }
            $a /= $p / 2;
            $b /= $p / 2;
        }
    
        // mix
        $c = $a + $b;
    
        // normalization
        if (is_null($threshold)) {
            // normalitze by dividing by 2 - results in a loss of about 6dB in volume
            $c /= 2;
        } elseif (abs($threshold) > 1) {
            // normalize by the divisor given
            $c /= abs($threshold);
        } elseif ($threshold > -1 && $threshold < 0 && abs($c) > abs($threshold)) {
            //linear compression
            $threshold = abs($threshold);
            $sign = $c < 0 ? -1 : 1;  //sign of $c (positive or negative)
            $c = $sign * ($threshold + (1 - $threshold) / (2 - $threshold) * (abs($c) - $threshold));
        } elseif ($threshold >= 0 && $threshold < 1 && abs($c) > $threshold) {
            // logarithmic compression
            $loga = $lookup[(int)($threshold * 20)]; // log base modifier
            $c = ($c < 0 ? -1 : 1) * ($threshold + (1 - $threshold) * log(1 + $loga * (abs($c) - $threshold) / (2 - $threshold)) / log(1 + $loga));
            /* version for future runtime optimization
            $srange = (2 - $threshold); // source range (relative)
            $drange = (1 - $threshold); // destination range (relative)
            $loga = $lookup[(int)($threshold * 20)]; // log base modifier
            $logbase = log(1 + $loga); // base for the log function
            $mult = $drange / $logbase; // for simplicity
            $sign = $c < 0 ? -1 : 1;  // sign of $c (positive or negative)
            $c = $sign * ($threshold + $mult * log(1 + $loga * (abs($c) - $threshold) / $srange));*/
        } // else values get clipped ($threshold == 1 || $threshold == -1)
    
        if ($bitdepth < 32) {
            // project values back to [-$p/2, $p/2-1] (actually the open interval [-$p/2, -$p/2))
            $c *= $p / 2;
        
            // quantize float back to integer values (and clip if necessary)
            $c = (int)min($p / 2 - 1, max(-$p / 2, round($c)));
    
            // convert 8-bit and below back to unsigned values
            if ($bitdepth <= 8) {
                $c += $p / 2;
            }
        } else {
            // clip 32-bit floats if necessary
            $c = min(1, max(-1, $c));
        }
    
        return $c;
    }
        
    /**
     * Add silence to the end of the wav file
     * 
     * @param float $duration   How many seconds of silence
     */
    public function insertSilence($duration = 1.0)
    {
        $numSamples  = $this->getSampleRate() * $duration;
        $numChannels = $this->getNumChannels();
        
        $sample      = $this->packSample(0);
        
        $this->_samples .= str_repeat($sample, $numSamples * $numChannels);
    }

    /**
     * Degrade the quality of the wav file by a random intensity
     * 
     * @param float quality  Decrease the quality from 1.0 to 0 where 1 = no distortion, 0 = max distortion range
     * @todo degrade only a portion of the audio 
     */
    public function degrade($quality = 1.0)
    {
        if ($quality < 0 || $quality > 1) $quality = 1;

        if ($quality == 1.0) {
            // nothing to do
            return ;
        }

        $numSamples   = $this->getNumSamples();
        $numChannels  = $this->getNumChannels();
        $maxAmp       = $this->getAmplitude();
        $maxThresh    = (int)((1 - $quality) * $maxAmp);
        $bitDepth     = $this->getBitsPerSample();
        $packFunction = "packSample{$bitDepth}bit";
        
        if ($bitDepth == 32) $packFunction .= 'f';
        
        
        for ($s = 0; $s < $numSamples; ++$s) {
        	$degraded = '';
        	$sample   = $this->getSample($s);
        	
        	for ($channel = 0; $channel < $numChannels; ++$channel) {
        		$c = $this->getChannelData($sample, $channel+1);
        		$c += rand(-($maxThresh+1), $maxThresh);
        		$degraded .= $this->$packFunction($c);
        	}
        	
        	$this->setSample($s, $degraded);
        }
        /*
        foreach($this->_samples as $index => $sample) {
            $degraded = '';

            for ($channel = 0; $channel < $numChannels; ++$channel) {
                $c = $this->getChannelData($sample, $channel+1);
                $c += rand(-($maxThresh+1), $maxThresh);
                $degraded .= $this->packSample($c);
            }

            $this->_samples[$index] = $degraded;
        }
        */
    }
        
    /**
     * Generate white noise at the end of the Wav for the specified duration and volume
     * 
     * @param float $duration  Number of seconds of noise to generate
     * @param float $percent   The percentage of the maximum amplitude to use 100% = full amplitude
     */
    public function generateNoise($duration = 1.0, $percent = 100)
    {
        $numChannels = $this->getNumChannels();
        $numSamples  = $this->getSampleRate() * $duration;
        $minAmp      = $this->getMinAmplitude();
        $maxAmp      = $this->getAmplitude();

        for ($s = 0; $s < $numSamples; ++$s) {
            $sample = '';
            $val = rand($minAmp, $maxAmp);
            $val = (int)($val * $percent / 100);
            for ($channel = 0; $channel < $numChannels; ++$channel) {
                $sample .= $this->packSample($val);
            }

            $this->_samples[] = $sample;
        }
    }

    /**
     * Save the wav data to a file
     * 
     * @param string $filename The file to save the wav to
     */
    public function save($filename)
    {
        $fp = @fopen($filename, 'w+b');
        
        if (!$fp) {
            throw new Exception("Failed to open " . htmlspecialchars($filename) . " for writing");
        }
        
        fwrite($fp, $this->makeHeader());
        fwrite($fp, $this->getDataSubchunk());
        fclose($fp);
        
        return $this;
    }
    
    /**
     * Get the character for php's pack() function used to encode samples
     * 
     * @return string the character or character sequence
     * @throws Exception
     */
    protected function getPackFormatString()
    {
        switch($this->getBitsPerSample()) {
            case 8:
                return 'C'; // unsigned char

            case 16:
                return 'v'; // unsigned short, little endian - still needs conversion to signed short

            case 24:
                return 'C3'; // 3 unsigned chars, little endian order - still needs conversion to signed 24-bit integer 

            case 32:
                // TODO: 64-bit PHP?
                return 'f'; // float (32-bit)
        }
        
        throw new Exception("Invalid bits per sample");
    }
    
    function packSample8bit($value)
    {
    	$fmt = 'C';
    	// unsigned char
    	return pack($fmt, $value);
    }
    
    function packSample16bit($value)
    {
    	$fmt = 'v';
    	// signed short, little endian
    	if ($value < 0) {
    		$value += 0x10000;
    	}
    	return pack($fmt, $value);
    }
    
    function packSample24bit($value)
    {
    	$fmt = 'C3';
    	// 3 byte packed signed integer, little endian
    	if ($value < 0) {
    		$value += 0x1000000;
    	}
    	return pack($fmt, $value & 0xff,
    			($value >>  8) & 0xff,
    			($value >> 16) & 0xff);
    }
    
    function packSample32bitf($value)
    {
    	$fmt = 'f';
    	return pack($fmt, $value);
    }
    
    /**
     * Pack a numeric sample to binary using the correct bits per sample
     * 
     * @param int|float $value The sample to encode
     */
    protected function packSample($value)
    {
        switch ($this->getBitsPerSample()) {
            case 8:
                return $this->packSample8bit($value);

            case 16:
                return $this->packSample16bit($value);

            case 24:
                return $this->packSample24bit($value);

            case 32:
                return $this->packSample32bitf($value);
        }
    }
    
    /**
     * Read wav file from a stream
     * 
     * @throws WavFormatException
     * @throws Exception
     */
    protected function readWav()
    {
        try {
            $this->getWavInfo();
        } catch (WavFormatException $wex) {
            fclose($this->_fp);
            throw $wex;
        } catch (Exception $ex) {
            fclose($this->_fp);
            throw $ex;
        }
        
        $this->readWavData();
        
        // TODO: read any extra data chunks
        
        fclose($this->_fp);
    }
    
    /**
     * Parse a wav header
     * 
     * @throws Exception
     * @throws WavFormatException WavFormatException occurs if the header or data is malformed
     */
    protected function getWavInfo()
    {
        if (!$this->_fp) {
            throw new Exception("No wav file open");
        }

        $wavHeaderSize = 36; // size of the wav header
        
        $header = fread($this->_fp, $wavHeaderSize);
        
        if (strlen($header) < $wavHeaderSize) {
            throw new WavFormatException('Not wav format, header too short', 1);
        }
        
        $RIFF = unpack('NChunkID/VChunkSize/NFormat', $header);
        
        if ($RIFF['ChunkID'] != 0x52494646) {
            throw new WavFormatException('Not wav format, RIFF signature missing', 2);
        }
        
        $stat       = fstat($this->_fp);
        $actualSize = $stat['size'];
        
        if ($actualSize - 8 != $RIFF['ChunkSize']) {
            //echo "$actualSize {$RIFF['ChunkSize']}\n";
            trigger_error("Bad chunk size, does not match actual file size ($actualSize {$RIFF['ChunkSize']})", E_USER_NOTICE);
            //throw new WavFormatException('Bad chunk size, does not match actual file size', 4);
        }

        if ($RIFF['Format'] != 0x57415645) {
            throw new WavFormatException('Not wav format, RIFF format not WAVE', 5);
        }
        
        $this->setSize($RIFF['ChunkSize'])
             ->setActualSize($actualSize);

        $fmt = unpack('NSubChunk1ID/VSubChunk1Size/vAudioFormat/vNumChannels/'
                     .'VSampleRate/VByteRate/vBlockAlign/vBitsPerSample',
                     substr($header, 12, 26));
                     
        if ($fmt['SubChunk1ID'] != 0x666d7420) {
            throw new WavFormatException('Bad wav header, expected fmt, found ' . $fmt['SubChunk1ID'], 6);
        }
        
        $this->setSubChunk1Size($fmt['SubChunk1Size']);
        
        if ($fmt['AudioFormat'] != 1) {
            throw new WavFormatException('Not PCM audio, non PCM is not supported', 7);
        }
        
        $this->setFormat('PCM')
             ->setNumChannels($fmt['NumChannels'])
             ->setSampleRate($fmt['SampleRate'])
             ->setBitsPerSample($fmt['BitsPerSample'])
             ->setByteRate($fmt['ByteRate'])
             ->setSampleRate($fmt['SampleRate'])
             ->setBlockAlign($fmt['BlockAlign']);
                
        if ($this->getSubChunk1Size() > 16) {
            $epSize          = fread($this->_fp, 2);
            $extraParamsSize = unpack('vSize', $epSize);
            if ($extraParamsSize['Size'] > 0) {
                $extraParams     = fread($this->_fp, $extraParamsSize['Size']);
            }
            
            $wavHeaderSize  += ($extraParamsSize['Size'] - 16);
        }

        $dataHeader = fread($this->_fp, 8);
        $data       = unpack('NSubchunk2ID/VSubchunk2Size', $dataHeader);
        
        if ($data['Subchunk2ID'] != 0x64617461) {
            throw new WavFormatException('Data chunk expected, found ' . $data['Subchunk2ID'], 8);
        }
        
        $this->setDataSize($data['Subchunk2Size'])
             ->setDataOffset($wavHeaderSize + 8);
        
        return $this;
    }
    
    /**
     * Read the wav data into buffer
     */
    protected function readWavData()
    {
        $this->_samples = fread($this->_fp, $this->getDataSize());
        //$this->_samples = str_split($samples, $this->getBlockAlign());
    }
    
    /**
     * Read a sample from stream and append to buffer
     */
    protected function readSample()
    {
        $sample = fread($this->_fp, $this->getBlockAlign());
        
        $this->_samples[] = $sample;
        
        return $sample;
    }
    
    public function __set($name, $value)
    {
        $method = 'set' . $name;
        
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            throw new Exception("No such property '$name' exists");
        }
    }
    
    public function __get($name) {
        $method = 'get' . $name;
        
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            throw new Exception("No such property '$name' exists");
        }
    }
    
    /**
     * Get data from one or more channels in a sample
     * @param int $sample The sample number to read from
     * @param int $channel The channel to get, or 0 for all
     * @return int|float|array
     */
    protected function getChannelData(&$sample, $channel = 0)
    {
        $channels = array();
        $csize = $this->getBitsPerSample() / 8;
        $numChannels = $this->getNumChannels();
        $bitDepth = $this->_bitsPerSample;
        $packChr = $this->getPackFormatString();
        
        $ch = $channel;
        
        for ($i = 1; $i <= self::MAX_CHANNEL; ++$i) {
            if ($i - 1 > $numChannels) break;
            
            if ($ch == 0 || $i == $ch) {
                $cdata = substr($sample, ($i - 1) * $csize, $csize);
                $data  = unpack($packChr, $cdata);
                
                switch ($bitDepth) {
                    case 8:
                        // unsigned char
                        $smpl = (int)$data[1];
                        break;

                    case 16:
                        // signed short
                        $smpl = (int)$data[1];
                        if ($smpl >= 0x8000) {
                            $smpl -= 0x10000;
                        }
                        break;

                    case 24:
                        // signed 3 byte integer
                        $smpl = (int)$data[1] | ((int)$data[2] << 8) | ((int)$data[3] << 16);
                        if ($smpl >= 0x800000) {
                            $smpl -= 0x1000000;
                        }
                        break;

                    case 32:
                        // 32-bit float
                        // TODO: 64-bit PHP?
                        $smpl = (float)$data[1];
                        break;
                }

                $channels[$i] = $smpl;
            }
        }
        
        if (sizeof($channels) == 1) {
            return array_shift($channels);
        } else {
            return $channels;
        }
    }
    
    /**
     * Output the wav file headers and data
     * 
     * @return string The encoded file
     */
    public function __toString()
    {
        return $this->makeHeader() .
               $this->getDataSubchunk();
    }
    
    /**
     * Output information about the wav object
     */
    public function displayInfo()
    {
        $s = "File Size: %u\n"
            ."Audio Format: %s\n"
            ."Sub Chunk 1 Size: %u\n"
            ."Channels: %u\n"
            ."Byte Rate: %u\n"
            ."Sample Size: %u\n"
            ."Sample Rate: %u\n"
            ."Bits Per Sample: %u\n";
            
        $s = sprintf($s, $this->getActualSize(),
                         $this->getFormat(),
                         $this->getSubChunk1Size(),
                         $this->getNumChannels(),
                         $this->getByteRate(),
                         $this->getBlockAlign(),
                         $this->getSampleRate(),
                         $this->getBitsPerSample());
                         
        if (php_sapi_name() == 'cli') {
            return $s;
        } else {
            return nl2br($s);
        }
    }
}

/**
 * WavFormatException indicates malformed wav header, or data or unsupported options
 *
 */
class WavFormatException extends Exception
{
    public function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

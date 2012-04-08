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

    /** @var int Filter flag for normalizing audio data */
    const FILTER_NORMALIZE = 0x04;

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

    /** @var array Log base modifier lookup table for a given threshold (in 0.05 steps) used by normalizeFloatSample. Adjusts the slope (1st derivative) of the log function at the threshold to 1 for a smooth transition from linear to logarithmic amplitude output. */
    protected $_logbase_lookup = array(
        2.513, 2.667, 2.841, 3.038, 3.262,
        3.520, 3.819, 4.171, 4.589, 5.093,
        5.711, 6.487, 7.483, 8.806, 10.634,
        13.302, 17.510, 24.970, 41.155, 96.088
    );

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
        $this->_blockAlign    = 1;
        $this->_sampleRate    = 11025;
        $this->_bitsPerSample = 8;
        $this->_numChannels   = 1;
        $this->_dataSize      = 0;

        if ($params instanceof WavFile) {
            foreach ($params as $prop => &$val) {
                $this->$prop = $val;
            }
            unset($val);
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
                 ->setBitsPerSample($bitsPerSample);
        }
    }

    public function getSize() {
        return $this->_size;
    }

    protected function setSize($size) {
        $this->_size = $size;
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

    public function setSubChunk1Size($subChunk1Size) {
        $this->_subChunk1Size = $subChunk1Size;
        return $this;
    }

    public function getNumChannels() {
        return $this->_numChannels;
    }

    public function setNumChannels($numChannels) {
        $this->_numChannels = $numChannels;
        $this->setByteRate();
        $this->setBlockAlign();
        return $this;
    }

    public function getSampleRate() {
        return $this->_sampleRate;
    }

    public function getByteRate() {
        return $this->_byteRate;
    }

    public function setByteRate($byteRate = null) {
        if (is_null($byteRate)) {
            $this->_byteRate = $this->_sampleRate * $this->_numChannels * $this->_bitsPerSample / 8;
        } else {
            $this->_byteRate = $byteRate;
        }
        return $this;
    }

    public function getBlockAlign() {
        return $this->_blockAlign;
    }

    protected function setBlockAlign($blockAlign = null) {
        if (is_null($blockAlign)) {
            $this->_blockAlign = $this->_numChannels * ($this->_bitsPerSample / 8);
        } else {
            $this->_blockAlign = $blockAlign;
        }
        return $this;
    }

    public function setSampleRate($sampleRate) {
        $this->_sampleRate = $sampleRate;
        $this->setByteRate();
        return $this;
    }

    public function getBitsPerSample() {
        return $this->_bitsPerSample;
    }

    public function setBitsPerSample($bitsPerSample) {
        if (!in_array($bitsPerSample, array(8, 16, 24, 32))) {
            throw new Exception('Unsupported bits per sample. Only 8, 16, 24 and 32 bits are supported.');
        }
        $this->_bitsPerSample = $bitsPerSample;
        $this->setByteRate();
        $this->setBlockAlign();
        return $this;
    }

    public function getDataSize() {
        return $this->_dataSize;
    }

    public function setDataSize($dataSize) {
        $this->_dataSize = $dataSize;
        return $this;
    }

    public function getDataOffset() {
        return $this->_dataOffset;
    }

    public function setDataOffset($dataOffset) {
        $this->_dataOffset = $dataOffset;
        return $this;
    }

    public function getSamples() {
        return $this->_samples;
    }

    public function setSamples($samples) {
        $this->_samples = $samples;
        $this->setDataSize(strlen($samples));
        return $this;
    }

    public function getNumSamples()
    {
        if ($this->_blockAlign == 0) {
            return 0;
        } else {
            return $this->_dataSize / $this->_blockAlign;
        }
    }

    public function getAmplitude()
    {
        if($this->_bitsPerSample == 8) {
            return 255;
        } elseif($this->_bitsPerSample == 32) {
            return 1;
        } else {
            return (1 << ($this->_bitsPerSample - 1)) - 1;
        }
    }

    public function getMinAmplitude()
    {
        if ($this->getBitsPerSample() == 8) {
            return 0;
        } elseif ($this->getBitsPerSample() == 32) {
            return -1;
        } else {
            return -(1 << ($this->_bitsPerSample - 1));
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
        $header .= pack('v', $this->getBitsPerSample() == 32 ? 3 : 1); // 1 - integer PCM, 3 - float PCM
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
               pack('V', $this->_dataSize) .
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
     * @return string  The binary sample
     */
    public function getSample($sampleNum)
    {
        $offset = $sampleNum * $this->_blockAlign;
        if ($offset + $this->_blockAlign > $this->_dataSize) {
            return null;
        } else {
            return substr($this->_samples, $offset, $this->_blockAlign);
        }
    }

    /**
     *
     * @param int $sampleNum
     * @param string $sample
     * @throws Exception
     */
    public function setSample($sampleNum, $sample)
    {
        if (strlen($sample) != $this->_blockAlign) {
            throw new Exception('Incorrect sample size.  Was ' . strlen($sample) . ' expected ' . $this->_blockAlign);
        }

        $numSamples = $this->_dataSize / $this->_blockAlign;
        $offset     = $sampleNum * $this->_blockAlign;

        if ($sampleNum > $numSamples) {
               throw new Exception('Sample was outside the range of the wav file, use append.');
        } elseif ($sampleNum == $numSamples) {
            $this->_samples .= $sample;
            $this->_dataSize += $this->_blockAlign;
        } else {
            for ($i = 0; $i < $this->_blockAlign; ++$i) {
                $this->_samples{$offset + $i} = $sample{$i};
            }
        }

        return $this;
    }

    /**
     * Unpack a binary sample to numeric value using the correct bits per sample
     *
     * @param string $sampleBinary  The channel sample to decode
     * @param int $bitDepth  The bits per sample to decode
     * @return int|float  The numeric sample value
     */
    public static function unpackSampleChannel($sampleBinary, $bitDepth = null)
    {
        if (is_null($bitDepth)) {
            $bitDepth = strlen($sampleBinary) * 8;
        }

        switch ($bitDepth) {
            case 8:
                // unsigned char
                return ord($sampleBinary);

            case 16:
                // signed short, little endian
                $data = unpack('v', $sampleBinary);
                $value = $data[1];
                if ($value >= 0x8000) {
                    $value -= 0x10000;
                }
                return $value;

            case 24:
                // 3 byte packed signed integer, little endian
                $data = unpack('C3', $sampleBinary);
                $value = $data[1] | ($data[2] << 8) | ($data[3] << 16);
                if ($value >= 0x800000) {
                    $value -= 0x1000000;
                }
                return $value;

            case 32:
                // 32-bit float
                // TODO: 64-bit PHP?
                return unpack('f', $sampleBinary);

            default:
                return null;
        }
    }

    /**
     * Pack a numeric sample to binary using the correct bits per sample
     *
     * @param int|float $value  The sample to encode
     * @param int bitDepth  The bits per sample to encode with
     * @return string  The encoded binary sample
     */
    public static function packSampleChannel($value, $bitDepth)
    {
        switch ($bitDepth) {
            case 8:
                // unsigned char
                return chr($value);

            case 16:
                // signed short, little endian
                if ($value < 0) {
                    $value += 0x10000;
                }
                return pack('v', $value);

            case 24:
                // 3 byte packed signed integer, little endian
                if ($value < 0) {
                    $value += 0x1000000;
                }
                return pack('C3', $value & 0xff, ($value >>  8) & 0xff, ($value >> 16) & 0xff);

            case 32:
                // 32-bit float
                // TODO: 64-bit PHP?
                return pack('f', $value);

            default:
                return null;
        }
    }

    /**
     * Get a sample value for a specific sample number and channel.
     *
     * @param int $sampleNum  The sample number to fetch
     * @param int $channelNum  The channel number within the sample to fetch
     * @param bool $asFloat  Returns the sample value as a normalized float value
     * @return int|float  The channel sample as a value
     * @throws Exception
     */
    public function getSampleChannel($sampleNum, $channelNum, $asFloat = false)
    {
        // check preconditions
        if ($channelNum < 1 || $channelNum > $this->_numChannels) {
            throw new Exception('Channel number was outside the range.');
        }

        $sampleBytes = $this->_bitsPerSample / 8;
        $offset = $sampleNum * $this->_blockAlign + ($channelNum - 1) * $sampleBytes;
        if ($offset + $sampleBytes > $this->_dataSize) {
            return null;
        }

        // read binary sample
        $sampleBinary = substr($this->_samples, $offset, $sampleBytes);

        // convert binary to value
        //$value = self::unpackSampleChannel($sampleBinary, $this->_bitsPerSample);
        switch ($this->_bitsPerSample) {
            case 8:
                // unsigned char
                $value = ord($sampleBinary);
                break;

            case 16:
                // signed short, little endian
                $data = unpack('v', $sampleBinary);
                $value = $data[1];
                if ($value >= 0x8000) {
                    $value -= 0x10000;
                }
                break;

            case 24:
                // 3 byte packed signed integer, little endian
                $data = unpack('C3', $sampleBinary);
                $value = $data[1] | ($data[2] << 8) | ($data[3] << 16);
                if ($value >= 0x800000) {
                    $value -= 0x1000000;
                }
                break;

            case 32:
                // 32-bit float
                // TODO: 64-bit PHP?
                $data = unpack('f', $sampleBinary);
                $value = $data[1];
                break;
        }

        // convert to float
        if ($asFloat && $this->_bitsPerSample != 32) {
            return $value / (1 << ($this->_bitsPerSample - 1));
        }

        return $value;
    }

    /**
     * Sets a sample value for a specific sample number and channel. <br />
     * Converts float values to appropriate integer values and clips properly. <br />
     * Allows to append channel samples in order.
     *
     * @param int|float $value  The integer of float sample value to set
     * @param int $sampleNum  The sample number to set or append
     * @param int $channelNum  The channel number within the sample to set or append
     * @throws Exception
     */
    public function setSampleChannel($value, $sampleNum, $channelNum)
    {
        // check preconditions
        if ($channelNum < 1 || $channelNum > $this->_numChannels) {
            throw new Exception('Channel number was outside the range.');
        }

        $sampleBytes = $this->_bitsPerSample / 8;
        $offset = $sampleNum * $this->_blockAlign + ($channelNum - 1) * $sampleBytes;
        if ($offset < 0 || ($offset + $sampleBytes > $this->_dataSize && $offset != $this->_dataSize)) { // allow appending
            throw new Exception('Sample or channel number was outside the range.');
        }

        //convert to value, quantize and clip
        if (is_float($value)) {
            if ($this->_bitsPerSample == 32) {
                // clip float values if necessary to [-1, 1]
                if ($value > 1) {
                    $value = 1;
                } elseif ($value < -1) {
                    $value = -1;
                }
            } else {
                $p = 1 << ($this->_bitsPerSample - 1); // 2 to the power of _bitsPerSample divided by 2

                // project values to [-$p, $p]
                $value = $value * $p;

                // quantize (round) float back to integer values
                $value = $value < 0 ? (int)($value - 0.5) : (int)($value + 0.5);
                // and clip if necessary to [-$p, $p - 1]
                if ($value < -$p) {
                    $value = -$p;
                } elseif ($value > $p - 1) {
                    $value = $p - 1;
                }
            }
        }

        // convert to binary
        //$sampleBinary = self::packSampleChannel($value, $this->_bitsPerSample);
        switch ($this->_bitsPerSample) {
            case 8:
                // unsigned char
                $sampleBinary = chr($value);

            case 16:
                // signed short, little endian
                if ($value < 0) {
                    $value += 0x10000;
                }
                $sampleBinary = pack('v', $value);

            case 24:
                // 3 byte packed signed integer, little endian
                if ($value < 0) {
                    $value += 0x1000000;
                }
                $sampleBinary = pack('C3', $value & 0xff, ($value >>  8) & 0xff, ($value >> 16) & 0xff);
                break;

            case 32:
                // 32-bit float
                // TODO: 64-bit PHP?
                $sampleBinary = pack('f', $value);
                break;
        }

        if ($offset == $this->_dataSize) {
            // append
            $this->_samples .= $sampleBinary;
            $this->_dataSize += $this->_bitsPerSample / 8;
        } else {
            // replace
            for ($i = 0; $i < $sampleBytes; ++$i) {
                $this->_samples{$offset + $i} = $sampleBinary{$i};
            }
        }
    }

    /**
    * Normalizes a float audio sample.
    *
    * @param float $sampleFloat  The sample to normalize
    * @param float $threshold  The threshold for normalizing the amplitude <br />
    *     null - Amplitudes are normalized by dividing by 2, i.e. loss of loudness by 6 dB. <br />
    *     [0, 1) - (open inverval - not including 1) - The threshold <br />
    *         above which amplitudes are comressed logarithmically (from $threshold to 2). <br />
    *         e.g. 0.6 to leave amplitudes up to 60% "as is" and compress above. <br />
    *     (-1, 0) - (open inverval - not including 0 and -1) - The negative of the threshold <br />
    *         above which amplitudes are comressed linearly (from $threshold to 2). <br />
    *         e.g. -0.6 to leave amplitudes up to 60% "as is" and compress above. <br />
    *     >= 1 - Normalize by dividing by $threshold.
    * @return float  The normalized sample
    **/
    protected function normalizeSample($sampleFloat, $threshold = null) {
        // normalitze by dividing by 2 - results in a loss of about 6dB in volume
        if (is_null($threshold)) {
            return $sampleFloat / 2;
        }

        // normalize by the divisor given
        if ($threshold > 1) {
            return $sampleFloat / $threshold;
        }

        $sign = $sampleFloat < 0 ? -1 : 1;
        $sampleAbs = abs($sampleFloat);

        // logarithmic compression
        if ($threshold >= 0 && $threshold < 1 && $sampleAbs > $threshold) {
            $loga = $this->_logbase_lookup[(int)($threshold * 20)]; // log base modifier
            return $sign * ($threshold + (1 - $threshold) * log(1 + $loga * ($sampleAbs - $threshold) / (2 - $threshold)) / log(1 + $loga));
        }

        // linear compression
        $thresholdAbs = abs($threshold);
        if ($threshold > -1 && $threshold < 0 && $sampleAbs > $thresholdAbs) {
            return $sign * ($thresholdAbs + (1 - $thresholdAbs) / (2 - $thresholdAbs) * ($sampleAbs - $thresholdAbs));
        }

        // else sample is untouched and has to be clipped later ($threshold == 1 || $threshold <= -1)
        return $sampleFloat;
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

        $this->_samples .= $wav->_samples;
        $this->setDataSize(strlen($this->_samples));

        return $this;
    }

    public function filter($filters, $options = array())
    {
        // check preconditions
        $filters = (int)$filters;
        if ($filters <= 0) {
            throw new Exception('No filters provided');
        }
        $numSamples  = $this->getNumSamples();
        $numChannels = $this->getNumChannels();

        // initialize options
        $wavMix = isset($options['filter_mix']) ? $options['filter_mix'] : null;
        $degradeQuality = isset($options['filter_degrade']) ? (float)$options['filter_degrade'] : 1;
        $threshold = isset($options['filter_normalize']) ? $options['filter_normalize'] : null;

        // check options
        if ($filters & self::FILTER_MIX) {
            if (!($wavMix instanceof WavFile)) {
                throw new Exception("WavFile to mix is missing or invalid");
            } elseif ($wavMix->getSampleRate() != $this->getSampleRate()) {
                throw new Exception("Sample rate for wav files do not match");
            } else if ($wavMix->getNumChannels() != $this->getNumChannels()) {
                throw new Exception("Number of channels for wav files does not match");
            }
        }
        if ($filters & self::FILTER_DEGRADE) {
            if ($degradeQuality < 0 || $degradeQuality >= 1) {
                // nothing to do
                $filters -= self::FILTER_DEGRADE;
            }
        }


        // loop through all samples
        for ($s = 0; $s < $numSamples; ++$s) {
            // loop through all channels
            for ($c = 1; $c <= $numChannels; ++$c) {
                // read current channel value
                $sampleFloat = $this->getSampleChannel($s, $c, true);


                /************* MIX FILTER ***********************/
                if ($filters & self::FILTER_MIX) {
                    $sampleFloat += $wavMix->getChannelSample($s, $c, true);
                }

                /************* DEGRADE FILTER *******************/
                if ($filters & self::FILTER_DEGRADE) {
                    $sampleFloat += rand(0, 1000000 * (1 - $degradeQuality)) / 1000000;
                }

                /************* NORMALIZE FILTER *******************/
                if ($filters & self::FILTER_NORMALIZE) {
                    $sampleFloat = $this->normalizeSample($sampleFloat, $threshold);
                }


                // write current channel value
                $this->setSampleChannel($sampleFloat, $s, $c);
            }
        }

        return $this;
    }

    /**
     * Mix 2 wav files together<br />
     * Both wavs must have the same sample rate and same number of channels
     *
     * @param WavFile $wav  The WavFile to mix
     * @param float $normalizeThreshold  See normalizeSample for explanation
     * @throws Exception
     */
    public function mergeWav(WavFile $wav, $normalizeThreshold = null) {
        return $this->filter(self::FILTER_MIX | self::FILTER_NORMALIZE, array(
            'filter_mix' => $wav,
            'filter_normalize' => $normalizeThreshold
        ));
    }

    /**
     * Add silence to the end of the wav file
     *
     * @param float $duration  How many seconds of silence
     */
    public function insertSilence($duration = 1.0)
    {
        $numSamples  = $this->getSampleRate() * $duration;
        $numChannels = $this->getNumChannels();

        $this->_samples .= str_repeat(self::packSampleChannel(0, $this->getBitsPerSample()), $numSamples * $numChannels);
        $this->setDataSize(strlen($this->_samples));

        return $this;
    }

    /**
     * Degrade the quality of the wav file by a random intensity
     *
     * @param float quality  Decrease the quality from 1.0 to 0 where 1 = no distortion, 0 = max distortion range
     * @todo degrade only a portion of the audio
     */
    public function degrade($quality = 1.0)
    {
        return $this->filter(self::FILTER_DEGRADE, array(
            'filter_degrade' => $quality
        ));
    }

    /**
     * Generate white noise at the end of the Wav for the specified duration and volume
     *
     * @param float $duration  Number of seconds of noise to generate
     * @param float $percent  The percentage of the maximum amplitude to use 100% = full amplitude
     */
    public function generateNoise($duration = 1.0, $percent = 100)
    {
        $numChannels = $this->getNumChannels();
        $numSamples  = $this->getSampleRate() * $duration;
        $minAmp      = $this->getMinAmplitude();
        $maxAmp      = $this->getAmplitude();
        $bitDepth    = $this->getBitsPerSample();

        for ($s = 0; $s < $numSamples; ++$s) {
            if ($bitDepth == 32) {
                $val = rand(-$percent * 10000, $percent * 10000) / 1000000;
            } else {
                $val = rand($minAmp, $maxAmp);
                $val = (int)($val * $percent / 100);
            }

            $this->_samples .= str_repeat(self::packSampleChannel($val, $bitDepth), $numChannels);
        }
    }

    /**
     * Save the wav data to a file
     *
     * @param string $filename  The file to save the wav to
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
     * @throws WavFormatException  WavFormatException occurs if the header or data is malformed
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

        if ($fmt['AudioFormat'] != 1 && $fmt['AudioFormat'] != 3) {
            throw new WavFormatException('Not PCM audio, non PCM is not supported', 7);
        } elseif ($fmt['AudioFormat'] == 1 && !in_array($fmt['BitsPerSample'], array(8, 16, 24))) {
            throw new WavFormatException('Only 8, 16 and 24-bit PCM audio is supported', 8);
        } elseif ($fmt['AudioFormat'] == 3 && $fmt['BitsPerSample'] != 32) {
        	throw new WavFormatException('Only 32-bit float PCM audio is supported', 9);
        }

        $this->setFormat('PCM')
             ->setNumChannels($fmt['NumChannels'])
             ->setSampleRate($fmt['SampleRate'])
             ->setBitsPerSample($fmt['BitsPerSample']);

        if ($this->getByteRate() != $fmt['ByteRate']) {
            throw new WavFormatException('Invalid ByteRate value in wav header, expected ' . $this->getByteRate() . ', found ' . $fmt['ByteRate'], 10);
        }
        if ($this->getBlockAlign() != $fmt['BlockAlign']) {
            throw new WavFormatException('Invalid BlockAlign value in wav header, expected ' . $this->getBlockAlign() . ', found ' . $fmt['BlockAlign'], 11);
        }

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
            throw new WavFormatException('Data chunk expected, found ' . $data['Subchunk2ID'], 12);
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

        $this->_samples .= $sample;

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

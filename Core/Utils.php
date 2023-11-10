<?php

/**
 * This file is part of simple-web3-php package.
 * 
 * (c) Alex Cabrera  
 * 
 * @author Alex Cabrera
 * @license MIT
 * 
 * This file is a modified part based on original code: web3.php package.
 * 
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 * 
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

namespace SWeb3;

use stdClass;
use InvalidArgumentException;  
use kornrunner\Keccak;
use Brick\Math\BigNumber;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

class Utils
{
    const PRICE_DECIMAL_PRECISION = 9;

    /**
     * SHA3_NULL_HASH
     * 
     * @const string
     */
    const SHA3_NULL_HASH = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';

    /**
     * UNITS
     * from ethjs-unit
     * 
     * @const array
     */
    const UNITS = [
        'noether' => '0',
        'wei' => '1',
        'kwei' => '1000',
        'Kwei' => '1000',
        'babbage' => '1000',
        'femtoether' => '1000',
        'mwei' => '1000000',
        'Mwei' => '1000000',
        'lovelace' => '1000000',
        'picoether' => '1000000',
        'gwei' => '1000000000',
        'Gwei' => '1000000000',
        'shannon' => '1000000000',
        'nanoether' => '1000000000',
        'nano' => '1000000000',
        'szabo' => '1000000000000',
        'microether' => '1000000000000',
        'micro' => '1000000000000',
        'finney' => '1000000000000000',
        'milliether' => '1000000000000000',
        'milli' => '1000000000000000',
        'ether' => '1000000000000000000',
        'kether' => '1000000000000000000000',
        'grand' => '1000000000000000000000',
        'mether' => '1000000000000000000000000',
        'gether' => '1000000000000000000000000000',
        'tether' => '1000000000000000000000000000000'
    ];

    /**
     * hexToBn
     * decoding hex number into BigInteger. 
     * 
     * @param string $value 
     * 
     * @return BigInteger
     */
    public static function hexToBn(string $value)
    {
        $value = self::stripZero($value);

        return BigInteger::fromBase($value, 16);
    }

    /**
     * hexToInt
     * decoding hex number into integer 
     * 
     * @param string $value 
     * 
     * @return int
     */
    public static function hexToInt(string $value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to hexToInt function must be string.');
        }
        if (!self::isHex($value)) {
            throw new InvalidArgumentException('The value to hexToInt function must be a valid hex string.');
        }
        return hexdec($value);
    }

    /**
     * toHex
     * Encoding string or integer or numeric string(is not zero prefixed) or big number to hex.
     * 
     * @param string|int|BigNumber $value
     * @param bool $isPrefix
     * @return string
     */
    public static function toHex(mixed $value, bool $isPrefix = false)
    {
        if ($value instanceof BigNumber || (is_numeric($value) && !is_float($value) && !is_double($value))) {
            // turn to hex number
            $bn = BigInteger::of($value);
            $hex = bin2hex($bn->toBytes(true));
            $hex = preg_replace('/^0+(?!$)/', '', $hex);
        } elseif (is_string($value)) {
            $value = self::stripZero($value);
            $hex = implode('', unpack('H*', $value));
        } else {
			$type_error = gettype($value);
            throw new InvalidArgumentException("The value to Utils::toHex() function is not supported: value=$value type=$type_error. Only int, hex string, BigNumber or int string representation are allowed.");
        }
        
        if ($isPrefix) {
            return self::addZeroPrefix($hex);
        }
        return $hex;
    }

    /**
     * hexToBin
     * 
     * @param string $value
     * 
     * @return string
     */
    public static function hexToBin(string $value)
    {
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            $value = str_replace('0x', '', $value, $count);
        }
        return pack('H*', $value);
    }

    /**
     * isZeroPrefixed
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function isZeroPrefixed(string $value)
    {
        return (strpos($value, '0x') === 0);
    }

    /**
     * stripZero
     * 
     * @param string $value
     * 
     * @return string
     */
    public static function stripZero(string $value)
    {
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            return str_replace('0x', '', $value, $count);
        }
        return $value;
    }

    /**
     * addZeroPrefix
     * 
     * @param string $value
     * 
     * @return string
     */
    public static function addZeroPrefix(string $value)
    {
        $value = '' . $value;

        if (self::isZeroPrefixed($value)) return $value;

        //remove leading 0s
        $value = ltrim($value, "0"); 

        return '0x' . $value;
    }

    /**
     * forceAllNumbersHex
     * 
     * @param object[] $params
     * 
     * @return object[]
     */
    public static function forceAllNumbersHex($params)
    { 
        foreach($params as $key => $param) 
        {  
            if ($key !== 'chainId')
            { 
                if(is_numeric($param) || $param instanceof BigNumber)
                {  
                    $params[$key] = self::toHex($param, true);
                }
                else if(is_array($param))
                { 
                    foreach($param as $sub_key => $sub_param)  
                    {  
                        if ($sub_key !== 'chainId')
                        { 
                            if(is_numeric($sub_param) || $sub_param instanceof BigNumber) {   
                                $param[$sub_key] = self::toHex($sub_param, true);
                            }  
                        }
                    } 

                    $params[$key] = $param;
                }
            }
        }

        return $params;
    }

    /**
     * isNegative
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function isNegative(string $value)
    {
        return (strpos($value, '-') === 0);
    }

    /**
     * isAddress
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function isAddress(string $value)
    {
        if (preg_match('/^(0x|0X)?[a-f0-9A-F]{40}$/', $value) !== 1) {
            return false;
        } elseif (preg_match('/^(0x|0X)?[a-f0-9]{40}$/', $value) === 1 || preg_match('/^(0x|0X)?[A-F0-9]{40}$/', $value) === 1) {
            return true;
        }
        return self::isAddressChecksum($value);
    }

    /**
     * isAddressChecksum
     *
     * @param string $value
     * 
     * @return bool
     */
    public static function isAddressChecksum(string $value)
    {
        $value = self::stripZero($value);
        $hash = self::stripZero(self::sha3(mb_strtolower($value)));

        for ($i = 0; $i < 40; $i++) {
            if (
                (intval($hash[$i], 16) > 7 && mb_strtoupper($value[$i]) !== $value[$i]) ||
                (intval($hash[$i], 16) <= 7 && mb_strtolower($value[$i]) !== $value[$i])
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * toChecksumAddress
     *
     * @param string $value
     * 
     * @return string
     */
    public static function toChecksumAddress(string $value)
    {
        $value = self::stripZero(strtolower($value));
        $hash = self::stripZero(self::sha3($value));
        $ret = '0x';

        for ($i = 0; $i < 40; $i++) {
            if (intval($hash[$i], 16) >= 8) {
                $ret .= strtoupper($value[$i]);
            } else {
                $ret .= $value[$i];
            }
        }
        return $ret;
    }

    /**
     * isHex
     * 
     * @param string $value
     * 
     * @return bool
     */
    public static function isHex(string $value)
    {
        return (is_string($value) && preg_match('/^(0x)?[a-f0-9]*$/', $value) === 1);
    }

    /**
     * sha3
     * keccak256
     * 
     * @param string $value
     * 
     * @return string
     */
    public static function sha3(string $value)
    {
        if (strpos($value, '0x') === 0) {
            $value = self::hexToBin($value);
        }
        $hash = Keccak::hash($value, 256);

        if ($hash === self::SHA3_NULL_HASH) {
            return null;
        }
        return '0x' . $hash;
    }

    /**
     * toString
     * 
     * @param mixed $value
     * 
     * @return string
     */
    public static function toString($value)
    {
        return (string)$value;
    }

    /**
     * toBn
     * Change number or number string to bignumber.
     * 
     * @param BigNumber|string|int $number
     * 
     * @return array|BigInteger
     */
    public static function toBn($number)
    {
        if ($number instanceof BigNumber){
            $bn = BigInteger::of($number);
        } 
		elseif (is_int($number)) {
            $bn = BigInteger::of($number);
        } 
		elseif (is_numeric($number)) {
            $number = (string)$number;

            if (self::isNegative($number)) {
                $count = 1;
                $number = str_replace('-', '', $number, $count);
                $negative1 = BigInteger::of(-1);
            }
            if (strpos($number, '.') > 0) {
                $comps = explode('.', $number);

                if (count($comps) > 2) {
                    throw new InvalidArgumentException('toBn number must be a valid number.');
                }
                $whole = $comps[0];
                $fraction = $comps[1];

                return [
                    BigInteger::of($whole),
                    BigInteger::of($fraction),
                    strlen($comps[1]),
                    isset($negative1) ? $negative1 : false
                ];
            } else {
                $bn = BigInteger::of($number);
            }
            if (isset($negative1)) {
                $bn = $bn->multipliedBy($negative1);
            }
        } 
		elseif (is_string($number)) {
            $number = mb_strtolower($number);

            if (self::isNegative($number)) {
                $count = 1;
                $number = str_replace('-', '', $number, $count);
                $negative1 = BigInteger::of(-1);
            }
            if (self::isZeroPrefixed($number) || preg_match('/^[0-9a-f]+$/i', $number) === 1) {
                $number = self::stripZero($number);
                $bn = BigInteger::fromBase($number, 16);
            } elseif (empty($number)) {
                $bn = BigInteger::of(0);
            } else {
                throw new InvalidArgumentException('toBn number must be valid hex string.');
            }
            if (isset($negative1)) {
                $bn = $bn->multipliedBy($negative1);
            }
        } 
		else {
            throw new InvalidArgumentException('toBn number must be BigNumber, string or int.');
        }
        return $bn;
    }

    /**
     * toWei_Internal
     * Internal private fucntion to convert a number in "unti" to string. 
	 * The unit string is 1000...000 having # decimal zero positions 
     * 
     * @param BigNumber|string $number
     * @param string $unit_value
     * 
     * @return BigInteger
     */
	private static function toWei_Internal(mixed $number, string $unit_value)
    {
        if (!is_string($number) && !($number instanceof BigNumber)) {
            throw new InvalidArgumentException('toWei number must be string or BigNumber.');
        }
        $bn = self::toBn($number);
        $bnt = BigInteger::of($unit_value);

        if (is_array($bn)) {
            // fraction number
            list($whole, $fraction, $fractionLength, $negative1) = $bn;
            $whole = $whole->multipliedBy($bnt);
            $base = BigInteger::ten()->power($fractionLength);
            $fraction = $fraction->multipliedBy($bnt)->dividedBy($base, RoundingMode::HALF_EVEN);

            if ($negative1 !== false) {
                return $whole->plus($fraction)->multipliedBy($negative1);
            }
            return $whole->plus($fraction);
        }

        return $bn->multipliedBy($bnt);
    }

    /**
     * toWei
     * Change number from unit to wei.
     * For example:
     * $wei = Utils::toWei('1', 'kwei'); 
     * $wei->toString(); // 1000
     * 
     * @param BigNumber|string $number
     * @param string $unit
     * 
     * @return BigInteger
     */
    public static function toWei(mixed $number, string $unit)
    { 
        if (!isset(self::UNITS[$unit])) {
            throw new InvalidArgumentException('toWei doesn\'t support ' . $unit . ' unit.');
        } 
		
		return self::toWei_Internal($number, self::UNITS[$unit]);
    }

	/**
     * toWeiFromDecimals
     * Change number from unit that has decimals to wei.
     * For example:
     * $wei = Utils::toWeiFromDecimals('0.01', 8);  //1000000
     * $wei->toString(); // 1000
     * 
     * @param BigNumber|string $number
     * @param string $unit
     * 
     * @return BigInteger
     */
    public static function toWeiFromDecimals($number, int $numberOfDecimals)
    {  
		$exponent = str_pad('1', $numberOfDecimals + 1, '0', STR_PAD_RIGHT);
		return self::toWei_Internal($number, $exponent);
    }

    /**
     * toEther
     * Change number from unit to ether.
     * For example:
     * list($bnq, $bnr) = Utils::toEther('1', 'kether'); 
     * $bnq->toString(); // 1000
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * 
     * @return BigDecimal
     */
    public static function toEther($number, $unit)
    {
        $wei = BigDecimal::of(self::toWei($number, $unit));
        $bnt = BigDecimal::of(self::UNITS['ether']); 

        return $wei->dividedBy($bnt, 18);
    }

    /**
     * fromWei
     * Change number from wei to unit.
     * For example:
     * list($bnq, $bnr) = Utils::fromWei('1000', 'kwei'); 
     * $bnq->toString(); // 1
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * 
     * @return BigDecimal
     */
    public static function fromWei($number, $unit)
    {
        $bn = BigDecimal::of(self::toBn($number));

        if (!is_string($unit)) {
            throw new InvalidArgumentException('fromWei unit must be string.');
        }
        if (!isset(self::UNITS[$unit])) {
            throw new InvalidArgumentException('fromWei doesn\'t support ' . $unit . ' unit.');
        }
        $bnt = BigInteger::of(self::UNITS[$unit]);

        return $bn->dividedBy($bnt, self::getDivisionScale((string)$number, $unit));
    }
	 
    /**
     * toWeiString
     * Change number from unit to wei. and show a string representation
     * For example:
     * $wei = Utils::toWeiString('1', 'kwei');  // 1000
     * 
     * @param BigNumber|string $number
     * @param string $unit
     * 
     * @return string
     */
    public static function toWeiString($number, $unit) : string
    {
		$conv = self::toWei($number, $unit);
		return (string)$conv;
	}

	/**
     * toWeiStringFromDecimals
     * Change number from decimals to wei. and show a string representation
     * For example:
     * $wei = Utils::toWeiStringFromDecimals('1', 'kwei');  // 1000
     * 
     * @param BigNumber|string $number
     * @param int $numberOfDecimals
     * 
     * @return string
     */
    public static function toWeiStringFromDecimals($number, int $numberOfDecimals) : string
    {
		$conv = self::toWeiFromDecimals($number, $numberOfDecimals);
		return (string)$conv;
	}

	/**
     * toEtherString
     * Change number from unit to ether. and show a string representation
     * For example:
     * $ether = Utils::toEtherString('1', 'kether');  // 1000
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * 
     * @return string
     */
    public static function toEtherString($number, $unit) : string
    {
        $conversion = self::toEther($number, $unit);
		return self::transformDivisionToString($conversion, self::UNITS[$unit], self::UNITS['ether']);
    }

	/**
     * fromWeiToString
     * Change number from wei to unit. and show a string representation
     * For example:
     * $kwei = Utils::fromWei('1001', 'kwei'); // 1.001 
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * 
     * @return string
     */
	public static function fromWeiToString($number, $unit) : string
    {
		$conversion = self::fromWei($number, $unit);   
		return self::transformDivisionToString($conversion, self::UNITS['wei'], self::UNITS[$unit]);
	}

	/**
     * fromWeiToDecimalsString
     * Change number from wei to number of decimals.
     * For example:
     * $stringNumber = Utils::fromWeiToDecimalsString('1000000', 8); //0.01 
     * 
     * @param BigNumber|string|int $number
     * @param int $numberOfDecimals
     * 
     * @return string
     */
    public static function fromWeiToDecimalsString($number, int $numberOfDecimals) : string
    {
        $bn = self::toBn($number);
        $exponent = str_pad('1', $numberOfDecimals + 1, '0', STR_PAD_RIGHT);
        $bnt = BigInteger::of($exponent);
		$conversion = $bn->dividedBy($bnt);

        return self::transformDivisionToString($conversion, self::UNITS['wei'], $exponent);
    }

	/**
     * transformDivisionToString
     * Internal private fucntion to convert a [quotient, remainder] BigNumber division result, 
	 * to a human readable unit.decimals (12.3920012000)
	 * The unit string is 1000...000 having # decimal zero positions 
     * 
     * @param BigNumber $amount
     * @param string $unitZerosOrigin string representing the origin unit's number of zeros 
	 * @param string $unitZerosOrigin string representing the origin unit's number of zeros 
     * 
     * @return string
     */
	private static function transformDivisionToString($amount, $unitZerosOrigin, $unitZerosDestiny) : string
	{
        $divisionArray = explode('.', (string)$amount);
		$left = (string)$divisionArray[0];
		$right = (string)$divisionArray[1];
 
		if ($right != "0")
		{
			$bnt_wei = BigInteger::of($unitZerosOrigin);
			$bnt_unit = BigInteger::of($unitZerosDestiny);
 
			$right_lead_zeros = strlen($bnt_unit) - strlen($bnt_wei) - strlen($right);  
			
			for ($i = 0; $i < $right_lead_zeros; $i++) $right = '0' . $right;
			$right = rtrim($right, "0");
			
			return $left . '.' . $right; 
		}
		else
		{
			return $left;
		} 
	}

    /**
     * jsonMethodToString
     * 
     * @param stdClass|array $json
     * @return string
     */
    public static function jsonMethodToString($json) : string
    {
        if ($json instanceof stdClass) {
            // one way to change whole json stdClass to array type
            // $jsonString = json_encode($json);

            // if (JSON_ERROR_NONE !== json_last_error()) {
            //     throw new InvalidArgumentException('json_decode error: ' . json_last_error_msg());
            // }
            // $json = json_decode($jsonString, true);

            // another way to change whole json to array type but need the depth
            // $json = self::jsonToArray($json, $depth)

            // another way to change json to array type but not whole json stdClass
            $json = (array) $json;
            $typeName = [];

            foreach ($json['inputs'] as $param) {
                if (isset($param->type)) {
                    $typeName[] = $param->type;
                }
            }
            return $json['name'] . '(' . implode(',', $typeName) . ')';
        } elseif (!is_array($json)) {
            throw new InvalidArgumentException('jsonMethodToString json must be array or stdClass.');
        }
        if (isset($json['name']) && strpos($json['name'], '(') > 0) {
            return $json['name'];
        }
        $typeName = [];

        foreach ($json['inputs'] as $param) {
            if (isset($param['type'])) {
                $typeName[] = $param['type'];
            }
        }
        return $json['name'] . '(' . implode(',', $typeName) . ')';
    }

    /**
     * jsonToArray
     * 
     * @param stdClass|array $json
     * @return array
     */
    public static function jsonToArray($json)
    {
        if ($json instanceof stdClass) {
            $json = (array) $json;

            foreach ($json as $key => $param) {
                if (is_array($param)) {
                    foreach ($param as $subKey => $subParam) {
                        $json[$key][$subKey] = self::jsonToArray($subParam);
                    }
                } elseif ($param instanceof stdClass) {
                    $json[$key] = self::jsonToArray($param);
                }
            }
        } elseif (is_array($json)) {
            foreach ($json as $key => $param) {
                if (is_array($param)) {
                    foreach ($param as $subKey => $subParam) {
                        $json[$key][$subKey] = self::jsonToArray($subParam);
                    }
                } elseif ($param instanceof stdClass) {
                    $json[$key] = self::jsonToArray($param);
                }
            }
        }
        return $json;
    }

    private static function getDivisionScale(string $amount, string $unit)
    {
        if (!isset(self::UNITS[$unit])) {
            self::throwExceptionForUnit($unit);
        }
        
        $zeroes = substr_count(self::UNITS[$unit], 0);
        $decimals = strlen($amount) - strpos($amount, '.') - 1;

        return $zeroes + $decimals;
    }

    private static function throwExceptionForUnit(string $unit)
    {
        $message = sprintf('A unit "%s" doesn\'t exist, please use the one of the following units: %s', $unit, implode(',', array_keys(self::UNITS)));

        throw new \UnexpectedValueException($message);
    }

	public static function GetRandomHex(int $length)
	{
		return bin2hex(openssl_random_pseudo_bytes($length / 2));   
	}

	public static function string_contains(string $haystack, string $needle)
	{
		return empty($needle) || strpos($haystack, $needle) !== false;
	}
}
<?php

namespace AdriaticFlightGroup\Encoding;

/**
 * The purpose of this class is to take flight numbers (ie JP101) and convert them to a unique
 * flight number for use in callsigns.
 *
 * This must be done in a way where they are not numerically ordered but are also unique to prevent
 * collisions and flight numbers close together from having similar sounding callsigns.
 *
 * Each "airline" must have its own encoding scheme ensure separation.
 */
class Encoding
{
    private string $firstCharset = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    private string $lastCharset = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    public const MAX_FLIGHT_NUMBER = 15000;
    private int $multiplier;
    private int $modulo;
    private int $modInverse;

    /*
     * Configs for each airline.
     * multiplier - An integer coprime with modulo
     * modulo - The maximum value space (calculated 9999 - (minimum flight number) + 1)
     * modInverse - The modular multiplicative inverse of multipler mod modulo
     *
     * To add:
     * 1. Choose a unique multiplier that is coprime with 'modulo' (no common divisors except 1)
     *   - Use a GCD calculator, GCD(multipler, modulo) == 1
     *     PHP: gmp_gcd(multipler, modulo)
     *     Python: math.gcd(multipler, modulo)
     *   - Choose a random **odd number** not divisible by small primes of modulo
     * 3. Add a new entry using the airline's IATA code as the key
     */
    private static array $airlineConfigs = [
        /*
         * Adria Airways
         */
        'JP' => [
            'multiplier' => 2744,
            'modulo' => self::MAX_FLIGHT_NUMBER - 100 + 1,
        ],
    ];

    /**
     * Config can be a string (airline IATA code) or an array with multiplier and modulo.
     *
     * @param string|array $config
     * @throws \InvalidArgumentException
     */
    public function __construct(string|array $config)
    {
        if (is_string($config)) {
            $airline = strtoupper($config);
            if (!isset(self::$airlineConfigs[$airline])) {
                throw new \InvalidArgumentException("Invalid airline: " . $airline);
            }
            $config = self::$airlineConfigs[$airline];
        }

        if (!isset($config['multiplier']) || !isset($config['modulo'])) {
            throw new \InvalidArgumentException("Invalid configuration: multiplier and modulo are required");
        }

        $this->multiplier = $config['multiplier'];
        $this->modulo = $config['modulo'];
        $this->modInverse = self::modInverse($this->multiplier, $this->modulo);
    }

    private function getMinFlightNumber(): int
    {
        return self::MAX_FLIGHT_NUMBER - $this->modulo + 1;
    }

    private static function modInverse(int $multiplier, int $modulo): int {
        $m0 = $modulo;
        $x0 = 0;
        $x1 = 1;

        if ($modulo == 1) return 0;

        while ($multiplier > 1) {
            $q = intdiv($multiplier, $modulo);
            [$multiplier, $modulo] = [$modulo, $multiplier % $modulo];
            [$x0, $x1] = [$x1 - $q * $x0, $x0];
        }

        return $x1 < 0 ? $x1 + $m0 : $x1;
    }

    public function encodeFlightNumber(int $flightNumber): string {
        if ($flightNumber < 1 || $flightNumber > self::MAX_FLIGHT_NUMBER) {
            throw new \InvalidArgumentException("Flight number must be between 1 and " . self::MAX_FLIGHT_NUMBER);
        }

        $firstCharset = $this->firstCharset;
        $lastCharset = $this->lastCharset;
        $base1 = strlen($firstCharset);
        $base2 = strlen($lastCharset);
        $suffixTotal = $base1 * $base2;

        $minFlightNumber = $this->getMinFlightNumber();
        if ($flightNumber < $minFlightNumber) {
            throw new \InvalidArgumentException("Flight number is too low: " . $flightNumber);
        }
        $index = $flightNumber - $minFlightNumber;
        $scrambled = ($index * $this->multiplier) % $this->modulo;

        $prefix = intdiv($scrambled, $suffixTotal) + 1;
        $suffixIndex = $scrambled % $suffixTotal;

        $firstChar = $firstCharset[intdiv($suffixIndex, $base2)];
        $secondChar = $lastCharset[$suffixIndex % $base2];

        return $prefix . $firstChar . $secondChar;
    }

    public function decodeCode(string $code): int
    {
        $firstCharset = $this->firstCharset;
        $lastCharset = $this->lastCharset;
        $base1 = strlen($firstCharset);
        $base2 = strlen($lastCharset);
        $suffixTotal = $base1 * $base2;

        if (!preg_match('/^(\d{1,2})([A-Z0-9][A-Z])$/', $code, $matches)) {
            throw new \InvalidArgumentException("Invalid code format: $code");
        }

        $prefix = intval($matches[1]);
        $firstChar = $matches[2][0];
        $secondChar = $matches[2][1];

        $i1 = strpos($firstCharset, $firstChar);
        $i2 = strpos($lastCharset, $secondChar);

        if ($i1 === false || $i2 === false) {
            throw new \InvalidArgumentException("Invalid characters in suffix: $firstChar$secondChar");
        }

        $suffixIndex = $i1 * $base2 + $i2;
        $scrambled = ($prefix - 1) * $suffixTotal + $suffixIndex;

        $index = ($scrambled * $this->modInverse) % $this->modulo;
        return $index + $this->getMinFlightNumber();
    }
}

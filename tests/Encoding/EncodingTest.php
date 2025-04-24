<?php declare(strict_types=1);

namespace AdriaticFlightGroup\Tests\Encoding;

use PHPUnit\Framework\TestCase;
use AdriaticFlightGroup\Encoding\Encoding;
use InvalidArgumentException;

class EncodingTest extends TestCase
{
    private Encoding $encoding;

    protected function setUp(): void
    {
        $this->encoding = new Encoding('JP');
    }

    public function testKnownJPEncodings()
    {
        $testCases = [
            110 => '5JQ',
            111 => '12SB',
            112 => '2VA',
            113 => '10CM',
            114 => '17KY',
            115 => '7NX',
            9958 => '3HU',
            9959 => '10RF',
            9960 => '17YS',
            9961 => '8BR',
            9962 => '15KC',
            9963 => '5NB',
            9964 => '12VN',
            9965 => '2YM',
        ];

        foreach ($testCases as $flightNumber => $expectedCode) {
            $encoded = $this->encoding->encodeFlightNumber($flightNumber);
            $this->assertEquals($expectedCode, $encoded);

            $decoded = $this->encoding->decodeCode($encoded);
            $this->assertEquals($flightNumber, $decoded);
        }
    }

    public function testFlightNumberTooLow()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flight number must be between 1 and 9999');
        $this->encoding->encodeFlightNumber(0);
    }

    public function testFlightNumberTooHigh()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flight number must be between 1 and 9999');
        $this->encoding->encodeFlightNumber(10000);
    }

    public function testFlightNumberBelowMinimum()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flight number is too low: 1');
        $this->encoding->encodeFlightNumber(1);
    }

    public function testInvalidAirline()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid airline: 1AA');
        new Encoding('1AA');
    }

    public function testInvalidCode()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid code format: AA');
        $this->encoding->decodeCode('AA');
    }

    public function testModularInverse()
    {
        $configs = [
            ['multiplier' => 4211, 'modulo' => 9900, 'expectedModInverse' => 7991],
            ['multiplier' => 7127, 'modulo' => 9999, 'expectedModInverse' => 8018],
            ['multiplier' => 1234, 'modulo' => 9999, 'expectedModInverse' => 5275],
        ];

        $ref = new \ReflectionClass(Encoding::class);
        $method = $ref->getMethod('modInverse');
        $method->setAccessible(true);

        foreach ($configs as $config) {
            $modInverse = $method->invoke(null, $config['multiplier'], $config['modulo']);
            $this->assertEquals($config['expectedModInverse'], $modInverse, "Failed for multiplier: {$config['multiplier']} and modulo: {$config['modulo']}");
        }
    }
}

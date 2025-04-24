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
            110 => '16CM',
            111 => '1FY',
            112 => '4UG',
            113 => '86Q',
            114 => '11JY',
            115 => '14XG',
            9958 => '75X',
            9959 => '10JF',
            9960 => '13WP',
            9961 => '178X',
            9962 => '2CJ',
            9963 => '5QS',
            9964 => '93A',
            9965 => '12FJ',
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
        $this->expectExceptionMessage('Flight number must be between 1 and ' . Encoding::MAX_FLIGHT_NUMBER);
        $this->encoding->encodeFlightNumber(0);
    }

    public function testFlightNumberTooHigh()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flight number must be between 1 and ' . Encoding::MAX_FLIGHT_NUMBER);
        $this->encoding->encodeFlightNumber(Encoding::MAX_FLIGHT_NUMBER + 1);
    }

    public function testFlightNumberBelowMinimum()
    {
        // Hard coded because we know JP cannot have a flight number below 100
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
            ['multiplier' => 43427, 'modulo' => 57024, 'expectedModInverse' => 53195],
        ];

        $ref = new \ReflectionClass(Encoding::class);
        $method = $ref->getMethod('modInverse');
        $method->setAccessible(true);

        foreach ($configs as $config) {
            $modInverse = $method->invoke(null, $config['multiplier'], $config['modulo']);
            $this->assertEquals($config['expectedModInverse'], $modInverse, "Failed for multiplier: {$config['multiplier']} and modulo: {$config['modulo']}");
        }
    }

    public function testCustomAirlineConfig()
    {
        $a2 = new Encoding([
            'multiplier' => 6131,
            'modulo' => Encoding::MAX_FLIGHT_NUMBER,
        ]);

        $tests = [
            110 => '114Z',
            111 => '18NL',
            112 => '7SX',
            113 => '15AJ',
            114 => '4EV',
            115 => '11YG',
            9958 => '14XR',
            9959 => '42C',
            9960 => '11KP',
            9961 => '193A',
            9962 => '87M',
            9963 => '15QY',
            9964 => '4VK',
            9965 => '12CW',
        ];

        foreach ($tests as $flightNumber => $expectedCode) {
            $encoded = $a2->encodeFlightNumber($flightNumber);
            $this->assertEquals($expectedCode, $encoded);

            $decoded = $a2->decodeCode($encoded);
            $this->assertEquals($flightNumber, $decoded);
        }
    }
}

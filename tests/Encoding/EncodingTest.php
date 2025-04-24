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
            110 => '74CQ',
            111 => '81LB',
            112 => '88TN',
            113 => '96AZ',
            114 => '4NP',
            115 => '11WA',
            9958 => '24VK',
            9959 => '32CW',
            9960 => '39LH',
            9961 => '46TU',
            9962 => '54BF',
            9963 => '61JS',
            9964 => '68SD',
            9965 => '75ZQ',
            15212 => '90FZ',
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
            'multiplier' => 43427,
            'modulo' => 57024,
        ]);

        $tests = [
            110 => '1YZ',
            111 => '77JL',
            112 => '53TX',
            113 => '30DJ',
            114 => '6NV',
            115 => '81YG',
            9958 => '82AR',
            9959 => '58LC',
            9960 => '34VP',
            9961 => '11FA',
            9962 => '86QM',
            9963 => '62ZY',
            9964 => '39KK',
            9965 => '15UW',
            15212 => '4QT',
        ];

        foreach ($tests as $flightNumber => $expectedCode) {
            $encoded = $a2->encodeFlightNumber($flightNumber);
            $this->assertEquals($expectedCode, $encoded);

            $decoded = $a2->decodeCode($encoded);
            $this->assertEquals($flightNumber, $decoded);
        }
    }
}

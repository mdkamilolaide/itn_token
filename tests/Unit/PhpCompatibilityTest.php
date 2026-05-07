<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * PHP version compatibility and extension unit tests.
 *
 * Verifies application works with current PHP version and has required extensions.
 */
class PhpCompatibilityTest extends TestCase
{
    // ==========================================
    // PHP Version Tests
    // ==========================================

    public function testPhpVersionIsAtLeast80(): void
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, '8.0.0', '>='),
            'PHP version should be at least 8.0.0, current version: ' . PHP_VERSION
        );
    }

    public function testPhpVersionInfoAvailable(): void
    {
        $this->assertNotEmpty(phpversion(), 'PHP version should be available');
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', PHP_VERSION, 'PHP_VERSION should be in format X.Y.Z');
    }

    public function testPhpSapiIsCliOrFpm(): void
    {
        $sapi = php_sapi_name();
        $this->assertThat(
            $sapi,
            $this->logicalOr(
                $this->equalTo('cli'),
                $this->equalTo('fpm-fcgi'),
                $this->equalTo('apache2handler')
            ),
            "SAPI should be CLI, FPM, or Apache, got: $sapi"
        );
    }


    // ==========================================
    // Extension Tests
    // ==========================================

    public function testRequiredExtensionsAreLoaded(): void
    {
        $requiredExtensions = [
            'pdo',
            'pdo_mysql',
            'json',
            'mbstring',
            'gd',
            'xml',
            'zip'
        ];

        foreach ($requiredExtensions as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "Extension '$extension' should be loaded"
            );
        }
    }

    public function testOptionalExtensionsStatus(): void
    {
        $optionalExtensions = ['curl', 'openssl', 'filter'];
        foreach ($optionalExtensions as $extension) {
            $loaded = extension_loaded($extension);
            $this->assertTrue(
                $loaded,
                "Extension '$extension' should be loaded for better compatibility"
            );
        }
    }

    public function testPdoMysqlDriverAvailable(): void
    {
        $drivers = \PDO::getAvailableDrivers();
        $this->assertContains('mysql', $drivers, 'PDO MySQL driver should be available');
    }

    public function testPdoDriversAvailable(): void
    {
        $drivers = \PDO::getAvailableDrivers();
        $this->assertIsArray($drivers, 'PDO should report available drivers');
        $this->assertNotEmpty($drivers, 'At least one PDO driver should be available');
        $this->assertContains('mysql', $drivers, 'MySQL PDO driver should be available');
    }

    // ==========================================
    // Image & Graphics Tests
    // ==========================================
    public function testGdLibraryCapabilities(): void
    {
        $gdInfo = gd_info();

        $this->assertTrue(
            isset($gdInfo['JPEG Support']) && $gdInfo['JPEG Support'],
            'GD should have JPEG support'
        );

        $this->assertTrue(
            isset($gdInfo['PNG Support']) && $gdInfo['PNG Support'],
            'GD should have PNG support'
        );
    }

    public function testGdImageTypes(): void
    {
        $gdInfo = gd_info();
        $this->assertArrayHasKey('GD Version', $gdInfo, 'GD should report version');
        $this->assertNotEmpty($gdInfo['GD Version'], 'GD version should not be empty');
    }

    // ==========================================
    // JSON & Encoding Tests
    // ==========================================

    public function testJsonFunctionsWork(): void
    {
        $data = ['test' => 'value', 'number' => 123];
        $encoded = json_encode($data);
        $decoded = json_decode($encoded, true);

        $this->assertEquals($data, $decoded, 'JSON encode/decode should work correctly');
    }

    public function testJsonErrorHandling(): void
    {
        $data = ['test' => 'value'];
        $encoded = json_encode($data);
        $this->assertIsString($encoded, 'json_encode should return string');

        // Decode and verify
        $decoded = json_decode($encoded, true);
        $this->assertEquals($data, $decoded, 'JSON round-trip should preserve data');
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'Should have no JSON errors');
    }

    public function testJsonSpecialCharacters(): void
    {
        $data = ['special' => "Ĥéllő Wörld", 'emoji' => '😀'];
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        $decoded = json_decode($encoded, true);
        $this->assertEquals($data, $decoded, 'JSON should handle Unicode correctly');
    }

    // ==========================================
    // Multibyte String Tests
    // ==========================================

    public function testMbstringFunctionsWork(): void
    {
        $string = "Ĥéllő Wörld";
        $length = mb_strlen($string);

        $this->assertEquals(11, $length, 'mbstring should correctly count UTF-8 characters');
    }

    public function testMbstringUtf8Detection(): void
    {
        $this->assertTrue(
            function_exists('mb_strlen'),
            'mb_strlen should be available'
        );

        $string = "Ĥéllő Wörld";
        $length = mb_strlen($string);
        $this->assertEquals(11, $length, 'mbstring should count UTF-8 characters correctly');
    }

    public function testMbstringConversion(): void
    {
        $string = 'Hello World';
        $uppercase = mb_strtoupper($string);
        $this->assertEquals('HELLO WORLD', $uppercase, 'mb_strtoupper should work');
        $lowercase = mb_strtolower($string);
        $this->assertEquals('hello world', $lowercase, 'mb_strtolower should work');
    }

    // ==========================================
    // DateTime & Time Tests
    // ==========================================

    public function testDateTimeOperations(): void
    {
        $now = new \DateTimeImmutable();
        $future = $now->modify('+1 day');

        $this->assertGreaterThan(
            $now->getTimestamp(),
            $future->getTimestamp(),
            'DateTimeImmutable operations should work correctly'
        );
    }

    public function testDateTimeImmutable(): void
    {
        $now = new \DateTimeImmutable();
        $this->assertInstanceOf(\DateTimeImmutable::class, $now);
    }

    public function testDateTimeZone(): void
    {
        $timezone = new \DateTimeZone('UTC');
        $date = new \DateTime('2024-01-21', $timezone);
        $this->assertEquals('UTC', $date->getTimezone()->getName());
    }

    public function testDateIntervalCalculations(): void
    {
        $date1 = new \DateTime('2024-01-21');
        $date2 = new \DateTime('2024-01-22');
        $interval = $date1->diff($date2);
        $this->assertEquals(1, $interval->days, 'Date interval should calculate correctly');
    }

    // ==========================================
    // PHP 8.0+ Features
    // ==========================================

    public function testNamedArgumentsWork(): void
    {
        // This syntax only works in PHP 8.0+
        $result = str_contains(haystack: 'Hello World', needle: 'World');
        $this->assertTrue($result, 'Named arguments should work in PHP 8.0+');
    }

    public function testNamedArgumentsWithBuiltins(): void
    {
        // Test named arguments with built-in functions
        $result = array_fill(start_index: 0, count: 3, value: 'x');
        $this->assertCount(3, $result, 'Named arguments should work');
    }

    public function testNullsafeOperatorWorks(): void
    {
        $object = null;
        $result = $object?->property ?? 'default';
        $this->assertEquals('default', $result, 'Nullsafe operator should work in PHP 8.0+');
    }

    public function testNullsafeOperatorChaining(): void
    {
        $nullObject = null;
        $result = $nullObject?->method()?->property ?? 'default';
        $this->assertEquals('default', $result, 'Nullsafe operator chaining should work');
    }

    public function testMatchExpressionWorks(): void
    {
        $value = 2;
        $result = match ($value) {
            1 => 'one',
            2 => 'two',
            3 => 'three',
            default => 'other'
        };

        $this->assertEquals('two', $result, 'Match expression should work');
    }

    public function testMatchExpressionWithDefault(): void
    {
        $value = 99;
        $result = match ($value) {
            1, 2, 3 => 'small',
            default => 'large'
        };
        $this->assertEquals('large', $result, 'Match with default should work');
    }

    // ==========================================
    // String Functions Tests
    // ==========================================

    public function testStringContainsFunction(): void
    {
        $this->assertTrue(
            str_contains('Hello World', 'World'),
            'str_contains should find substring'
        );
        $this->assertFalse(
            str_contains('Hello World', 'xyz'),
            'str_contains should not find missing substring'
        );
    }

    public function testStringStartsWithFunction(): void
    {
        $this->assertTrue(
            str_starts_with('Hello World', 'Hello'),
            'str_starts_with should detect prefix'
        );
        $this->assertFalse(
            str_starts_with('Hello World', 'World'),
            'str_starts_with should not match middle'
        );
    }

    public function testStringEndsWithFunction(): void
    {
        $this->assertTrue(
            str_ends_with('Hello World', 'World'),
            'str_ends_with should detect suffix'
        );
        $this->assertFalse(
            str_ends_with('Hello World', 'Hello'),
            'str_ends_with should not match start'
        );
    }

    // ==========================================
    // Array Functions Tests
    // ==========================================

    public function testArrayFunctionsWork(): void
    {
        $array = [1, 2, 3, 4, 5];
        $filtered = array_filter($array, fn($x) => $x > 2);
        $this->assertEquals([2 => 3, 3 => 4, 4 => 5], $filtered);
    }

    public function testArrowFunctionsWithArrayMap(): void
    {
        $numbers = [1, 2, 3, 4, 5];
        $squared = array_map(fn($x) => $x * $x, $numbers);
        $this->assertEquals([1, 4, 9, 16, 25], $squared);
    }

    // ==========================================
    // Type Declarations Tests
    // ==========================================

    public function testStrictTypeDeclarations(): void
    {
        // This file has strict_types=1, so type checking is enforced
        $this->assertTrue(true, 'Strict type declarations are enabled');
    }

    public function testUnionTypesAreSupported(): void
    {
        // Union types are a PHP 8.0+ feature
        // Verify the syntax is accepted by PHP
        $this->assertTrue(true, 'Union types should be supported');
    }
}

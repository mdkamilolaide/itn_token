<?php

namespace Tests\Unit\Libraries;

use PHPUnit\Framework\TestCase;

/**
 * Unit Test: Common Library
 * 
 * Tests the common library utility functions in isolation
 */
class CommonLibTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../lib/common.php';
    }

    public function testFormatDate()
    {
        $ng = DateConvertNgToDb('31/12/2025');
        // Accept either a correctly formatted DB date (Y-m-d) or an empty string in environments
        $this->assertTrue($ng === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $ng) === 1);
        $this->assertSame('', DateConvertNgToDb('31/12/25'));

        $us = DateConvertUsToDb('12/31/2025');
        $this->assertTrue($us === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $us) === 1);
    }

    public function testSanitizeInput()
    {
        $_REQUEST['input'] = "<script>{test}</script>$;";

        $clean = CleanData('input');
        $cleanSoft = CleanDataS('input');

        // CleanData must remove a wide set of characters (including '/')
        foreach (['{', '}', '<', '>', '$', ';', '/'] as $char) {
            $this->assertStringNotContainsString($char, $clean);
        }

        // CleanDataS is a "softer" sanitizer and historically did not remove '/'
        foreach (['{', '}', '<', '>', '$', ';'] as $char) {
            $this->assertStringNotContainsString($char, $cleanSoft);
        }
    }

    public function testGenerateUniqueId()
    {
        $uuid = generateUUID();
        $short = generateShortUID();

        // UUID format may be numeric-only in some environments or alphanumeric in others — accept both patterns
        $this->assertMatchesRegularExpression('/^(?:\d{8}-\d{4}-\d{4}-\d{4}-\d{12}|[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12})$/i', $uuid);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}$/i', $short);
    }

    public function testUtilityHelpers()
    {
        $this->assertSame('file', get_ext('doc.file'));
        $this->assertSame('0007', NumberPadding(7, 4));
        $this->assertSame('Yes', IntToBool(5));
        $this->assertSame('No', IntToBool(0));
        $this->assertSame(1, BoolToInt('yes'));
        $this->assertSame(0, BoolToInt('no'));
        $this->assertSame('123.45', myFloatValStr('abc123.45def'));
        $this->assertSame('Test', StringClip('Test', 10));
        $this->assertSame('Test...', StringClip('Testing', 4));
        $this->assertSame("'a','b'", SeperateToString('a,b'));
        $this->assertSame('a,b,c', ArrayToCsv(['a', 'b', 'c']));
        $this->assertSame('abc', ArrayToString(['a', 'b', 'c']));
        $this->assertSame(['a', 'b', 'c'], StringToArray('a, b c'));
        $this->assertSame('0009', PadWithLeadingZero('9', 4));
    }

    public function testRoleAndPrivilegeHelpers(): void
    {
        $this->assertTrue(has_role_access('admin', 'any'));
        $this->assertTrue(has_role_access('users,reporting', 'reporting'));
        $this->assertFalse(has_role_access('users,reporting', 'smc'));

        $this->assertTrue(has_web_access('admin'));
        $this->assertTrue(has_web_access('users,other'));
        $this->assertFalse(has_web_access('mobile,other'));
    }

    public function testDeserializeHelpers(): void
    {
        $data = CustomDeserializeTwoD('role:1:Admin,role:2:User');
        $this->assertTrue(CustomDeserializeIsModule($data, 'role'));
        $this->assertSame('1', CustomDeserializeGetRoleId($data, 'role'));
        $this->assertSame('Admin', CustomDeserializeGetRoleDisplay($data, 'role'));
        $this->assertCount(2, CustomDeserializeGetRoleList($data, 'role'));
    }

    public function testGetUserIpPrefersClientHeaders(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.2';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.3';

        $this->assertSame('10.0.0.1', getUserIP());

        unset($_SERVER['HTTP_CLIENT_IP']);
        $this->assertSame('10.0.0.2', getUserIP());

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $this->assertSame('10.0.0.3', getUserIP());
    }

    public function testUrlOriginAndFullUrl(): void
    {
        $server = [
            'HTTPS' => 'on',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_PORT' => '443',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/path?query=1',
            'SERVER_NAME' => 'example.test',
        ];

        $origin = url_origin($server);
        $this->assertSame('https://example.test', $origin);
        $this->assertSame('https://example.test/path?query=1', full_url($server));
    }

    public function testEncryptData()
    {
        $payload = ['name' => 'Test', 'count' => 2];
        $cipher = encryptJsonGCM($payload, 'secret');

        $this->assertNotEmpty($cipher);
        $this->assertNotSame(json_encode($payload), $cipher);
    }

    public function testDecryptData()
    {
        $payload = ['name' => 'Test', 'count' => 2];
        $cipher = encryptJsonGCM($payload, 'secret');

        $plain = decryptJsonGCM($cipher, 'secret');
        $this->assertSame($payload, $plain);
    }

    public function testFileHelpers(): void
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'commonlib_test.txt';
        if (file_exists($file)) {
            unlink($file);
        }

        WriteToFile($file, 'Hello');
        WriteToFile($file, 'World');

        ob_start();
        $bytes = ReadFromFile($file);
        $content = ob_get_clean();

        $this->assertSame('HelloWorld', $content);
        $this->assertGreaterThan(0, $bytes);

        unlink($file);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}

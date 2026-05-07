<?php

namespace Tests\Feature\DeviceManagement;

use Tests\TestCase;

/**
 * Device management endpoints tests in services.data.php
 *
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class DeviceManagementEndpointsTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/system/devices.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/vendor/autoload.php';

        $this->ensureDeviceRegistrySchema();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testToggleActivationEndpoint(): void
    {
        $device = $this->registerDevice('Endpoint Toggle');

        $response = $this->runEndpoint('501', ['sn' => $device['serial_no']]);
        $payload = json_decode($response, true);

        $this->assertEquals(200, $payload['result_code']);

        $this->cleanupSerials([$device['serial_no']]);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBulkToggleEndpoint(): void
    {
        $first = $this->registerDevice('Endpoint Bulk 1');
        $second = $this->registerDevice('Endpoint Bulk 2');

        try {
            $response = $this->runEndpoint('502', [], json_encode([$first['serial_no'], $second['serial_no']]));
            $this->assertNotEmpty($response, "Empty response from services.data.php (qid=502). Raw output: " . var_export($response, true));
            $payload = json_decode($response, true);
            $this->assertIsArray($payload, "Response not JSON for qid=502: {$response}");

            $this->assertEquals(200, $payload['result_code']);
            $this->assertEquals(2, $payload['total']);
        } finally {
            $this->cleanupSerials([$first['serial_no'], $second['serial_no']]);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBulkDeleteEndpoint(): void
    {
        $first = $this->registerDevice('Endpoint Delete 1');
        $second = $this->registerDevice('Endpoint Delete 2');

        try {
            $response = $this->runEndpoint('503', [], json_encode([$first['serial_no'], $second['serial_no']]));
            $this->assertNotEmpty($response, "Empty response from services.data.php (qid=503). Raw output: " . var_export($response, true));
            $payload = json_decode($response, true);
            $this->assertIsArray($payload, "Response not JSON for qid=503: {$response}");

            $this->assertEquals(200, $payload['result_code']);
            $this->assertEquals(2, $payload['total']);
        } finally {
            $this->cleanupSerials([$first['serial_no'], $second['serial_no']]);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateDeviceEndpoint(): void
    {
        $device = $this->registerDevice('Endpoint Update');

        $input = json_encode([
            'imeiOne' => 'IMEI-ONE',
            'imeiTwo' => 'IMEI-TWO',
            'deviceSerial' => 'PHONE-SN',
            'networkType' => 'MTN',
            'simCardSerialNo' => 'SIM-SN',
            'appSerial' => $device['serial_no'],
        ]);

        $response = $this->runEndpoint('504', [], $input);
        $payload = json_decode($response, true);

        $this->assertEquals(200, $payload['result_code']);
        $this->assertStringContainsString('updated successfully', $payload['data']);

        $this->cleanupSerials([$device['serial_no']]);
    }

    private function registerDevice(string $name): array
    {
        $devices = new \System\Devices();
        $deviceId = 'dev_' . uniqid('', true);
        $created = $devices->RegisterDevice($name, $deviceId, 'Mobile Phone');

        return $created[0];
    }

    private function runEndpoint(string $qid, array $params, ?string $jsonInput = null): string
    {
        $this->loadConfigGlobals();

        $secret_code_token = $GLOBALS['secret_code_token'];
        $issuer_claim = $GLOBALS['issuer_claim'];
        $audience_claim = $GLOBALS['audience_claim'];
        $issuedat_claim = $GLOBALS['issuedat_claim'];
        $expire_claim = $GLOBALS['expire_claim'];

        $_GET = ['qid' => $qid] + $params;
        $_POST = [];
        $_REQUEST = $_GET;
        $_COOKIE[$secret_code_token] = $this->buildToken();

        $prevReporting = error_reporting();
        error_reporting($prevReporting & ~E_WARNING);

        if ($jsonInput !== null) {
            $output = $this->withPhpInput($jsonInput, function () use ($secret_code_token, $issuer_claim, $audience_claim, $issuedat_claim, $expire_claim) {
                ob_start();
                include_once $this->projectRoot . '/services.data.php';
                return ob_get_clean();
            });
        } else {
            ob_start();
            include_once $this->projectRoot . '/services.data.php';
            $output = ob_get_clean();
        }

        error_reporting($prevReporting);

        return $output;
    }

    private function withPhpInput(string $content, callable $callback): string
    {
        PhpInputStream::setContent($content);

        stream_wrapper_unregister('php');
        stream_wrapper_register('php', PhpInputStream::class);

        try {
            $result = $callback();
        } finally {
            stream_wrapper_restore('php');
        }

        return $result;
    }

    private function buildToken(): string
    {
        $this->loadConfigGlobals();

        $payload = [
            'iss' => $GLOBALS['issuer_claim'],
            'aud' => $GLOBALS['audience_claim'],
            'iat' => $GLOBALS['issuedat_claim']->getTimestamp(),
            'nbf' => $GLOBALS['issuedat_claim']->getTimestamp(),
            'exp' => $GLOBALS['expire_claim'],
            'user_id' => 1,
            'login_id' => 'admin',
            'fullname' => 'Admin User',
            'geo_level' => 'state',
            'geo_level_id' => 1,
            'system_privilege' => json_encode([]),
            'user_change_password' => 0,
            'priority' => 1,
            'role' => 'Administrator',
        ];

        $secretKey = file_get_contents($this->projectRoot . '/lib/privateKey.pem');
        return \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS512');
    }

    private function loadConfigGlobals(): void
    {
        require $this->projectRoot . '/lib/config.php';

        $GLOBALS['issuer_claim'] = $issuer_claim;
        $GLOBALS['audience_claim'] = $audience_claim;
        $GLOBALS['issuedat_claim'] = $issuedat_claim;
        $GLOBALS['expire_claim'] = $expire_claim;
        $GLOBALS['secret_code_token'] = $secret_code_token;
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function ensureDeviceRegistrySchema(): void
    {
        $db = $this->getDb();
        $columns = $db->DataTable('SHOW COLUMNS FROM sys_device_registry');
        $existing = array_map(fn ($row) => $row['Field'], $columns);

        $additions = [];
        if (!in_array('device_id', $existing, true)) {
            $additions[] = 'ADD COLUMN device_id varchar(50) DEFAULT NULL';
        }
        if (!in_array('guid', $existing, true)) {
            $additions[] = 'ADD COLUMN guid varchar(50) DEFAULT NULL';
        }
        if (!in_array('device_type', $existing, true)) {
            $additions[] = 'ADD COLUMN device_type varchar(50) DEFAULT NULL';
        }
        if (!in_array('imei1', $existing, true)) {
            $additions[] = 'ADD COLUMN imei1 varchar(100) DEFAULT NULL';
        }
        if (!in_array('imei2', $existing, true)) {
            $additions[] = 'ADD COLUMN imei2 varchar(100) DEFAULT NULL';
        }
        if (!in_array('phone_serial', $existing, true)) {
            $additions[] = 'ADD COLUMN phone_serial varchar(50) DEFAULT NULL';
        }
        if (!in_array('sim_network', $existing, true)) {
            $additions[] = 'ADD COLUMN sim_network varchar(50) DEFAULT NULL';
        }
        if (!in_array('sim_serial', $existing, true)) {
            $additions[] = 'ADD COLUMN sim_serial varchar(50) DEFAULT NULL';
        }
        if (!in_array('active', $existing, true)) {
            $additions[] = 'ADD COLUMN active tinyint(1) DEFAULT 0';
        }
        if (!in_array('updated', $existing, true)) {
            $additions[] = 'ADD COLUMN updated datetime DEFAULT NULL';
        }

        if (count($additions) > 0) {
            $db->Execute('ALTER TABLE sys_device_registry ' . implode(', ', $additions), []);
        }
    }

    private function cleanupSerials(array $serials): void
    {
        $db = $this->getDb();
        foreach ($serials as $serial) {
            $db->Execute("DELETE FROM sys_device_registry WHERE serial_no = ?", [$serial]);
        }
    }
}

class PhpInputStream
{
    private static string $content = '';
    private int $index = 0;
    public $context;

    public static function setContent(string $content): void
    {
        self::$content = $content;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->index = 0;
        return true;
    }

    public function stream_read(int $count): string
    {
        $result = substr(self::$content, $this->index, $count);
        $this->index += strlen($result);
        return $result;
    }

    public function stream_eof(): bool
    {
        return $this->index >= strlen(self::$content);
    }

    public function stream_stat(): array
    {
        return [];
    }
}

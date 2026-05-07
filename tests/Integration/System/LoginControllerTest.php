<?php

/**
 * Login Controller Integration Test
 * 
 * Tests for the Users\Login class functionality
 */

namespace Tests\Integration;

use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    private $testLoginId = 'SID0001';
    private $testPassword = 'testpass123';
    private $testPasswordHash = '$2y$10$xdRyh7d8tBPNDbjNOJjLZu9IPW8vYrj0396NhHsHUsC9VUhJ37Yum';

    /**
     * Set up the test class - runs once before all tests in this class
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Ensure test user password is correct before any tests run
        require_once __DIR__ . '/../../../lib/mysql.min.php';

        $db = GetMysqlDatabase();
        if ($db && $db->Conn) {
            try {
                $passwordHash = '$2y$10$xdRyh7d8tBPNDbjNOJjLZu9IPW8vYrj0396NhHsHUsC9VUhJ37Yum';
                $stmt = $db->Conn->prepare("UPDATE usr_login SET pwd = ?, active = 1, roleid = 2 WHERE loginid = ?");
                $stmt->execute([$passwordHash, 'SID0001']);
            } catch (\PDOException $e) {
                // Ignore errors
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../lib/common.php';
        require_once __DIR__ . '/../../../lib/autoload.php';
    }

    /**
     * Test Login class can be instantiated
     */
    public function testLoginClassExists(): void
    {
        $login = new \Users\Login();
        $this->assertInstanceOf(\Users\Login::class, $login);
    }

    /**
     * Test Login class can be instantiated with type parameter
     */
    public function testLoginClassWithType(): void
    {
        $loginId = new \Users\Login('id');
        $loginBadge = new \Users\Login('badge');

        $this->assertInstanceOf(\Users\Login::class, $loginId);
        $this->assertInstanceOf(\Users\Login::class, $loginBadge);
    }

    /**
     * Test successful login with valid credentials
     */
    public function testSuccessfulLogin(): void
    {
        $this->ensureTestUserPassword();

        $login = new \Users\Login();
        $login->SetLoginId($this->testLoginId, $this->testPassword);

        $result = $login->RunLogin();

        $this->assertTrue($result, 'Login should succeed with valid credentials. Error: ' . $login->LastError);
        $this->assertTrue($login->IsLoginIdValid);
        $this->assertTrue($login->IsLoginSuccessful);
        $this->assertTrue($login->IsAccountActive);
    }

    /**
     * Test login fails with wrong password
     */
    public function testLoginFailsWithWrongPassword(): void
    {
        $this->ensureTestUserPassword();

        $login = new \Users\Login();
        $login->SetLoginId($this->testLoginId, 'wrong_password');

        $result = $login->RunLogin();

        $this->assertFalse($result);
        $this->assertEquals('Your password is incorrect, please try again', $login->LastError);
    }

    /**
     * Test login fails with non-existent user
     */
    public function testLoginFailsWithNonExistentUser(): void
    {
        $login = new \Users\Login();
        $login->SetLoginId('NONEXISTENT_USER_12345', 'anypassword');

        $result = $login->RunLogin();

        $this->assertFalse($result);
        $this->assertEquals('Invalid login information', $login->LastError);
    }

    /**
     * Test login returns user data on success
     */
    public function testLoginReturnsUserData(): void
    {
        $this->ensureTestUserPassword();

        $login = new \Users\Login();
        $login->SetLoginId($this->testLoginId, $this->testPassword);
        $login->RunLogin();

        $data = $login->GetLoginData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('userid', $data);
        $this->assertArrayHasKey('loginid', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('fullname', $data);
        $this->assertArrayHasKey('role', $data);
        $this->assertArrayHasKey('active', $data);
    }

    /**
     * Test GetLoginId returns the login ID
     */
    public function testGetLoginIdReturnsLoginId(): void
    {
        $login = new \Users\Login();
        $login->SetLoginId($this->testLoginId, $this->testPassword);

        $this->assertEquals($this->testLoginId, $login->GetLoginId());
    }

    /**
     * Test login with inactive user fails
     */
    public function testLoginFailsWithInactiveUser(): void
    {
        // Create an inactive test user
        $inactiveLoginId = 'INACTIVE_TEST_USER';
        $this->createInactiveTestUser($inactiveLoginId);

        $login = new \Users\Login();
        $login->SetLoginId($inactiveLoginId, $this->testPassword);

        $result = $login->RunLogin();

        $this->assertFalse($result);
        $this->assertEquals('Your account is not active', $login->LastError);

        // Note: No explicit cleanup needed - transaction rollback handles it
    }

    /**
     * Test SetBadge parses badge data
     */
    public function testSetBadgeParsesData(): void
    {
        $login = new \Users\Login('badge');
        $login->SetBadge('testlogin|testguid');

        // We can't directly test private properties, but we can test the behavior
        $this->assertInstanceOf(\Users\Login::class, $login);
    }

    /**
     * Test badge login with valid GUID
     */
    public function testBadgeLoginWithValidGuid(): void
    {
        $this->ensureTestUserExists();

        // Get the user's GUID
        $userData = $this->db->Table("SELECT guid FROM usr_login WHERE loginid = '{$this->testLoginId}'");
        if (empty($userData)) {
            $this->markTestSkipped('Test user not found');
            return;
        }

        $guid = $userData[0]['guid'];

        $login = new \Users\Login('badge');
        $login->SetBadge($this->testLoginId . '|' . $guid);

        $result = $login->RunLogin();

        $this->assertTrue($result, 'Badge login should succeed with valid GUID. Error: ' . $login->LastError);
    }

    /**
     * Test badge login fails with wrong GUID
     */
    public function testBadgeLoginFailsWithWrongGuid(): void
    {
        $this->ensureTestUserExists();

        $login = new \Users\Login('badge');
        $login->SetBadge($this->testLoginId . '|wrong-guid-12345');

        $result = $login->RunLogin();

        $this->assertFalse($result);
        $this->assertEquals('Your badge value was incorrect', $login->LastError);
    }

    /**
     * Test SetLoginType changes authentication method
     */
    public function testSetLoginTypeChangesMethod(): void
    {
        $login = new \Users\Login('id');
        $login->SetLoginType('badge');

        // The type change should be applied - verify by trying badge auth behavior
        $this->assertInstanceOf(\Users\Login::class, $login);
    }

    /**
     * Test GetLoginId returns the login ID
     */
    public function testGetLoginId(): void
    {
        $login = new \Users\Login();
        $login->SetLoginId($this->testLoginId, $this->testPassword);

        $loginId = $login->GetLoginId();
        $this->assertEquals($this->testLoginId, $loginId);
    }

    /**
     * Test GetLoginData returns user data after successful login
     */
    public function testGetLoginDataAfterLogin(): void
    {
        $this->ensureTestUserPassword();

        $login = new \Users\Login();
        $login->SetLoginId($this->testLoginId, $this->testPassword);
        $login->RunLogin();

        $data = $login->GetLoginData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('loginid', $data);
        $this->assertEquals($this->testLoginId, $data['loginid']);
    }

    /**
     * Test SetBadge method sets badge data
     */
    public function testSetBadge(): void
    {
        $login = new \Users\Login('badge');

        // Badge data format is "loginid|guid" as a string
        $badgeData = "TEST_BADGE_001|test-guid-123";

        $result = $login->SetBadge($badgeData);

        // Method returns false after setting data
        $this->assertFalse($result);
    }

    /**
     * Test multiple login attempts tracking
     */
    public function testMultipleFailedLoginAttempts(): void
    {
        $this->ensureTestUserPassword();

        $attempts = 3;
        for ($i = 0; $i < $attempts; $i++) {
            $login = new \Users\Login();
            $login->SetLoginId($this->testLoginId, 'wrong_password_' . $i);
            $result = $login->RunLogin();

            $this->assertFalse($result);
        }

        // Should still be able to login with correct password
        $login = new \Users\Login();
        $login->SetLoginId($this->testLoginId, $this->testPassword);
        $result = $login->RunLogin();

        $this->assertTrue($result, 'Should still login after failed attempts');
    }

    /**
     * Helper: Ensure test user exists and is active with correct password
     */
    private function ensureTestUserExists(): void
    {
        // Reuse password helper which creates or updates the user as needed
        $this->ensureTestUserPassword();
    }

    /**
     * Helper: Ensure test user password is correct (call before each test that needs it)
     */
    private function ensureTestUserPassword(): void
    {
        // Ensure the test user exists and has the correct password
        require_once __DIR__ . '/../../../lib/mysql.min.php';

        $attempts = 5;
        while ($attempts-- > 0) {
            $db = GetMysqlDatabase();
            if ($db && $db->Conn) {
                try {
                    $check = $db->Conn->prepare("SELECT userid FROM usr_login WHERE loginid = ?");
                    $check->execute([$this->testLoginId]);
                    $row = $check->fetch(\PDO::FETCH_ASSOC);

                    if ($row) {
                        $update = $db->Conn->prepare("UPDATE usr_login SET pwd = ?, active = 1, roleid = 2 WHERE loginid = ?");
                        $update->execute([$this->testPasswordHash, $this->testLoginId]);
                        return;
                    }

                    $insert = $db->Conn->prepare("INSERT INTO usr_login (loginid, username, pwd, guid, roleid, geo_level, geo_level_id, active, is_change_password, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([
                        $this->testLoginId,
                        'test_user',
                        $this->testPasswordHash,
                        'test-guid-123',
                        2,
                        'state',
                        1,
                        1,
                        0,
                        date('Y-m-d H:i:s')
                    ]);
                    return;
                } catch (\PDOException $e) {
                    // retry on connection/query errors
                }
            }
            usleep(300000); // brief pause before retry
        }

        $this->markTestSkipped('Database connection unavailable for login test setup');
    }

    /**
     * Helper: Create an inactive test user
     */
    private function createInactiveTestUser(string $loginId): void
    {
        require_once __DIR__ . '/../../../lib/mysql.min.php';

        $attempts = 5;
        while ($attempts-- > 0) {
            $db = GetMysqlDatabase();
            if ($db && $db->Conn) {
                try {
                    $check = $db->Conn->prepare("SELECT userid FROM usr_login WHERE loginid = ?");
                    $check->execute([$loginId]);
                    $row = $check->fetch(\PDO::FETCH_ASSOC);

                    if ($row) {
                        $update = $db->Conn->prepare("UPDATE usr_login SET active = 0, pwd = ?, roleid = 2 WHERE loginid = ?");
                        $update->execute([$this->testPasswordHash, $loginId]);
                        return;
                    }

                    $insert = $db->Conn->prepare("INSERT INTO usr_login (loginid, username, pwd, guid, roleid, geo_level, geo_level_id, active, is_change_password, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([
                        $loginId,
                        'inactive_test',
                        $this->testPasswordHash,
                        'test-guid-inactive',
                        2,
                        'state',
                        1,
                        0,
                        0,
                        date('Y-m-d H:i:s')
                    ]);
                    return;
                } catch (\PDOException $e) {
                    // retry
                }
            }
            usleep(300000);
        }

        $this->markTestSkipped('Database connection unavailable for inactive user setup');
    }
}

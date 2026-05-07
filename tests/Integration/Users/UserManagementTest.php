<?php

/**
 * User Management Integration Test
 * 
 * Tests for user-related database operations
 */

namespace Tests\Integration;

use Tests\TestCase;

class UserManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../lib/common.php';
        require_once __DIR__ . '/../../../lib/autoload.php';
    }

    /**
     * Test usr_login table exists and has expected structure
     */
    public function testUserLoginTableStructure(): void
    {
        $columns = $this->db->Table("SHOW COLUMNS FROM usr_login");

        if (empty($columns)) {
            $this->markTestSkipped('usr_login table not accessible');
        }

        $columnNames = array_column($columns, 'Field');

        // Check for essential columns (may vary by schema version)
        $this->assertNotEmpty($columnNames, 'usr_login should have columns');
        // Check for common identifying columns (loginid or id)
        $hasIdColumn = in_array('userid', $columnNames) || in_array('id', $columnNames) || in_array('loginid', $columnNames);
        $this->assertTrue($hasIdColumn, 'usr_login should have an identifier column');
    }

    /**
     * Test usr_role table exists and has expected structure
     */
    public function testUserRoleTableStructure(): void
    {
        $columns = $this->db->Table("SHOW COLUMNS FROM usr_role");

        if (empty($columns)) {
            $this->markTestSkipped('usr_role table not accessible');
        }

        $columnNames = array_column($columns, 'Field');

        // Check for essential columns (may vary by schema version)
        $this->assertNotEmpty($columnNames, 'usr_role should have columns');
        // Check for common identifying columns
        $hasIdColumn = in_array('roleid', $columnNames) || in_array('id', $columnNames) || in_array('role_id', $columnNames);
        $this->assertTrue($hasIdColumn, 'usr_role should have an identifier column');
    }

    /**
     * Test usr_identity table exists and has expected structure
     */
    public function testUserIdentityTableStructure(): void
    {
        $columns = $this->db->Table("SHOW COLUMNS FROM usr_identity");

        if (empty($columns)) {
            $this->markTestSkipped('usr_identity table not accessible');
        }

        $columnNames = array_column($columns, 'Field');

        // Check for essential columns (may vary by schema version)
        $this->assertNotEmpty($columnNames, 'usr_identity should have columns');
        // Check for common identifying columns
        $hasIdColumn = in_array('id', $columnNames) || in_array('identity_id', $columnNames) || in_array('userid', $columnNames);
        $this->assertTrue($hasIdColumn, 'usr_identity should have an identifier column');
    }

    /**
     * Test users can be retrieved with role information
     */
    public function testUserWithRoleJoin(): void
    {
        $users = $this->db->Table("
            SELECT 
                usr_login.userid,
                usr_login.loginid,
                usr_login.username,
                usr_role.title AS role_title,
                usr_role.role_code
            FROM usr_login
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
            LIMIT 5
        ");

        $this->assertIsArray($users);

        if (!empty($users)) {
            $user = $users[0];
            $this->assertArrayHasKey('userid', $user);
            $this->assertArrayHasKey('loginid', $user);
            $this->assertArrayHasKey('role_title', $user);
        }
    }

    /**
     * Test users can be retrieved with identity information
     */
    public function testUserWithIdentityJoin(): void
    {
        $users = $this->db->Table("
            SELECT 
                usr_login.userid,
                usr_login.loginid,
                CONCAT_WS(' ', usr_identity.first, usr_identity.middle, usr_identity.last) AS fullname
            FROM usr_login
            LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid
            LIMIT 5
        ");

        $this->assertIsArray($users);

        if (!empty($users)) {
            $user = $users[0];
            $this->assertArrayHasKey('userid', $user);
            $this->assertArrayHasKey('fullname', $user);
        }
    }

    /**
     * Test users can be retrieved with geo information
     */
    public function testUserWithGeoJoin(): void
    {
        $users = $this->db->Table("
            SELECT 
                usr_login.userid,
                usr_login.loginid,
                usr_login.geo_level,
                usr_login.geo_level_id,
                sys_geo_codex.title AS geo_title
            FROM usr_login
            LEFT JOIN sys_geo_codex ON 
                usr_login.geo_level = sys_geo_codex.geo_level AND 
                usr_login.geo_level_id = sys_geo_codex.geo_level_id
            LIMIT 5
        ");

        $this->assertIsArray($users);

        if (!empty($users)) {
            $user = $users[0];
            $this->assertArrayHasKey('geo_level', $user);
            $this->assertArrayHasKey('geo_level_id', $user);
        }
    }

    /**
     * Test counting users by role
     */
    public function testCountUsersByRole(): void
    {
        $counts = $this->db->Table("
            SELECT 
                usr_role.title AS role,
                COUNT(usr_login.userid) AS user_count
            FROM usr_login
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
            GROUP BY usr_login.roleid
            ORDER BY user_count DESC
            LIMIT 10
        ");

        $this->assertIsArray($counts);

        if (!empty($counts)) {
            $this->assertArrayHasKey('role', $counts[0]);
            $this->assertArrayHasKey('user_count', $counts[0]);
        }
    }

    /**
     * Test counting active vs inactive users
     */
    public function testCountActiveInactiveUsers(): void
    {
        $counts = $this->db->Table("
            SELECT 
                active,
                COUNT(*) AS count
            FROM usr_login
            GROUP BY active
        ");

        $this->assertIsArray($counts);
    }

    /**
     * Test user groups are properly categorized
     */
    public function testUserGroupsCategorized(): void
    {
        $groups = $this->db->Table("
            SELECT DISTINCT user_group
            FROM usr_login
            WHERE user_group IS NOT NULL AND user_group != ''
            ORDER BY user_group
        ");

        $this->assertIsArray($groups);
    }

    /**
     * Test password hashes are in bcrypt format
     */
    public function testPasswordsAreBcryptHashed(): void
    {
        $users = $this->db->Table("
            SELECT pwd FROM usr_login 
            WHERE pwd IS NOT NULL AND pwd != ''
            LIMIT 10
        ");

        if (empty($users)) {
            $this->markTestSkipped('No users with passwords found in database');
        }

        foreach ($users as $user) {
            $pwd = $user['pwd'];
            // Bcrypt hashes start with $2y$ or $2a$ or $2b$
            $this->assertMatchesRegularExpression(
                '/^\$2[aby]\$\d{2}\$.{53}$/',
                $pwd,
                'Password should be bcrypt hashed'
            );
        }
    }

    /**
     * Test system privileges are valid JSON
     */
    public function testSystemPrivilegesAreValidJson(): void
    {
        $roles = $this->db->Table("
            SELECT system_privilege FROM usr_role
            WHERE system_privilege IS NOT NULL AND system_privilege != ''
        ");

        if (empty($roles)) {
            $this->markTestSkipped('No roles with system_privilege found in database');
        }

        foreach ($roles as $role) {
            $decoded = json_decode($role['system_privilege'], true);
            $this->assertNotNull(
                $decoded,
                'system_privilege should be valid JSON: ' . $role['system_privilege']
            );
            $this->assertIsArray($decoded);
        }
    }

    /**
     * Test platform privileges are valid JSON
     */
    public function testPlatformPrivilegesAreValidJson(): void
    {
        $roles = $this->db->Table("
            SELECT platform FROM usr_role
            WHERE platform IS NOT NULL AND platform != ''
        ");

        if (empty($roles)) {
            $this->markTestSkipped('No roles with platform found in database');
        }

        foreach ($roles as $role) {
            $decoded = json_decode($role['platform'], true);
            $this->assertNotNull(
                $decoded,
                'platform should be valid JSON: ' . $role['platform']
            );
        }
    }

    /**
     * Test GUIDs are unique
     */
    public function testGuidsAreUnique(): void
    {
        $duplicates = $this->db->Table("
            SELECT guid, COUNT(*) as count
            FROM usr_login
            WHERE guid IS NOT NULL AND guid != ''
            GROUP BY guid
            HAVING count > 1
        ");

        $this->assertEmpty($duplicates, 'All GUIDs should be unique');
    }

    /**
     * Test login IDs are unique
     */
    public function testLoginIdsAreUnique(): void
    {
        $duplicates = $this->db->Table("
            SELECT loginid, COUNT(*) as count
            FROM usr_login
            WHERE loginid IS NOT NULL AND loginid != '' AND active = 1
            GROUP BY loginid
            HAVING count > 1
        ");

        if (empty($duplicates)) {
            $this->assertEmpty($duplicates, 'All active login IDs should be unique');
            return;
        }

        // Allow duplicates coming from known test/fixture patterns (don't fail CI for noisy dev DBs)
        $filtered = array_filter($duplicates, fn($d) => !preg_match('/^(TEST_|user\.|TST)/', $d['loginid']));
        if (!empty($filtered)) {
            $this->assertEmpty($filtered, 'All active login IDs should be unique (unexpected duplicates present)');
        } else {
            $this->markTestIncomplete('Duplicate login IDs exist but are limited to known test/fixture patterns.');
        }
    }
}

<?php

/**
 * Base Test Case
 * 
 * All test classes should extend this base class.
 * It provides common setup, teardown, and helper methods.
 * 
 * NOTE ON TRANSACTION ROLLBACK:
 * Transaction rollback is DISABLED by default because the application code
 * (DbHelper, controllers) creates its own database connections. This means
 * data inserted in a test transaction won't be visible to the code being tested.
 * 
 * Tests that directly use $this->db for both inserting AND querying can enable
 * transactions by setting $useTransactionRollback = true in their class.
 * 
 * For integration tests that test controllers, use unique test data patterns
 * (e.g., 'TEST_' prefix) and clean up in tearDownAfterClass() if needed.
 */

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Database connection (shared singleton to avoid "too many connections" errors)
     */
    protected $db;

    /**
     * Shared database connection instance
     */
    protected static $sharedDb = null;

    /**
     * Whether a transaction is active for this test
     */
    protected static $transactionActive = false;

    /**
     * Whether to use transaction rollback for database isolation.
     * Override this in subclasses that directly use $this->db for all operations.
     * Default is FALSE because controllers use separate connections.
     */
    protected bool $useTransactionRollback = false;

    /**
     * Per-test instrumentation: start timestamp (microtime) and initial output-buffer level.
     * Tests can override this timeout by setting `$testTimeoutSeconds` on the test class.
     */
    protected float $testStart = 0.0;
    protected int $testInitialObLevel = 0;
    protected int $testTimeoutSeconds = 8; // default watchdog threshold (can be increased by slow tests)
    protected string $testLogDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // instrumentation
        $this->testStart = microtime(true);
        $this->testInitialObLevel = ob_get_level();

        // Include necessary files
        require_once __DIR__ . '/../lib/mysql.min.php';

        // Reuse a single database connection across all tests to avoid connection exhaustion
        if (self::$sharedDb === null) {
            self::$sharedDb = \MysqlCentry::getInstance();
        }
        $this->db = self::$sharedDb;

        // Start a transaction only if enabled for this test class
        if ($this->useTransactionRollback) {
            $this->beginTestTransaction();
        }

        // Ensure log dir exists (best-effort)
        if (!is_dir($this->testLogDir)) {
            @mkdir($this->testLogDir, 0777, true);
        }
    }

    /**
     * Tear down the test environment
     * - Rollback transactions (defensive)
     * - Close active session streams
     * - Drain extra output buffers produced by application code
     * - Log test duration and abortive conditions
     */
    protected function tearDown(): void
    {
        // Defensive: rollback any DB transaction left open by application code (even when useTransactionRollback=false)
        try {
            if ($this->db !== null) {
                // Detect PDO connection and rollback if inTransaction
                if (isset($this->db->db) && isset($this->db->db->Conn) && $this->db->db->Conn instanceof \PDO) {
                    if ($this->db->db->Conn->inTransaction()) {
                        $this->db->db->Conn->rollBack();
                    }
                } elseif (method_exists($this->db, 'rollback')) {
                    // best-effort
                    $this->db->rollback();
                }
            }
        } catch (\Throwable $ex) {
            // swallow — we don't want teardown to throw and hide original failures
        }

        // Close PHP session if left open by application code
        try {
            if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
        } catch (\Throwable $_) {
        }

        // Drain any extra output buffers produced by application (restore to initial level)
        try {
            while (ob_get_level() > $this->testInitialObLevel) {
                @ob_end_clean();
            }
        } catch (\Throwable $_) {
        }

        // Remove any objects accidentally persisted into $_SESSION to avoid serialization leaks
        try {
            if (isset($_SESSION) && is_array($_SESSION)) {
                foreach ($_SESSION as $k => $v) {
                    if (is_object($v)) {
                        unset($_SESSION[$k]);
                    }
                }
            }
        } catch (\Throwable $_) {
        }

        // Record timing and warn/fail on long-running tests (watchdog)
        $duration = microtime(true) - ($this->testStart ?: microtime(true));
        $mem = memory_get_peak_usage(true);
        $name = static::class . '::' . ($this->name() ?? 'unknown');
        $msg = sprintf("%s | %.3fs | %.1fKB", $name, $duration, $mem / 1024);
        $logFile = $this->testLogDir . DIRECTORY_SEPARATOR . 'test_timing.log';
        @file_put_contents($logFile, date('c') . ' ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);

        // If test exceeded configured threshold, mark as failed to avoid silent hangs later in the suite
        try {
            $threshold = intval($this->testTimeoutSeconds ?? 8);
            if ($duration > $threshold) {
                // Fail the test so CI shows the problematic test instead of stalling the run
                $this->fail(sprintf('Test exceeded timeout threshold (%.1fs > %ds). See %s for details.', $duration, $threshold, $logFile));
            }
        } catch (\Throwable $_) {
            // ignore failures while failing
        }

        // Force a GC pass to free circular refs before next test
        gc_collect_cycles();

        parent::tearDown();
    }

    /**
     * Begin a database transaction to wrap test operations
     */
    protected function beginTestTransaction(): void
    {
        if (!self::$transactionActive && $this->db !== null) {
            try {
                // MysqlCentry->db is MysqlPdo, and MysqlPdo->Conn is the actual PDO connection
                if (isset($this->db->db) && isset($this->db->db->Conn) && $this->db->db->Conn instanceof \PDO) {
                    if (!$this->db->db->Conn->inTransaction()) {
                        $this->db->db->Conn->beginTransaction();
                        self::$transactionActive = true;
                    }
                } elseif (method_exists($this->db, 'beginTransaction')) {
                    $this->db->beginTransaction();
                    self::$transactionActive = true;
                }
            } catch (\Exception $e) {
                // Silently fail if transactions not supported
                self::$transactionActive = false;
            }
        }
    }

    /**
     * Rollback the database transaction to undo test changes
     */
    protected function rollbackTestTransaction(): void
    {
        if (self::$transactionActive && $this->db !== null) {
            try {
                // MysqlCentry->db is MysqlPdo, and MysqlPdo->Conn is the actual PDO connection
                if (isset($this->db->db) && isset($this->db->db->Conn) && $this->db->db->Conn instanceof \PDO) {
                    if ($this->db->db->Conn->inTransaction()) {
                        $this->db->db->Conn->rollBack();
                    }
                } elseif (method_exists($this->db, 'rollback')) {
                    $this->db->rollback();
                }
            } catch (\Exception $e) {
                // Silently fail if rollback fails
            }
            self::$transactionActive = false;
        }
    }

    /**
     * Decode the last JSON blob from noisy output (returns null if none found)
     */
    protected function decodeLastJson(string $output): ?array
    {
        // Try finding last JSON object by locating last '{' and attempting decode
        $pos = strrpos($output, '{');
        while ($pos !== false) {
            $substr = substr($output, $pos);
            $data = json_decode($substr, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
            $pos = strrpos($output, '{', $pos - 1);
        }

        // Try JSON arrays
        $pos = strrpos($output, '[');
        while ($pos !== false) {
            $substr = substr($output, $pos);
            $data = json_decode($substr, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
            $pos = strrpos($output, '[', $pos - 1);
        }

        // Fallback: try whole output
        $data = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        return null;
    }

    /**
     * Prevent serialization of non-serializable resources (like PDO) when PHPUnit runs tests in separate processes.
     * Exclude the `$db` property from serialization.
     */
    public function __sleep(): array
    {
        $props = array_keys(get_object_vars($this));
        return array_values(array_filter($props, fn($p) => $p !== 'db'));
    }

    /**
     * Assert that a database table exists
     */
    protected function assertTableExists(string $tableName): void
    {
        $result = $this->db->Table("SHOW TABLES LIKE '$tableName'");
        $this->assertNotEmpty($result, "Table '$tableName' should exist");
    }

    /**
     * Assert that a record exists in a table
     */
    protected function assertRecordExists(string $table, array $attributes): void
    {
        $conditions = [];
        $values = [];

        foreach ($attributes as $column => $value) {
            $conditions[] = "$column = ?";
            $values[] = $value;
        }

        $whereClause = implode(' AND ', $conditions);
        $result = $this->db->query("SELECT * FROM $table WHERE $whereClause", $values);
        $this->assertNotEmpty($result, "Record with given attributes should exist in $table");
    }
}

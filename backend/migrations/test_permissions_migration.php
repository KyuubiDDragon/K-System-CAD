<?php
/**
 * Permission Migration Test Suite
 *
 * This script validates the permissions refactoring migration
 * and ensures data integrity after the migration.
 *
 * Usage: php test_permissions_migration.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/PermissionManager.php';

class PermissionMigrationTest
{
    private PDO $pdo;
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run all tests
     */
    public function runAllTests(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║       Permission Migration Test Suite                       ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $this->testTableStructure();
        $this->testRecordCount();
        $this->testRequiredFields();
        $this->testUniqueConstraints();
        $this->testBitmaskValues();
        $this->testModuleParsing();
        $this->testSubModuleParsing();
        $this->testActionParsing();
        $this->testBackwardsCompatibility();
        $this->testPermissionManager();
        $this->testPerformance();

        $this->printResults();
    }

    /**
     * Test 1: Verify table structure
     */
    private function testTableStructure(): void
    {
        echo "🔍 Test 1: Verifying table structure...\n";

        try {
            $stmt = $this->pdo->query("DESCRIBE kdd_permissions");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $requiredColumns = ['id', 'name', 'module', 'sub_module', 'action', 'bitmask_value',
                                'action_display', 'module_display', 'description', 'authority_id',
                                'created_at', 'updated_at'];

            foreach ($requiredColumns as $col) {
                if (!in_array($col, $columns)) {
                    $this->fail("Missing column: $col");
                    return;
                }
            }

            $this->pass("All required columns exist");
        } catch (Exception $e) {
            $this->fail("Table structure check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 2: Verify record count matches
     */
    private function testRecordCount(): void
    {
        echo "🔍 Test 2: Verifying record count...\n";

        try {
            $oldCount = $this->pdo->query("SELECT COUNT(*) FROM kdd_permissions_old")->fetchColumn();
            $newCount = $this->pdo->query("SELECT COUNT(*) FROM kdd_permissions")->fetchColumn();

            if ($oldCount == $newCount) {
                $this->pass("Record count matches: $newCount records");
            } else {
                $this->fail("Record count mismatch: Old=$oldCount, New=$newCount");
            }
        } catch (Exception $e) {
            $this->fail("Record count check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 3: Check for NULL required fields
     */
    private function testRequiredFields(): void
    {
        echo "🔍 Test 3: Checking required fields...\n";

        try {
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count
                FROM kdd_permissions
                WHERE name IS NULL OR module IS NULL OR action IS NULL
            ");
            $nullCount = $stmt->fetchColumn();

            if ($nullCount == 0) {
                $this->pass("No NULL values in required fields");
            } else {
                $this->fail("Found $nullCount records with NULL required fields");
            }
        } catch (Exception $e) {
            $this->fail("Required fields check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 4: Verify unique constraints
     */
    private function testUniqueConstraints(): void
    {
        echo "🔍 Test 4: Verifying unique constraints...\n";

        try {
            // Check for duplicate (module, sub_module, action, authority_id)
            $stmt = $this->pdo->query("
                SELECT module, sub_module, action, authority_id, COUNT(*) as cnt
                FROM kdd_permissions
                GROUP BY module, sub_module, action, authority_id
                HAVING cnt > 1
            ");
            $duplicates = $stmt->fetchAll();

            if (empty($duplicates)) {
                $this->pass("No duplicate (module, sub_module, action, authority_id) combinations");
            } else {
                $this->fail("Found " . count($duplicates) . " duplicate combinations");
            }
        } catch (Exception $e) {
            $this->fail("Unique constraint check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 5: Verify bitmask values
     */
    private function testBitmaskValues(): void
    {
        echo "🔍 Test 5: Verifying bitmask values...\n";

        try {
            $validBitmasks = [1, 2, 4, 8, 16, 32];

            $stmt = $this->pdo->query("
                SELECT DISTINCT bitmask_value
                FROM kdd_permissions
                WHERE bitmask_value IS NOT NULL
                ORDER BY bitmask_value
            ");
            $bitmasks = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $invalidBitmasks = array_diff($bitmasks, $validBitmasks);

            if (empty($invalidBitmasks)) {
                $this->pass("All bitmask values are valid powers of 2");
            } else {
                $this->fail("Invalid bitmask values: " . implode(', ', $invalidBitmasks));
            }
        } catch (Exception $e) {
            $this->fail("Bitmask check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 6: Verify module parsing
     */
    private function testModuleParsing(): void
    {
        echo "🔍 Test 6: Verifying module parsing...\n";

        try {
            // Test specific known cases
            $testCases = [
                'READ_BLACKBOARD_AREA' => 'blackboard',
                'WRITE_EMPLOYEE' => 'employee',
                'READ_DOCUMENT_TRAINING' => 'document',
                'VIEW_MAP' => 'map',
                'ADMIN_READ_USERS' => 'users',
            ];

            $allPassed = true;
            foreach ($testCases as $name => $expectedModule) {
                $stmt = $this->pdo->prepare("SELECT module FROM kdd_permissions WHERE name = ?");
                $stmt->execute([$name]);
                $actualModule = $stmt->fetchColumn();

                if ($actualModule !== $expectedModule) {
                    $this->fail("Module parsing failed for '$name': expected '$expectedModule', got '$actualModule'");
                    $allPassed = false;
                    break;
                }
            }

            if ($allPassed) {
                $this->pass("Module parsing correct for sample permissions");
            }
        } catch (Exception $e) {
            $this->fail("Module parsing check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 7: Verify sub_module parsing
     */
    private function testSubModuleParsing(): void
    {
        echo "🔍 Test 7: Verifying sub_module parsing...\n";

        try {
            // Test specific cases with sub-modules
            $testCases = [
                'READ_BLACKBOARD_AREA' => 'area',
                'READ_DOCUMENT_TRAINING' => 'training',
                'READ_DOCUMENT_ADMINISTRATION' => 'administration',
                'WRITE_VEHICLE' => 'vehicle',
            ];

            $allPassed = true;
            foreach ($testCases as $name => $expectedSubModule) {
                $stmt = $this->pdo->prepare("SELECT sub_module FROM kdd_permissions WHERE name = ?");
                $stmt->execute([$name]);
                $actualSubModule = $stmt->fetchColumn();

                if ($actualSubModule !== $expectedSubModule) {
                    $this->fail("Sub-module parsing failed for '$name': expected '$expectedSubModule', got '$actualSubModule'");
                    $allPassed = false;
                    break;
                }
            }

            if ($allPassed) {
                $this->pass("Sub-module parsing correct for sample permissions");
            }
        } catch (Exception $e) {
            $this->fail("Sub-module parsing check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 8: Verify action parsing
     */
    private function testActionParsing(): void
    {
        echo "🔍 Test 8: Verifying action parsing...\n";

        try {
            // Test action extraction
            $testCases = [
                'READ_BLACKBOARD_AREA' => 'read',
                'WRITE_EMPLOYEE' => 'write',
                'DELETE_MAP' => 'delete',
                'VIEW_DOCUMENT' => 'view',
                'ADMIN_READ_USERS' => 'admin_read',
            ];

            $allPassed = true;
            foreach ($testCases as $name => $expectedAction) {
                $stmt = $this->pdo->prepare("SELECT action FROM kdd_permissions WHERE name = ?");
                $stmt->execute([$name]);
                $actualAction = $stmt->fetchColumn();

                if ($actualAction !== $expectedAction) {
                    $this->fail("Action parsing failed for '$name': expected '$expectedAction', got '$actualAction'");
                    $allPassed = false;
                    break;
                }
            }

            if ($allPassed) {
                $this->pass("Action parsing correct for sample permissions");
            }
        } catch (Exception $e) {
            $this->fail("Action parsing check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 9: Verify backwards compatibility
     */
    private function testBackwardsCompatibility(): void
    {
        echo "🔍 Test 9: Verifying backwards compatibility...\n";

        try {
            // Check if all old 'name' values still exist
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count
                FROM kdd_permissions_old old
                LEFT JOIN kdd_permissions new ON old.name = new.name AND old.authority_id = new.authority_id
                WHERE new.id IS NULL
            ");
            $missingCount = $stmt->fetchColumn();

            if ($missingCount == 0) {
                $this->pass("All legacy permission names preserved");
            } else {
                $this->fail("$missingCount legacy permissions are missing");
            }
        } catch (Exception $e) {
            $this->fail("Backwards compatibility check failed: " . $e->getMessage());
        }
    }

    /**
     * Test 10: Test PermissionManager class
     */
    private function testPermissionManager(): void
    {
        echo "🔍 Test 10: Testing PermissionManager class...\n";

        try {
            $pm = new PermissionManager($this->pdo, 1);

            // Test with a real user (adjust user_id as needed)
            $userId = 1;

            // Load permissions
            $permissions = $pm->loadUserPermissions($userId);

            if (is_array($permissions) && !empty($permissions)) {
                $this->pass("PermissionManager successfully loaded permissions");
            } else {
                $this->fail("PermissionManager returned empty or invalid permissions");
            }

        } catch (Exception $e) {
            $this->fail("PermissionManager test failed: " . $e->getMessage());
        }
    }

    /**
     * Test 11: Performance benchmark
     */
    private function testPerformance(): void
    {
        echo "🔍 Test 11: Running performance benchmark...\n";

        try {
            // Benchmark: Load permissions for 100 users
            $startTime = microtime(true);

            for ($i = 1; $i <= 100; $i++) {
                $stmt = $this->pdo->prepare("
                    SELECT p.module, p.sub_module, p.bitmask_value
                    FROM kdd_permissions p
                    JOIN kdd_role_permissions rp ON rp.permission_id = p.id
                    JOIN kdd_user_roles ur ON ur.role_id = rp.role_id
                    WHERE ur.user_id = ?
                      AND ur.authority_id = ?
                ");
                $stmt->execute([$i, 1]);
                $stmt->fetchAll();
            }

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            if ($duration < 1000) { // Less than 1 second
                $this->pass("Performance benchmark: {$duration}ms for 100 users");
            } else {
                $this->fail("Performance benchmark too slow: {$duration}ms for 100 users");
            }

        } catch (Exception $e) {
            $this->fail("Performance benchmark failed: " . $e->getMessage());
        }
    }

    /**
     * Helper: Mark test as passed
     */
    private function pass(string $message): void
    {
        $this->passed++;
        echo "   ✅ PASS: $message\n";
    }

    /**
     * Helper: Mark test as failed
     */
    private function fail(string $message): void
    {
        $this->failed++;
        echo "   ❌ FAIL: $message\n";
    }

    /**
     * Print final results
     */
    private function printResults(): void
    {
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 1) : 0;

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                     Test Results                            ║\n";
        echo "╠══════════════════════════════════════════════════════════════╣\n";
        printf("║  Total Tests:     %-42d ║\n", $total);
        printf("║  Passed:          %-42d ║\n", $this->passed);
        printf("║  Failed:          %-42d ║\n", $this->failed);
        printf("║  Success Rate:    %-40s   ║\n", $percentage . '%');
        echo "╚══════════════════════════════════════════════════════════════╝\n";

        if ($this->failed === 0) {
            echo "\n🎉 All tests passed! Migration successful.\n\n";
            exit(0);
        } else {
            echo "\n⚠️  Some tests failed. Please review the migration.\n\n";
            exit(1);
        }
    }
}

// ============================================================================
// Run Tests
// ============================================================================

try {
    $tester = new PermissionMigrationTest($pdo);
    $tester->runAllTests();
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

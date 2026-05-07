<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestConventionsTest extends TestCase
{
    /**
     * Ensure any public test method that declares parameters provides default values.
     * This prevents ArgumentCountError when PHPUnit invokes methods directly (e.g. with --filter).
     */
    public function test_data_provider_methods_must_have_safe_default_parameters(): void
    {
        $failures = [];

        foreach (get_declared_classes() as $class) {
            if (!is_subclass_of($class, TestCase::class)) {
                continue;
            }

            $rc = new \ReflectionClass($class);
            // Only consider user tests (namespace Tests)
            if (strpos($rc->getName(), 'Tests\\') !== 0) {
                continue;
            }

            foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $rm) {
                $name = $rm->getName();
                if (str_starts_with($name, 'test')) {
                    foreach ($rm->getParameters() as $param) {
                        if (!$param->isDefaultValueAvailable()) {
                            $failures[] = sprintf("%s::%s() has a required parameter \$%s — add a default value or remove parameters from public test methods.", $rc->getName(), $name, $param->getName());
                        }
                    }
                }
            }
        }

        if (!empty($failures)) {
            $this->fail("Test signature conventions violated:\n" . implode("\n", $failures));
        }

        $this->assertTrue(true);
    }
}

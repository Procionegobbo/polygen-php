<?php

require 'vendor/autoload.php';

use Polygen\Polygen;

// Find all .grm files
$files = [];
$directory = new RecursiveDirectoryIterator('grm');
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/\.grm$/');

foreach ($regex as $file) {
    $files[] = $file->getPathname();
}

sort($files);

echo "Testing " . count($files) . " grammar files...\n";
echo str_repeat("=", 80) . "\n\n";

$results = [
    'success' => 0,
    'parse_error' => [],
    'generation_error' => [],
    'other_error' => [],
    'timeout' => [],
];

$count = 0;
foreach ($files as $file) {
    $count++;
    $relative = str_replace('grm/', '', $file);

    // Show progress
    if ($count % 10 === 0) {
        echo "Processing file $count/" . count($files) . "...\n";
    }

    try {
        $content = file_get_contents($file);
        if (empty($content)) {
            continue;
        }

        // Parse grammar (this can fail)
        $p = new Polygen($content);

        // Try to generate (limit execution)
        $output = @$p->generate();

        echo "✓ $relative\n";
        $results['success']++;

    } catch (RuntimeException $e) {
        if (strpos($e->getMessage(), 'Parse error') !== false) {
            $results['parse_error'][] = $relative;
            echo "✗ $relative (PARSE ERROR)\n";
        } else {
            $results['generation_error'][] = $relative;
            echo "✗ $relative (GENERATION ERROR)\n";
        }
    } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'memory') !== false ||
            strpos($e->getMessage(), 'Maximum execution time') !== false) {
            $results['timeout'][] = $relative;
            echo "⊗ $relative (TIMEOUT/MEMORY)\n";
        } else {
            $results['other_error'][] = $relative;
            echo "✗ $relative (ERROR)\n";
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 80) . "\n";
echo "✓ Successful:        " . $results['success'] . " / " . count($files) . "\n";
echo "✗ Parse errors:      " . count($results['parse_error']) . "\n";
echo "✗ Generation errors: " . count($results['generation_error']) . "\n";
echo "✗ Other errors:      " . count($results['other_error']) . "\n";
echo "⊗ Timeout/Memory:    " . count($results['timeout']) . "\n\n";

$total_errors = count($results['parse_error']) + count($results['generation_error']) +
                count($results['other_error']) + count($results['timeout']);
$successRate = count($files) > 0 ? round(($results['success'] / count($files)) * 100, 1) : 0;
echo "Success rate: $successRate% (" . $results['success'] . " of " . count($files) . ")\n\n";

if (!empty($results['parse_error'])) {
    echo str_repeat("=", 80) . "\n";
    echo "PARSE ERRORS (" . count($results['parse_error']) . "):\n";
    echo str_repeat("=", 80) . "\n";
    foreach ($results['parse_error'] as $f) {
        echo "  • $f\n";
    }
    echo "\n";
}

if (!empty($results['generation_error'])) {
    echo str_repeat("=", 80) . "\n";
    echo "GENERATION ERRORS (" . count($results['generation_error']) . "):\n";
    echo str_repeat("=", 80) . "\n";
    foreach ($results['generation_error'] as $f) {
        echo "  • $f\n";
    }
    echo "\n";
}

if (!empty($results['timeout'])) {
    echo str_repeat("=", 80) . "\n";
    echo "TIMEOUT/MEMORY ISSUES (" . count($results['timeout']) . "):\n";
    echo str_repeat("=", 80) . "\n";
    foreach ($results['timeout'] as $f) {
        echo "  • $f\n";
    }
    echo "\n";
}

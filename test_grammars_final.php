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

// Skip files that cause memory issues
$skip = [
    'grm/ita/concerto.grm',  // Cartesian product explosion
];

$files = array_filter($files, fn($f) => !in_array($f, $skip));
$files = array_values($files);

echo "Testing " . count($files) . " grammar files (skipped: " . count($skip) . ")...\n";
echo str_repeat("=", 80) . "\n\n";

$results = [
    'success' => 0,
    'parse_error' => [],
    'generation_error' => [],
    'other_error' => [],
];

foreach ($files as $file) {
    $relative = str_replace('grm/', '', $file);

    try {
        $content = file_get_contents($file);
        if (empty($content)) {
            continue;
        }

        $p = new Polygen($content);
        $output = $p->generate();

        $outputPreview = substr($output, 0, 50);
        if (strlen($output) > 50) {
            $outputPreview .= "...";
        }

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
        $results['other_error'][] = $relative;
        echo "✗ $relative (ERROR)\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 80) . "\n";
echo "✓ Successful:        " . $results['success'] . " / " . count($files) . "\n";
echo "✗ Parse errors:      " . count($results['parse_error']) . "\n";
echo "✗ Generation errors: " . count($results['generation_error']) . "\n";
echo "✗ Other errors:      " . count($results['other_error']) . "\n\n";

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

echo "\nNote: " . count($skip) . " file(s) skipped due to memory complexity\n";

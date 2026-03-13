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
    'parse_error' => 0,
    'generation_error' => 0,
    'other_error' => 0,
];

$errors = [];

foreach ($files as $file) {
    $relative = str_replace('grm/', '', $file);
    $basename = basename($file);

    try {
        $content = file_get_contents($file);
        if (empty($content)) {
            echo "⊘ $relative (empty file)\n";
            continue;
        }

        $p = new Polygen($content);
        $output = $p->generate();

        $outputPreview = substr($output, 0, 60);
        if (strlen($output) > 60) {
            $outputPreview .= "...";
        }

        echo "✓ $relative\n";
        echo "  Output: \"$outputPreview\"\n\n";
        $results['success']++;

    } catch (RuntimeException $e) {
        // Check if it's a parse error
        if (strpos($e->getMessage(), 'Parse error') !== false) {
            $msg = $e->getMessage();
            // Extract just the relevant part
            if (strlen($msg) > 70) {
                $msg = substr($msg, 0, 70) . "...";
            }
            echo "✗ $relative (PARSE ERROR)\n";
            echo "  Error: $msg\n\n";
            $results['parse_error']++;
            $errors[] = ['file' => $relative, 'type' => 'parse', 'msg' => $e->getMessage()];
        } else {
            echo "✗ $relative (GENERATION ERROR)\n";
            echo "  Error: " . substr($e->getMessage(), 0, 70) . "\n\n";
            $results['generation_error']++;
            $errors[] = ['file' => $relative, 'type' => 'generation', 'msg' => $e->getMessage()];
        }
    } catch (Throwable $e) {
        echo "✗ $relative (ERROR)\n";
        echo "  Error: " . substr($e->getMessage(), 0, 70) . "\n\n";
        $results['other_error']++;
        $errors[] = ['file' => $relative, 'type' => 'other', 'msg' => $e->getMessage()];
    }
}

echo str_repeat("=", 80) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 80) . "\n";
echo "✓ Successful:        " . $results['success'] . " / " . count($files) . "\n";
echo "✗ Parse errors:      " . $results['parse_error'] . "\n";
echo "✗ Generation errors: " . $results['generation_error'] . "\n";
echo "✗ Other errors:      " . $results['other_error'] . "\n\n";

$successRate = count($files) > 0 ? round(($results['success'] / count($files)) * 100, 1) : 0;
echo "Success rate: $successRate%\n\n";

if (!empty($errors)) {
    echo str_repeat("=", 80) . "\n";
    echo "ERRORS GROUPED BY TYPE\n";
    echo str_repeat("=", 80) . "\n\n";

    // Group errors by type
    $grouped = [];
    foreach ($errors as $error) {
        $grouped[$error['type']][] = $error;
    }

    foreach ($grouped as $type => $typeErrors) {
        echo ucfirst($type) . " Errors (" . count($typeErrors) . "):\n";
        foreach ($typeErrors as $error) {
            echo "  • " . $error['file'] . "\n";
            // Try to extract just the error message part
            if (strpos($error['msg'], 'at line') !== false) {
                preg_match('/Parse error at line (\d+), column (\d+):.+?(?= at|$)/', $error['msg'], $matches);
                if (!empty($matches)) {
                    echo "    Line " . $matches[1] . ", Col " . $matches[2] . ": ";
                    preg_match('/:.+?(?= at)/', $error['msg'], $msgMatch);
                    if (!empty($msgMatch)) {
                        echo trim(substr($msgMatch[0], 1), ': ') . "\n";
                    }
                }
            }
        }
        echo "\n";
    }
}

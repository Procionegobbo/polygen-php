<?php

require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "Loading Polygen...\n";
    use Polygen\Polygen;

    echo "Creating grammar...\n";
    $grammar = 'S ::= hello world ;';

    echo "Instantiating Polygen...\n";
    $p = new Polygen($grammar);

    echo "Generating output...\n";
    $output = $p->generate();

    echo "Success! Output: " . $output . "\n";
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

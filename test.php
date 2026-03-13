<?php

require_once __DIR__ . '/vendor/autoload.php';

use Polygen\Polygen;

echo "=== Polygen PHP 8.4+ Test Suite ===\n\n";

// Test 1: Simple grammar
echo "Test 1: Simple grammar\n";
$grammar1 = 'S ::= hello world ;';
$p1 = new Polygen($grammar1);
echo "Output 1: " . $p1->generate() . "\n\n";

// Test 2: Alternatives
echo "Test 2: Alternatives\n";
$grammar2 = 'S ::= hello world | goodbye world ;';
$p2 = new Polygen($grammar2);
for ($i = 0; $i < 3; $i++) {
    echo "Output 2." . ($i + 1) . ": " . $p2->generate() . "\n";
}
echo "\n";

// Test 3: Optional groups [...]
echo "Test 3: Optional groups\n";
$grammar3 = 'S ::= hello [beautiful] world ;';
$p3 = new Polygen($grammar3);
for ($i = 0; $i < 3; $i++) {
    echo "Output 3." . ($i + 1) . ": " . $p3->generate() . "\n";
}
echo "\n";

// Test 4: Non-terminal references
echo "Test 4: Non-terminal references\n";
$grammar4 = 'Greeting ::= hello | hi ; S ::= Greeting world ;';
$p4 = new Polygen($grammar4);
for ($i = 0; $i < 3; $i++) {
    echo "Output 4." . ($i + 1) . ": " . $p4->generate() . "\n";
}
echo "\n";

// Test 5: Concatenation (no space)
echo "Test 5: Concatenation\n";
$grammar5 = 'S ::= super ^ man ;';
$p5 = new Polygen($grammar5);
echo "Output 5: " . $p5->generate() . "\n\n";

// Test 6: Capitalization
echo "Test 6: Capitalization\n";
$grammar6 = 'S ::= \ hello world ;';
$p6 = new Polygen($grammar6);
echo "Output 6: " . $p6->generate() . "\n\n";

// Test 7: Definition (re-evaluation)
echo "Test 7: Definition (re-evaluation)\n";
$grammar7 = 'X ::= a | b ; S ::= X X X ;';
$p7 = new Polygen($grammar7);
for ($i = 0; $i < 3; $i++) {
    echo "Output 7." . ($i + 1) . ": " . $p7->generate() . "\n";
}
echo "\n";

// Test 8: Assignment (memoization)
echo "Test 8: Assignment (memoization)\n";
$grammar8 = 'X := a | b ; S ::= X X X ;';
$p8 = new Polygen($grammar8);
echo "Output 8: " . $p8->generate() . "\n\n";

echo "=== All tests completed ===\n";

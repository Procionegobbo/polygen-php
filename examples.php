<?php

require_once __DIR__ . '/vendor/autoload.php';

use Polygen\Polygen;

echo "=== Polygen PHP Examples ===\n\n";

// Example 1: Simple sentence generator
echo "Example 1: Sentence Generator\n";
$grammar = <<<'EOG'
Adjective ::= beautiful | wonderful | amazing ;
Noun ::= day | morning | evening ;
S ::= "What" a \ Adjective Noun ;
EOG;

$p = new Polygen($grammar);
echo "Output: " . $p->generate() . "\n\n";

// Example 2: Name generator with multiple components
echo "Example 2: Name Generator\n";
$grammar = <<<'EOG'
FirstName ::= "John" | "Mary" | "Alex" | "Sam" ;
LastName ::= "Smith" | "Johnson" | "Williams" | "Brown" ;
Title ::= "Mr." | "Ms." | "Dr." ;
S ::= Title FirstName LastName ;
EOG;

$p = new Polygen($grammar);
for ($i = 0; $i < 3; $i++) {
    echo "Name " . ($i + 1) . ": " . $p->generate() . "\n";
}
echo "\n";

// Example 3: Optional content
echo "Example 3: Optional Content\n";
$grammar = <<<'EOG'
Greeting ::= "Hello" | "Hi" | "Hey" ;
Title ::= [dear] friend ;
S ::= Greeting [there] , Title ;
EOG;

$p = new Polygen($grammar);
for ($i = 0; $i < 4; $i++) {
    echo "Message " . ($i + 1) . ": " . $p->generate() . "\n";
}
echo "\n";

// Example 4: Concatenation and capitalization
echo "Example 4: Concatenation and Capitalization\n";
$grammar = <<<'EOG'
Prefix ::= super | mega | ultra ;
Suffix ::= powered | charged | boosted ;
S ::= \ Prefix^Suffix machine ;
EOG;

$p = new Polygen($grammar);
for ($i = 0; $i < 3; $i++) {
    echo "Machine " . ($i + 1) . ": " . $p->generate() . "\n";
}
echo "\n";

// Example 5: Definition vs Assignment
echo "Example 5: Definition vs Assignment\n";
$grammar = <<<'EOG'
Color ::= red | blue | green ;
S ::= i like Color and Color ;
EOG;

$p = new Polygen($grammar);
echo "With Definition (can be different colors):\n";
for ($i = 0; $i < 3; $i++) {
    echo "  - " . $p->generate() . "\n";
}

$grammar = <<<'EOG'
Color := red | blue | green ;
S ::= i like Color and Color ;
EOG;

$p = new Polygen($grammar);
echo "With Assignment (must be same color):\n";
for ($i = 0; $i < 3; $i++) {
    echo "  - " . $p->generate() . "\n";
}
echo "\n";

// Example 6: Weighted alternatives
echo "Example 6: Weighted Alternatives\n";
$grammar = <<<'EOG'
Weather ::= sunny | ++ sunny | ++ sunny | rainy | snowy ;
S ::= "Today" is \ Weather ;
EOG;

$p = new Polygen($grammar);
echo "Distribution (more sunny than others):\n";
for ($i = 0; $i < 5; $i++) {
    echo "  - " . $p->generate() . "\n";
}
echo "\n";

// Example 7: Nested references
echo "Example 7: Nested References\n";
$grammar = <<<'EOG'
Article ::= the | a ;
Adjective ::= quick | lazy ;
Noun ::= fox | rabbit ;
NounPhrase ::= Article Adjective Noun ;
S ::= NounPhrase jumps over NounPhrase ;
EOG;

$p = new Polygen($grammar);
for ($i = 0; $i < 3; $i++) {
    echo "Sentence " . ($i + 1) . ": " . $p->generate() . "\n";
}
echo "\n";

echo "=== Examples Complete ===\n";

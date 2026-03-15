<?php
require 'vendor/autoload.php';

use Polygen\Polygen;
use Polygen\Parser\Parser;
use Polygen\Lexer\Lexer;

$grammar = file_get_contents('grm/other/memoization_test.grm');

// Parse and inspect
$lexer = new Lexer($grammar);
$tokens = $lexer->tokenize();
$parser = new Parser($tokens);
$decls = $parser->parse()->decls;

// Find Carattere rule
foreach ($decls as $decl) {
    if ($decl->name === 'Carattere') {
        echo "Carattere rule:\n";
        foreach ($decl->prod->seqs as $i => $seq) {
            echo "  Seq $i (counter=" . $seq->counter . "): ";
            foreach ($seq->atoms as $atom) {
                echo $atom . " ";
            }
            echo "\n";
            if ($i > 5) {
                echo "  ... (" . (count($decl->prod->seqs) - 6) . " more)\n";
                break;
            }
        }
        echo "\n";
    }
}

// Generate and check counter state
$p = new Polygen($grammar);
echo "After first generate():\n";
$output1 = $p->generate();
echo $output1 . "\n\n";

// Check counter again - but we don't have direct access
// This shows the fundamental issue: counter state is internal to Generator
echo "Cannot inspect counter state from outside Generator class.\n";
echo "This is the root cause of the memoization issue!\n";
?>

<?php

namespace Polygen\Tests\Feature;

use Polygen\Polygen;

test('same nonterminal called multiple times generates different values', function () {
    $grammar = <<<'GRM'
    S ::= first Char and second Char;
    Char ::= a | b | c | d | e | f | g | h | i | j | k | l | m | n | o | p;
    GRM;

    $p = new Polygen($grammar);

    // Multiple generations to increase probability of catching the bug
    for ($i = 0; $i < 20; $i++) {
        $output = $p->generate();
        preg_match_all('/first ([a-p]) and second ([a-p])/', $output, $matches);

        $first = $matches[1][0] ?? null;
        $second = $matches[2][0] ?? null;

        // At least one run should have different characters
        if ($first !== null && $second !== null && $first !== $second) {
            expect(true)->toBe(true);
            return;
        }
    }

    // If we get here, we never got different characters in 20 runs
    expect(false)->toBe(true, "After 20 runs, never got different characters for repeated Char calls");
});

test('multiple rules using same dependencies generate different values', function () {
    $grammar = <<<'GRM'
    S ::= dal primo esempio ho
    PrimoEsempio
    e dal secondo esempio ho
    SecondoEsempio;
    PrimoEsempio := scelto il carattere Carattere;
    SecondoEsempio := scelto il carattere Carattere;

    Carattere ::= a | b | c | d | e | f | g | h | i | l | m | n | o | p | q | r | s | t | u | v | w | x | y | z;
    GRM;

    $p = new Polygen($grammar);

    // Run multiple times to check for diversity
    $results = [];
    for ($i = 0; $i < 15; $i++) {
        $output = $p->generate();
        preg_match_all('/scelto il carattere ([a-z])/', $output, $matches);

        if (isset($matches[1]) && count($matches[1]) === 2) {
            $first = $matches[1][0];
            $second = $matches[1][1];
            $results[] = [$first, $second];

            // If any generation has different characters, test passes
            if ($first !== $second) {
                expect(true)->toBe(true);
                return;
            }
        }
    }

    // If all results had same characters, fail
    expect(false)->toBe(true, "After 15 runs, all generations had identical characters in both positions");
});

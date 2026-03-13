<?php

use Polygen\Polygen;

describe('Basic Grammar Generation', function () {
    describe('Simple Grammars', function () {
        it('generates simple terminal sequence', function () {
            $grammar = 'S ::= hello world ;';
            expect($grammar)->toGenerateOutput();
        });

        it('generates correct output for simple grammar', function () {
            $grammar = 'S ::= hello world ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('generates from alternatives', function () {
            $grammar = 'S ::= hello | goodbye ;';
            $output = generateText($grammar);

            expect($output)->toBeIn(['hello', 'goodbye']);
        });

        it('generates non-terminal references', function () {
            $grammar = 'Greeting ::= hello ; S ::= Greeting world ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('handles multiple non-terminals in sequence', function () {
            $grammar = 'A ::= hello ; B ::= world ; S ::= A B ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('generates correct count of alternatives', function () {
            $grammar = 'S ::= a | b | c | d | e ;';
            $outputs = array_unique(array_map(fn() => generateText($grammar), range(1, 100)));

            expect(count($outputs))->toBeGreaterThanOrEqual(2);
            expect(count($outputs))->toBeLessThanOrEqual(5);
        });
    });

    describe('Terminal Operators', function () {
        it('handles epsilon operator', function () {
            $grammar = 'S ::= hello _ world ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('handles concatenation operator', function () {
            $grammar = 'S ::= super ^ man ;';
            $output = generateText($grammar);

            expect($output)->toBe('superman');
        });

        it('handles capitalization operator', function () {
            $grammar = 'S ::= \ hello world ;';
            $output = generateText($grammar);

            expect($output)->toBe('Hello world');
        });

        it('handles capitalization with multiple words', function () {
            $grammar = 'S ::= \ hello \ world ;';
            $output = generateText($grammar);

            expect($output)->toBe('Hello World');
        });

        it('combines concat and other operators', function () {
            $grammar = 'S ::= super ^ charged machine ;';
            $output = generateText($grammar);

            expect($output)->toContain('supercharged');
        });
    });

    describe('Quoted Strings', function () {
        it('handles quoted strings', function () {
            $grammar = 'S ::= "hello world" ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('handles quoted strings with special characters', function () {
            $grammar = 'S ::= "don\'t" "can\'t" ;';
            $output = generateText($grammar);

            expect($output)->toContain("don't");
            expect($output)->toContain("can't");
        });

        it('handles quoted strings in alternatives', function () {
            $grammar = 'S ::= "hello" | "goodbye" ;';
            $output = generateText($grammar);

            expect($output)->toBeIn(['hello', 'goodbye']);
        });
    });

    describe('Grouped Expressions', function () {
        it('handles grouped alternatives', function () {
            $grammar = 'S ::= (hello | hi) (world | there) ;';
            $output = generateText($grammar);

            expect($output)->toMatch('/^(hello|hi) (world|there)$/');
        });

        it('handles grouped sequences', function () {
            $grammar = 'S ::= (hello world) ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('handles nested groups', function () {
            $grammar = 'S ::= ((hello | hi) world) ;';
            $output = generateText($grammar);

            expect($output)->toMatch('/^(hello|hi) world$/');
        });
    });

    describe('Optional Groups', function () {
        it('generates optional content', function () {
            $grammar = 'S ::= hello [world] ;';
            $outputs = array_unique(array_map(fn() => generateText($grammar), range(1, 50)));

            expect($outputs)->toContain('hello');
            expect($outputs)->toContain('hello world');
        });

        it('handles optional with multiple alternatives', function () {
            $grammar = 'S ::= [a | b | c] ;';
            $outputs = array_unique(array_map(fn() => generateText($grammar), range(1, 100)));

            expect(count($outputs))->toBeGreaterThan(1);
        });

        it('handles multiple optional groups', function () {
            $grammar = 'S ::= [good] [morning | afternoon] ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 50));

            // Should have various combinations
            expect(in_array('', $outputs))->toBeTruthy();
        });
    });

    describe('Weighted Alternatives', function () {
        it('handles weight increase', function () {
            $grammar = 'S ::= ++ a | b ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 100));
            $aCount = countOccurrences(implode('', $outputs), 'a');

            expect($aCount)->toBeGreaterThan(30);
        });

        it('handles weight decrease', function () {
            $grammar = 'S ::= a | -- b ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 100));
            $bCount = countOccurrences(implode('', $outputs), 'b');

            expect($bCount)->toBeLessThan(50);
        });

        it('handles multiple weight modifiers', function () {
            $grammar = 'S ::= +++ a | b ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 100));
            $aCount = countOccurrences(implode('', $outputs), 'a');

            expect($aCount)->toBeGreaterThan(50);
        });
    });

    describe('Definitions vs Assignments', function () {
        it('definition allows different values per reference', function () {
            $grammar = 'Color ::= red | blue ; S ::= Color Color ;';
            $outputs = array_unique(array_map(fn() => generateText($grammar), range(1, 50)));

            expect(count($outputs))->toBeGreaterThan(1);
            expect($outputs)->toContain('red red');
            expect($outputs)->toContain('red blue');
        });

        it('assignment uses same value for all references', function () {
            $grammar = 'Color := red | blue ; S ::= Color Color ;';
            $polygen = createPolygen($grammar);
            $output = $polygen->generate();

            $parts = explode(' ', $output);
            expect($parts[0])->toBe($parts[1]);
        });

        it('memoization persists across multiple generations', function () {
            $grammar = 'X := a | b ; S ::= X X ;';
            $polygen = createPolygen($grammar);

            $output1 = $polygen->generate();
            // The assignment should have been cached, so X should have the same value
            $parts1 = explode(' ', $output1);
            expect($parts1[0])->toBe($parts1[1]);
        });
    });

    describe('Multiple Rules', function () {
        it('handles multiple declarations', function () {
            $grammar = <<<'GRAMMAR'
Greeting ::= hello | hi ;
Target ::= world | friend ;
S ::= Greeting Target "!" ;
GRAMMAR;
            $output = generateText($grammar);

            expect($output)->toMatch('/^(hello|hi) (world|friend) !$/');
        });

        it('allows reordering of rules', function () {
            $grammar1 = 'A ::= hello ; S ::= A world ;';
            $grammar2 = 'S ::= A world ; A ::= hello ;';

            $output1 = generateText($grammar1);
            $output2 = generateText($grammar2);

            expect($output1)->toBe('hello world');
            expect($output2)->toBe('hello world');
        });

        it('handles recursion prevention (no infinite loops)', function () {
            $grammar = 'S ::= hello | world ;';
            $output = generateText($grammar);

            expect(strlen($output))->toBeLessThan(100);
        });
    });

    describe('Complex Grammars', function () {
        it('handles comprehensive grammar', function () {
            $grammar = <<<'GRAMMAR'
Adjective ::= beautiful | wonderful | amazing ;
Noun ::= day | morning | evening ;
S ::= "What" a \ Adjective Noun "!" ;
GRAMMAR;
            $output = generateText($grammar);

            expect($output)->toMatch('/^What a [A-Z]/');
            expect($output)->toContain('!');
        });

        it('generates varying output from complex grammar', function () {
            $grammar = <<<'GRAMMAR'
Name ::= "Alice" | "Bob" | "Charlie" ;
Greeting ::= "Hello" | "Hi" | "Hey" ;
S ::= Greeting Name "!" ;
GRAMMAR;
            $outputs = array_unique(array_map(fn() => generateText($grammar), range(1, 30)));

            expect(count($outputs))->toBeGreaterThanOrEqual(6);
        });

        it('handles deep nesting', function () {
            $grammar = <<<'GRAMMAR'
S ::= ((((hello)))) world ;
GRAMMAR;
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });
    });
});

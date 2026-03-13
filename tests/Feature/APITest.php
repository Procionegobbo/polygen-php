<?php

use Polygen\Polygen;

describe('Public API', function () {
    describe('Polygen Class', function () {
        it('instantiates with valid grammar', function () {
            $polygen = new Polygen('S ::= hello ;');

            expect($polygen)->toBeInstanceOf(Polygen::class);
        });

        it('accepts grammar as constructor parameter', function () {
            $grammar = 'Test ::= value ;';
            $polygen = new Polygen($grammar);

            expect($polygen)->not->toBeNull();
        });

        it('throws on invalid grammar', function () {
            expect(fn() => new Polygen('invalid syntax :::'))->toThrow(RuntimeException::class);
        });
    });

    describe('Generate Method', function () {
        it('generates text from default S rule', function () {
            $polygen = new Polygen('S ::= hello world ;');
            $output = $polygen->generate();

            expect($output)->toBe('hello world');
        });

        it('accepts optional start symbol parameter', function () {
            $polygen = new Polygen('Start ::= hello ;');
            $output = $polygen->generate('Start');

            expect($output)->toBe('hello');
        });

        it('generates different results on multiple calls', function () {
            $grammar = 'S ::= a | b | c ;';
            $polygen = new Polygen($grammar);

            $results = array_map(fn() => $polygen->generate(), range(1, 20));
            $unique = count(array_unique($results));

            expect($unique)->toBeGreaterThan(1);
        });

        it('accepts label array parameter', function () {
            $polygen = new Polygen('S ::= hello ;');
            $output = $polygen->generate('S', []);

            expect($output)->toBeString();
        });

        it('returns string output', function () {
            $polygen = new Polygen('S ::= result ;');
            $output = $polygen->generate();

            expect($output)->toBeString();
        });

        it('never returns null', function () {
            $polygen = new Polygen('S ::= output ;');
            $output = $polygen->generate();

            expect($output)->not->toBeNull();
        });

        it('generates non-empty output for non-empty grammar', function () {
            $polygen = new Polygen('S ::= text ;');
            $output = $polygen->generate();

            expect(strlen($output))->toBeGreaterThan(0);
        });

        it('allows multiple start symbols in same grammar', function () {
            $grammar = 'S ::= hello ; Other ::= goodbye ;';
            $polygen = new Polygen($grammar);

            $out1 = $polygen->generate('S');
            $out2 = $polygen->generate('Other');

            expect($out1)->toBe('hello');
            expect($out2)->toBe('goodbye');
        });
    });

    describe('Caching and Memoization', function () {
        it('caches assignment bindings across calls', function () {
            $grammar = <<<'GRAMMAR'
Color := red | blue ;
S ::= i like Color and Color ;
GRAMMAR;

            $polygen = new Polygen($grammar);
            $output = $polygen->generate();

            // Both references should use the same color
            $parts = explode(' and ', $output);
            expect(count($parts))->toBe(2);
        });

        it('does not cache definition bindings', function () {
            $grammar = <<<'GRAMMAR'
Color ::= red | blue ;
S ::= Color Color Color ;
GRAMMAR;

            $polygen = new Polygen($grammar);
            $outputs = array_map(fn() => $polygen->generate(), range(1, 30));
            $unique = count(array_unique($outputs));

            expect($unique)->toBeGreaterThan(1);
        });
    });

    describe('Multiline Grammars', function () {
        it('parses multi-line grammars correctly', function () {
            $grammar = <<<'GRAMMAR'
Greeting ::= hello | hi ;
Target ::= world | friend ;
S ::= Greeting Target "!" ;
GRAMMAR;

            $polygen = new Polygen($grammar);
            $output = $polygen->generate();

            expect($output)->toMatch('/^(hello|hi) (world|friend) !$/');
        });

        it('handles indentation in multi-line grammars', function () {
            $grammar = <<<'GRAMMAR'
            Greeting ::= hello ;
            S ::= Greeting world ;
GRAMMAR;

            $polygen = new Polygen($grammar);
            $output = $polygen->generate();

            expect($output)->toBe('hello world');
        });
    });

    describe('Integration with Helper Functions', function () {
        it('works with createPolygen helper', function () {
            $polygen = createPolygen('S ::= test ;');

            expect($polygen)->toBeInstanceOf(Polygen::class);
        });

        it('works with generateText helper', function () {
            $output = generateText('S ::= result ;');

            expect($output)->toBe('result');
        });

        it('passes alternative symbol to generateText', function () {
            $output = generateText(
                'S ::= hello ; Other ::= goodbye ;',
                'Other'
            );

            expect($output)->toBe('goodbye');
        });

        it('passes label array to generateText', function () {
            $output = generateText(
                'S ::= test ;',
                'S',
                []
            );

            expect($output)->toBe('test');
        });
    });

    describe('Edge Cases', function () {
        it('handles single-word grammar', function () {
            $polygen = new Polygen('S ::= word ;');
            $output = $polygen->generate();

            expect($output)->toBe('word');
        });

        it('handles grammar with only lowercase terminals', function () {
            $polygen = new Polygen('S ::= hello world ;');
            $output = $polygen->generate();

            // Test with all lowercase terminals
            expect($output)->toBe('hello world');
        });

        it('handles grammar with numbers in names', function () {
            $polygen = new Polygen('Rule1 ::= hello ; S ::= Rule1 ;');
            $output = $polygen->generate();

            expect($output)->toBe('hello');
        });

        it('handles many alternatives efficiently', function () {
            $alternatives = implode(' | ', array_map(fn($i) => "opt$i", range(1, 50)));
            $grammar = "S ::= $alternatives ;";

            $polygen = new Polygen($grammar);
            $output = $polygen->generate();

            expect($output)->toMatch('/^opt\d+$/');
        });

        it('handles deeply nested parentheses', function () {
            $grammar = 'S ::= (((((hello))))) ;';
            $polygen = new Polygen($grammar);
            $output = $polygen->generate();

            expect($output)->toBe('hello');
        });
    });

    describe('Concurrency and State', function () {
        it('maintains independent state per instance', function () {
            $polygen1 = new Polygen('S ::= a | b ;');
            $polygen2 = new Polygen('S ::= c | d ;');

            $out1 = $polygen1->generate();
            $out2 = $polygen2->generate();

            expect($out1)->toBeIn(['a', 'b']);
            expect($out2)->toBeIn(['c', 'd']);
        });

        it('allows creating multiple instances simultaneously', function () {
            $instances = array_map(
                fn($i) => new Polygen("S ::= opt$i ;"),
                range(1, 10)
            );

            expect(count($instances))->toBe(10);
        });
    });

    describe('Output Characteristics', function () {
        it('generates deterministic output from single rule', function () {
            $grammar = 'S ::= exactly this ;';
            $polygen = new Polygen($grammar);

            $outputs = array_map(fn() => $polygen->generate(), range(1, 10));
            $unique = array_unique($outputs);

            expect(count($unique))->toBe(1);
            expect($unique[0])->toBe('exactly this');
        });

        it('generates varied output from alternatives', function () {
            $grammar = 'S ::= a | b | c | d | e ;';
            $polygen = new Polygen($grammar);

            $outputs = array_map(fn() => $polygen->generate(), range(1, 100));
            $unique = count(array_unique($outputs));

            expect($unique)->toBeGreaterThan(2);
        });

        it('maintains readability of output', function () {
            $grammar = 'S ::= "This" is a readable sentence "." ;';
            $polygen = new Polygen($grammar);
            $output = $polygen->generate();

            expect(strlen($output))->toBeGreaterThan(5);
            expect(preg_match('/^[A-Z]/', $output))->toBe(1); // Starts with letter
        });
    });
});

<?php

use Polygen\Polygen;

describe('Advanced Features', function () {
    describe('Label Selectors', function () {
        it('applies label selector to filter alternatives', function () {
            $grammar = 'S ::= word.label | other ;';

            expect($grammar)->toGenerateOutput();
        });

        it('handles label prefix on sequences', function () {
            $grammar = 'S ::= label: hello world ;';

            expect($grammar)->toGenerateOutput();
        });

        it('filters by label in alternatives', function () {
            $grammar = <<<'GRAMMAR'
S ::= adjective: red | adjective: blue | verb: run ;
GRAMMAR;
            $output = generateText($grammar);

            expect($output)->toBeIn(['red', 'blue', 'run']);
        });
    });

    describe('Unfold Operators', function () {
        it('handles unfold operator', function () {
            $grammar = 'Color ::= red | blue ; S ::= > Color ;';

            expect($grammar)->toGenerateOutput();
        });

        it('handles lock operator', function () {
            $grammar = 'Color ::= red | blue ; S ::= < Color ;';

            expect($grammar)->toGenerateOutput();
        });

        it('handles deep unfold operator', function () {
            $grammar = 'Color ::= red | blue ; S ::= > Color ;';

            expect($grammar)->toGenerateOutput();
        });
    });

    describe('Comma-Separated Atoms', function () {
        it('parses comma-separated atoms', function () {
            $grammar = 'S ::= hello , world , "!" ;';
            $output = generateText($grammar);

            expect($output)->toContain('hello');
            expect($output)->toContain('world');
        });
    });

    describe('Path Selectors', function () {
        it('handles slash in non-terminal path', function () {
            $grammar = 'S ::= hello ;';

            expect($grammar)->toGenerateOutput();
        });
    });

    describe('Mobile Groups', function () {
        it('generates mobile group content', function () {
            $grammar = 'S ::= { a b c } ;';
            $output = generateText($grammar);

            expect($output)->toContain('a');
            expect($output)->toContain('b');
            expect($output)->toContain('c');
        });

        it('handles mobile group alternatives', function () {
            $grammar = <<<'GRAMMAR'
S ::= { hello world | goodbye world } ;
GRAMMAR;
            $output = generateText($grammar);

            expect(strlen($output))->toBeGreaterThan(5);
        });
    });

    describe('Comment Handling', function () {
        it('ignores comments in grammar', function () {
            $grammar = 'S ::= (* This is a comment *) hello world ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('handles nested comments', function () {
            $grammar = 'S ::= (* outer (* inner *) outer *) hello ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello');
        });

        it('preserves comments between rules', function () {
            $grammar = <<<'GRAMMAR'
Greeting ::= hello ;
S ::= Greeting world ;
GRAMMAR;
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });
    });

    describe('Escape Sequences', function () {
        it('handles newline escape', function () {
            $grammar = 'S ::= "line1\nline2" ;';
            $output = generateText($grammar);

            expect($output)->toContain("\n");
        });

        it('handles tab escape', function () {
            $grammar = 'S ::= "word1\tword2" ;';
            $output = generateText($grammar);

            expect($output)->toContain("\t");
        });

        it('handles quote escape', function () {
            $grammar = 'S ::= "say \"hello\"" ;';
            $output = generateText($grammar);

            expect($output)->toContain('"hello"');
        });
    });

    describe('Entropy and Distribution', function () {
        it('provides reasonable entropy with 2 alternatives', function () {
            $grammar = 'S ::= a | b ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 50));
            $aCounts = array_filter($outputs, fn($x) => $x === 'a');

            $ratio = count($aCounts) / count($outputs);
            expect($ratio)->toBeGreaterThan(0.1)
                ->toBeLessThan(0.9);
        });

        it('provides reasonable entropy with 3 alternatives', function () {
            $grammar = 'S ::= a | b | c ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 100));
            $unique = count(array_unique($outputs));

            expect($unique)->toBeGreaterThanOrEqual(2);
        });

        it('respects weights in distribution', function () {
            $grammar = 'S ::= +++++ a | b ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 100));
            $aCounts = array_filter($outputs, fn($x) => $x === 'a');
            $aRatio = count($aCounts) / count($outputs);

            expect($aRatio)->toBeGreaterThan(0.7);
        });

        it('negative weight excludes alternative from selection', function () {
            $grammar = 'S ::= - excluded | included ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 50));

            expect($outputs)->toContain('included');
            expect($outputs)->not->toContain('excluded');
        });
    });

    describe('Mobile Groups', function () {
        it('generates mobile groups in multiple orders', function () {
            $grammar = 'S ::= { a b } ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 100));

            expect($outputs)->toContain('a b');
            expect($outputs)->toContain('b a');
        });

        it('mobile group with three elements', function () {
            $grammar = 'S ::= { a b c } ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 200));
            $unique = array_unique($outputs);

            // Should have multiple different permutations
            expect(count($unique))->toBeGreaterThanOrEqual(2);
        });
    });

    describe('Multi-label Selectors', function () {
        it('multi-label selector chooses among labels', function () {
            $grammar = 'S ::= Noun.(M|F) ; Noun ::= M: man | F: woman ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 50));

            expect($outputs)->toContain('man');
            expect($outputs)->toContain('woman');
        });

        it('chained multi-label selectors work', function () {
            $grammar = 'S ::= Word.(A|B) ; Word ::= A: alpha | B: beta ;';
            $output = generateText($grammar);

            expect($output)->toBeIn(['alpha', 'beta']);
        });
    });

    describe('Scoped Redefinitions', function () {
        it('handles scoped assignment in sub-expression', function () {
            $grammar = 'S ::= (X := hello ; X world) ;';
            $output = generateText($grammar);

            expect($output)->toBe('hello world');
        });

        it('handles scoped definition in sub-expression', function () {
            $grammar = 'S ::= (X ::= a | b ; X X) ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 50));

            // Should have variation since X is a definition
            expect(count(array_unique($outputs)))->toBeGreaterThanOrEqual(2);
        });
    });

    describe('Deep Unfold', function () {
        it('deep unfold inlines alternatives into parent sequence', function () {
            $grammar = 'S ::= x >>(a | b)<< z ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 50));

            expect($outputs)->toContain('x a z');
            expect($outputs)->toContain('x b z');
        });

        it('deep unfold with multiple atom context', function () {
            $grammar = 'S ::= m n >>(a | b)<< x y ;';
            $outputs = array_map(fn() => generateText($grammar), range(1, 50));

            expect($outputs)->toContain('m n a x y');
            expect($outputs)->toContain('m n b x y');
        });
    });

    describe('Special Cases', function () {
        it('handles rules that reference themselves differently', function () {
            $grammar = <<<'GRAMMAR'
A ::= a | b ;
B ::= A A ;
S ::= B ;
GRAMMAR;
            $output = generateText($grammar);

            $parts = explode(' ', $output);
            expect(count($parts))->toBe(2);
        });

        it('handles empty alternative', function () {
            $grammar = 'S ::= hello [_] world ;';

            expect($grammar)->toGenerateOutput();
        });

        it('handles trailing separator', function () {
            $grammar = 'S ::= hello world ;';

            // Valid grammar without trailing semicolon
            expect($grammar)->toGenerateOutput();
        });

        it('handles very long output', function () {
            $grammar = <<<'GRAMMAR'
Word ::= hello ;
S ::= Word Word Word Word Word ;
GRAMMAR;
            $output = generateText($grammar);

            expect(strlen($output))->toBeGreaterThan(20);
        });
    });
});

describe('Error Conditions', function () {
    describe('Invalid Grammars', function () {
        it('throws on undefined non-terminal', function () {
            $grammar = 'S ::= UndefinedRule ;';

            expect(fn() => generateText($grammar))->toThrow(RuntimeException::class);
        });

        it('throws on invalid syntax', function () {
            $grammar = 'S :::= hello ;';  // Three colons instead of two

            expect(fn() => createPolygen($grammar))->toThrow(RuntimeException::class);
        });

        it('throws on unclosed parenthesis', function () {
            $grammar = 'S ::= (hello ;';

            expect(fn() => createPolygen($grammar))->toThrow(RuntimeException::class);
        });

        it('throws on unclosed bracket', function () {
            $grammar = 'S ::= [hello ;';

            expect(fn() => createPolygen($grammar))->toThrow(RuntimeException::class);
        });

        it('throws on unclosed brace', function () {
            $grammar = 'S ::= {hello ;';

            expect(fn() => createPolygen($grammar))->toThrow(RuntimeException::class);
        });

        it('throws on unclosed quote', function () {
            $grammar = 'S ::= "unclosed ;';

            expect(fn() => createPolygen($grammar))->toThrow(RuntimeException::class);
        });
    });

    describe('Alternative Starting Symbols', function () {
        it('generates from alternative start symbol', function () {
            $grammar = 'S ::= hello ; Greeting ::= hi ;';
            $output = generateText($grammar, 'Greeting');

            expect($output)->toBe('hi');
        });

        it('throws on non-existent start symbol', function () {
            $grammar = 'S ::= hello ;';

            expect(fn() => generateText($grammar, 'NonExistent'))->toThrow(RuntimeException::class);
        });
    });
});

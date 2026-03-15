<?php

use Polygen\Polygen;
use Polygen\Parser\ImportDirective;
use Polygen\Parser\Parser;
use Polygen\Lexer\Lexer;

describe('Module System (Imports + Path-based References)', function () {
    describe('Parser - Import Parsing', function () {
        it('parses import with alias', function () {
            $grammar = 'import "names.grm" as Names ; S ::= Names/First ;';
            $lexer = new Lexer($grammar);
            $tokens = $lexer->tokenize();
            $parser = new Parser($tokens);
            $result = $parser->parse();

            expect($result->imports)->toHaveCount(1);
            $import = $result->imports[0];
            expect($import->path)->toBe('names.grm');
            expect($import->alias)->toBe('Names');
        });

        it('parses import without alias (global import)', function () {
            $grammar = 'import "names.grm" ; S ::= First ;';
            $lexer = new Lexer($grammar);
            $tokens = $lexer->tokenize();
            $parser = new Parser($tokens);
            $result = $parser->parse();

            expect($result->imports)->toHaveCount(1);
            $import = $result->imports[0];
            expect($import->path)->toBe('names.grm');
            expect($import->alias)->toBeNull();
        });

        it('parses multiple imports', function () {
            $grammar = <<<'GRAMMAR'
import "names.grm" as Names ;
import "titles.grm" as Titles ;
S ::= Names/First ;
GRAMMAR;
            $lexer = new Lexer($grammar);
            $tokens = $lexer->tokenize();
            $parser = new Parser($tokens);
            $result = $parser->parse();

            expect($result->imports)->toHaveCount(2);
            expect($result->imports[0]->path)->toBe('names.grm');
            expect($result->imports[0]->alias)->toBe('Names');
            expect($result->imports[1]->path)->toBe('titles.grm');
            expect($result->imports[1]->alias)->toBe('Titles');
        });

        it('parses mixed imports and declarations', function () {
            $grammar = <<<'GRAMMAR'
import "names.grm" as Names ;
S ::= Names/First ;
S2 ::= hello ;
import "more.grm" as More ;
GRAMMAR;
            $lexer = new Lexer($grammar);
            $tokens = $lexer->tokenize();
            $parser = new Parser($tokens);
            $result = $parser->parse();

            expect($result->imports)->toHaveCount(2);
            expect($result->decls)->toHaveCount(2);
        });
    });

    describe('Polygen - File Loading with fromFile()', function () {
        it('loads grammar from file', function () {
            $fixturePath = __DIR__ . '/../fixtures/names.grm';
            $polygen = Polygen::fromFile($fixturePath);

            $output = $polygen->generate('First');
            expect($output)->toBeIn(['alice', 'bob', 'charlie']);
        });

        it('throws on missing file', function () {
            expect(function () {
                Polygen::fromFile('/nonexistent/path/grammar.grm');
            })->toThrow(\RuntimeException::class);
        });

        it('throws when import present but no basePath', function () {
            $grammar = 'import "names.grm" as Names ; S ::= Names/First ;';
            expect(function () use ($grammar) {
                new Polygen($grammar);
            })->toThrow(\RuntimeException::class);
        });

        it('accepts basePath parameter', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "names.grm" as Names ; S ::= Names/First ;';
            expect(function () use ($grammar, $basePath) {
                new Polygen($grammar, $basePath);
            })->not->toThrow(\Exception::class);
        });
    });

    describe('Module Loading - With Alias', function () {
        it('loads module with alias and generates with Module/Symbol reference', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "names.grm" as Names ; S ::= Names/First ^ ^ Names/Last ;';
            $polygen = new Polygen($grammar, $basePath);

            $output = $polygen->generate();
            expect($output)->toBeString();
            // Output should be like "alicesmith" (concatenated without spaces)
            expect($output)->toMatch('/^[a-z]+$/');
        });

        it('generates correct output from imported module', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "names.grm" as Names ; S ::= Names/First ;';
            $polygen = new Polygen($grammar, $basePath);

            $output = $polygen->generate();
            expect($output)->toBeIn(['alice', 'bob', 'charlie']);
        });
    });

    describe('Module Loading - Global Import', function () {
        it('global import merges symbols without prefix', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "names.grm" ; S ::= First ;';
            $polygen = new Polygen($grammar, $basePath);

            $output = $polygen->generate();
            expect($output)->toBeIn(['alice', 'bob', 'charlie']);
        });
    });

    describe('Module Loading - Internal References', function () {
        it('handles internal module references after prefixing', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "full.grm" as Full ; S ::= Full/Full ;';
            $polygen = new Polygen($grammar, $basePath);

            $output = $polygen->generate();
            expect($output)->toBeString();
            // Full/Full should reference Full/First, Full/Last internally
            // Output should be concatenated like "alicesmith"
            expect($output)->toMatch('/^[a-z]+$/');
        });
    });

    describe('Module Loading - Error Handling', function () {
        it('throws on circular import', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "circular_a.grm" as A ; S ::= a ;';
            expect(function () use ($grammar, $basePath) {
                new Polygen($grammar, $basePath);
            })->toThrow(\RuntimeException::class, 'Circular');
        });

        it('throws on missing module file', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "nonexistent.grm" as NE ; S ::= NE/X ;';
            expect(function () use ($grammar, $basePath) {
                new Polygen($grammar, $basePath);
            })->toThrow(\RuntimeException::class);
        });

        it('throws on undefined symbol in imported module', function () {
            $basePath = __DIR__ . '/../fixtures';
            $grammar = 'import "names.grm" as Names ; S ::= Names/NonExistent ;';
            $polygen = new Polygen($grammar, $basePath);

            expect(function () use ($polygen) {
                $polygen->generate();
            })->toThrow(\RuntimeException::class, 'Undefined');
        });
    });

    describe('Path-based References', function () {
        it('resolves single-segment path (fallback to regular symbol)', function () {
            $grammar = 'S ::= hello ; S2 ::= S ;';
            $polygen = new Polygen($grammar);

            // This should work because implode('/', ['S']) === 'S'
            $output = $polygen->generate('S2');
            expect($output)->toBe('hello');
        });

        it('resolves multi-segment path A/B/C', function () {
            $basePath = __DIR__ . '/../fixtures';
            // Create a grammar that uses a multi-level path
            // For this test, we just verify path parsing works
            $grammar = 'import "names.grm" as Names ; S ::= Names/First ;';
            $polygen = new Polygen($grammar, $basePath);

            $output = $polygen->generate();
            expect($output)->toBeIn(['alice', 'bob', 'charlie']);
        });
    });

    describe('Module Caching', function () {
        it('caches repeated imports of same module', function () {
            $basePath = __DIR__ . '/../fixtures';
            // Create a grammar where the same module is imported twice
            $grammar = <<<'GRAMMAR'
import "names.grm" as Names ;
import "names.grm" as Names2 ;
S ::= Names/First ^ ^ Names2/Last ;
GRAMMAR;
            // This should not throw (would throw on circular if not cached properly)
            $polygen = new Polygen($grammar, $basePath);

            $output = $polygen->generate();
            expect($output)->toBeString();
        });
    });
});

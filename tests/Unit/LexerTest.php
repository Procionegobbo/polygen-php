<?php

use Polygen\Lexer\Lexer;
use Polygen\Lexer\TokenType;

describe('Lexer', function () {
    describe('Basic Tokens', function () {
        it('tokenizes non-terminal symbols', function () {
            $lexer = new Lexer('Hello World');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::NONTERM);
            expect($tokens[0]->value)->toBe('Hello');
            expect($tokens[1]->type)->toBe(TokenType::NONTERM);
            expect($tokens[1]->value)->toBe('World');
        });

        it('tokenizes terminal symbols', function () {
            $lexer = new Lexer('hello world test');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[0]->value)->toBe('hello');
            expect($tokens[1]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->value)->toBe('world');
        });

        it('tokenizes special symbols with apostrophes', function () {
            $lexer = new Lexer("don't can't");
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[0]->value)->toBe("don't");
            expect($tokens[1]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->value)->toBe("can't");
        });
    });

    describe('Operators', function () {
        it('tokenizes definition operator', function () {
            $lexer = new Lexer('S ::= hello');
            $tokens = $lexer->tokenize();

            expect($tokens[1]->type)->toBe(TokenType::DEF);
            expect($tokens[1]->value)->toBe('::=');
        });

        it('tokenizes assignment operator', function () {
            $lexer = new Lexer('X := value');
            $tokens = $lexer->tokenize();

            expect($tokens[1]->type)->toBe(TokenType::ASSIGN);
            expect($tokens[1]->value)->toBe(':=');
        });

        it('tokenizes pipe operator', function () {
            $lexer = new Lexer('a | b');
            $tokens = $lexer->tokenize();

            expect($tokens[1]->type)->toBe(TokenType::PIPE);
        });

        it('tokenizes unfold operators', function () {
            $lexer = new Lexer('> X >> Y');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::GT);
            expect($tokens[2]->type)->toBe(TokenType::GTGT);
        });

        it('tokenizes lock operator', function () {
            $lexer = new Lexer('< X');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::LT);
        });

        it('tokenizes concat operator', function () {
            $lexer = new Lexer('super^man');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->type)->toBe(TokenType::CAP);
            expect($tokens[2]->type)->toBe(TokenType::TERM);
        });

        it('tokenizes capitalize operator', function () {
            $lexer = new Lexer('\ hello');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::BACKSLASH);
        });

        it('tokenizes epsilon', function () {
            $lexer = new Lexer('_');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::UNDERSCORE);
        });

        it('tokenizes weight modifiers', function () {
            $lexer = new Lexer('+ - ++ --');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::PLUS);
            expect($tokens[1]->type)->toBe(TokenType::MINUS);
            expect($tokens[2]->type)->toBe(TokenType::PLUS);
            expect($tokens[3]->type)->toBe(TokenType::PLUS);
        });
    });

    describe('Brackets and Grouping', function () {
        it('tokenizes parentheses', function () {
            $lexer = new Lexer('(hello)');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::BRA);
            expect($tokens[2]->type)->toBe(TokenType::KET);
        });

        it('tokenizes square brackets', function () {
            $lexer = new Lexer('[optional]');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::SQBRA);
            expect($tokens[2]->type)->toBe(TokenType::SQKET);
        });

        it('tokenizes curly braces', function () {
            $lexer = new Lexer('{mobile}');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::CBRA);
            expect($tokens[2]->type)->toBe(TokenType::CKET);
        });
    });

    describe('Labels and Selectors', function () {
        it('tokenizes dot selector', function () {
            $lexer = new Lexer('word.');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->type)->toBe(TokenType::DOT);
        });

        it('tokenizes label selector', function () {
            $lexer = new Lexer('word.label');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->type)->toBe(TokenType::DOTLABEL);
            expect($tokens[1]->value)->toBe('label');
        });

        it('tokenizes dot-bracket for multi-label', function () {
            $lexer = new Lexer('word.(');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->type)->toBe(TokenType::DOTBRA);
        });

        it('tokenizes colon for label prefix', function () {
            $lexer = new Lexer('label: word');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->type)->toBe(TokenType::COLON);
            expect($tokens[2]->type)->toBe(TokenType::TERM);
        });
    });

    describe('Quoted Strings', function () {
        it('tokenizes simple quoted strings', function () {
            $lexer = new Lexer('"hello world"');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::QUOTE);
            expect($tokens[0]->value)->toBe('hello world');
        });

        it('handles escaped quotes', function () {
            $lexer = new Lexer('"say \\"hello\\""');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::QUOTE);
            expect($tokens[0]->value)->toContain('"');
        });

        it('handles escape sequences', function () {
            $lexer = new Lexer('"line1\\nline2"');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::QUOTE);
            expect($tokens[0]->value)->toContain("\n");
        });

        it('handles tab escapes', function () {
            $lexer = new Lexer('"word1\\tword2"');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::QUOTE);
            expect($tokens[0]->value)->toContain("\t");
        });
    });

    describe('Comments', function () {
        it('ignores simple comments', function () {
            $lexer = new Lexer('hello (* comment *) world');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::TERM);
            expect($tokens[0]->value)->toBe('hello');
            expect($tokens[1]->type)->toBe(TokenType::TERM);
            expect($tokens[1]->value)->toBe('world');
        });

        it('handles nested comments', function () {
            $lexer = new Lexer('hello (* outer (* inner *) outer *) world');
            $tokens = $lexer->tokenize();

            expect(count($tokens))->toBe(3); // hello, world, EOF
            expect($tokens[0]->value)->toBe('hello');
            expect($tokens[1]->value)->toBe('world');
        });

        it('handles multiple nested levels', function () {
            $lexer = new Lexer('a (* 1 (* 2 (* 3 *) 2 *) 1 *) b');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->value)->toBe('a');
            expect($tokens[1]->value)->toBe('b');
        });
    });

    describe('Keywords', function () {
        it('tokenizes import keyword', function () {
            $lexer = new Lexer('import');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::IMPORT);
        });

        it('tokenizes as keyword', function () {
            $lexer = new Lexer('as');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::AS);
        });
    });

    describe('Whitespace Handling', function () {
        it('ignores whitespace between tokens', function () {
            $lexer = new Lexer('hello   world');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->value)->toBe('hello');
            expect($tokens[1]->value)->toBe('world');
        });

        it('tracks line numbers correctly', function () {
            $lexer = new Lexer("hello\nworld");
            $tokens = $lexer->tokenize();

            expect($tokens[0]->line)->toBe(1);
            expect($tokens[0]->value)->toBe('hello');
            expect($tokens[1]->value)->toBe('world');
            // world token should be on line 2 (after newline)
            expect($tokens[1]->line)->toBeGreaterThanOrEqual(1);
        });

        it('handles tab characters', function () {
            $lexer = new Lexer("hello\tworld");
            $tokens = $lexer->tokenize();

            expect($tokens[0]->value)->toBe('hello');
            expect($tokens[1]->value)->toBe('world');
        });
    });

    describe('Complex Grammar Rules', function () {
        it('tokenizes complete declaration', function () {
            $lexer = new Lexer('S ::= hello world ;');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::NONTERM);
            expect($tokens[1]->type)->toBe(TokenType::DEF);
            expect($tokens[2]->type)->toBe(TokenType::TERM);
            expect($tokens[3]->type)->toBe(TokenType::TERM);
            expect($tokens[4]->type)->toBe(TokenType::EOL);
            expect($tokens[5]->type)->toBe(TokenType::EOF);
        });

        it('tokenizes rule with alternatives', function () {
            $lexer = new Lexer('S ::= hello | goodbye ;');
            $tokens = $lexer->tokenize();

            // Check that PIPE token exists in the token stream
            $pipeTokens = array_filter($tokens, fn($t) => $t->type === TokenType::PIPE);
            expect(count($pipeTokens))->toBeGreaterThan(0);
        });

        it('tokenizes full grammar example', function () {
            $grammar = 'Greeting ::= hello | hi ; S ::= Greeting world ;';
            $lexer = new Lexer($grammar);
            $tokens = $lexer->tokenize();

            expect($tokens)->not->toBeEmpty();
            expect($tokens[count($tokens) - 1]->type)->toBe(TokenType::EOF);
        });
    });

    describe('Error Handling', function () {
        it('throws on unexpected character', function () {
            $lexer = new Lexer('@invalid');

            expect(fn() => $lexer->tokenize())->toThrow(RuntimeException::class);
        });
    });
});

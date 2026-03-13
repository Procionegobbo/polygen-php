<?php

namespace Polygen;

use Polygen\Generator\Generator;
use Polygen\Lexer\Lexer;
use Polygen\Parser\Parser;
use Polygen\Preprocessor\Preprocessor;

final class Polygen
{
    /** @var array */
    private array $decls;

    public function __construct(string $grammar)
    {
        // Lexer
        $lexer = new Lexer($grammar);
        $tokens = $lexer->tokenize();

        // Parser (Absyn0)
        $parser = new Parser($tokens);
        $ast = $parser->parse();

        // Preprocessor (Absyn0 → Absyn1)
        $preprocessor = new Preprocessor();
        $this->decls = $preprocessor->process($ast);
    }

    public function generate(string $startSymbol = 'S', array $labels = []): string
    {
        $generator = new Generator($this->decls);
        return $generator->run($startSymbol, $labels);
    }
}

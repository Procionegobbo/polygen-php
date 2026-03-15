<?php

namespace Polygen;

use Polygen\Generator\Generator;
use Polygen\Lexer\Lexer;
use Polygen\ModuleLoader;
use Polygen\Parser\Parser;
use Polygen\Preprocessor\Preprocessor;

final class Polygen
{
    /** @var array */
    private array $decls;

    /**
     * @param string $grammar Grammar content
     * @param ?string $basePath Base path for resolving imports. Required if grammar contains import statements.
     */
    public function __construct(string $grammar, ?string $basePath = null)
    {
        // Lexer
        $lexer = new Lexer($grammar);
        $tokens = $lexer->tokenize();

        // Parser (Absyn0)
        $parser = new Parser($tokens);
        $parseResult = $parser->parse();

        // Handle imports
        $importedDecls = [];
        if (!empty($parseResult->imports)) {
            if ($basePath === null) {
                throw new \RuntimeException(
                    "Grammar contains import statements but no basePath was provided. " .
                    "Use Polygen::fromFile() or pass a basePath as second argument."
                );
            }
            $loader = ModuleLoader::root($basePath);
            foreach ($parseResult->imports as $directive) {
                $importedDecls = array_merge(
                    $importedDecls,
                    $loader->load($directive->path, $directive->alias)
                );
            }
        }

        // Imported symbols first, then grammar's own declarations (can override imports)
        $allDecls = array_merge($importedDecls, $parseResult->decls);

        // Preprocessor (Absyn0 → Absyn1)
        $preprocessor = new Preprocessor();
        $this->decls = $preprocessor->process($allDecls);
    }

    /**
     * Create Polygen instance from a grammar file.
     *
     * @param string $filePath Path to the grammar file
     * @return self
     * @throws \RuntimeException if file cannot be read
     */
    public static function fromFile(string $filePath): self
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read grammar file: $filePath");
        }
        return new self($content, dirname(realpath($filePath)));
    }

    public function generate(string $startSymbol = 'S', array $labels = []): string
    {
        $generator = new Generator($this->decls);
        return $generator->run($startSymbol, $labels);
    }
}

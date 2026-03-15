<?php

namespace Polygen\Parser;

use Polygen\Parser\Ast\DeclNode;

final readonly class ParseResult
{
    /**
     * @param DeclNode[] $decls
     * @param ImportDirective[] $imports
     */
    public function __construct(
        public array $decls,    // DeclNode[]
        public array $imports,  // ImportDirective[]
    ) {}
}

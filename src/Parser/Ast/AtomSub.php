<?php

namespace Polygen\Parser\Ast;

final class AtomSub extends AtomNode
{
    public function __construct(
        public readonly array $decls,  // DeclNode[]
        public readonly ProdNode $prod,
    ) {}

    public function __toString(): string { return "Sub(...)"; }
}

<?php

namespace Polygen\Parser\Ast;

final class AtomSub extends AtomNode
{
    public function __construct(
        public readonly array $decls,      // DeclNode[]
        public readonly ProdNode $prod,
        public readonly bool $mobile = false,       // {…} shuffle group
        public readonly bool $unfolded = false,     // >>…<< deep unfold
        public readonly bool $selectorSub = false,  // .(A|B) expansion
    ) {}

    public function __toString(): string { return "Sub(...)"; }
}

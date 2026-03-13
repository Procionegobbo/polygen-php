<?php

namespace Polygen\Parser\Ast;

final class DeclNode
{
    public function __construct(
        public readonly BindMode $mode,
        public readonly string $name,
        public readonly ProdNode $prod,
    ) {}

    public function __toString(): string {
        return "$this->name {$this->mode->value} $this->prod";
    }
}

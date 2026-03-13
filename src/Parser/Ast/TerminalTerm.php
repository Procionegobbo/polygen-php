<?php

namespace Polygen\Parser\Ast;

final class TerminalTerm extends TerminalNode
{
    public function __construct(
        public readonly string $value,
    ) {}

    public function __toString(): string { return "Term($this->value)"; }
}

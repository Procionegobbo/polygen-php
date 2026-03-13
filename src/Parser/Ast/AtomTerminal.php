<?php

namespace Polygen\Parser\Ast;

final class AtomTerminal extends AtomNode
{
    public function __construct(
        public readonly TerminalNode $terminal,
    ) {}

    public function __toString(): string { return "Terminal($this->terminal)"; }
}

<?php

namespace Polygen\Parser\Ast;

final class TerminalEpsilon extends TerminalNode
{
    public function __toString(): string { return 'Epsilon'; }
}

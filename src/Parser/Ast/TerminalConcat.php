<?php

namespace Polygen\Parser\Ast;

final class TerminalConcat extends TerminalNode
{
    public function __toString(): string { return 'Concat'; }
}

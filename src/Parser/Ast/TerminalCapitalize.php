<?php

namespace Polygen\Parser\Ast;

final class TerminalCapitalize extends TerminalNode
{
    public function __toString(): string { return 'Capitalize'; }
}

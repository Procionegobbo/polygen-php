<?php

namespace Polygen\Parser\Ast;

final class AtomSel extends AtomNode
{
    public function __construct(
        public readonly AtomNode $atom,
        public readonly ?string $label = null,  // null = Sel(atom, .), non-null = Sel(atom, .label)
    ) {}

    public function __toString(): string {
        $label = $this->label === null ? '.' : ".$this->label";
        return "Sel($this->atom, $label)";
    }
}

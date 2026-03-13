<?php

namespace Polygen\Parser\Ast;

final class SeqNode
{
    public function __construct(
        public readonly ?string $label,     // null or label name
        public readonly array $atoms,       // AtomNode[]
        public int $counter = 0,            // mutable: usage counter for shuffle selection
    ) {}

    public function __toString(): string {
        $labelStr = $this->label === null ? '' : "$this->label:";
        $atomsStr = implode(', ', $this->atoms);
        return "$labelStr [$atomsStr]";
    }
}

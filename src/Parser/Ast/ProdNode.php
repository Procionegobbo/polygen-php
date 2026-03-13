<?php

namespace Polygen\Parser\Ast;

final class ProdNode
{
    public function __construct(
        public readonly array $seqs,  // SeqNode[]
    ) {}

    public function __toString(): string {
        $seqsStr = implode(' | ', $this->seqs);
        return "Prod($seqsStr)";
    }
}

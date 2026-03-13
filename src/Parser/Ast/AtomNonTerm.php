<?php

namespace Polygen\Parser\Ast;

final class AtomNonTerm extends AtomNode
{
    public function __construct(
        public readonly array $path,  // string[] (path segments)
    ) {}

    public function __toString(): string {
        $pathStr = implode('/', $this->path);
        return "NonTerm($pathStr)";
    }
}

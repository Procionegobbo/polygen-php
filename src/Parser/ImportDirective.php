<?php

namespace Polygen\Parser;

final readonly class ImportDirective
{
    public function __construct(
        public string $path,      // e.g. "grm/names.grm"
        public ?string $alias,    // e.g. "Names", null = global import
    ) {}
}

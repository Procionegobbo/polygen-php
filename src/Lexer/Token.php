<?php

namespace Polygen\Lexer;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
        public int $line,
        public int $column,
    ) {}

    public function __toString(): string
    {
        return match ($this->type) {
            TokenType::TERM, TokenType::NONTERM, TokenType::DOTLABEL, TokenType::QUOTE =>
                "{$this->type->value}({$this->value})",
            default => $this->type->value,
        };
    }
}

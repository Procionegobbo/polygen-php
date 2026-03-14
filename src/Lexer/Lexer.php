<?php

namespace Polygen\Lexer;

final class Lexer
{
    private string $input;
    private int $pos = 0;
    private int $line = 1;
    private int $column = 1;
    private int $commentDepth = 0;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function tokenize(): array
    {
        $tokens = [];
        while ($this->pos < strlen($this->input)) {
            $this->skipWhitespace();
            if ($this->pos >= strlen($this->input)) {
                break;
            }

            if ($this->tryComment()) {
                continue;
            }

            $token = $this->nextToken();
            if ($token !== null) {
                $tokens[] = $token;
            }
        }

        $tokens[] = new Token(TokenType::EOF, '', $this->line, $this->column);
        return $tokens;
    }

    private function nextToken(): ?Token
    {
        $char = $this->current();
        $line = $this->line;
        $column = $this->column;

        // Handle remaining whitespace (newlines)
        if (in_array($char, ["\n", "\r", null], true)) {
            return null; // Skip newlines and empty input
        }

        // Single-char or multi-char operators
        if ($char === ':') {
            $this->advance();
            if ($this->current() === ':' && $this->peek() === '=') {
                $this->advance();
                $this->advance();
                return new Token(TokenType::DEF, '::=', $line, $column);
            } elseif ($this->current() === '=') {
                $this->advance();
                return new Token(TokenType::ASSIGN, ':=', $line, $column);
            }
            return new Token(TokenType::COLON, ':', $line, $column);
        }

        if ($char === ';') {
            $this->advance();
            return new Token(TokenType::EOL, ';', $line, $column);
        }

        if ($char === '|') {
            $this->advance();
            return new Token(TokenType::PIPE, '|', $line, $column);
        }

        if ($char === '>') {
            $this->advance();
            if ($this->current() === '>') {
                $this->advance();
                return new Token(TokenType::GTGT, '>>', $line, $column);
            }
            return new Token(TokenType::GT, '>', $line, $column);
        }

        if ($char === '<') {
            $this->advance();
            if ($this->current() === '<') {
                $this->advance();
                return new Token(TokenType::LTLT, '<<', $line, $column);
            }
            return new Token(TokenType::LT, '<', $line, $column);
        }

        if ($char === '.') {
            $this->advance();
            if ($this->current() === '(') {
                $this->advance();
                return new Token(TokenType::DOTBRA, '.(', $line, $column);
            }
            if (ctype_alnum($this->current()) || $this->current() === '_') {
                $label = $this->readLabel();
                return new Token(TokenType::DOTLABEL, $label, $line, $column);
            }
            return new Token(TokenType::DOT, '.', $line, $column);
        }

        if ($char === '+') {
            $this->advance();
            return new Token(TokenType::PLUS, '+', $line, $column);
        }

        if ($char === '-') {
            $this->advance();
            return new Token(TokenType::MINUS, '-', $line, $column);
        }

        if ($char === '^') {
            $this->advance();
            return new Token(TokenType::CAP, '^', $line, $column);
        }

        if ($char === '\\') {
            $this->advance();
            return new Token(TokenType::BACKSLASH, '\\', $line, $column);
        }

        if ($char === '_') {
            $this->advance();
            return new Token(TokenType::UNDERSCORE, '_', $line, $column);
        }

        if ($char === '/') {
            $this->advance();
            return new Token(TokenType::SLASH, '/', $line, $column);
        }

        if ($char === ',') {
            $this->advance();
            return new Token(TokenType::COMMA, ',', $line, $column);
        }

        if ($char === '(') {
            $this->advance();
            return new Token(TokenType::BRA, '(', $line, $column);
        }

        if ($char === ')') {
            $this->advance();
            return new Token(TokenType::KET, ')', $line, $column);
        }

        if ($char === '[') {
            $this->advance();
            return new Token(TokenType::SQBRA, '[', $line, $column);
        }

        if ($char === ']') {
            $this->advance();
            return new Token(TokenType::SQKET, ']', $line, $column);
        }

        if ($char === '{') {
            $this->advance();
            return new Token(TokenType::CBRA, '{', $line, $column);
        }

        if ($char === '}') {
            $this->advance();
            return new Token(TokenType::CKET, '}', $line, $column);
        }

        if ($char === '*') {
            $this->advance();
            return new Token(TokenType::STAR, '*', $line, $column);
        }

        // Quoted strings
        if ($char === '"') {
            return $this->readQuote($line, $column);
        }

        // Identifiers
        if (ctype_upper($char)) {
            $word = $this->readWord();
            return new Token(TokenType::NONTERM, $word, $line, $column);
        }

        if (ctype_lower($char) || ctype_digit($char) || $char === "'") {
            $word = $this->readWord();
            if ($word === 'import') {
                return new Token(TokenType::IMPORT, 'import', $line, $column);
            }
            if ($word === 'as') {
                return new Token(TokenType::AS, 'as', $line, $column);
            }
            return new Token(TokenType::TERM, $word, $line, $column);
        }

        // Allow UTF-8 multi-byte sequences (bytes ≥ 128) as terminal words
        if (ord($char) > 127) {
            $word = $this->readWord();
            return new Token(TokenType::TERM, $word, $line, $column);
        }

        // Skip any remaining whitespace characters (including newlines, tabs, carriage returns)
        if (ctype_space($char) || ord($char) < 33) {
            $this->pos++;
            $this->column++;
            if ($char === "\n") {
                $this->line++;
                $this->column = 1;
            }
            return $this->nextToken(); // Try again with next character
        }

        throw new \RuntimeException("Unexpected character: '$char' (ord=" . ord($char) . ") at line $line, column $column");
    }

    private function readWord(): string
    {
        $word = '';
        while ($this->pos < strlen($this->input)) {
            $char = $this->current();
            if (ctype_alnum($char) || $char === '_' || $char === "'" || ord($char) > 127) {
                $word .= $char;
                $this->advance();
            } else {
                break;
            }
        }
        return $word;
    }

    private function readLabel(): string
    {
        $label = '';
        while ($this->pos < strlen($this->input)) {
            $char = $this->current();
            if (ctype_alnum($char) || $char === '_') {
                $label .= $char;
                $this->advance();
            } else {
                break;
            }
        }
        return $label;
    }

    private function readQuote(int $line, int $column): Token
    {
        $this->advance(); // skip opening "
        $value = '';

        while ($this->pos < strlen($this->input)) {
            $char = $this->current();

            if ($char === '"') {
                $this->advance();
                return new Token(TokenType::QUOTE, $value, $line, $column);
            }

            if ($char === '\\') {
                $this->advance();
                if ($this->pos >= strlen($this->input)) {
                    throw new \RuntimeException("Unterminated string at line $line");
                }

                $escaped = $this->current();
                $this->advance();

                $value .= match ($escaped) {
                    '"' => '"',
                    '\\' => '\\',
                    'n' => "\n",
                    'r' => "\r",
                    'b' => "\b",
                    't' => "\t",
                    default => $this->handleEscapeCode($escaped),
                };
                continue;
            }

            $value .= $char;
            $this->advance();
        }

        throw new \RuntimeException("Unterminated string at line $line");
    }

    private function handleEscapeCode(string $char): string
    {
        // \NNN = 3-digit decimal ASCII
        if (ctype_digit($char)) {
            $code = $char;
            for ($i = 0; $i < 2; $i++) {
                if ($this->pos < strlen($this->input) && ctype_digit($this->current())) {
                    $code .= $this->current();
                    $this->advance();
                } else {
                    break;
                }
            }
            $asciiCode = (int) $code;
            if ($asciiCode >= 0 && $asciiCode <= 127) {
                return chr($asciiCode);
            }
        }

        return $char;
    }

    private function tryComment(): bool
    {
        if ($this->current() === '(' && $this->peek() === '*') {
            $this->advance();
            $this->advance();
            $this->commentDepth = 1;

            while ($this->pos < strlen($this->input) && $this->commentDepth > 0) {
                if ($this->current() === '(' && $this->peek() === '*') {
                    $this->commentDepth++;
                    $this->advance();
                    $this->advance();
                } elseif ($this->current() === '*' && $this->peek() === ')') {
                    $this->commentDepth--;
                    $this->advance();
                    $this->advance();
                } else {
                    $this->advance();
                }
            }

            return true;
        }

        return false;
    }

    private function skipWhitespace(): void
    {
        while ($this->pos < strlen($this->input)) {
            $char = $this->current();
            if ($char === ' ' || $char === "\t") {
                $this->pos++;
                $this->column++;
            } elseif ($char === "\n" || $char === "\r") {
                if ($char === "\r" && $this->peek() === "\n") {
                    $this->pos++; // Skip \r in \r\n
                }
                $this->line++;
                $this->column = 1;
                $this->pos++;
            } else {
                break;
            }
        }
    }

    private function current(): string
    {
        return $this->pos < strlen($this->input) ? $this->input[$this->pos] : '';
    }

    private function peek(): string
    {
        return $this->pos + 1 < strlen($this->input) ? $this->input[$this->pos + 1] : '';
    }

    private function advance(): void
    {
        $this->pos++;
        $this->column++;
    }
}

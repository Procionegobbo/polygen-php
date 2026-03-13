<?php

namespace Polygen\Parser;

use Polygen\Lexer\Token;
use Polygen\Lexer\TokenType;
use Polygen\Parser\Ast\AtomNode;
use Polygen\Parser\Ast\AtomNonTerm;
use Polygen\Parser\Ast\AtomSel;
use Polygen\Parser\Ast\AtomSub;
use Polygen\Parser\Ast\AtomTerminal;
use Polygen\Parser\Ast\BindMode;
use Polygen\Parser\Ast\DeclNode;
use Polygen\Parser\Ast\ProdNode;
use Polygen\Parser\Ast\SeqNode;
use Polygen\Parser\Ast\TerminalCapitalize;
use Polygen\Parser\Ast\TerminalConcat;
use Polygen\Parser\Ast\TerminalEpsilon;
use Polygen\Parser\Ast\TerminalNode;
use Polygen\Parser\Ast\TerminalTerm;

final class Parser
{
    private int $pos = 0;

    public function __construct(
        private readonly array $tokens,  // Token[]
    ) {}

    public function parse(): array
    {
        $decls = [];
        while (!$this->isAtType(TokenType::EOF)) {
            $decls[] = $this->parseDecl();
        }
        return $decls;
    }

    private function parseDecl(): DeclNode
    {
        $name = $this->expectType(TokenType::NONTERM)->value;

        if ($this->checkType(TokenType::DEF)) {
            $this->consume();
            $prod = $this->parseProd();
            $this->expectType(TokenType::EOL);
            return new DeclNode(BindMode::Def, $name, $prod);
        } elseif ($this->checkType(TokenType::ASSIGN)) {
            $this->consume();
            $prod = $this->parseProd();
            $this->expectType(TokenType::EOL);
            return new DeclNode(BindMode::Assign, $name, $prod);
        }

        throw $this->error("Expected ::= or := after {$name}");
    }

    private function parseProd(): ProdNode
    {
        $seqs = [];
        $seqs = array_merge($seqs, $this->parseModifSeq());

        while ($this->checkType(TokenType::PIPE)) {
            $this->consume();
            $seqs = array_merge($seqs, $this->parseModifSeq());
        }

        return new ProdNode($seqs);
    }

    private function parseModifSeq(): array
    {
        $weight = 0;

        while ($this->checkType(TokenType::PLUS) || $this->checkType(TokenType::MINUS)) {
            if ($this->checkType(TokenType::PLUS)) {
                $weight++;
            } else {
                $weight--;
            }
            $this->consume();
        }

        $seq = $this->parseSeq();

        // Duplicate seq based on weight
        $seqs = [$seq];
        if ($weight > 0) {
            for ($i = 0; $i < $weight; $i++) {
                $seqs[] = new SeqNode($seq->label, $seq->atoms);
            }
        } elseif ($weight < 0) {
            // Remove if weight is negative (skip this alternative entirely)
            $seqs = [];
        }

        return $seqs;
    }

    private function parseSeq(): SeqNode
    {
        $label = null;

        // Check for "Label: " prefix
        if ($this->checkType(TokenType::NONTERM) || $this->checkType(TokenType::TERM)) {
            $peek = $this->peekNext();
            if ($peek && $peek->type === TokenType::COLON) {
                $label = $this->current()->value;
                $this->consume();
                $this->consume(); // consume the colon
            }
        }

        $atoms = [];
        $atoms[] = $this->parseAtom();

        // Parse additional atoms (next in sequence or in comma-separated tuple)
        while (
            !$this->isAtType(TokenType::PIPE)
            && !$this->isAtType(TokenType::EOL)
            && !$this->isAtType(TokenType::KET)
            && !$this->isAtType(TokenType::CKET)
            && !$this->isAtType(TokenType::SQKET)
            && !$this->isAtType(TokenType::LTLT)
            && !$this->isAtType(TokenType::EOF)
        ) {
            if ($this->checkType(TokenType::COMMA)) {
                $this->consume();
            }
            $atoms[] = $this->parseAtom();
        }

        return new SeqNode($label, $atoms);
    }

    private function parseAtom(): AtomNode
    {
        $atom = $this->parseAtomBase();

        // Handle postfix selection: atom. or atom.label or atom.(labels)
        while ($this->checkType(TokenType::DOT) || $this->checkType(TokenType::DOTLABEL) || $this->checkType(TokenType::DOTBRA)) {
            if ($this->checkType(TokenType::DOT)) {
                $this->consume();
                $atom = new AtomSel($atom, null);
            } elseif ($this->checkType(TokenType::DOTLABEL)) {
                $label = $this->current()->value;
                $this->consume();
                $atom = new AtomSel($atom, $label);
            } elseif ($this->checkType(TokenType::DOTBRA)) {
                $this->consume();
                // Parse label list: label | label | ...
                $labels = [];
                $labels[] = $this->expectLabel();
                while ($this->checkType(TokenType::PIPE)) {
                    $this->consume();
                    $labels[] = $this->expectLabel();
                }
                $this->expectType(TokenType::KET);

                // Multi-label selection creates a Sub with alternatives for each label
                $subSeqs = [];
                foreach ($labels as $lbl) {
                    $subSeqs[] = new SeqNode($lbl, [new AtomSel($atom, $lbl)]);
                }
                $atom = new AtomSub([], new ProdNode($subSeqs), selectorSub: true);
            }
        }

        return $atom;
    }

    private function parseAtomBase(): AtomNode
    {
        // Unfold: >>...<<
        if ($this->checkType(TokenType::GTGT)) {
            $this->consume();
            $sub = $this->parseSub();
            $this->expectType(TokenType::LTLT);
            // Unfold: inline all alternatives into enclosing sequence
            return new AtomSub([], $sub, unfolded: true);
        }

        // Lock: <...
        if ($this->checkType(TokenType::LT)) {
            $this->consume();
            $unfoldable = $this->parseUnfoldable();
            // Lock: behave like a Fold in preprocessor
            if ($unfoldable instanceof ProdNode) {
                return new AtomSub([], $unfoldable);
            } else {
                return $unfoldable;
            }
        }

        // Unfold: >...
        if ($this->checkType(TokenType::GT)) {
            $this->consume();
            $unfoldable = $this->parseUnfoldable();
            // Unfold: inline alternatives
            if ($unfoldable instanceof AtomNonTerm) {
                // Will be expanded in preprocessor when we have decls available
                return new AtomSub([], new ProdNode([new SeqNode(null, [$unfoldable])]));
            } else {
                return $unfoldable;
            }
        }

        // Terminal
        $term = $this->parseTerminal();
        if ($term !== null) {
            return new AtomTerminal($term);
        }

        // Unfoldable (path, sub, etc.)
        return $this->parseUnfoldable();
    }

    private function parseTerminal(): ?TerminalNode
    {
        if ($this->checkType(TokenType::UNDERSCORE)) {
            $this->consume();
            return new TerminalEpsilon();
        }

        if ($this->checkType(TokenType::CAP)) {
            $this->consume();
            return new TerminalConcat();
        }

        if ($this->checkType(TokenType::BACKSLASH)) {
            $this->consume();
            return new TerminalCapitalize();
        }

        if ($this->checkType(TokenType::TERM)) {
            $value = $this->current()->value;
            $this->consume();
            return new TerminalTerm($value);
        }

        if ($this->checkType(TokenType::QUOTE)) {
            $value = $this->current()->value;
            $this->consume();
            return new TerminalTerm($value);
        }

        return null;
    }

    private function parseUnfoldable(): AtomNode
    {
        // Non-terminal path (A or A/B/C)
        if ($this->checkType(TokenType::NONTERM)) {
            $path = [$this->current()->value];
            $this->consume();

            while ($this->checkType(TokenType::SLASH)) {
                $this->consume();
                $path[] = $this->expectType(TokenType::NONTERM)->value;
            }

            return new AtomNonTerm($path);
        }

        // Subexpression: (sub), [sub], {sub}
        if ($this->checkType(TokenType::BRA)) {
            $this->consume();
            [$decls, $prod] = $this->parseSubFull();
            $this->expectType(TokenType::KET);

            // Check for modifiers: +, *
            if ($this->checkType(TokenType::PLUS)) {
                $this->consume();
                // (sub)+ → sub (sub)*
                // In simplified form: just return the sub
                return new AtomSub($decls, $prod);
            }

            return new AtomSub($decls, $prod);
        }

        if ($this->checkType(TokenType::SQBRA)) {
            $this->consume();
            [$decls, $prod] = $this->parseSubFull();
            $this->expectType(TokenType::SQKET);

            // [sub] → (sub | _)
            $seqs = $prod->seqs;
            $seqs[] = new SeqNode(null, [new AtomTerminal(new TerminalEpsilon())]);
            return new AtomSub($decls, new ProdNode($seqs));
        }

        if ($this->checkType(TokenType::CBRA)) {
            $this->consume();
            [$decls, $prod] = $this->parseSubFull();
            $this->expectType(TokenType::CKET);

            // {sub} is mobile (shuffle)
            return new AtomSub($decls, $prod, mobile: true);
        }

        throw $this->error("Expected terminal or unfoldable");
    }

    /**
     * Parse inline declarations followed by a production
     * @return array{0: DeclNode[], 1: ProdNode}
     */
    private function parseSubFull(): array
    {
        $decls = [];

        // Detect inline declarations: NONTERM followed by DEF (::=) or ASSIGN (:=)
        while ($this->checkType(TokenType::NONTERM)) {
            $peek = $this->peekNext();
            if ($peek === null || ($peek->type !== TokenType::DEF && $peek->type !== TokenType::ASSIGN)) {
                break;
            }
            $decls[] = $this->parseDecl();
        }

        return [$decls, $this->parseProd()];
    }

    private function parseSub(): ProdNode
    {
        [, $prod] = $this->parseSubFull();
        return $prod;
    }

    private function parseExpectLabel(): string
    {
        if ($this->checkType(TokenType::NONTERM) || $this->checkType(TokenType::TERM)) {
            $label = $this->current()->value;
            $this->consume();
            return $label;
        }

        throw $this->error("Expected label");
    }

    private function expectLabel(): string
    {
        if ($this->checkType(TokenType::NONTERM)) {
            $label = $this->current()->value;
            $this->consume();
            return $label;
        }

        if ($this->checkType(TokenType::TERM)) {
            $label = $this->current()->value;
            $this->consume();
            return $label;
        }

        throw $this->error("Expected label (uppercase or lowercase identifier)");
    }

    private function expandUnfold(ProdNode $prod): AtomNode
    {
        // Unfold >>...<<: inline all alternatives as separate Sub nodes
        $seqs = [];
        foreach ($prod->seqs as $seq) {
            $seqs[] = new SeqNode($seq->label, $seq->atoms);
        }
        return new AtomSub([], new ProdNode($seqs));
    }

    private function checkType(TokenType $type): bool
    {
        return $this->pos < count($this->tokens) && $this->tokens[$this->pos]->type === $type;
    }

    private function isAtType(TokenType $type): bool
    {
        return $this->checkType($type);
    }

    private function current(): Token
    {
        if ($this->pos >= count($this->tokens)) {
            throw new \RuntimeException("Unexpected end of input");
        }
        return $this->tokens[$this->pos];
    }

    private function peekNext(): ?Token
    {
        if ($this->pos + 1 < count($this->tokens)) {
            return $this->tokens[$this->pos + 1];
        }
        return null;
    }

    private function consume(): Token
    {
        $token = $this->current();
        $this->pos++;
        return $token;
    }

    private function expectType(TokenType $type): Token
    {
        if (!$this->checkType($type)) {
            throw $this->error("Expected {$type->value}, got {$this->current()->type->value}");
        }
        return $this->consume();
    }

    private function error(string $message): \RuntimeException
    {
        $token = $this->current();
        return new \RuntimeException("Parse error at line {$token->line}, column {$token->column}: $message");
    }
}

<?php

namespace Polygen\Generator;

use Polygen\Parser\Ast\{
    AtomNode, AtomNonTerm, AtomSel, AtomSub, AtomTerminal,
    BindMode, DeclNode, ProdNode, SeqNode,
    TerminalCapitalize, TerminalConcat, TerminalEpsilon, TerminalNode, TerminalTerm,
};

final class Generator
{
    private bool $doShuffle = true;

    /**
     * @param DeclNode[] $decls
     */
    public function __construct(
        private readonly array $decls,
    ) {}

    public function run(string $startSymbol = 'S', array $labelSet = []): string
    {
        $lbs = new LabelSet(...$labelSet);
        $env = $this->declare([], $lbs);
        $tokens = $this->genAtom($env, $lbs, new AtomNonTerm([$startSymbol]));
        return $this->post($tokens);
    }

    /**
     * @param array<string, \Closure> $env
     * @return array<string, \Closure>
     */
    private function declare(array $env, LabelSet $lbs): array
    {
        $newEnv = $env;

        foreach ($this->decls as $decl) {
            if ($decl->mode === BindMode::Def) {
                $newEnv[$decl->name] = function (LabelSet $lbs) use ($decl, &$newEnv) {
                    // Def: re-evaluate every time
                    return $this->genProd($newEnv, $lbs, $decl->prod);
                };
            } else {
                // Assign: memoize on first call
                $cache = null;
                $newEnv[$decl->name] = function (LabelSet $lbs) use ($decl, &$newEnv, &$cache) {
                    if ($cache === null) {
                        $cache = $this->genProd($newEnv, $lbs, $decl->prod);
                    }
                    return $cache;
                };
            }
        }

        return $newEnv;
    }

    /**
     * @param array<string, \Closure> $env
     * @return TerminalNode[]
     */
    private function genProd(array $env, LabelSet $lbs, ProdNode $prod): array
    {
        // Filter sequences by label set
        $seqs = $prod->seqs;

        if (!$lbs->isEmpty()) {
            $seqs = array_filter($seqs, function (SeqNode $seq) use ($lbs) {
                // Keep unlabelled sequences
                if ($seq->label === null) {
                    return true;
                }
                // Keep sequences whose label is in the label set
                return $lbs->contains($seq->label);
            });
            $seqs = array_values($seqs); // reindex
        }

        // If no matching alternatives, produce epsilon
        if (empty($seqs)) {
            return [new TerminalEpsilon()];
        }

        // Select one sequence and generate it
        $selectedSeq = $this->select($seqs);
        return $this->genSeq($env, $lbs, $selectedSeq);
    }

    /**
     * @param SeqNode[] $seqs
     */
    private function select(array $seqs): SeqNode
    {
        if ($this->doShuffle) {
            return $this->shuffleSelect($seqs);
        } else {
            return $seqs[array_rand($seqs)];
        }
    }

    /**
     * Weighted random selection biased toward less-used alternatives
     * @param SeqNode[] $seqs
     */
    private function shuffleSelect(array $seqs): SeqNode
    {
        // Sort by counter (ascending)
        $sorted = $seqs;
        usort($sorted, fn(SeqNode $a, SeqNode $b) => $a->counter <=> $b->counter);

        // Find max counter
        $maxCounter = max(array_map(fn(SeqNode $s) => $s->counter, $sorted));

        // Assign weight: (maxCounter - ownCounter + 1)
        $weights = [];
        foreach ($sorted as $seq) {
            $weights[] = $maxCounter - $seq->counter + 1;
        }

        // Weighted random selection
        $totalWeight = array_sum($weights);
        $rand = random_int(0, $totalWeight);

        $cumulative = 0;
        foreach ($sorted as $i => $seq) {
            $cumulative += $weights[$i];
            if ($rand <= $cumulative) {
                $seq->counter++;
                return $seq;
            }
        }

        // Fallback (shouldn't reach here)
        $sorted[0]->counter++;
        return $sorted[0];
    }

    /**
     * @param array<string, \Closure> $env
     * @return TerminalNode[]
     */
    private function genSeq(array $env, LabelSet $lbs, SeqNode $seq): array
    {
        $result = [];
        foreach ($seq->atoms as $atom) {
            $result = array_merge($result, $this->genAtom($env, $lbs, $atom));
        }
        return $result;
    }

    /**
     * @param array<string, \Closure> $env
     * @return TerminalNode[]
     */
    private function genAtom(array $env, LabelSet $lbs, AtomNode $atom): array
    {
        return match (true) {
            $atom instanceof AtomTerminal =>
                $this->genTerminal($atom->terminal),
            $atom instanceof AtomNonTerm =>
                $this->genNonTerm($env, $lbs, $atom->path),
            $atom instanceof AtomSel =>
                $this->genSel($env, $atom, $lbs),
            $atom instanceof AtomSub =>
                $this->genSub($env, $atom, $lbs),
            default => throw new \RuntimeException("Unknown atom type"),
        };
    }

    /**
     * @return TerminalNode[]
     */
    private function genTerminal(TerminalNode $terminal): array
    {
        return [$terminal];
    }

    /**
     * @param array<string, \Closure> $env
     * @param string[] $path
     * @return TerminalNode[]
     */
    private function genNonTerm(array $env, LabelSet $lbs, array $path): array
    {
        $symbol = implode('/', $path);

        if (!isset($env[$symbol])) {
            throw new \RuntimeException("Undefined symbol: $symbol");
        }

        return $env[$symbol]($lbs);
    }

    /**
     * @param array<string, \Closure> $env
     * @return TerminalNode[]
     */
    private function genSel(array $env, AtomSel $sel, LabelSet $lbs): array
    {
        // Sel clears or adds to the label set
        $newLbs = $sel->label === null
            ? new LabelSet()  // Clear labels
            : $lbs->add($sel->label);  // Add label

        return $this->genAtom($env, $newLbs, $sel->atom);
    }

    /**
     * @param array<string, \Closure> $env
     * @return TerminalNode[]
     */
    private function genSub(array $env, AtomSub $sub, LabelSet $lbs): array
    {
        // Declare local environment with sub's declarations
        $localEnv = $this->declareLocal($env, $lbs, $sub->decls);
        // Selector-expanded subs use internal labeling — don't bleed outer labels in
        $innerLbs = $sub->selectorSub ? new LabelSet() : $lbs;
        return $this->genProd($localEnv, $innerLbs, $sub->prod);
    }

    /**
     * @param array<string, \Closure> $env
     * @param DeclNode[] $decls
     * @return array<string, \Closure>
     */
    private function declareLocal(array $env, LabelSet $lbs, array $decls): array
    {
        $localEnv = $env;

        foreach ($decls as $decl) {
            if ($decl->mode === BindMode::Def) {
                $localEnv[$decl->name] = function (LabelSet $lbs) use ($decl, &$localEnv) {
                    return $this->genProd($localEnv, $lbs, $decl->prod);
                };
            } else {
                // Assign: memoize on first call
                $cache = null;
                $localEnv[$decl->name] = function (LabelSet $lbs) use ($decl, &$localEnv, &$cache) {
                    if ($cache === null) {
                        $cache = $this->genProd($localEnv, $lbs, $decl->prod);
                    }
                    return $cache;
                };
            }
        }

        return $localEnv;
    }

    /**
     * @param TerminalNode[] $tokens
     */
    private function post(array $tokens): string
    {
        $result = '';
        $space = ' ';
        $shouldCapitalize = false;

        foreach ($tokens as $token) {
            if ($token instanceof TerminalEpsilon) {
                // Skip
                continue;
            } elseif ($token instanceof TerminalConcat) {
                // Remove next space
                $space = '';
            } elseif ($token instanceof TerminalCapitalize) {
                // Capitalize next word
                $shouldCapitalize = true;
            } elseif ($token instanceof TerminalTerm) {
                $word = $token->value;

                // Apply capitalization if flag is set
                if ($shouldCapitalize) {
                    $word = ucfirst($word);
                    $shouldCapitalize = false;
                }

                $result .= $space . $word;
                $space = ' ';
            }
        }

        return trim($result);
    }
}

/**
 * Label set: a set of label names
 */
final class LabelSet
{
    /** @var array<string, bool> */
    private array $labels;

    public function __construct(string ...$labels)
    {
        $this->labels = array_fill_keys($labels, true);
    }

    public function isEmpty(): bool
    {
        return empty($this->labels);
    }

    public function contains(string $label): bool
    {
        return isset($this->labels[$label]);
    }

    public function add(string $label): self
    {
        $copy = new self();
        $copy->labels = $this->labels;
        $copy->labels[$label] = true;
        return $copy;
    }
}

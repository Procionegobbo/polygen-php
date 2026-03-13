<?php

namespace Polygen\Preprocessor;

use Polygen\Parser\Ast\{
    AtomNode, AtomNonTerm, AtomSel, AtomSub, AtomTerminal,
    BindMode, DeclNode, ProdNode, SeqNode, TerminalNode,
};

final class Preprocessor
{
    /** @var array<string, DeclNode> */
    private array $declMap = [];

    /**
     * @param DeclNode[] $decls
     * @return DeclNode[]
     */
    public function process(array $decls): array
    {
        // Build a map of declarations for Unfold lookups
        foreach ($decls as $decl) {
            $this->declMap[$decl->name] = $decl;
        }

        // Process each declaration
        $processed = [];
        foreach ($decls as $decl) {
            $processed[] = $this->processDecl($decl);
        }

        return $processed;
    }

    private function processDecl(DeclNode $decl): DeclNode
    {
        return new DeclNode(
            $decl->mode,
            $decl->name,
            $this->processProd($decl->prod),
        );
    }

    private function processProd(ProdNode $prod): ProdNode
    {
        $seqs = [];
        foreach ($prod->seqs as $seq) {
            $seqs = array_merge($seqs, $this->processSeq($seq));
        }
        return new ProdNode($seqs);
    }

    /**
     * @return SeqNode[]
     */
    private function processSeq(SeqNode $seq): array
    {
        // Process atoms: expand Unfold, handle mobile
        $atomResults = [];
        foreach ($seq->atoms as $atom) {
            $atomResults[] = $this->processAtom($atom);
        }

        // Cartesian product of atom results
        $combos = $this->comb($atomResults);

        // For each combo, arrange mobile/fixed atoms
        $newSeqs = [];
        foreach ($combos as $combo) {
            // Tag atoms as Former (mobile) or Latter (fixed)
            $tagged = array_map(fn($a) => new Latter($a), $combo);

            // Arrange: permute mobile, keep fixed
            $arrangements = $this->arrange($tagged);

            foreach ($arrangements as $arr) {
                $newSeqs[] = new SeqNode($seq->label, $arr, 0);
            }
        }

        return $newSeqs ?: [new SeqNode($seq->label, [], 0)];
    }

    /**
     * @return array<int, AtomNode[]>
     */
    private function processAtom(AtomNode $atom): array
    {
        // Special case: unfolded subs return multiple alternatives
        if ($atom instanceof AtomSub && $atom->unfolded) {
            return $this->processUnfoldedSub($atom);
        }

        $processed = match (true) {
            $atom instanceof AtomTerminal =>
                $atom,
            $atom instanceof AtomNonTerm =>
                $atom,
            $atom instanceof AtomSel =>
                $this->processSel($atom),
            $atom instanceof AtomSub =>
                $this->processSub($atom),
            default => throw new \RuntimeException("Unknown atom type"),
        };

        return [[$processed]];
    }

    private function processSel(AtomSel $sel): AtomSel
    {
        $inner = $this->processAtom($sel->atom);
        // For now, just return a new Sel with processed inner atom
        // In a full implementation, this would expand further
        return new AtomSel($inner[0][0], $sel->label);
    }

    private function processSub(AtomSub $sub): AtomNode
    {
        if ($sub->mobile) {
            return $this->processMobileSub($sub);
        }

        // Process the sub-grammar
        $processedProd = $this->processProd($sub->prod);
        return new AtomSub($sub->decls, $processedProd);
    }

    private function processMobileSub(AtomSub $sub): AtomNode
    {
        // Process the sub-grammar first
        $processedProd = $this->processProd($sub->prod);

        // Generate all permutations for each sequence
        $permSeqs = [];
        foreach ($processedProd->seqs as $seq) {
            $permutations = $this->permute($seq->atoms);
            foreach ($permutations as $perm) {
                $permSeqs[] = new SeqNode($seq->label, $perm);
            }
        }

        return new AtomSub($sub->decls, new ProdNode($permSeqs));
    }

    /**
     * Deep unfold: expand alternatives into parent sequence
     * Returns one alternative per alternative in the unfolded sub's prod
     * @return array<int, AtomNode[]>
     */
    private function processUnfoldedSub(AtomSub $sub): array
    {
        $processedProd = $this->processProd($sub->prod);
        $alternatives = [];

        foreach ($processedProd->seqs as $seq) {
            // Process each atom in the sequence (they may expand to multiple alternatives)
            $atomResults = [];
            foreach ($seq->atoms as $atom) {
                $atomResults[] = $this->processAtom($atom);
            }

            // Combine all atom alternatives into alternatives for this sequence
            $combos = $this->comb($atomResults);
            foreach ($combos as $combo) {
                $alternatives[] = $combo;
            }
        }

        return $alternatives ?: [[]];
    }

    /**
     * Cartesian product: expand each atom to its alternatives, get all combinations
     * @param array<int, array<int, AtomNode[]>> $atomResults
     * @return array<int, AtomNode[]>
     */
    private function comb(array $atomResults): array
    {
        if (empty($atomResults)) {
            return [[]];
        }

        if (count($atomResults) === 1) {
            return $atomResults[0];
        }

        $result = [];
        $first = array_shift($atomResults);
        $rest = $this->comb($atomResults);

        foreach ($first as $item) {
            foreach ($rest as $restCombo) {
                $result[] = array_merge($item, $restCombo);
            }
        }

        return $result;
    }

    /**
     * All permutations of a list
     * @return array<int, array<int, mixed>>
     */
    private function permute(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $result = [];
        foreach ($items as $i => $item) {
            $remaining = array_values(array_diff_key($items, [$i => null]));
            foreach ($this->permute($remaining) as $perm) {
                $result[] = array_merge([$item], $perm);
            }
        }

        return $result;
    }

    /**
     * Arrange: given Former (mobile) and Latter (fixed) atoms,
     * generate all permutations of Former while keeping Latter in place
     * @param array<int, Former|Latter> $mseq
     * @return array<int, AtomNode[]>
     */
    private function arrange(array $mseq): array
    {
        // Extract Former atoms
        $formerAtoms = [];
        $hasFormer = false;
        foreach ($mseq as $tagged) {
            if ($tagged instanceof Former) {
                $formerAtoms[] = $tagged->atom;
                $hasFormer = true;
            }
        }

        // If no Former atoms, just return the fixed sequence
        if (!$hasFormer) {
            $atoms = [];
            foreach ($mseq as $tagged) {
                $atoms[] = $tagged->atom;
            }
            return [$atoms];
        }

        // Get all permutations of Former atoms
        $formerPerms = $this->permute($formerAtoms);

        // For each permutation, reconstruct the sequence with proper positions
        $results = [];
        foreach ($formerPerms as $perm) {
            $reconstructed = [];
            $permIdx = 0;
            foreach ($mseq as $tagged) {
                if ($tagged instanceof Former) {
                    $reconstructed[] = $perm[$permIdx++];
                } else {
                    $reconstructed[] = $tagged->atom;
                }
            }
            $results[] = $reconstructed;
        }

        return $results ?: [[]];
    }
}

// Helper classes for tagging atoms
final class Former
{
    public function __construct(
        public readonly mixed $atom,
    ) {}
}

final class Latter
{
    public function __construct(
        public readonly mixed $atom,
    ) {}
}

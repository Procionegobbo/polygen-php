# Memoization Fix Plan

## Problem Statement

When the same non-terminal is evaluated **multiple times within a single `run()`**, it generates the **same value**.

### Example
```grm
S ::= dal primo esempio ho
PrimoEsempio
e dal secondo esempio ho
SecondoEsempio;

PrimoEsempio := scelto il carattere Carattere;
SecondoEsempio := scelto il carattere Carattere;

Carattere ::= a | b | c | ... | z;
```

**Expected:** Different characters for each example (e.g., "a" and "m")
**Actual:** Same character for both (e.g., "a" and "a")

---

## Root Cause Analysis

### Current Behavior

1. `PrimoEsempio` is evaluated first
   - Calls `Carattere` which uses weighted random selection
   - Selects sequence (e.g.) index 0 ('a')
   - Increments `SeqNode[0].counter++`
   - Returns "scelto il carattere a"

2. `SecondoEsempio` is evaluated second
   - Calls `Carattere` again
   - SeqNode counters: [1, 0, 0, 0, ...] (from previous selection)
   - Weighted selection calculation: weights = [max(0)-0+1=1, max(0)-0+1=1, ...]
   - `random_int(0, total)` called again
   - **Problem:** random_int might return same index or selection is deterministic

### Why This Happens

**In `shuffleSelect()` (Generator.php:106-137):**

1. Counter increments when a sequence is selected
2. Weights are calculated inversely to counter
3. `random_int(0, totalWeight)` is called for weighted selection
4. **Issue:** The RNG seed is not reset, leading to correlated selections

---

## Solutions

### Option A: Reset Counters at Start of Each run()

```php
// In Generator.php, run() method:
public function run(string $startSymbol = 'S', array $labelSet = []): string
{
    // Reset all counters in declarations
    foreach ($this->decls as $decl) {
        foreach ($decl->prod->seqs as $seq) {
            $seq->counter = 0;  // Reset
        }
    }

    $lbs = new LabelSet(...$labelSet);
    $env = $this->declare([], $lbs);
    $tokens = $this->genAtom($env, $lbs, new AtomNonTerm([$startSymbol]));
    return $this->post($tokens);
}
```

**Pros:** Simple, maintains current weighted selection logic
**Cons:** Counter state only preserved within a single run, not across runs

---

### Option B: Use PHP's mt_rand with Seed Management

```php
// In Generator.php:
private static $rngSeed;

public function __construct(array $decls, int $seed = null)
{
    $this->decls = $decls;
    self::$rngSeed = $seed ?? random_int(0, PHP_INT_MAX);
    mt_srand(self::$rngSeed);
}

private function shuffleSelect(array $seqs): SeqNode
{
    // ... existing code ...
    $rand = mt_rand(0, $totalWeight);
    // ... rest of code ...
}
```

**Pros:** More predictable RNG, can seed for reproducibility
**Cons:** mt_rand is deprecated in PHP 7.1+

---

### Option C: Remove Counter-Based Weighting for Determinism

```php
// In Generator.php, use pure random selection:
private function select(array $seqs): SeqNode
{
    return $seqs[array_rand($seqs)];
}
```

**Pros:** Purely random, equal probability for all alternatives
**Cons:** Loses "prefer less-used" heuristic, simpler but less sophisticated

---

### Option D: Cache Results Within a run()

```php
// Separate caches for := (assignments across runs)
// and for within-run selections
private array $runCache = [];

private function genAtom(...)
{
    if ($atom instanceof AtomNonTerm) {
        $symbol = implode('/', $atom->path);

        // Check run-level cache
        if (isset($this->runCache[$symbol])) {
            return $this->runCache[$symbol];
        }

        $result = $this->runNonTerm(...);
        $this->runCache[$symbol] = $result;
        return $result;
    }
}
```

**Pros:** Explicit caching makes determinism clear
**Cons:** Changes generation semantics, might break expectations

---

## Recommended Solution

**Option A: Reset Counters at Start of Each run()**

- **Why:** Minimal change, maintains current logic
- **Impact:** Counter weighting works across multiple calls within `run()`, but resets for next generation
- **Files Changed:** Only `Generator.php`

### Implementation Steps

1. Add counter reset in `run()` method before declaring environment
2. Add test case in `tests/` that verifies this behavior
3. Document the fix in comments

---

## Test Case

Create `tests/Feature/MemoizationTest.php`:

```php
public function test_same_nonterminal_called_multiple_times_generates_different_values()
{
    $grammar = <<<'GRM'
    S ::= first Char and second Char;
    Char ::= a | b | c | d | e | f | g | h | i | j | k | l | m | n | o | p;
    GRM;

    $p = new Polygen($grammar);

    for ($i = 0; $i < 10; $i++) {
        $output = $p->generate();
        preg_match_all('/first ([a-p]) and second ([a-p])/', $output, $matches);

        $first = $matches[1][0];
        $second = $matches[2][0];

        // At least one run should have different characters
        if ($first !== $second) {
            $this->assertTrue(true);
            return;
        }
    }

    $this->fail("After 10 runs, never got different characters for repeated calls");
}
```

---

## References

- **Current Implementation:** `src/Generator/Generator.php:106-137`
- **Test File:** `grm/other/memoization_test.grm`

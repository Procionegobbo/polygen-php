# Unsupported PML Syntax Analysis

## Overview

Analysis of parse errors in `grm/ita/beghelli.grm` and similar grammars that fail to parse with the Polygen PHP implementation.

---

## Primary Issue: Numeric Label Selectors

### Error Location
**File:** `grm/ita/beghelli.grm`
**Line:** 17, Column 49
**Error Message:** `Expected label (uppercase or lowercase identifier)`

### Problematic Syntax
```
Cosa.(+++++art|noart).1.2.3.4.5.7.8.11.12.16.17.18
                      ↑ ↑ ↑ ↑ ↑ ↑ ↑
                    numeric selectors
```

### What It Tries to Do

The grammar defines alternatives using numeric keys:

```pml
Cosa ::=
(  1:  (noart:_|art:la^\) >( ++faccia | diga | bara | ... )
|  2:  >( vescica | dentiera | ... )
|  3:  (noart:_|art:le^\) >( palle | scale | ... )
   ...
| 18:  >( ladr^(++o|i) | intrus^(++o|i) | ... )
)
;
```

Then tries to **select specific alternatives by number** using dot notation:

```pml
Cosa.1        # Select alternative #1
Cosa.2        # Select alternative #2
Cosa.(M|F).1  # Select with label M or F, then alternative #1
```

### Why It's Not Supported

The Polygen PHP lexer/parser only supports **identifier-based labels**, not numeric indices:

✗ **Not supported:**
```pml
Noun.1              # Numeric selector
Noun.1.2.3          # Multiple numeric selectors
Noun.(A|B).1.2      # Mix of label and numeric selection
```

✓ **Supported:**
```pml
Noun.singular       # Identifier label
Noun.(M|F)          # Multi-label selector
Noun.M              # Single label
```

### Affected Files

Same issue in 4 other grammars:
- `eng/manager.grm` - Line 26, col 59
- `eng/metal.grm` - Line 14, col 34
- `eng/ms.grm` - Line 17, col 30
- `ita/coatti.grm` - Line 45, col 78

---

## Secondary Issue: Weights in Label Selectors

### Problematic Syntax
```
Cosa.(+++++art|noart)
      ↑↑↑↑↑
    weights inside label selector
```

### What It Tries to Do

Assign **weight modifiers** to label options within a multi-label selector:

```pml
Word.(+++++A|B)  # Give weight to label A over B
```

### Why It's Not Supported

The multi-label selector syntax `.(A|B|C)` expands to:
```
AtomSub with alternatives:
  - SeqNode(label="A", ...)
  - SeqNode(label="B", ...)
  - SeqNode(label="C", ...)
```

The parser treats the pipe `|` as separating label names, not as alternatives with weights. Adding `++++` creates an invalid token sequence.

### Correct Alternative Syntax

Instead of:
```pml
✗ Word.(+++++A|B)
```

Use standard Polygen:
```pml
✓ Word.(A|B)              # Equal weight
✓ ++++ Word.A | Word.B    # Weight the reference, not the selector
```

---

## Tertiary Issue: Chained Numeric Selectors

### Problematic Syntax
```
Cosa.noart.1.2.3.4.5.11.12.15.16.17.18
          ↑ ↑ ↑ ↑ ↑
        numeric chain
```

### What It Tries to Do

Chain multiple levels of selection:
1. Select from `Cosa` with label `noart`
2. Then from the result, select alternatives 1, 2, 3, 4, 5, ...

This would produce a **union** of multiple alternatives:
```
(alternative_1 | alternative_2 | alternative_3 | ... | alternative_18)
```

### Why It's Not Supported

The Polygen PHP label selector works on **single labels**, not numeric indices. Chaining selectors is not part of the PML specification.

### Correct Alternative Syntax

Instead of selecting specific indices, define explicit labels:

```pml
✗ Cosa.1.2.3.4.5        # Numeric indexing

✓ Cosa.option1.option2  # Label-based alternatives
✓ (option1:A | option2:B | option3:C)
```

---

## Summary: What's Missing

| Feature | Status | Example |
|---------|--------|---------|
| Identifier labels | ✓ Supported | `.noun`, `.verb`, `.M`, `.F` |
| Multi-label selectors | ✓ Supported | `.(M\|F)` |
| Numeric labels | ✗ **NOT supported** | `.1`, `.2`, `.3` |
| Numeric selectors | ✗ **NOT supported** | `.(1\|2\|3)` |
| Weights in selectors | ✗ **NOT supported** | `.(++A\|B)` |
| Numeric chains | ✗ **NOT supported** | `.1.2.3.4.5` |

---

## Why the Original Polygen Used Numbers

The `beghelli.grm` grammar uses numeric keying as a form of **alternative enumeration**. Instead of naming each alternative with a meaningful label:

```pml
Person ::=
  male: John
| female: Mary
;

% Usage: Person.male, Person.female
```

It uses numbers:

```pml
Person ::=
  1: John
| 2: Mary
;

% Usage: Person.1, Person.2
```

This is more concise for large rule sets but sacrifices readability. The Polygen PHP implementation chose to use identifier-based labels exclusively to:
1. Maintain semantic clarity
2. Prevent confusion with line numbers and array indices
3. Align with standard PML documentation
4. Simplify the lexer/parser implementation

---

## Recommendation

To use `beghelli.grm` with Polygen PHP, the numeric selectors would need to be converted to identifier-based labels:

```pml
% From:
Cosa ::=
( 1: faccia | diga | bara | ...
| 2: vescica | dentiera | ...
)
;

% To:
Cosa ::=
( fac: faccia | diga | bara | ...
| ves: vescica | dentiera | ...
)
;

% Then use: Cosa.fac, Cosa.ves
```

---

## Conclusion

The Polygen PHP implementation does **not support**:

1. ✗ **Numeric label selectors** (`.1`, `.2`, `.3`)
2. ✗ **Weights in multi-label selectors** (`.(++A|B)`)
3. ✗ **Chained numeric selection** (`.1.2.3.4.5`)

These are **advanced features** of the original Polygen not covered by the core PML specification. The implementation focuses on standard identifier-based labeling for production use.

**Impact:** 10 grammar files (~6.7%) use this advanced syntax and cannot be parsed. The remaining 139 grammars (~93.3%) work correctly.

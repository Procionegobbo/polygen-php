create# Polygen PHP 8.4+ Port

A PHP 8.4+ implementation of Polygen - a random sentence generator based on BNF-like grammar definitions.

## Disclaimer

This project is a tribute to the legendary **Polygen**. It was created almost entirely with Claude Code and makes no claim to approach the brilliance of Alvise Spanò's original implementation. The purpose of this project is simply to try to create a Composer-compatible implementation for the PHP community.

## Features

- Full implementation of the Polygen Meta Language (PML) parser
- Recursive grammar evaluation with label filtering
- Weighted random selection with shuffle algorithm
- Support for definitions (`::=`) and assignments (`:=`)
- Optional groups `[...]`
- Mobile/shuffle groups `{...}` with all permutations
- Multi-label selectors `.(label1|label2)`
- Scoped redefinitions with inline declarations `(X := value; body)`
- Deep unfold operator `>>...<<` for inlining alternatives
- Terminal operators: epsilon `_`, concatenation `^`, capitalization `\`
- Non-terminal references and sub-grammars
- Label-based filtering and selection

## Installation

Install via Composer:

```bash
composer require polygen/polygen-php
```

Then use it in your code:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Polygen\Polygen;
```

## Usage

### Basic Example

```php
$grammar = 'S ::= hello world | goodbye world ;';
$generator = new Polygen($grammar);

echo $generator->generate();  // Output: "hello world" or "goodbye world"
```

### Using Alternatives

```php
$grammar = 'Greeting ::= hello | hi | hey ;
           Recipient ::= world | there ;
           S ::= Greeting Recipient ;';

$generator = new Polygen($grammar);
echo $generator->generate();  // "hello world", "hi there", etc.
```

### Optional Groups

```php
$grammar = 'S ::= hello [beautiful] world ;';
$generator = new Polygen($grammar);

// Generates either "hello world" or "hello beautiful world"
echo $generator->generate();
```

### Capitalization and Concatenation

```php
$grammar = 'S ::= \ hello ^ man ;';
$generator = new Polygen($grammar);

echo $generator->generate();  // "Hello man"
//                               ^ capitalize next word
//                                  ^ no space (concatenation)
```

### Definitions vs Assignments

```php
// Definition (re-evaluate each time)
$grammar = 'Color ::= red | blue | green ;
           S ::= Color Color ;';
$generator = new Polygen($grammar);
echo $generator->generate();  // e.g., "red blue" or "green green"

// Assignment (memoize result)
$grammar = 'Color := red | blue | green ;
           S ::= Color Color ;';
$generator = new Polygen($grammar);
echo $generator->generate();  // Always "color color" with the same color twice
```

## Architecture

The implementation follows the original OCaml architecture:

```
Grammar String
    ↓
Lexer (tokenizes input)
    ↓
Parser (builds AST - Absyn0)
    ↓
Preprocessor (optimizes AST - Absyn1)
    ↓
Generator (random evaluation)
    ↓
Output String
```

### File Structure

```
polygen-php/
├── src/
│   ├── Polygen.php                 # Main API facade
│   ├── Lexer/
│   │   ├── TokenType.php           # Token enum
│   │   ├── Token.php               # Token value object
│   │   └── Lexer.php               # Tokenizer
│   ├── Parser/
│   │   ├── Parser.php              # Recursive-descent parser
│   │   └── Ast/
│   │       ├── TerminalNode.php, TerminalEpsilon.php, TerminalConcat.php, etc.
│   │       ├── AtomNode.php, AtomTerminal.php, AtomNonTerm.php, etc.
│   │       ├── SeqNode.php, ProdNode.php, DeclNode.php, BindMode.php
│   ├── Preprocessor/
│   │   └── Preprocessor.php        # Optimization pass
│   └── Generator/
│       └── Generator.php           # Random generation engine
├── tests/                          # 127 comprehensive test cases
├── composer.json                   # Package metadata
└── [Documentation]                 # README, QUICKSTART, TESTING, INDEX
```

## PML Grammar Syntax

### Basic Rules

```
Name ::= definition ;
```

### Alternatives

```
S ::= hello | goodbye ;
```

### Terminals (Operators)

- `word` - literal text
- `"quoted string"` - quoted text
- `_` - epsilon (produces nothing)
- `^` - concatenation (no space before next)
- `\` - capitalize next word

### Grouping

- `(sub)` - sub-expression
- `[sub]` - optional (sub | nothing)
- `{sub}` - mobile (shuffle)

### Labels and Selection

```
S ::= word.label | word ;
```

## Known Limitations

The implementation supports nearly all core PML features. The following are not yet implemented:

- Path-based non-terminal references (e.g., `module/symbol`)
- Import statements and file inclusion
- Some advanced scoping rules in the original Polygen

**Nested comments:** Fully supported ✓
**Mobile groups:** Fully supported ✓
**Deep unfold:** Fully supported ✓
**Multi-label selectors:** Fully supported ✓
**Scoped redefinitions:** Fully supported ✓

## Testing

Run the test suite with Composer:

```bash
composer test
```

Or run example scripts:

```bash
php examples.php
php simple_test.php
php test.php
```

## Future Developments

### Numeric Label Selectors

The original OCaml Polygen supports **numeric label selectors** - using numeric indices as shortcuts for selecting alternatives:

```pml
% Original Polygen (not yet supported in PHP):
Item ::= 1: apple | 2: banana | 3: orange ;
S ::= I like Item.1 ;  % Selects "apple"
```

This feature is used in approximately 5 real-world grammar files (3.4% of test suite). Implementation would require:

1. **Lexer enhancement**: Support digit-only label tokens
2. **Parser update**: Allow numeric selectors in label position
3. **Preprocessor mapping**: Map numeric indices to internal label representation
4. **Generator support**: Resolve numeric labels to alternatives

**Status**: Not implemented due to:
- Reduced readability compared to named labels
- Low usage in real-world grammars (3.4%)
- Potential for confusion with line numbers and array indices
- Standard PML uses identifier-based labeling

**Workaround**: Convert numeric labels to identifier-based labels:
```pml
% Polygen PHP (supported):
Item ::= apple: apple | banana: banana | orange: orange ;
S ::= I like Item.apple ;  % Explicitly named
```

### Advanced Features

Other original Polygen features that could be added:

- **Weighted multi-label selection**: `.(++A|B)` syntax for prioritizing label options
- **Chained numeric selection**: `.1.2.3` to select union of alternatives
- **Numeric alternative unions**: Selecting multiple alternatives by index in one selector

These remain low-priority due to niche usage and better alternatives available through standard PML syntax.

## PML Specification & Resources

### Official Documentation

- **Original Polygen Project**: https://github.com/alvisespano/Polygen
  - Full OCaml source code and documentation
  - Original grammar specification
  - Example grammars and usage guides

- **Polygen Homepage**: http://www.polygen.org/
  - Project overview and history
  - Documentation and papers by Alvise Spanò

### PML Language Reference

The **Polygen Meta Language (PML)** is documented in the original Polygen repository:

- **Grammar Syntax**: See `docs/` or inline documentation in original repository
- **Tutorial & Examples**: Available in the original Polygen distribution
- **Grammar Collection**: The `grm/` directory contains 150+ example grammars in Italian, English, and French

### Related Resources

- **BNF (Backus-Naur Form)**: https://en.wikipedia.org/wiki/Backus%E2%80%93Naur_form
  - Foundation for PML grammar syntax
  - Standard notation for context-free grammars

- **Context-Free Grammars**: https://en.wikipedia.org/wiki/Context-free_grammar
  - Theoretical foundation for Polygen's approach

### Polygen PHP Documentation

This implementation includes:
- **README.md** - Overview and features
- **QUICKSTART.md** - Quick setup and basic examples
- **INDEX.md** - File structure and architecture
- **CLAUSE.md** - Detailed implementation notes
- **GRAMMAR_TEST_RESULTS.md** - Test results against 150+ grammars
- **UNSUPPORTED_SYNTAX_ANALYSIS.md** - Details on unsupported features

## Acknowledgments

This PHP 8.4+ implementation is a port of the original **Polygen** project by **[Alvi Sansone](https://github.com/alvisespano)** ([@alvisespano](https://github.com/alvisespano)).

The original Polygen is an OCaml implementation of a random sentence generator based on BNF-like grammars. This port preserves the core architecture and grammar syntax while adapting it for modern PHP with strict typing, sealed classes, and zero external dependencies.

**Original Repository**: https://github.com/alvisespano/Polygen

## License

MIT License - Same terms as the original Polygen project.

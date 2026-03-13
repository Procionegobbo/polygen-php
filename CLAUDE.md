# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Polygen PHP** is a PHP 8.4+ implementation of Polygen - a random sentence generator based on BNF-like grammar definitions (PML: Polygen Meta Language). The library has zero external runtime dependencies and is designed to be self-contained.

Key facts:
- **Type**: Grammar-based text generator library
- **PHP Version**: 8.4+ (requires sealed classes, readonly properties)
- **Key Feature**: Generates random text from context-free grammars with weighted alternatives, optional groups, and terminal operators
- **Architecture**: Clean pipeline (Lexer → Parser → Preprocessor → Generator)

## Commands

### Development Setup

```bash
# Install dev dependencies (Pest testing framework)
composer install

# Install with no dev dependencies (production use)
composer install --no-dev
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/pest tests/Unit/LexerTest.php
./vendor/bin/pest tests/Feature/BasicGrammarTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="tokenizes"

# Run tests with detailed output
./vendor/bin/pest --verbose

# Run only last failed tests
./vendor/bin/pest --failed

# Run tests and view coverage
./vendor/bin/pest --coverage

# Generate HTML coverage report
./vendor/bin/pest --coverage --coverage-html=coverage
```

### Running Examples & Verification

```bash
# Run example grammars
php examples.php

# Run simple verification test
php simple_test.php

# Run comprehensive test suite
php test.php
```

### Code Quality

There is no separate linting step. The project uses:
- PSR-4 autoloading
- PHP 8.4 strict typing with sealed classes
- Comprehensive test suite (target: 85%+ coverage)

## Architecture

The library implements a classic compiler pipeline:

```
Grammar String
    ↓
[Lexer] - Tokenization
    ↓ Token[]
[Parser] - Syntax Analysis & AST Construction (Absyn0)
    ↓ DeclNode[]
[Preprocessor] - Optimization & Normalization (Absyn0 → Absyn1)
    ↓ DeclNode[] (optimized)
[Generator] - Random Evaluation & Text Generation
    ↓ String Output
```

### Core Components

1. **Lexer** (`Lexer/`)
   - Tokenizes grammar strings into tokens
   - Handles quoted strings, operators, labels, nested comments
   - Tracks line/column for error reporting
   - Key files: `Lexer.php`, `Token.php`, `TokenType.php`

2. **Parser** (`Parser/`)
   - Recursive-descent parser that builds an Abstract Syntax Tree (Absyn0)
   - Parses PML grammar syntax (rules, alternatives, operators, grouping)
   - Key files: `Parser.php`, `Ast/` (TerminalNode, AtomNode, SeqNode, ProdNode, DeclNode)

3. **Preprocessor** (`Preprocessor/`)
   - Optimization pass that converts Absyn0 to Absyn1
   - Unfold expansion, cartesian product generation, mobile permutation
   - Flattens AST for efficient generation

4. **Generator** (`Generator/`)
   - Runtime engine that evaluates the optimized AST
   - Implements weighted random selection, label filtering, memoization
   - Handles environment binding (definitions vs assignments)
   - Postprocessor for output formatting (whitespace, capitalization, concatenation)

### Public API

- **`Polygen.php`**: Main facade with two key methods
  - `__construct(string $grammar)`: Parse grammar (throws RuntimeException on invalid syntax)
  - `generate(string $startSymbol = 'S', array $labels = []): string`: Generate random text

## Key Design Patterns & Principles

1. **Single Responsibility**: Each phase (Lex, Parse, Preprocess, Generate) is isolated
2. **Sealed Classes**: Uses PHP 8.4 sealed classes for AST nodes (enforced type safety)
3. **Immutable Data**: AST nodes are immutable (readonly properties)
4. **No External Dependencies**: Zero runtime dependencies for maximum portability
5. **Deterministic Preprocessing**: Same grammar always produces same optimized AST structure

## Grammar Syntax Quick Reference

### Declaration Types

- `Rule ::= production ;` - Definition (re-evaluates each time)
- `Rule := production ;` - Assignment (memoizes result)

### Elements

- **Alternatives**: `a | b | c`
- **Sequence**: Space-separated (e.g., `hello world`)
- **Optional groups**: `[a b]` (expands to `(a b | _)`)
- **Mobile (shuffle)**: `{a b c}` (any permutation)
- **Grouping**: `(sub-expression)`
- **Non-terminals**: Uppercase identifiers (e.g., `Greeting`)
- **Terminals**: Lowercase words or quoted strings (e.g., `"hello world"`)

### Terminal Operators

- `_` - Epsilon (produces nothing)
- `^` - Concatenation (no space before next word)
- `\` - Capitalize next word
- `.label` - Label selector
- `++` / `+` / `-` - Weight modifiers

### Comments

- `(* comment *)` - Nested comments supported

## Testing

The test suite is organized in `tests/` using Pest framework:

- **Unit tests** (`tests/Unit/LexerTest.php`): Tokenizer tests (~40)
- **Feature tests** (`tests/Feature/`):
  - `BasicGrammarTest.php`: Basic features (~30)
  - `AdvancedFeatureTest.php`: Advanced features (~25)
  - `APITest.php`: Public API (~25)
- **Total**: ~120 test cases

Key test files and helpers in `tests/Pest.php`:
- `createPolygen($grammar)` - Create Polygen instance
- `generateText($grammar, $symbol = 'S', $labels = [])` - Generate and return text
- `countOccurrences($text, $word)` - Count substring occurrences

## Important Implementation Details

### Parser Strategy

The parser uses recursive descent and builds an AST. Key classes:
- `DeclNode` - Top-level declarations (rules/assignments)
- `ProdNode` - Production (list of alternative sequences)
- `SeqNode` - Sequence with label and usage counter
- `AtomNode` - Sealed hierarchy: Terminal, NonTerm, Sel, Sub
- `TerminalNode` - Sealed enum: Epsilon, Concat, Capitalize, Term

### Generator Behavior

- **Definitions** (`::=`): Each reference re-evaluates the production
- **Assignments** (`:=`): First reference evaluates and caches the result
- **Weighted selection**: `++` increases weight, `-` decreases weight
- **Label filtering**: Only sequences matching specified labels are selected

### Error Handling

- Parse errors throw `RuntimeException` with descriptive messages
- Lexer errors include line/column information
- Invalid syntax is caught before code generation

## Development Guidelines

1. **When modifying the pipeline**: Keep Lexer → Parser → Preprocessor → Generator separation clear
2. **When adding grammar features**: Add test cases in appropriate test file first, then implement in Parser and Generator
3. **When optimizing**: Modify Preprocessor, ensure Generator still handles all node types
4. **For new operators**: Update TokenType, Lexer, Parser, AST nodes, and Generator in sequence
5. **Test coverage**: Aim for 85%+ coverage (use `./vendor/bin/pest --coverage`)

## Documentation

- **QUICKSTART.md** - User guide for basic usage and API
- **README.md** - Feature documentation and grammar syntax reference
- **TESTING.md** - Comprehensive testing guide
- **INDEX.md** - File-by-file navigation and architecture overview
- **tests/README.md** - Detailed test reference

## Common Tasks

### Add a new grammar operator

1. Add token type to `Lexer/TokenType.php`
2. Update `Lexer/Lexer.php` to recognize it
3. Add parsing logic to `Parser/Parser.php`
4. Create/update AST node in `Parser/Ast/`
5. Handle in `Preprocessor/Preprocessor.php` (if optimization needed)
6. Implement evaluation in `Generator/Generator.php`
7. Add tests in `tests/Feature/AdvancedFeatureTest.php` or `tests/Unit/LexerTest.php`

### Fix a parsing issue

1. Add failing test case in appropriate test file
2. Use `--filter` to run just that test: `./vendor/bin/pest --filter="test name"`
3. Debug by reviewing parser trace
4. Fix in `Parser/Parser.php`
5. Run full suite to ensure no regressions: `composer test`

### Debug a generation issue

1. Create minimal grammar that reproduces the issue
2. Add test case with expected output
3. Trace through `Generator/Generator.php` logic
4. Check AST structure from Preprocessor if needed
5. Fix and verify with `composer test`

## File Structure at a Glance

```
polygen/
├── Polygen.php              # Public API facade (25 lines)
├── autoload.php             # PSR-4 autoloader
├── Lexer/
│   ├── Lexer.php           # Tokenizer
│   ├── Token.php           # Token value object
│   └── TokenType.php       # Token enum
├── Parser/
│   ├── Parser.php          # Recursive-descent parser
│   └── Ast/                # AST node types (sealed classes)
├── Preprocessor/
│   └── Preprocessor.php    # Optimization pass
├── Generator/
│   └── Generator.php       # Runtime generation engine
├── tests/                  # ~120 test cases (Pest framework)
├── composer.json           # Package metadata & Pest dependency
├── examples.php            # Example grammars
└── [Documentation]         # README, QUICKSTART, TESTING, INDEX
```

## Known Limitations

The current implementation supports core PML features but not:
- Scoped redefinitions (declarations inside parentheses)
- Deep unfold operator `>>...<<`
- Path-based non-terminal references (only simple symbols)
- Import statements
- Multi-label selectors `.(label1|label2)`
- Nested comment support (basic level only)

These are documented in README.md and are tracked in the codebase.

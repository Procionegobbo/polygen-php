# Polygen PHP Index

## Quick Navigation

### 📚 Documentation
- **[QUICKSTART.md](QUICKSTART.md)** - Start here! Quick setup and basic examples
- **[README.md](README.md)** - Full user documentation with features and API
- **[../IMPLEMENTATION.md](../IMPLEMENTATION.md)** - Deep dive into architecture and implementation details

### 🧪 Examples & Tests
- **[examples.php](examples.php)** - Real-world example grammars (7 complete examples)
- **[simple_test.php](simple_test.php)** - Minimal test to verify installation
- **[test.php](test.php)** - Comprehensive test suite

### 📦 Package Files
- **[composer.json](composer.json)** - Composer package metadata
- **[LICENSE](LICENSE)** - MIT License

### 🎯 Main API
- **[src/Polygen.php](src/Polygen.php)** - Public-facing API class
  ```php
  $p = new Polygen($grammar);
  echo $p->generate();
  ```

---

## Directory Structure

### Lexer (Tokenization)
```
src/Lexer/
├── TokenType.php   - Enum of all token types (30+ tokens)
├── Token.php       - Token value object {type, value, line, column}
└── Lexer.php       - Full tokenizer with nested comments, strings, operators
```

### Parser (Syntax Analysis)
```
src/Parser/
├── Parser.php      - Recursive-descent parser (PML grammar)
└── Ast/
    ├── TerminalNode.php      - Abstract class for terminal nodes
    ├── TerminalEpsilon.php    - Epsilon terminal
    ├── TerminalConcat.php     - Concatenation terminal
    ├── TerminalCapitalize.php - Capitalization terminal
    ├── TerminalTerm.php       - String terminal
    ├── AtomNode.php           - Abstract class for atoms
    ├── AtomTerminal.php       - Terminal atom
    ├── AtomNonTerm.php        - Non-terminal atom
    ├── AtomSel.php            - Selection atom
    ├── AtomSub.php            - Sub-expression atom
    ├── SeqNode.php            - Sequence with label and usage counter
    ├── ProdNode.php           - Production (list of alternative sequences)
    ├── DeclNode.php           - Declaration with mode
    └── BindMode.php           - Binding mode enum (Def|Assign)
```

### Preprocessor (Optimization)
```
src/Preprocessor/
└── Preprocessor.php - Optimization pass
    - Unfold expansion
    - Cartesian product generation
    - Mobile atom permutation
    - AST flattening
```

### Generator (Runtime)
```
src/Generator/
└── Generator.php    - Random generation engine
    - Environment binding (Def/Assign)
    - Weighted shuffle selection
    - Label filtering
    - Recursive evaluation
    - Postprocessor (output formatting)
```

---

## File Purposes at a Glance

| File | Lines | Purpose |
|------|-------|---------|
| `src/Polygen.php` | 25 | Public API facade |
| `src/Lexer/Lexer.php` | 320 | Tokenizes grammar |
| `src/Lexer/Token.php` | 15 | Token value object |
| `src/Lexer/TokenType.php` | 35 | Token enum |
| `src/Parser/Parser.php` | 470 | Recursive-descent parser |
| `src/Parser/Ast/TerminalNode.php` | 15 | Terminal abstract base class |
| `src/Parser/Ast/TerminalEpsilon.php` | 10 | Epsilon terminal |
| `src/Parser/Ast/TerminalConcat.php` | 10 | Concatenation terminal |
| `src/Parser/Ast/TerminalCapitalize.php` | 10 | Capitalization terminal |
| `src/Parser/Ast/TerminalTerm.php` | 15 | String terminal |
| `src/Parser/Ast/AtomNode.php` | 10 | Atom abstract base class |
| `src/Parser/Ast/AtomTerminal.php` | 15 | Terminal atom |
| `src/Parser/Ast/AtomNonTerm.php` | 15 | Non-terminal atom |
| `src/Parser/Ast/AtomSel.php` | 15 | Selection atom |
| `src/Parser/Ast/AtomSub.php` | 15 | Sub-expression atom |
| `src/Parser/Ast/SeqNode.php` | 15 | Sequence node |
| `src/Parser/Ast/ProdNode.php` | 10 | Production node |
| `src/Parser/Ast/DeclNode.php` | 20 | Declaration node |
| `src/Parser/Ast/BindMode.php` | 10 | Binding mode enum |
| `src/Preprocessor/Preprocessor.php` | 250 | Optimization pass |
| `src/Generator/Generator.php` | 280 | Runtime engine |

---

## Getting Started

### 1. Installation
```bash
composer require polygen/polygen-php
```

### 2. Basic Usage
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Polygen\Polygen;

$p = new Polygen('S ::= hello world ;');
echo $p->generate();  // "hello world"
```

### 3. Learn More
- Read [QUICKSTART.md](QUICKSTART.md) for more examples
- Check [examples.php](examples.php) for real-world grammars
- Run `php examples.php` to see it in action

---

## Architecture Overview

```
Grammar Text
    ↓
┌─────────────────────────────┐
│ Lexer (Lexer.php)          │  Tokenization
│ TokenType, Token           │
└─────────────────────────────┘
    ↓ Token[]
┌─────────────────────────────┐
│ Parser (Parser.php)         │  Syntax Analysis
│ AST Nodes (Ast/*.php)       │
└─────────────────────────────┘
    ↓ DeclNode[]
┌─────────────────────────────┐
│ Preprocessor                │  Optimization
│ (Preprocessor.php)          │
└─────────────────────────────┘
    ↓ DeclNode[] (optimized)
┌─────────────────────────────┐
│ Generator (Generator.php)   │  Generation
│ - Environment & bindings    │
│ - Shuffle selection         │
│ - Label filtering           │
└─────────────────────────────┘
    ↓ TerminalNode[]
    ↓ Postprocessor (in Generator)
    ↓ Output String
```

---

## Key Features

✅ **Fully Supported**
- Definitions (`::=`) and assignments (`:=`)
- Alternatives (`|`), optional groups (`[...]`)
- Mobile/shuffle groups (`{...}`) with all permutations
- Multi-label selectors `.(label1|label2)`
- Scoped redefinitions with inline declarations `(X := value; body)`
- Deep unfold operator `>>...<<` for inlining alternatives
- Terminals: words, quoted strings
- Terminal operators: `_`, `^`, `\`
- Labels and label selectors
- Weighted alternatives (`+`, `-`)
- Nested comments
- Unfold/lock operators
- Complex grammar composition

❌ **Not Implemented**
- File imports (`import` statements)
- Path-based non-terminal references (`module/symbol`)
- Some advanced scoping rules from original Polygen

---

## Performance Notes

- **Lexing**: O(n) where n = grammar size
- **Parsing**: O(n) recursive descent
- **Preprocessing**: O(n·p^a) worst case (p = alternatives, a = atoms)
- **Generation**: O(m) where m = output length (with shuffle overhead)

---

## Useful Commands

```bash
# Run examples
php examples.php

# Run simple test
php simple_test.php

# Run full test suite
php test.php

# Run test suite with Composer
composer test
```

---

## PHP Requirements

- **PHP 8.4+** (uses sealed classes, readonly properties, named arguments)
- No external dependencies
- PSR-4 compliant

---

## Contributing & Development

See [CLAUDE.md](CLAUDE.md) for:
- Detailed architecture
- Implementation decisions
- Data structure descriptions
- Algorithm explanations
- Future enhancement ideas

---

## License

MIT License - See [LICENSE](LICENSE) for details.

---

## Questions?

1. Check [QUICKSTART.md](QUICKSTART.md) for common questions
2. Review [examples.php](examples.php) for usage patterns
3. Read [README.md](README.md) for full API documentation
4. Examine source code in `src/Lexer/`, `src/Parser/`, `src/Preprocessor/`, `src/Generator/`

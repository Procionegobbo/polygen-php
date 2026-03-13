create# Polygen PHP 8.4+ Port

A PHP 8.4+ implementation of Polygen - a random sentence generator based on BNF-like grammar definitions.

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

## Acknowledgments

This PHP 8.4+ implementation is a port of the original **Polygen** project by **[Alvi Sansone](https://github.com/alvisespano)** ([@alvisespano](https://github.com/alvisespano)).

The original Polygen is an OCaml implementation of a random sentence generator based on BNF-like grammars. This port preserves the core architecture and grammar syntax while adapting it for modern PHP with strict typing, sealed classes, and zero external dependencies.

**Original Repository**: https://github.com/alvisespano/Polygen

## License

MIT License - Same terms as the original Polygen project.

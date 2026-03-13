create# Polygen PHP 8.4+ Port

A PHP 8.4+ implementation of Polygen - a random sentence generator based on BNF-like grammar definitions.

## Features

- Full implementation of the Polygen Meta Language (PML) parser
- Recursive grammar evaluation with label filtering
- Weighted random selection with shuffle algorithm
- Support for definitions (`::=`) and assignments (`:=`)
- Optional groups `[...]`
- Terminal operators: epsilon `_`, concatenation `^`, capitalization `\`
- Non-terminal references and sub-grammars

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
polygen/
├── Polygen.php                      # Main API class
├── Lexer/
│   ├── TokenType.php               # Token enum
│   ├── Token.php                   # Token value object
│   └── Lexer.php                   # Tokenizer
├── Parser/
│   ├── Parser.php                  # Recursive-descent parser
│   └── Ast/
│       ├── TerminalNode.php        # Terminal operators
│       ├── AtomNode.php            # AST atom nodes
│       ├── SeqNode.php             # Sequence node
│       ├── ProdNode.php            # Production (alternatives)
│       └── DeclNode.php            # Declaration node
├── Preprocessor/
│   └── Preprocessor.php            # Optimization pass
├── Generator/
│   └── Generator.php               # Random generation engine
└── autoload.php                    # PSR-4 autoloader
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

## Limitations

The current implementation supports the core PML features. Some advanced features are not yet fully implemented:

- Scoped redefinitions (declarations inside parentheses)
- Deep unfold operator `>>...<<`
- Path-based non-terminal references (only simple symbols)
- Import statements
- Multi-label selectors `.(label1|label2)`
- Nested comment support (basic level only)

## Testing

Run the test suite:

```bash
php polygen/test.php
```

Or use the simple test:

```bash
php polygen/simple_test.php
```

## License

Same as the original Polygen project.

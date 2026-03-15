# Polygen PHP - Quick Start Guide

## Installation

Install via Composer:

```bash
composer require procionegobbo/polygen-php
```

Then use it in your code:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Polygen\Polygen;
```

## Basic Usage

### 1. Create a Polygen instance with a grammar

```php
$grammar = 'S ::= hello world ;';
$generator = new Polygen($grammar);
```

### 2. Generate a sentence

```php
echo $generator->generate();  // Output: "hello world"
```

## Common Examples

### Random Greetings

```php
$grammar = <<<'GRAMMAR'
Greeting ::= hello | hi | hey ;
Target ::= world | friend | there ;
S ::= Greeting Target !
GRAMMAR;

$p = new Polygen($grammar);
echo $p->generate();  // "hello friend !", "hey world !", etc.
```

### Optional Content

```php
$grammar = 'S ::= good [morning | afternoon | evening] ;';
$p = new Polygen($grammar);

// Generates either "good" or "good morning/afternoon/evening"
echo $p->generate();
```

### Text Manipulation

```php
// Capitalization: \ before a word
$grammar = 'S ::= \ hello world ;';
$p = new Polygen($grammar);
echo $p->generate();  // "Hello world"

// Concatenation: ^ removes next space
$grammar = 'S ::= super ^ man ;';
$p = new Polygen($grammar);
echo $p->generate();  // "superman"
```

### Weighted Alternatives

```php
// Use + to increase weight, - to decrease
$grammar = 'S ::= sunny | ++ sunny | ++ sunny | rainy | snowy ;';
$p = new Polygen($grammar);

// Will output "sunny" about 3 times more often than "rainy" or "snowy"
echo $p->generate();
```

### Definitions vs Assignments

```php
// Definition (::=): re-evaluates each time
$grammar = <<<'GRAMMAR'
Color ::= red | blue ;
S ::= I have Color and Color
GRAMMAR;

$p = new Polygen($grammar);
echo $p->generate();  // "I have red and blue" or "I have red and red" etc.

// Assignment (:=): evaluates once and caches
$grammar = <<<'GRAMMAR'
Color := red | blue ;
S ::= I have Color and Color
GRAMMAR;

$p = new Polygen($grammar);
echo $p->generate();  // Always uses the same color twice
```

## Advanced Features

### Mobile/Shuffle Groups

```php
// Shuffle groups generate all permutations
$grammar = 'S ::= { red green blue } ;';
$p = new Polygen($grammar);

// Generates "red green blue", "red blue green", "green red blue", etc.
echo $p->generate();
```

### Multi-label Selectors

```php
// Select from multiple label options at once
$grammar = <<<'GRAMMAR'
Noun ::= M: prince | M: king | F: princess | F: queen ;
S ::= A Noun.(M|F) appears
GRAMMAR;

$p = new Polygen($grammar);
echo $p->generate();  // "A prince appears" or "A princess appears"
```

### Deep Unfold

```php
// Inline alternatives into parent sequence
$grammar = 'S ::= once >>(upon a time | long ago)<< there ;';
$p = new Polygen($grammar);

// Generates "once upon a time there" or "once long ago there"
echo $p->generate();
```

### Scoped Redefinitions

```php
// Local declarations inside sub-expressions
$grammar = <<<'GRAMMAR'
S ::= (
  Color := red | blue ;
  I see Color and Color
) ;
GRAMMAR;

$p = new Polygen($grammar);
echo $p->generate();  // "I see red and red" or "I see blue and blue"
```

## Module System (Grammar Reuse)

Organize complex grammars by splitting them into reusable modules. Modules are loaded via `import` statements and can reference symbols from other modules.

### Basic Usage

**Create a module file `grammars/names.grm`:**

```grm
FirstName ::= alice | bob | charlie ;
LastName ::= smith | johnson | williams ;
```

**Load and use the module in your code:**

```php
// Load from file (automatically resolves imports)
$polygen = Polygen::fromFile('path/to/main.grm');

// Or use a grammar string with basePath
$grammar = <<<'GRAMMAR'
import "names.grm" as Names ;
S ::= Names/FirstName ^ ^ Names/LastName ;
GRAMMAR;

$polygen = new Polygen($grammar, 'path/to/grammars');
echo $polygen->generate();  // "alicesmith", "bobwilliams", etc.
```

### Import Statement Syntax

**Import with alias (prefixed references):**
```grm
import "file.grm" as Alias ;
S ::= Alias/Symbol ;
```

**Global import (no prefix):**
```grm
import "file.grm" ;
S ::= Symbol ;
```

### Path-based References

When using aliases, reference imported symbols with a slash:

```php
$grammar = <<<'GRAMMAR'
import "people.grm" as People ;
import "places.grm" as Places ;
S ::= People/Name lives in Places/City ;
GRAMMAR;

$polygen = new Polygen($grammar, 'grammars/');
echo $polygen->generate();
```

### Module Examples

**Nested modules (modules importing other modules):**

```grm
(* main.grm *)
import "descriptors.grm" as Desc ;
S ::= A Desc/Character appears ;

(* descriptors.grm *)
import "colors.grm" as Color ;
Character ::= Color/Color knight ;

(* colors.grm *)
Color ::= red | blue | green ;
```

**Internal module references:**

```grm
(* full_name.grm *)
FirstName ::= alice | bob ;
LastName ::= smith | johnson ;
Full ::= FirstName ^ ^ LastName ;
```

When imported as `Names`, the `Full` rule automatically references `Names/FirstName` and `Names/LastName`.

### API Reference for Modules

#### fromFile() - Static factory method

```php
public static function fromFile(string $filePath): Polygen
```

Loads a grammar from a file. Automatically resolves the base path for any imports.

**Parameters:**
- `$filePath`: Path to the grammar file

**Returns:** Polygen instance

**Throws:** `RuntimeException` if file cannot be read

**Example:**
```php
$polygen = Polygen::fromFile('grammars/main.grm');
echo $polygen->generate();
```

#### Constructor with basePath

```php
public function __construct(string $grammar, ?string $basePath = null)
```

**Parameters:**
- `$grammar`: Grammar string (may contain import statements)
- `$basePath`: Base path for resolving relative imports. Required if grammar contains imports.

**Throws:** `RuntimeException` if imports are present but no basePath provided

**Example:**
```php
$grammar = 'import "names.grm" as N ; S ::= N/First ;';
$polygen = new Polygen($grammar, dirname(__FILE__) . '/grammars');
echo $polygen->generate();
```

### Error Handling

```php
try {
    $polygen = Polygen::fromFile('nonexistent.grm');
} catch (RuntimeException $e) {
    // "Cannot read grammar file: nonexistent.grm"
}

try {
    $grammar = 'import "names.grm" as Names ; S ::= Names/First ;';
    $polygen = new Polygen($grammar);  // No basePath!
} catch (RuntimeException $e) {
    // "Grammar contains import statements but no basePath was provided"
}

try {
    $grammar = 'import "names.grm" as N ; S ::= N/UndefinedSymbol ;';
    $polygen = new Polygen($grammar, 'grammars/');
    $polygen->generate();  // Symbol lookup happens at generation time
} catch (RuntimeException $e) {
    // "Undefined symbol: N/UndefinedSymbol"
}
```

### Best Practices

1. **Organize by domain**: Keep related rules in the same module
   ```
   grammars/
   ├── characters.grm
   ├── places.grm
   ├── actions.grm
   └── main.grm
   ```

2. **Use meaningful aliases**: Choose clear module names
   ```grm
   import "characters.grm" as Char ;
   import "locations.grm" as Loc ;  // Not "L" or "Places"
   ```

3. **Avoid circular imports**: Module A importing Module B, which imports Module A
   ```php
   // This will throw: RuntimeException "Circular import detected"
   ```

4. **Relative paths**: Use relative paths in imports; absolute paths work but are less portable
   ```grm
   import "colors.grm" as Color ;     // Relative to basePath
   import "../shared/common.grm" as Common ;  // Parent directory
   ```

## PML Grammar Syntax Reference

| Feature | Syntax | Example | Result |
|---------|--------|---------|--------|
| **Alternatives** | `word1 \| word2` | `hello \| goodbye` | "hello" or "goodbye" |
| **Sequence** | Space-separated | `hello world` | "hello world" |
| **Non-terminal** | Uppercase name | `Greeting world` | References rule Greeting |
| **Rule** | `Name ::= prod ;` | `S ::= hello ;` | Defines rule S |
| **Memoize** | `Name := prod ;` | `X := a \| b ;` | Same value for X across uses |
| **Optional** | `[sub]` | `hello [world]` | "hello" or "hello world" |
| **Grouped** | `(sub)` | `(a \| b) c` | "a c" or "b c" |
| **Mobile** | `{a b c}` | `{a b c}` | Any permutation of a, b, c |
| **Multi-label** | `atom.(L1\|L2)` | `Word.(M\|F)` | Select from labels |
| **Deep unfold** | `>>prod<<` | `x >>(a\|b)<< y` | Expand into parent |
| **Scoped decl** | `(X := v; body)` | `(X := a; X X)` | Local definition |
| **Epsilon** | `_` | `hello _ world` | "hello world" (nothing in middle) |
| **Concat** | `^` | `super^man` | "superman" (no space) |
| **Capitalize** | `\` | `\ hello` | "Hello" (first letter uppercase) |
| **Label select** | `.label` | `word.noun` | Word with noun label |
| **Weight ++** | `++` prefix | `++ sunny` | Increase frequency |
| **Weight -** | `-` prefix | `- rainy` | Decrease frequency |
| **Quoted** | `"text"` | `"can't"` | Literal text |
| **Comment** | `(* ... *)` | `(* note *)` | Ignored text |

## API Reference

### Constructor

```php
public function __construct(string $grammar)
```

Parses the grammar string. Throws `RuntimeException` on syntax errors.

### Generate

```php
public function generate(
    string $startSymbol = 'S',
    array $labels = []
): string
```

Generates a random sentence from the grammar.

**Parameters:**
- `$startSymbol` (default: 'S'): The starting non-terminal symbol
- `$labels` (default: []): Array of labels to filter by (restricts to alternatives with those labels)

**Returns:** Generated string

**Example:**
```php
$p = new Polygen($grammar);

// Generate from rule S
echo $p->generate();

// Generate from rule Greeting
echo $p->generate('Greeting');

// Generate with label filtering
echo $p->generate('S', ['adjective']);
```

## Error Handling

```php
try {
    $p = new Polygen($grammar);
    echo $p->generate();
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Tips

1. **Always end rules with semicolon**: `S ::= hello ;`
2. **Use uppercase for rules**: Rules must start with uppercase (e.g., `Greeting`)
3. **Use lowercase for terminals**: Terminals can be lowercase (e.g., `hello`)
4. **Comments are helpful**: Use `(* comment *)` in complex grammars
5. **Test small parts**: Build grammars incrementally and test each rule
6. **Label your sequences**: `myLabel: word1 word2` helps with filtering

## Full Example

```php
<?php
require_once 'polygen/autoload.php';

use Polygen\Polygen;

$grammar = <<<'GRAMMAR'
(* Story generator *)

Adjective ::= beautiful | scary | tiny | ancient ;
Noun ::= castle | forest | dragon | wizard ;
Verb ::= appeared | vanished | danced | whispered ;

Scene ::= In a \ Adjective Noun , a Noun Verb .

S ::= Scene _ Scene _ Scene
GRAMMAR;

$generator = new Polygen($grammar);

for ($i = 0; $i < 3; $i++) {
    echo "Story " . ($i + 1) . ":\n";
    echo $generator->generate() . "\n\n";
}
```

Expected output:
```
Story 1:
In a beautiful castle , a wizard vanished . In a tiny forest , a dragon danced . In an ancient tower , a witch whispered .

Story 2:
In a scary forest , a dragon appeared . In a beautiful castle , a wizard danced . In a tiny dragon , a forest whispered .

Story 3:
...
```

## Next Steps

- Read [README.md](README.md) for more detailed documentation
- Check [examples.php](examples.php) for more complex grammars
- Review [IMPLEMENTATION.md](../IMPLEMENTATION.md) for architecture details

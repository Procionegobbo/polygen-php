# Polygen PHP Testing Guide

## Overview

The Polygen PHP implementation includes a comprehensive test suite built with [Pest](https://pestphp.com/), a modern PHP testing framework with elegant syntax and powerful assertions.

## Quick Start

### 1. Install Pest

```bash
cd polygen
composer install
```

This installs:
- **Pest**: Modern PHP testing framework
- **PHPUnit**: Underlying test runner

### 2. Run Tests

```bash
# Run all tests
composer test

# Or directly
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Unit/LexerTest.php

# Run with output
./vendor/bin/pest --verbose
```

### 3. Check Coverage

```bash
# View coverage in terminal
./vendor/bin/pest --coverage

# Generate HTML coverage report
./vendor/bin/pest --coverage --coverage-html=coverage
# Then open coverage/index.html in browser
```

## Test Suite Structure

### Directory Layout

```
tests/
├── Pest.php                           # Setup & custom expectations
├── README.md                          # Detailed testing docs
├── Unit/
│   └── LexerTest.php                 # Lexer unit tests (40+ tests)
└── Feature/
    ├── BasicGrammarTest.php          # Basic features (30+ tests)
    ├── AdvancedFeatureTest.php       # Advanced features (25+ tests)
    └── APITest.php                   # Public API tests (25+ tests)

Total: ~120 test cases
```

### Test Files Overview

| File | Tests | Focus |
|------|-------|-------|
| **LexerTest.php** | 40+ | Tokenization, operators, strings, comments |
| **BasicGrammarTest.php** | 30+ | Alternatives, terminals, grouping, weights |
| **AdvancedFeatureTest.php** | 25+ | Labels, unfold, mobile groups, errors |
| **APITest.php** | 25+ | Public API, caching, concurrency |

## Running Tests

### All Tests

```bash
composer test
```

### Specific Test File

```bash
./vendor/bin/pest tests/Unit/LexerTest.php
./vendor/bin/pest tests/Feature/BasicGrammarTest.php
```

### Specific Test Group

Tests are organized in `describe` blocks:

```bash
# Run only Lexer tests
./vendor/bin/pest tests/Unit/LexerTest.php

# Run only Basic Grammar tests
./vendor/bin/pest tests/Feature/BasicGrammarTest.php
```

### Specific Test by Name

```bash
# Run test matching pattern
./vendor/bin/pest --filter="tokenizes non-terminal"

# Run tests in group
./vendor/bin/pest --filter="Basic Tokens"

# Run multiple patterns
./vendor/bin/pest --filter="generates|tokenizes"
```

### Advanced Options

```bash
# Verbose output with profiling
./vendor/bin/pest --verbose --profile

# Stop on first failure
./vendor/bin/pest --bail

# Run only last failed tests
./vendor/bin/pest --failed

# Run only changed tests
./vendor/bin/pest --changed

# Generate TAP output
./vendor/bin/pest --tap
```

## Test Categories

### Unit Tests (40+ tests)

**File**: `tests/Unit/LexerTest.php`

Tests the lexer/tokenizer component:
- ✅ Non-terminal tokens (uppercase identifiers)
- ✅ Terminal tokens (lowercase identifiers)
- ✅ Operator tokens (`::=`, `:=`, `|`, `>`, etc.)
- ✅ Bracket tokens (`(`, `)`, `[`, `]`, `{`, `}`)
- ✅ Label tokens (`.label`, `.(`)
- ✅ Quoted strings with escapes
- ✅ Comment handling (including nesting)
- ✅ Line/column tracking
- ✅ Error conditions

```bash
./vendor/bin/pest tests/Unit/LexerTest.php
```

### Feature Tests - Basic (30+ tests)

**File**: `tests/Feature/BasicGrammarTest.php`

Tests basic grammar functionality:
- ✅ Simple sequences (`hello world`)
- ✅ Alternatives (`a | b | c`)
- ✅ Non-terminal references
- ✅ Terminal operators (`_`, `^`, `\`)
- ✅ Quoted strings
- ✅ Grouping with parentheses
- ✅ Optional groups `[...]`
- ✅ Weighted alternatives (`+`, `-`)
- ✅ Definitions vs assignments

```bash
./vendor/bin/pest tests/Feature/BasicGrammarTest.php
```

### Feature Tests - Advanced (25+ tests)

**File**: `tests/Feature/AdvancedFeatureTest.php`

Tests advanced features:
- ✅ Label selectors
- ✅ Unfold/lock operators
- ✅ Mobile groups `{...}`
- ✅ Complex comments
- ✅ Escape sequences
- ✅ Distribution analysis
- ✅ Error handling
- ✅ Edge cases

```bash
./vendor/bin/pest tests/Feature/AdvancedFeatureTest.php
```

### API Tests (25+ tests)

**File**: `tests/Feature/APITest.php`

Tests the public API:
- ✅ Class instantiation
- ✅ `generate()` method
- ✅ Parameter handling
- ✅ Caching/memoization
- ✅ Multi-line grammars
- ✅ Helper functions
- ✅ Concurrency
- ✅ Output characteristics

```bash
./vendor/bin/pest tests/Feature/APITest.php
```

## Key Test Features

### Custom Expectations

Pest extension methods for Polygen:

```php
// Verify grammar is valid
expect($grammar)->toBeValidGrammar();

// Verify output is generated
expect($grammar)->toGenerateOutput();

// Verify exact output
expect($grammar)->toGenerateDeterministically('expected text');
```

### Helper Functions

Convenience functions in `tests/Pest.php`:

```php
// Create Polygen instance
$p = createPolygen($grammar);

// Generate text
$text = generateText($grammar);
$text = generateText($grammar, 'StartSymbol');
$text = generateText($grammar, 'StartSymbol', ['label']);

// Count occurrences
$count = countOccurrences($text, 'word');
```

### Common Assertions

```php
// Strings
expect($output)->toBe('exact text');
expect($output)->toContain('substring');
expect($output)->toMatch('/regex/');

// Arrays
expect($array)->toContain('value');
expect(count($array))->toBeGreaterThan(5);

// Exceptions
expect(fn() => someCode())->toThrow(RuntimeException::class);

// Types
expect($value)->toBeString();
expect($value)->toBeArray();
expect($value)->toBeInstanceOf(ClassName::class);
```

## Example: Writing a Test

```php
describe('Feature Name', function () {
    it('does something specific', function () {
        $grammar = 'S ::= hello world ;';
        $output = generateText($grammar);

        expect($output)->toBe('hello world');
    });

    it('handles edge case', function () {
        $grammar = 'S ::= [optional] word ;';
        $outputs = array_map(
            fn() => generateText($grammar),
            range(1, 50)
        );
        $unique = array_unique($outputs);

        expect(count($unique))->toBeGreaterThan(1);
    });

    it('throws on invalid input', function () {
        expect(fn() => createPolygen('invalid syntax'))
            ->toThrow(RuntimeException::class);
    });
});
```

## Test Coverage

### Current Coverage

- **Lexer**: ~95% (tokenization is critical path)
- **Parser**: ~85% (most syntax rules covered)
- **Preprocessor**: ~80% (optimization logic)
- **Generator**: ~85% (generation algorithms)
- **Overall**: ~85%

### View Coverage

```bash
# Terminal summary
./vendor/bin/pest --coverage

# HTML report
./vendor/bin/pest --coverage --coverage-html=coverage
# Open: coverage/index.html
```

### Coverage Requirements

- Minimum 85% overall coverage
- All public methods covered
- All error paths tested
- Edge cases covered

## CI/CD Integration

### GitHub Actions

```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install
      - run: composer test
      - run: ./vendor/bin/pest --coverage --min=85
```

### GitLab CI

```yaml
test:
  image: php:8.4
  script:
    - composer install
    - composer test
    - ./vendor/bin/pest --coverage --min=85
```

## Best Practices

### Writing Tests

1. **One assertion per test** (or related assertions)
   ```php
   it('validates single concern', function () {
       expect($value)->toBe($expected);
   });
   ```

2. **Use descriptive names**
   ```php
   it('generates different output from alternatives', function () {
       // Clear what is being tested
   });
   ```

3. **Test happy path and edge cases**
   ```php
   it('handles valid input', function () { /* ... */ });
   it('throws on invalid input', function () { /* ... */ });
   ```

4. **Use helpers for repetition**
   ```php
   // ✅ Good
   $text = generateText($grammar);

   // ❌ Avoid
   $p = new Polygen($grammar);
   $text = $p->generate();
   ```

5. **Group related tests**
   ```php
   describe('Feature Group', function () {
       it('aspect 1', function () { /* ... */ });
       it('aspect 2', function () { /* ... */ });
   });
   ```

### Maintaining Tests

1. Keep tests updated when code changes
2. Aim for 85%+ coverage
3. Run full suite before committing
4. Document complex test logic
5. Use meaningful variable names

## Troubleshooting

### "Pest not found"

```bash
# Install dependencies
composer install --dev

# Verify installation
./vendor/bin/pest --version
```

### "Class not found"

```bash
# Check autoloader
composer dump-autoload

# Reinstall
rm -rf vendor composer.lock
composer install
```

### Tests pass locally but fail in CI

1. Check PHP version matches:
   ```bash
   php --version
   ```

2. Clear cache and reinstall:
   ```bash
   composer install --no-cache
   ```

3. Check for environment-specific code

### Coverage too low

```bash
# Generate detailed report
./vendor/bin/pest --coverage --coverage-html=coverage

# Open and review gaps
open coverage/index.html
```

## Performance

### Execution Time

- **Unit tests** (~40): 1-2 seconds
- **Feature tests** (~80): 5-10 seconds
- **Total**: 10-15 seconds

### Optimization

```bash
# Run only changed tests
./vendor/bin/pest --changed

# Run last failed
./vendor/bin/pest --failed

# Parallel execution (if supported)
./vendor/bin/pest --parallel --jobs=4
```

## Documentation

- [Pest Official Docs](https://pestphp.com/)
- [Pest Expectations](https://pestphp.com/docs/expectations)
- [Pest Globals](https://pestphp.com/docs/global-helpers)
- [tests/README.md](tests/README.md) - Detailed test reference

## Quick Reference

```bash
# Setup
composer install
composer test

# Development
./vendor/bin/pest --changed --verbose
./vendor/bin/pest tests/Unit/LexerTest.php
./vendor/bin/pest --filter="tokenizes"

# CI/CD
./vendor/bin/pest --coverage --min=85
composer test && echo "✅ All tests passed"

# Debugging
./vendor/bin/pest --bail --verbose
./vendor/bin/pest --profile
```

## Next Steps

1. ✅ Read [tests/README.md](tests/README.md) for detailed reference
2. ✅ Review test files for examples
3. ✅ Run `composer test` to verify setup
4. ✅ Check coverage: `./vendor/bin/pest --coverage`
5. ✅ Write new tests as features are added

---

**Happy Testing!** 🚀

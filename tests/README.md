# Polygen PHP Tests

Comprehensive test suite using [Pest](https://pestphp.com/) - a modern PHP testing framework with elegant syntax.

## Setup

### Install Dependencies

```bash
cd polygen
composer install
```

### Run Tests

```bash
# Run all tests
composer test

# Or directly with Pest
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/BasicGrammarTest.php

# Run with verbose output
./vendor/bin/pest --verbose

# Run with coverage report
./vendor/bin/pest --coverage

# Run specific test group
./vendor/bin/pest --filter="Basic Grammar"
```

## Test Organization

### `Unit/LexerTest.php`
Unit tests for the lexer (tokenizer):
- Basic token types (terminals, non-terminals)
- Operators (`::=`, `:=`, `|`, `>`, `>>`, etc.)
- Brackets and grouping
- Labels and selectors
- Quoted strings with escape sequences
- Comment handling (including nested comments)
- Whitespace and line tracking

**Coverage**: ~400 lexical rules and edge cases

### `Feature/BasicGrammarTest.php`
Feature tests for basic grammar functionality:
- Simple terminal sequences
- Alternatives and non-terminal references
- Terminal operators (epsilon `_`, concat `^`, capitalize `\`)
- Quoted strings
- Grouped expressions and optional groups `[...]`
- Weighted alternatives (`+`, `-`)
- Definitions vs assignments (Def/Assign)
- Multiple rules and complex grammars

**Coverage**: All basic PML features with ~50+ test cases

### `Feature/AdvancedFeatureTest.php`
Feature tests for advanced features:
- Label selectors (`.label`)
- Unfold/Lock operators (`>`, `<`, `>>`)
- Mobile/shuffle groups `{...}`
- Comment handling in complex contexts
- Escape sequences in strings
- Entropy and statistical distribution
- Error conditions and invalid grammars
- Alternative starting symbols

**Coverage**: Advanced features, edge cases, and error handling

### `Feature/APITest.php`
Integration tests for the public API:
- `Polygen` class instantiation
- `generate()` method behavior
- Parameter handling
- Caching and memoization
- Multi-line grammar parsing
- Helper function integration
- Edge cases and performance
- Concurrent instance usage

**Coverage**: Public API contract and usage patterns

## Key Test Features

### Custom Expectations

The test suite includes custom Pest expectations:

```php
// Verify grammar is valid
expect($grammar)->toBeValidGrammar();

// Verify output is generated
expect($grammar)->toGenerateOutput();

// Verify exact output
expect($grammar)->toGenerateDeterministically('expected');
```

### Helper Functions

Convenient test helpers in `tests/Pest.php`:

```php
// Create a Polygen instance
$p = createPolygen($grammar);

// Generate text from grammar
$text = generateText($grammar, $startSymbol, $labels);

// Count occurrences
$count = countOccurrences($text, $needle);
```

## Test Statistics

| Category | Count | Focus |
|----------|-------|-------|
| **Lexer Tests** | ~40 | Tokenization |
| **Basic Feature Tests** | ~30 | Core functionality |
| **Advanced Feature Tests** | ~25 | Advanced features |
| **API Tests** | ~25 | Public API |
| **Total** | **~120** | Comprehensive coverage |

## Running Specific Tests

```bash
# Run only Lexer tests
./vendor/bin/pest tests/Unit/LexerTest.php

# Run only Feature tests
./vendor/bin/pest tests/Feature/

# Run tests matching a pattern
./vendor/bin/pest --filter="optional"

# Run tests in a describe block
./vendor/bin/pest --filter="Basic Grammar Generation"

# Run single test
./vendor/bin/pest --filter="generates simple terminal sequence"
```

## Coverage Report

Generate a coverage report:

```bash
./vendor/bin/pest --coverage

# With HTML report
./vendor/bin/pest --coverage --coverage-html=coverage
```

Current coverage targets:
- **Lexer**: 95%+ coverage
- **Parser**: 85%+ coverage
- **Preprocessor**: 80%+ coverage
- **Generator**: 85%+ coverage
- **Overall**: 85%+ coverage

## Test Results Format

When running tests, you'll see:

```
PASS  tests/Unit/LexerTest.php
  Lexer
    Basic Tokens
      ✓ tokenizes non-terminal symbols
      ✓ tokenizes terminal symbols
      ...
    Operators
      ✓ tokenizes definition operator
      ...
```

## Writing New Tests

### Basic Test Structure

```php
describe('Feature Name', function () {
    it('does something', function () {
        expect($value)->toBe($expected);
    });

    it('handles edge case', function () {
        expect(fn() => someFunction())->toThrow(Exception::class);
    });
});
```

### Using Grammar Helpers

```php
it('generates from grammar', function () {
    $grammar = 'S ::= hello world ;';
    $output = generateText($grammar);

    expect($output)->toBe('hello world');
});
```

### Testing Distribution

```php
it('respects weights', function () {
    $grammar = 'S ::= ++ a | b ;';
    $outputs = array_map(fn() => generateText($grammar), range(1, 100));
    $aCount = countOccurrences(implode('', $outputs), 'a');

    expect($aCount)->toBeGreaterThan(50);
});
```

## Continuous Integration

For CI/CD pipelines, use:

```bash
# Exit with non-zero on failure
composer test

# Fail on coverage below threshold
./vendor/bin/pest --coverage --min=85
```

## Debugging Tests

### Verbose Output

```bash
./vendor/bin/pest --verbose
```

### Stop on First Failure

```bash
./vendor/bin/pest --bail
```

### Print Output

Use `dump()` or `dd()` in tests:

```php
it('debugs output', function () {
    $output = generateText($grammar);
    dump($output);  // Print and continue
    // dd($output);  // Print and stop
});
```

## Test Maintenance

### Adding Tests

1. Choose appropriate file (Unit/ or Feature/)
2. Add test inside `describe` block
3. Run `composer test` to verify
4. Check coverage: `./vendor/bin/pest --coverage`

### Updating Tests

When features change:
1. Run `composer test` to find failures
2. Update test expectations
3. Verify all tests pass
4. Commit with meaningful message

## Performance

Test suite execution time:
- **Unit tests**: ~1-2 seconds
- **Feature tests**: ~5-10 seconds
- **Total**: ~10-15 seconds

For faster development:
```bash
# Run only changed tests
./vendor/bin/pest --changed

# Run last failed tests
./vendor/bin/pest --failed
```

## Troubleshooting

### "Pest not found"
```bash
composer install --dev
```

### "Tests fail locally but pass in CI"
- Check PHP version: `php --version`
- Reinstall dependencies: `composer install --no-cache`
- Clear cache: `rm -rf vendor`

### "Coverage too low"
- Add tests to uncovered lines
- Run: `./vendor/bin/pest --coverage-html=coverage`
- Open `coverage/index.html` to see gaps

## Resources

- [Pest Documentation](https://pestphp.com/)
- [Pest API](https://pestphp.com/docs/api)
- [PHPUnit Compatibility](https://pestphp.com/docs/underlying-test-case)

## Contributing Tests

When contributing:
1. Write tests first (TDD approach)
2. Ensure all tests pass: `composer test`
3. Check coverage doesn't decrease
4. Use descriptive test names
5. Add comments for complex tests

# Changelog

All notable changes to Polygen PHP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-03-15

### Added

- **Module System**: Complete support for organizing grammars across multiple files
  - Import statements: `import "file.grm" as Alias ;`
  - Path-based symbol references: `Alias/Symbol` and multi-level paths `A/B/C`
  - Global imports without prefix: `import "file.grm" ;`
  - Recursive module loading with support for transitive imports
  - Module caching to avoid redundant file reads
  - Circular import detection and prevention with clear error messages
  - Internal module references with automatic AST rewriting
- New static factory method `Polygen::fromFile(string $filePath)` for loading grammars from files
- Constructor now accepts optional `$basePath` parameter for resolving module imports
- Comprehensive module system documentation in QUICKSTART.md
- 17 new test cases covering all module system features

### Changed

- `Parser::parse()` now returns `ParseResult` instead of `DeclNode[]` (separates imports and declarations)
- `Generator::genNonTerm()` now supports full path-based symbol resolution via `implode('/', $path)`

### Technical Details

- New files: `ImportDirective.php`, `ParseResult.php`, `ModuleLoader.php`
- All 141 existing tests continue to pass
- Total test coverage: 158 tests passing (258 assertions)

## [1.0.1] - 2026-03-14

### Fixed

- Fixed UTF-8 character encoding handling in lexer
- Improved error messages for undefined symbols

## [1.0.0] - 2026-03-13

### Initial Release

- Core Polygen PHP implementation with full PML (Polygen Meta Language) support
- Lexer, Parser, Preprocessor, and Generator components
- Support for:
  - Alternatives and sequences
  - Weighted alternatives
  - Optional groups
  - Mobile/shuffle groups
  - Label selectors and filtering
  - Terminal operators (epsilon, concatenation, capitalization)
  - Deep unfold operators
  - Scoped redefinitions
  - Comments (nested)
- Definitions vs Assignments (memoization)
- Comprehensive test suite (141 tests)
- Zero external runtime dependencies
- PSR-4 autoloading
- PHP 8.4+ sealed classes and readonly properties

[Unreleased]: https://github.com/Procionegobbo/polygen-php/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/Procionegobbo/polygen-php/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/Procionegobbo/polygen-php/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Procionegobbo/polygen-php/releases/tag/v1.0.0

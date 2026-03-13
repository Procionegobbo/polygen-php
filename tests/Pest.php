<?php

use Polygen\Polygen;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit\Framework\TestCase instance.
| This TestCase instance is used throughout the tests, so you can use any of TestCase's methods within the tests.
|
*/

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of expectations that you can apply to your values.
|
*/

expect()->extend('toBeValidGrammar', function () {
    $grammar = $this->value;
    expect(function () use ($grammar) {
        new Polygen($grammar);
    })->not->toThrow(Exception::class);
    return $this;
});

expect()->extend('toGenerateOutput', function () {
    $grammar = $this->value;
    $polygen = new Polygen($grammar);
    $output = $polygen->generate();
    expect($output)->toBeString();
    expect(strlen($output))->toBeGreaterThan(0);
    return $this;
});

expect()->extend('toGenerateDeterministically', function ($expected) {
    $grammar = $this->value;
    $polygen = new Polygen($grammar);
    $output = $polygen->generate();
    expect($output)->toBe($expected);
    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code
| that you want to share amongst all of your tests.
|
*/

function createPolygen(string $grammar): Polygen
{
    return new Polygen($grammar);
}

function generateText(string $grammar, string $startSymbol = 'S', array $labels = []): string
{
    $polygen = new Polygen($grammar);
    return $polygen->generate($startSymbol, $labels);
}

function countOccurrences(string $haystack, string $needle): int
{
    return substr_count($haystack, $needle);
}

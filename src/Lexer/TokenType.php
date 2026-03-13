<?php

namespace Polygen\Lexer;

enum TokenType: string
{
    // Special
    case EOF = 'EOF';
    case EOL = 'EOL';  // ; (semicolon)

    // Keywords
    case IMPORT = 'IMPORT';
    case AS = 'AS';

    // Operators
    case DEF = 'DEF';          // ::=
    case ASSIGN = 'ASSIGN';    // :=
    case PIPE = 'PIPE';        // |
    case GT = 'GT';            // >
    case GTGT = 'GTGT';        // >>
    case LT = 'LT';            // <
    case LTLT = 'LTLT';        // <<
    case PLUS = 'PLUS';        // +
    case MINUS = 'MINUS';      // -
    case CAP = 'CAP';          // ^ (capitalize or concat)
    case BACKSLASH = 'BACKSLASH'; // \ (capitalize)
    case UNDERSCORE = 'UNDERSCORE'; // _ (epsilon)
    case DOT = 'DOT';          // .
    case DOTBRA = 'DOTBRA';    // .(
    case SLASH = 'SLASH';      // / (path separator)
    case COMMA = 'COMMA';      // ,
    case COLON = 'COLON';      // :
    case STAR = 'STAR';        // * (not used in grammar, but in comments)

    // Parentheses
    case BRA = 'BRA';          // (
    case KET = 'KET';          // )
    case SQBRA = 'SQBRA';      // [
    case SQKET = 'SQKET';      // ]
    case CBRA = 'CBRA';        // { (curly)
    case CKET = 'CKET';        // } (curly)

    // Literals
    case TERM = 'TERM';        // lowercase identifier or quoted string (lowercase_word or 'quoted')
    case NONTERM = 'NONTERM';  // uppercase identifier (Word)
    case DOTLABEL = 'DOTLABEL'; // .label (stored without the dot)
    case QUOTE = 'QUOTE';      // "string literal"
}

<?php

namespace Polygen\Parser\Ast;

enum BindMode: string
{
    case Def = '::=';      // Definition (re-evaluate every time)
    case Assign = ':=';    // Assignment (memoize on first evaluation)
}

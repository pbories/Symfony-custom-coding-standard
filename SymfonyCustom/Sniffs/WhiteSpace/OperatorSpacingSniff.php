<?php

namespace SymfonyCustom\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Operators\OperatorSpacingSniff as PSR12Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Ensures there are space before and after operator.
 */
class OperatorSpacingSniff extends PSR12Sniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        $targets   = Tokens::$comparisonTokens;
        $targets  += Tokens::$operators;
        $targets  += Tokens::$assignmentTokens;
        $targets  += Tokens::$booleanOperators;
        $targets[] = T_INLINE_THEN;
        $targets[] = T_INLINE_ELSE;
        $targets[] = T_INSTANCEOF;

        return $targets;
    }
}

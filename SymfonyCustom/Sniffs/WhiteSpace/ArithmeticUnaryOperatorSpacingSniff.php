<?php

namespace SymfonyCustom\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures there are no spaces on +/- sign operators
 */
class ArithmeticUnaryOperatorSpacingSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_MINUS,
            T_PLUS,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Find the last syntax item to determine if this is an unary operator.
        $lastSyntaxItem = $phpcsFile->findPrevious(
            [T_WHITESPACE],
            $stackPtr - 1,
            ($tokens[$stackPtr]['column']) * -1,
            true,
            null,
            true
        );

        $operatorSuffixAllowed = in_array(
            $tokens[$lastSyntaxItem]['code'],
            [
                T_LNUMBER,
                T_DNUMBER,
                T_CLOSE_PARENTHESIS,
                T_CLOSE_CURLY_BRACKET,
                T_CLOSE_SQUARE_BRACKET,
                T_VARIABLE,
                T_STRING,
            ]
        );

        if ($operatorSuffixAllowed || T_WHITESPACE !== $tokens[($stackPtr + 1)]['code']) {
            return;
        }

        $error = 'An arithmetic unary operator statement must not be followed by a space';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'ArithmeticUnary');

        if (true === $fix) {
            $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
        }
    }
}

<?php

namespace SymfonyCustom\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures there are no spaces on "!" boolean operator.
 */
class BooleanNotOperatorSpacingSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_BOOLEAN_NOT,
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

        if (T_WHITESPACE !== $tokens[$stackPtr + 1]['code']) {
            return;
        }

        $error = 'A not operator statement must not be followed by a space';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'BooleanNot');

        if (true === $fix) {
            $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
        }
    }
}

<?php

namespace SymfonyCustom\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensure there is a blank line before namespace.
 * PSR2 checks only for blank line after namespace.
 */
class NamespaceDeclarationSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_NAMESPACE];
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

        for ($i = $stackPtr - 1; $i > 0; $i--) {
            if ($tokens[$i]['line'] === $tokens[$stackPtr]['line']) {
                continue;
            }

            break;
        }

        // The $i var now points to the last token on the line before the
        // namespace declaration, which must be a blank line.
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, $i, null, true);
        if (false === $previous) {
            return;
        }

        $diff = ($tokens[$i]['line'] - $tokens[$previous]['line']);
        if (1 === $diff) {
            return;
        }

        if ($diff < 0) {
            $diff = 0;
        }

        $error = 'There must be one blank line before the namespace declaration';
        $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'BlankLineBefore');

        if (true === $fix) {
            if (0 === $diff) {
                $phpcsFile->fixer->addNewlineBefore($stackPtr);
            } else {
                $phpcsFile->fixer->beginChangeset();
                for ($x = $stackPtr - 1; $x > $previous; $x--) {
                    if ($tokens[$x]['line'] === $tokens[$previous]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($x, '');
                }

                $phpcsFile->fixer->addNewlineBefore($stackPtr);
                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}

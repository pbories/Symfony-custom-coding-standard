<?php

namespace SymfonyCustom\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Throws warnings if properties are declared after methods
 */
class PropertyDeclarationSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_CLASS,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token
     *                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $end = null;
        if (isset($tokens[$stackPtr]['scope_closer'])) {
            $end = $tokens[$stackPtr]['scope_closer'];
        }

        $scope = $phpcsFile->findNext(
            T_FUNCTION,
            $stackPtr,
            $end
        );

        $wantedTokens = [
            T_PUBLIC,
            T_PROTECTED,
            T_PRIVATE,
        ];

        while ($scope) {
            $scope = $phpcsFile->findNext(
                $wantedTokens,
                $scope + 1,
                $end
            );

            if ($scope && T_VARIABLE === $tokens[$scope + 2]['code']) {
                $phpcsFile->addError(
                    'Declare class properties before methods',
                    $scope,
                    'Invalid'
                );
            }
        }
    }
}

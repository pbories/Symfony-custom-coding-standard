<?php

namespace SymfonyCustom\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the OpenBracketSpacing sniff.
 *
 * @group SymfonyCustom
 */
class OpenBracketSpacingUnitTest extends AbstractSniffUnitTest
{
    /**
     * Returns the lines where errors should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of errors that should occur on that line.
     *
     * @return array<int, int>
     */
    public function getErrorList()
    {
        return [
            3 => 1,
            5 => 2,
            7 => 1,
            9 => 1,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of warnings that should occur on that line.
     *
     * @return array(int => int)
     */
    protected function getWarningList()
    {
        return [];
    }
}

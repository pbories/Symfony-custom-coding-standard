<?php

namespace SymfonyCustom\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * A test to ensure that arrays conform to the array coding standard.
 */
class ArrayDeclarationSniff implements Sniff
{
    /**
     * The number of spaces code should be indented.
     *
     * @var int
     */
    public $indent = 4;

    /**
     * Allow or disallow a multiline assignment
     * By default, it is allowed to avoid conflict with a maximum line length rule
     *
     * [
     *     'key' =>
     *         'value'
     * ]
     *
     * @var bool
     */
    public $ignoreNewLines = true;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_ARRAY,
            T_OPEN_SHORT_ARRAY,
        ];
    }

    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The current file being checked.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (T_ARRAY === $tokens[$stackPtr]['code']) {
            $phpcsFile->recordMetric($stackPtr, 'Short array syntax used', 'no');

            // Array keyword should be lower case.
            if (strtolower($tokens[$stackPtr]['content']) !== $tokens[$stackPtr]['content']) {
                if (strtoupper($tokens[$stackPtr]['content']) === $tokens[$stackPtr]['content']) {
                    $phpcsFile->recordMetric($stackPtr, 'Array keyword case', 'upper');
                } else {
                    $phpcsFile->recordMetric($stackPtr, 'Array keyword case', 'mixed');
                }

                $error = 'Array keyword should be lower case; expected "array" but found "%s"';
                $data  = [$tokens[$stackPtr]['content']];
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NotLowerCase', $data);

                if (true === $fix) {
                    $phpcsFile->fixer->replaceToken($stackPtr, 'array');
                }
            } else {
                $phpcsFile->recordMetric($stackPtr, 'Array keyword case', 'lower');
            }

            $arrayStart = $tokens[$stackPtr]['parenthesis_opener'];
            if (isset($tokens[$arrayStart]['parenthesis_closer']) === false) {
                return;
            }

            $arrayEnd = $tokens[$arrayStart]['parenthesis_closer'];

            if (($stackPtr + 1) !== $arrayStart) {
                $error = 'There must be no space between the "array" keyword and the opening parenthesis';
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterKeyword');

                if (true === $fix) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($stackPtr + 1); $i < $arrayStart; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->endChangeset();
                }
            }
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Short array syntax used', 'yes');
            $arrayStart = $stackPtr;
            $arrayEnd   = $tokens[$stackPtr]['bracket_closer'];
        }

        // Check for empty arrays.
        $content = $phpcsFile->findNext(T_WHITESPACE, ($arrayStart + 1), ($arrayEnd + 1), true);
        if ($content === $arrayEnd) {
            // Empty array, but if the brackets aren't together, there's a problem.
            if (($arrayEnd - $arrayStart) !== 1) {
                $error = 'Empty array declaration must have no space between the parentheses';
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceInEmptyArray');

                if (true === $fix) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($arrayStart + 1); $i < $arrayEnd; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->endChangeset();
                }
            }

            // We can return here because there is nothing else to check.
            // All code below can assume that the array is not empty.
            return;
        }

        if ($tokens[$arrayStart]['line'] === $tokens[$arrayEnd]['line']) {
            $this->processSingleLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd);
        } else {
            $this->processMultiLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd);
        }
    }

    /**
     * Processes a single-line array definition.
     *
     * @param File $phpcsFile  The current file being checked.
     * @param int  $stackPtr   The position of the current token in the stack passed in $tokens.
     * @param int  $arrayStart The token that starts the array definition.
     * @param int  $arrayEnd   The token that ends the array definition.
     *
     * @return void
     */
    public function processSingleLineArray(File $phpcsFile, $stackPtr, $arrayStart, $arrayEnd)
    {
        $tokens = $phpcsFile->getTokens();

        // Check if there are multiple values. If so, then it has to be multiple lines
        // unless it is contained inside a function call or condition.
        $valueCount = 0;
        $commas     = [];
        for ($i = ($arrayStart + 1); $i < $arrayEnd; $i++) {
            // Skip bracketed statements, like function calls.
            if (T_OPEN_PARENTHESIS === $tokens[$i]['code']) {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }

            if (T_COMMA === $tokens[$i]['code']) {
                // Before counting this comma, make sure we are not at the end of the array.
                $next = $phpcsFile->findNext(T_WHITESPACE, ($i + 1), $arrayEnd, true);
                if (false !== $next) {
                    $valueCount++;
                    $commas[] = $i;
                } else {
                    // There is a comma at the end of a single line array.
                    $error = 'Comma not allowed after last value in single-line array declaration';
                    $fix   = $phpcsFile->addFixableError($error, $i, 'CommaAfterLast');
                    if (true === $fix) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                }
            }
        }

        // Now check each of the double arrows (if any).
        $nextArrow = $arrayStart;
        while (($nextArrow = $phpcsFile->findNext(T_DOUBLE_ARROW, ($nextArrow + 1), $arrayEnd)) !== false) {
            if (T_WHITESPACE !== $tokens[($nextArrow - 1)]['code']) {
                $content = $tokens[($nextArrow - 1)]['content'];
                $error   = 'Expected 1 space between "%s" and double arrow; 0 found';
                $data    = [$content];
                $fix     = $phpcsFile->addFixableError($error, $nextArrow, 'NoSpaceBeforeDoubleArrow', $data);
                if (true === $fix) {
                    $phpcsFile->fixer->addContentBefore($nextArrow, ' ');
                }
            } else {
                $spaceLength = $tokens[($nextArrow - 1)]['length'];
                if (1 !== $spaceLength) {
                    $content = $tokens[($nextArrow - 2)]['content'];
                    $error   = 'Expected 1 space between "%s" and double arrow; %s found';
                    $data    = [$content, $spaceLength];

                    $fix = $phpcsFile->addFixableError($error, $nextArrow, 'SpaceBeforeDoubleArrow', $data);
                    if (true === $fix) {
                        $phpcsFile->fixer->replaceToken(($nextArrow - 1), ' ');
                    }
                }
            }

            if (T_WHITESPACE !== $tokens[($nextArrow + 1)]['code']) {
                $content = $tokens[($nextArrow + 1)]['content'];
                $error   = 'Expected 1 space between double arrow and "%s"; 0 found';
                $data    = [$content];

                $fix     = $phpcsFile->addFixableError($error, $nextArrow, 'NoSpaceAfterDoubleArrow', $data);
                if (true === $fix) {
                    $phpcsFile->fixer->addContent($nextArrow, ' ');
                }
            } else {
                $spaceLength = $tokens[($nextArrow + 1)]['length'];
                if (1 !== $spaceLength) {
                    $content = $tokens[($nextArrow + 2)]['content'];
                    $error   = 'Expected 1 space between double arrow and "%s"; %s found';
                    $data    = [$content, $spaceLength];

                    $fix = $phpcsFile->addFixableError($error, $nextArrow, 'SpaceAfterDoubleArrow', $data);
                    if (true === $fix) {
                        $phpcsFile->fixer->replaceToken(($nextArrow + 1), ' ');
                    }
                }
            }
        }

        if ($valueCount > 0) {
            // We have a multiple value array
            foreach ($commas as $comma) {
                if (T_WHITESPACE !== $tokens[($comma + 1)]['code']) {
                    $content = $tokens[($comma + 1)]['content'];
                    $error = 'Expected 1 space between comma and "%s"; 0 found';
                    $data = [$content];

                    $fix = $phpcsFile->addFixableError($error, $comma, 'NoSpaceAfterComma', $data);
                    if (true === $fix) {
                        $phpcsFile->fixer->addContent($comma, ' ');
                    }
                } else {
                    $spaceLength = $tokens[($comma + 1)]['length'];
                    if (1 !== $spaceLength) {
                        $content = $tokens[($comma + 2)]['content'];
                        $error = 'Expected 1 space between comma and "%s"; %s found';
                        $data = [$content, $spaceLength];

                        $fix = $phpcsFile->addFixableError($error, $comma, 'SpaceAfterComma', $data);
                        if (true === $fix) {
                            $phpcsFile->fixer->replaceToken(($comma + 1), ' ');
                        }
                    }
                }

                if (T_WHITESPACE === $tokens[($comma - 1)]['code']) {
                    $content = $tokens[($comma - 2)]['content'];
                    $spaceLength = $tokens[($comma - 1)]['length'];
                    $error = 'Expected 0 spaces between "%s" and comma; %s found';
                    $data = [$content, $spaceLength];

                    $fix = $phpcsFile->addFixableError($error, $comma, 'SpaceBeforeComma', $data);
                    if (true === $fix) {
                        $phpcsFile->fixer->replaceToken(($comma - 1), '');
                    }
                }
            }
        }
    }

    /**
     * Processes a multi-line array definition.
     *
     * @param File $phpcsFile  The current file being checked.
     * @param int  $stackPtr   The position of the current token in the stack passed in $tokens.
     * @param int  $arrayStart The token that starts the array definition.
     * @param int  $arrayEnd   The token that ends the array definition.
     *
     * @return void
     */
    public function processMultiLineArray(File $phpcsFile, $stackPtr, $arrayStart, $arrayEnd)
    {
        $tokens = $phpcsFile->getTokens();

        $indent = $phpcsFile->findFirstOnLine(T_WHITESPACE, $arrayStart);
        if (false === $indent || $tokens[$indent]['column'] > 1) {
            $currentIndent = 0;
        } else {
            $currentIndent = mb_strlen($tokens[$indent]['content']);
        }

        // Check the closing bracket is on a new line.
        $lastContent = $phpcsFile->findPrevious(T_WHITESPACE, ($arrayEnd - 1), $arrayStart, true);
        if ($tokens[$lastContent]['line'] === $tokens[$arrayEnd]['line']) {
            $error = 'Closing parenthesis of array declaration must be on a new line';
            $fix   = $phpcsFile->addFixableError($error, $arrayEnd, 'CloseBraceNewLine');
            if (true === $fix) {
                $phpcsFile->fixer->addNewlineBefore($arrayEnd);
            }
        } elseif ($tokens[$arrayEnd]['column'] !== $currentIndent + 1) {
            // Check the closing bracket is lined up under the "a" in array.
            $expected = ($currentIndent);
            $found    = ($tokens[$arrayEnd]['column'] - 1);
            $error    = 'Closing parenthesis not aligned correctly; expected %s space(s) but found %s';
            $data     = [$expected, $found];

            $fix = $phpcsFile->addFixableError($error, $arrayEnd, 'CloseBraceNotAligned', $data);
            if (true === $fix) {
                if (0 === $found) {
                    $phpcsFile->fixer->addContent(($arrayEnd - 1), str_repeat(' ', $expected));
                } else {
                    $phpcsFile->fixer->replaceToken(($arrayEnd - 1), str_repeat(' ', $expected));
                }
            }
        }

        $keyUsed    = false;
        $singleUsed = false;
        $indices    = [];
        $maxLength  = 0;

        if (T_ARRAY === $tokens[$stackPtr]['code']) {
            $lastToken = $tokens[$stackPtr]['parenthesis_opener'];
        } else {
            $lastToken = $stackPtr;
        }

        // Find all the double arrows that reside in this scope.
        for ($nextToken = ($stackPtr + 1); $nextToken < $arrayEnd; $nextToken++) {
            // Skip array or function calls
            switch ($tokens[$nextToken]['code']) {
                case T_ARRAY:
                    $nextToken = $tokens[$tokens[$nextToken]['parenthesis_opener']]['parenthesis_closer'];
                    continue 2;
                case T_OPEN_SHORT_ARRAY:
                    $nextToken = $tokens[$nextToken]['bracket_closer'];
                    continue 2;
                case T_CLOSURE:
                    $nextToken = $tokens[$nextToken]['scope_closer'];
                    continue 2;
                case T_OPEN_PARENTHESIS:
                    if (false === isset($tokens[$nextToken]['parenthesis_owner'])
                        || $tokens[$nextToken]['parenthesis_owner'] !== $stackPtr
                    ) {
                        $nextToken = $tokens[$nextToken]['parenthesis_closer'];
                        continue 2;
                    }
                    break;
            }

            if (!in_array($tokens[$nextToken]['code'], [T_DOUBLE_ARROW, T_COMMA])
                && $nextToken !== $arrayEnd - 1
            ) {
                continue;
            }

            $currentEntry = [];

            if (T_COMMA === $tokens[$nextToken]['code'] || $nextToken === $arrayEnd - 1) {
                $stackPtrCount = 0;
                if (true === isset($tokens[$stackPtr]['nested_parenthesis'])) {
                    $stackPtrCount = count($tokens[$stackPtr]['nested_parenthesis']);
                }

                $commaCount = 0;
                if (true === isset($tokens[$nextToken]['nested_parenthesis'])) {
                    $commaCount = count($tokens[$nextToken]['nested_parenthesis']);
                    if (T_ARRAY === $tokens[$stackPtr]['code']) {
                        // Remove parenthesis that are used to define the array.
                        $commaCount--;
                    }
                }

                if ($commaCount > $stackPtrCount) {
                    // This comma is inside more parenthesis than the ARRAY keyword,
                    // then there it is actually a comma used to separate arguments
                    // in a function call.
                    continue;
                }

                $valueContent = $phpcsFile->findNext(
                    Tokens::$emptyTokens,
                    ($lastToken + 1),
                    $nextToken,
                    true
                );

                if (false !== $valueContent && T_DOUBLE_ARROW !== $tokens[$lastToken]['code']) {
                    if (true === $keyUsed) {
                        $error = 'No key specified for array entry; first entry specifies key';
                        $phpcsFile->addError($error, $nextToken, 'NoKeySpecified');
                    } else {
                        $singleUsed = true;
                    }

                    $indices[] = ['value' => $valueContent];
                }

                if (T_COMMA === $tokens[$nextToken]['code']
                    && T_WHITESPACE === $tokens[($nextToken - 1)]['code']
                ) {
                    $content = $tokens[($nextToken - 2)]['content'];
                    if ($tokens[($nextToken - 1)]['content'] === $phpcsFile->eolChar) {
                        $spaceLength = 'newline';
                    } else {
                        $spaceLength = $tokens[($nextToken - 1)]['length'];
                    }

                    $error = 'Expected 0 spaces between "%s" and comma; %s found';
                    $data  = [$content, $spaceLength];

                    $fix = $phpcsFile->addFixableError($error, $nextToken, 'SpaceBeforeComma', $data);
                    if (true === $fix) {
                        $phpcsFile->fixer->replaceToken(($nextToken - 1), '');
                    }
                }

                $lastToken = $nextToken;
                continue;
            }

            if (T_DOUBLE_ARROW === $tokens[$nextToken]['code']) {
                if (true === $singleUsed) {
                    $error = 'Key specified for array entry; first entry has no key';
                    $phpcsFile->addError($error, $nextToken, 'KeySpecified');
                } else {
                    $keyUsed = true;
                }

                $currentEntry['arrow'] = $nextToken;

                // Find the start of index that uses this double arrow.
                $indexEnd   = $phpcsFile->findPrevious(T_WHITESPACE, ($nextToken - 1), $arrayStart, true);
                $indexStart = $phpcsFile->findStartOfStatement($indexEnd);

                if ($indexStart === $indexEnd) {
                    $currentEntry['index']         = $indexEnd;
                    $currentEntry['index_content'] = $tokens[$indexEnd]['content'];
                } else {
                    $currentEntry['index']         = $indexStart;
                    $currentEntry['index_content'] = $phpcsFile->getTokensAsString(
                        $indexStart,
                        ($indexEnd - $indexStart + 1)
                    );
                }

                $indexLength = mb_strlen($currentEntry['index_content']);
                if ($maxLength < $indexLength) {
                    $maxLength = $indexLength;
                }

                // Find the value of this index.
                $nextContent = $phpcsFile->findNext(
                    Tokens::$emptyTokens,
                    ($nextToken + 1),
                    $arrayEnd,
                    true
                );

                $currentEntry['value'] = $nextContent;
                $indices[] = $currentEntry;
                $lastToken = $nextToken;
            }
        }

        if (false === empty($indices)) {
            $count     = count($indices);
            $lastIndex = $indices[($count - 1)]['value'];

            $trailingContent = $phpcsFile->findPrevious(
                Tokens::$emptyTokens,
                ($arrayEnd - 1),
                $lastIndex,
                true
            );

            if (T_COMMA !== $tokens[$trailingContent]['code']) {
                $phpcsFile->recordMetric($stackPtr, 'Array end comma', 'no');
                $error = 'Comma required after last value in array declaration';
                $fix   = $phpcsFile->addFixableError($error, $trailingContent, 'NoCommaAfterLast');
                if (true === $fix) {
                    $phpcsFile->fixer->addContent($trailingContent, ',');
                }
            } else {
                $phpcsFile->recordMetric($stackPtr, 'Array end comma', 'yes');
            }

            $lastValueLine = $stackPtr;
            foreach ($indices as $value) {
                if (false === empty($value['arrow'])) {
                    // Array value with arrow are checked later cause there is more checks.
                    continue;
                }

                if (true === empty($value['value'])) {
                    // Array was malformed, so we have to ignore it.
                    // Other parts of this sniff will correct the error.
                    continue;
                }

                $lastValue = $phpcsFile->findPrevious(T_COMMA, $value['value'] - 1, $lastValueLine);
                $lastValueLine = $lastValue ? $tokens[$lastValue]['line'] : false;

                if (false !== $lastValueLine && $tokens[$value['value']]['line'] === $lastValueLine) {
                    $error = 'Each value in a multi-line array must be on a new line';
                    $fix   = $phpcsFile->addFixableError($error, $value['value'], 'ValueNoNewline');
                    if (true === $fix) {
                        if (T_WHITESPACE === $tokens[($value['value'] - 1)]['code']) {
                            $phpcsFile->fixer->replaceToken(($value['value'] - 1), '');
                        }

                        $phpcsFile->fixer->addNewlineBefore($value['value']);
                    }
                } elseif (T_WHITESPACE === $tokens[($value['value'] - 1)]['code']) {
                    $expected = $currentIndent + $this->indent;

                    $first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $value['value'], true);
                    $found = ($tokens[$first]['column'] - 1);
                    if ($found !== $expected) {
                        $error = 'Array value not aligned correctly; expected %s spaces but found %s';
                        $data  = [$expected, $found];

                        $fix = $phpcsFile->addFixableError($error, $value['value'], 'ValueNotAligned', $data);
                        if (true === $fix) {
                            if (0 === $found) {
                                $phpcsFile->fixer->addContent(($value['value'] - 1), str_repeat(' ', $expected));
                            } else {
                                $phpcsFile->fixer->replaceToken(($value['value'] - 1), str_repeat(' ', $expected));
                            }
                        }
                    }
                }
            }
        }

        /*
            Below the actual indentation of the array is checked.
            Errors will be thrown when a key is not aligned, when
            a double arrow is not aligned, and when a value is not
            aligned correctly.
            If an error is found in one of the above areas, then errors
            are not reported for the rest of the line to avoid reporting
            spaces and columns incorrectly. Often fixing the first
            problem will fix the other 2 anyway.

            For example:

            $a = array(
                  'index'  => '2',
                 );

            or

            $a = [
                  'index'  => '2',
                 ];

            In this array, the double arrow is indented too far, but this
            will also cause an error in the value's alignment. If the arrow were
            to be moved back one space however, then both errors would be fixed.
        */
        $numValues = count($indices);

        $indicesStart  = ($currentIndent + $this->indent + 1);
        $arrowStart    = ($indicesStart + $maxLength + 1);
        $valueStart    = ($arrowStart + 3);
        $lastIndexLine = $tokens[$stackPtr]['line'];
        foreach ($indices as $index) {
            if (isset($index['index']) === false) {
                // Array value only.
                if ($tokens[$index['value']]['line'] === $tokens[$stackPtr]['line'] && $numValues > 1) {
                    $error = 'The first value in a multi-value array must be on a new line';
                    $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'FirstValueNoNewline');
                    if (true === $fix) {
                        $phpcsFile->fixer->addNewlineBefore($index['value']);
                    }
                }

                continue;
            }

            $lastIndex = $phpcsFile->findPrevious(T_COMMA, $index['index'] - 1, $lastIndexLine);
            $lastIndexLine = $lastIndex ? $tokens[$lastIndex]['line'] : false;
            $indexLine     = $tokens[$index['index']]['line'];

            if ($indexLine === $tokens[$stackPtr]['line']) {
                $error = 'The first index in a multi-value array must be on a new line';
                $fix   = $phpcsFile->addFixableError($error, $index['index'], 'FirstIndexNoNewline');
                if (true === $fix) {
                    $phpcsFile->fixer->addNewlineBefore($index['index']);
                }

                continue;
            }

            if ($indexLine === $lastIndexLine) {
                $error = 'Each index in a multi-line array must be on a new line';
                $fix   = $phpcsFile->addFixableError($error, $index['index'], 'IndexNoNewline');
                if (true === $fix) {
                    if (T_WHITESPACE === $tokens[($index['index'] - 1)]['code']) {
                        $phpcsFile->fixer->replaceToken(($index['index'] - 1), '');
                    }

                    $phpcsFile->fixer->addNewlineBefore($index['index']);
                }

                continue;
            }

            if ($indicesStart !== $tokens[$index['index']]['column']) {
                $expected = ($indicesStart - 1);
                $found    = ($tokens[$index['index']]['column'] - 1);
                $error    = 'Array key not aligned correctly; expected %s spaces but found %s';
                $data     = [$expected, $found];

                $fix = $phpcsFile->addFixableError($error, $index['index'], 'KeyNotAligned', $data);

                if (true === $fix) {
                    if (0 === $found) {
                        $phpcsFile->fixer->addContent(($index['index'] - 1), str_repeat(' ', $expected));
                    } else {
                        $phpcsFile->fixer->replaceToken(($index['index'] - 1), str_repeat(' ', $expected));
                    }
                }

                continue;
            }

            if ($tokens[$index['arrow']]['column'] !== $arrowStart) {
                $expected = ($arrowStart
                    - (mb_strlen($index['index_content']) + $tokens[$index['index']]['column']));
                $found    = $tokens[$index['arrow']]['column']
                    - (mb_strlen($index['index_content']) + $tokens[$index['index']]['column']);

                if ($found < 0) {
                    $found = 'newline';
                }

                $error = 'Array double arrow not aligned correctly; expected %s space(s) but found %s';
                $data  = [$expected, $found];

                if ('newline' !== $found || false === $this->ignoreNewLines) {
                    $fix = $phpcsFile->addFixableError($error, $index['arrow'], 'DoubleArrowNotAligned', $data);
                    if (true === $fix) {
                        if ('newline' === $found) {
                            $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($index['value'] - 1), null, true);
                            $phpcsFile->fixer->beginChangeset();
                            for ($i = ($prev + 1); $i < $index['value']; $i++) {
                                $phpcsFile->fixer->replaceToken($i, '');
                            }

                            $phpcsFile->fixer->replaceToken(($index['value'] - 1), str_repeat(' ', $expected));
                            $phpcsFile->fixer->endChangeset();
                        } elseif (0 === $found) {
                            $phpcsFile->fixer->addContent(($index['arrow'] - 1), str_repeat(' ', $expected));
                        } else {
                            $phpcsFile->fixer->replaceToken(($index['arrow'] - 1), str_repeat(' ', $expected));
                        }
                    }
                }

                continue;
            }

            if ($tokens[$index['value']]['column'] !== $valueStart) {
                $expected = ($valueStart - ($tokens[$index['arrow']]['length'] + $tokens[$index['arrow']]['column']));
                $found    = $tokens[$index['value']]['column']
                    - ($tokens[$index['arrow']]['length'] + $tokens[$index['arrow']]['column']);

                if ($found < 0) {
                    $found = 'newline';
                }

                if ('newline' !== $found || false === $this->ignoreNewLines) {
                    $error = 'Array value not aligned correctly; expected %s space(s) but found %s';
                    $data  = [$expected, $found];

                    $fix = $phpcsFile->addFixableError($error, $index['arrow'], 'ValueNotAligned', $data);
                    if (true === $fix) {
                        if ('newline' === $found) {
                            $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($index['value'] - 1), null, true);
                            $phpcsFile->fixer->beginChangeset();
                            for ($i = ($prev + 1); $i < $index['value']; $i++) {
                                $phpcsFile->fixer->replaceToken($i, '');
                            }

                            $phpcsFile->fixer->replaceToken(($index['value'] - 1), str_repeat(' ', $expected));
                            $phpcsFile->fixer->endChangeset();
                        } elseif (0 === $found) {
                            $phpcsFile->fixer->addContent(($index['value'] - 1), str_repeat(' ', $expected));
                        } else {
                            $phpcsFile->fixer->replaceToken(($index['value'] - 1), str_repeat(' ', $expected));
                        }
                    }
                }
            }
        }
    }
}

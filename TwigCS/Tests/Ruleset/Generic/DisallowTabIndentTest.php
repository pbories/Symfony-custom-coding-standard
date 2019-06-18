<?php

namespace TwigCS\Tests\Ruleset\Generic;

use TwigCS\Ruleset\Generic\DisallowCommentedCodeSniff;
use TwigCS\Ruleset\Generic\DisallowTabIndentSniff;
use TwigCS\Tests\AbstractSniffTest;

/**
 * Class DisallowTabIndentTest
 */
class DisallowTabIndentTest extends AbstractSniffTest
{
    public function testSniff()
    {
        $this->checkGenericSniff(new DisallowTabIndentSniff(), [
            [2, 1],
        ]);
    }
}

<?php

namespace TwigCS\Ruleset\Generic;

use \Exception;
use TwigCS\Sniff\AbstractSniff;
use TwigCS\Token\Token;

/**
 * Disallow the use of tabs to indent code.
 */
class DisallowTabIndentSniff extends AbstractSniff
{
    /**
     * @param int     $tokenPosition
     * @param Token[] $tokens
     *
     * @return Token
     *
     * @throws Exception
     */
    public function process(int $tokenPosition, array $tokens)
    {
        $token = $tokens[$tokenPosition];

        if ($this->isTokenMatching($token, Token::TAB_TYPE)) {
            $fix = $this->addFixableMessage(
                $this::MESSAGE_TYPE_ERROR,
                'Indentation using tabs is not allowed; use spaces instead',
                $token
            );

            if ($fix) {
                $this->fixer->replaceToken($tokenPosition, '    ');
            }
        }

        return $token;
    }
}

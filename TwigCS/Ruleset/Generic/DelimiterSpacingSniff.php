<?php

namespace TwigCS\Ruleset\Generic;

use Exception;
use TwigCS\Sniff\AbstractSniff;
use TwigCS\Token\Token;

/**
 * Ensure there is one space before and after a delimiter {{, {%, {#, }}, %} and #}
 */
class DelimiterSpacingSniff extends AbstractSniff
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

        if ($this->isTokenMatching($token, Token::VAR_START_TYPE)
            || $this->isTokenMatching($token, Token::BLOCK_START_TYPE)
            || $this->isTokenMatching($token, Token::COMMENT_START_TYPE)
        ) {
            $this->processStart($tokenPosition, $tokens);
        }

        if ($this->isTokenMatching($token, Token::VAR_END_TYPE)
            || $this->isTokenMatching($token, Token::BLOCK_END_TYPE)
            || $this->isTokenMatching($token, Token::COMMENT_END_TYPE)
        ) {
            $this->processEnd($tokenPosition, $tokens);
        }

        return $token;
    }

    /**
     * @param int     $tokenPosition
     * @param Token[] $tokens
     *
     * @throws Exception
     */
    public function processStart(int $tokenPosition, array $tokens)
    {
        $token = $tokens[$tokenPosition];

        // Ignore new line
        $next = $this->findNext(Token::WHITESPACE_TYPE, $tokens, $tokenPosition + 1, true);
        if ($this->isTokenMatching($tokens[$next], Token::EOL_TYPE)) {
            return;
        }

        if ($this->isTokenMatching($tokens[$tokenPosition + 1], Token::WHITESPACE_TYPE)) {
            $count = strlen($tokens[$tokenPosition + 1]->getValue());
        } else {
            $count = 0;
        }

        if (1 !== $count) {
            $fix = $this->addFixableMessage(
                $this::MESSAGE_TYPE_ERROR,
                sprintf('Expecting 1 whitespace after "%s"; found %d', $token->getValue(), $count),
                $token
            );

            if ($fix) {
                if (0 === $count) {
                    $this->fixer->addContent($tokenPosition, ' ');
                } else {
                    $this->fixer->replaceToken($tokenPosition + 1, ' ');
                }
            }
        }
    }

    /**
     * @param int     $tokenPosition
     * @param Token[] $tokens
     *
     * @throws Exception
     */
    public function processEnd(int $tokenPosition, array $tokens)
    {
        $token = $tokens[$tokenPosition];

        // Ignore new line
        $previous = $this->findPrevious(Token::WHITESPACE_TYPE, $tokens, $tokenPosition - 1, true);
        if ($this->isTokenMatching($tokens[$previous], Token::EOL_TYPE)) {
            return;
        }

        if ($this->isTokenMatching($tokens[$tokenPosition - 1], Token::WHITESPACE_TYPE)) {
            $count = strlen($tokens[$tokenPosition - 1]->getValue());
        } else {
            $count = 0;
        }

        if (1 !== $count) {
            $fix = $this->addFixableMessage(
                $this::MESSAGE_TYPE_ERROR,
                sprintf('Expecting 1 whitespace before "%s"; found %d', $token->getValue(), $count),
                $token
            );

            if ($fix) {
                if (0 === $count) {
                    $this->fixer->addContentBefore($tokenPosition, ' ');
                } else {
                    $this->fixer->replaceToken($tokenPosition - 1, ' ');
                }
            }
        }
    }
}

<?php

namespace TwigCS\Sniff;

use \Exception;
use TwigCS\Report\Report;
use TwigCS\Token\Token;

/**
 * Interface for all sniffs.
 */
interface SniffInterface
{
    const MESSAGE_TYPE_NOTICE  = 0;
    const MESSAGE_TYPE_WARNING = 1;
    const MESSAGE_TYPE_ERROR   = 2;

    /**
     * Enable the sniff.
     *
     * Once the sniff is enabled, it will be registered and executed when a template is tokenized or parsed.
     * Messages will be added to the given `$report` object.
     *
     * @param Report $report
     *
     * @return self
     */
    public function enable(Report $report);

    /**
     * Disable the sniff.
     *
     * It usually is disabled when the processing is over, it will reset the sniff internal values for next check.
     *
     * @return self
     */
    public function disable();

    /**
     * Get the current report.
     *
     * @return Report
     *
     * @throws Exception A disabled sniff has no current report.
     */
    public function getReport();

    /**
     * @param Token   $token
     * @param int     $tokenPosition
     * @param Token[] $stream
     */
    public function process(Token $token, $tokenPosition, $stream);
}

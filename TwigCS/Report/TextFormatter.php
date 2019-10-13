<?php

namespace TwigCS\Report;

use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Human readable output with context.
 */
class TextFormatter
{
    public const ERROR_CURSOR_CHAR   = '>>';
    public const ERROR_LINE_FORMAT   = '%-5s| %s';
    public const ERROR_CONTEXT_LIMIT = 2;
    public const ERROR_LINE_WIDTH    = 120;

    /**
     * Input-output helper object.
     *
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param Report      $report
     * @param string|null $level
     */
    public function display(Report $report, string $level = null)
    {
        foreach ($report->getFiles() as $file) {
            $fileMessages = $report->getMessages([
                'file'  => $file,
                'level' => $level,
            ]);

            if (count($fileMessages) > 0) {
                $this->io->text('<fg=red>KO</fg=red> '.$file);
            }

            $rows = [];
            foreach ($fileMessages as $message) {
                $lines = $this->getContext(file_get_contents($file), $message->getLine(), $this::ERROR_CONTEXT_LIMIT);

                $formattedText = [];
                if (!$message->getLine()) {
                    $formattedText[] = $this->formatErrorMessage($message);
                }

                foreach ($lines as $no => $code) {
                    $formattedText[] = sprintf($this::ERROR_LINE_FORMAT, $no, wordwrap($code, $this::ERROR_LINE_WIDTH));

                    if ($no === $message->getLine()) {
                        $formattedText[] = $this->formatErrorMessage($message);
                    }
                }

                if (count($rows) > 0) {
                    $rows[] = new TableSeparator();
                }

                $rows[] = [
                    new TableCell('<comment>'.$message->getLevelAsString().'</comment>'),
                    implode("\n", $formattedText),
                ];
            }

            if (count($rows) > 0) {
                $this->io->table([], $rows);
            }
        }

        $summaryString = sprintf(
            'Files linted: %d, notices: %d, warnings: %d, errors: %d',
            $report->getTotalFiles(),
            $report->getTotalNotices(),
            $report->getTotalWarnings(),
            $report->getTotalErrors()
        );

        if (0 === $report->getTotalWarnings() && 0 === $report->getTotalErrors()) {
            $this->io->block($summaryString, 'SUCCESS', 'fg=black;bg=green', ' ', true);
        } elseif (0 < $report->getTotalWarnings() && 0 === $report->getTotalErrors()) {
            $this->io->block($summaryString, 'WARNING', 'fg=black;bg=yellow', ' ', true);
        } else {
            $this->io->block($summaryString, 'ERROR', 'fg=black;bg=red', ' ', true);
        }
    }

    /**
     * @param string $template
     * @param int    $line
     * @param int    $context
     *
     * @return array
     */
    protected function getContext(string $template, int $line, int $context)
    {
        $lines = explode("\n", $template);

        $position = max(0, $line - $context);
        $max = min(count($lines), $line - 1 + $context);

        $result = [];
        $indentCount = null;
        while ($position < $max) {
            if (preg_match('/^([\s\t]+)/', $lines[$position], $match)) {
                if (null === $indentCount) {
                    $indentCount = strlen($match[1]);
                }

                if (strlen($match[1]) < $indentCount) {
                    $indentCount = strlen($match[1]);
                }
            } else {
                $indentCount = 0;
            }

            $result[$position + 1] = $lines[$position];
            $position++;
        }

        foreach ($result as $index => $code) {
            $result[$index] = substr($code, $indentCount);
        }

        return $result;
    }

    /**
     * @param SniffViolation $message
     *
     * @return string
     */
    protected function formatErrorMessage(SniffViolation $message)
    {
        return sprintf(
            '<fg=red>'.$this::ERROR_LINE_FORMAT.'</fg=red>',
            $this::ERROR_CURSOR_CHAR,
            wordwrap($message->getMessage(), $this::ERROR_LINE_WIDTH)
        );
    }
}

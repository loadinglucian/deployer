<?php

declare(strict_types=1);

namespace Deployer\Traits;

/**
 * Provides log content highlighting for error detection.
 */
trait LogHighlightingTrait
{
    /**
     * Highlight error keywords in log content.
     */
    protected function highlightErrors(string $content): string
    {
        $textKeywords = [
            'error',
            'exception',
            'fail',
            'failed',
            'fatal',
            'panic',
        ];

        $statusPattern = '/\b(500|502|503|504)\b/';

        $lines = explode("\n", $content);
        $processedLines = [];

        foreach ($lines as $line) {
            $lowerLine = strtolower($line);
            $hasError = false;

            foreach ($textKeywords as $keyword) {
                if (str_contains($lowerLine, $keyword)) {
                    $hasError = true;
                    break;
                }
            }

            if (!$hasError && preg_match($statusPattern, $line)) {
                $hasError = true;
            }

            if ($hasError) {
                $processedLines[] = "<fg=red>{$line}</>";
            } else {
                $processedLines[] = $line;
            }
        }

        return implode("\n", $processedLines);
    }
}

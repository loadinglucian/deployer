<?php

declare(strict_types=1);

namespace DeployerPHP\Builders;

use DeployerPHP\DTOs\CronDTO;

/**
 * Builder for CronDTO - centralizes all CronDTO instantiation.
 */
final class CronBuilder
{
    private string $script = '';
    private string $schedule = '';

    private function __construct()
    {
    }

    /**
     * Create a new CronBuilder for fresh creation.
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Create a CronBuilder from an existing CronDTO.
     */
    public static function from(CronDTO $dto): self
    {
        return (new self())
            ->script($dto->script)
            ->schedule($dto->schedule);
    }

    /**
     * Create a CronBuilder from storage data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromStorage(array $data): self
    {
        $script = $data['script'] ?? '';
        $schedule = $data['schedule'] ?? '';

        return (new self())
            ->script(is_string($script) ? $script : '')
            ->schedule(is_string($schedule) ? $schedule : '');
    }

    public function script(string $script): self
    {
        $this->script = $script;

        return $this;
    }

    public function schedule(string $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Build the CronDTO.
     *
     * @throws \RuntimeException If required fields are missing.
     */
    public function build(): CronDTO
    {
        if ('' === $this->script) {
            throw new \RuntimeException('CronDTO requires a script');
        }
        if ('' === $this->schedule) {
            throw new \RuntimeException('CronDTO requires a schedule');
        }

        return new CronDTO(
            script: $this->script,
            schedule: $this->schedule,
        );
    }
}

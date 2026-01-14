<?php

declare(strict_types=1);

namespace DeployerPHP\Builders;

use DeployerPHP\DTOs\SupervisorDTO;

/**
 * Builder for SupervisorDTO - centralizes all SupervisorDTO instantiation.
 */
final class SupervisorBuilder
{
    private string $program = '';
    private string $script = '';
    private bool $autostart = true;
    private bool $autorestart = true;
    private int $stopwaitsecs = 3600;
    private int $numprocs = 1;

    private function __construct()
    {
    }

    /**
     * Create a new SupervisorBuilder for fresh creation.
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Create a SupervisorBuilder from an existing SupervisorDTO.
     */
    public static function from(SupervisorDTO $dto): self
    {
        return (new self())
            ->program($dto->program)
            ->script($dto->script)
            ->autostart($dto->autostart)
            ->autorestart($dto->autorestart)
            ->stopwaitsecs($dto->stopwaitsecs)
            ->numprocs($dto->numprocs);
    }

    /**
     * Create a SupervisorBuilder from storage data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromStorage(array $data): self
    {
        $program = $data['program'] ?? '';
        $script = $data['script'] ?? '';
        $autostart = $data['autostart'] ?? true;
        $autorestart = $data['autorestart'] ?? true;
        $stopwaitsecs = $data['stopwaitsecs'] ?? 3600;
        $numprocs = $data['numprocs'] ?? 1;

        return (new self())
            ->program(is_string($program) ? $program : '')
            ->script(is_string($script) ? $script : '')
            ->autostart(is_bool($autostart) ? $autostart : true)
            ->autorestart(is_bool($autorestart) ? $autorestart : true)
            ->stopwaitsecs(is_int($stopwaitsecs) ? $stopwaitsecs : 3600)
            ->numprocs(is_int($numprocs) ? $numprocs : 1);
    }

    public function program(string $program): self
    {
        $this->program = $program;

        return $this;
    }

    public function script(string $script): self
    {
        $this->script = $script;

        return $this;
    }

    public function autostart(bool $autostart): self
    {
        $this->autostart = $autostart;

        return $this;
    }

    public function autorestart(bool $autorestart): self
    {
        $this->autorestart = $autorestart;

        return $this;
    }

    public function stopwaitsecs(int $stopwaitsecs): self
    {
        $this->stopwaitsecs = $stopwaitsecs;

        return $this;
    }

    public function numprocs(int $numprocs): self
    {
        $this->numprocs = $numprocs;

        return $this;
    }

    /**
     * Build the SupervisorDTO.
     *
     * @throws \RuntimeException If required fields are missing.
     */
    public function build(): SupervisorDTO
    {
        if ('' === $this->program) {
            throw new \RuntimeException('SupervisorDTO requires a program name');
        }
        if ('' === $this->script) {
            throw new \RuntimeException('SupervisorDTO requires a script');
        }

        return new SupervisorDTO(
            program: $this->program,
            script: $this->script,
            autostart: $this->autostart,
            autorestart: $this->autorestart,
            stopwaitsecs: $this->stopwaitsecs,
            numprocs: $this->numprocs,
        );
    }
}

<?php

namespace EdmondsCommerce\JiraShell\Runner;

class Args
{
    public const ARG_QUEUE_FILE_PATH = 1;
    public const ARG_ENV_FILE_PATH   = 2;

    public const ARGS = [
        self::ARG_QUEUE_FILE_PATH,
        self::ARG_ENV_FILE_PATH,
    ];

    private $queueFilePath;

    private $envFilePath;

    /**
     * Args constructor.
     *
     * @param array $argv
     * @param int   $argc
     */
    public function __construct(array $argv, int $argc)
    {
        if ($argc !== (\count(self::ARGS) + 1)) {
            throw new \RuntimeException(
                'Usage: command [queue file path] [env file path]'
            );
        }

        $this->queueFilePath = $argv[self::ARG_QUEUE_FILE_PATH];
        $this->envFilePath   = $argv[self::ARG_ENV_FILE_PATH];
    }

    public function getQueueFilePath(): string
    {
        return $this->queueFilePath;
    }

    public function getEnvFilePath(): string
    {
        return $this->envFilePath;
    }
}

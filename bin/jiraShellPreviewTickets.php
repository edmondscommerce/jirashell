#!/usr/bin/env php

<?php

/**
 * Shows a formatted preview of all the currently queued Jira tickets
 */

require __DIR__ . '/../vendor/autoload.php';

$args = new \EdmondsCommerce\JiraShell\Runner\Args($argv, $argc);

$jiraShell = new \EdmondsCommerce\JiraShell\JiraShell(
    $args->getQueueFilePath(),
    $args->getEnvFilePath()
);

$jiraShell->printQueue();
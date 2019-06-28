#!/usr/bin/env php

<?php

/**
 * Send all the currently queued Jira tickets
 */

require __DIR__ . '/../vendor/autoload.php';

echo '

Sending Jira Tickets

';

$args = new \EdmondsCommerce\JiraShell\Runner\Args($argv, $argc);

$jiraShell = new \EdmondsCommerce\JiraShell\JiraShell(
    $args->getQueueFilePath(),
    $args->getEnvFilePath()
);

$jiraShell->flushQueue();

echo '

All Tickets Sent

';
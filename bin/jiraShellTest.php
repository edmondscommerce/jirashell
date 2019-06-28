#!/usr/bin/env php

<?php

/**
 * This will test creating tickets - useful for making sure its configured correctly
 */

require __DIR__ . '/../vendor/autoload.php';

$args = new \EdmondsCommerce\JiraShell\Runner\Args($argv, $argc);

$jiraShell = new \EdmondsCommerce\JiraShell\JiraShell(
    $args->getQueueFilePath(),
    $args->getEnvFilePath()
);

echo '

Creating Test Ticket

';

$jiraShell->createIssue(
    'Testing Jira Shell Connectivity',
    'Testing Jira Shell Connectivity from ' . gethostname(),
    [
        [
            'Sub Task Title 1',
            'Sub Task Description 1',
        ],
        [
            'Sub Task Title 2',
            'Sub Task Description 2',
        ],
    ]
);

echo '

Tickets Created Successfully

';

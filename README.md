# JiraShell
## By [Edmonds Commerce](https://www.edmondscommerce.co.uk)

## Env

The first thing you need to setup is your `env` file. This can be placed anywhere on the system:

```text
readonly _jiraUrl=https://your.jira.url;
readonly _jiraUser=jira_user;
readonly _jiraPass=jira_pass;
readonly _jiraProject=jira_project;
```

## Queue

You can now queue a set of tickets for JiraShell to send. You can do this using:

```php
$jiraShell = new EdmondsCommerce\JiraShell\JiraShell(
    '/path/to/queue.json',
    '/path/to/env'
);

$jiraShell->queueIssue(
    'Title',
    'Description',
    [
        'Sub-task Title',
        'Sub-task Description'
    ]
);
```

## Preview

You can preview the tickets that you currently have queued using:

```bash
php jiraShellPreviewTickets.php '/path/to/queue.json' '/path/to/env'
```

## Test

You can send a test ticket to Jira to ensure you have things setup correctly using:

```bash
php jiraShellTest.php '/path/to/queue.json' '/path/to/env'
```

## Send

And finally you can send your tickets to Jira using:

```bash
php jiraShellSendTickets.php '/path/to/queue.json' '/path/to/env'
```
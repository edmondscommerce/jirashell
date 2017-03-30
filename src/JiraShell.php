<?php

namespace EdmondsCommerce\JiraShell;

use Exception;

class JiraShell
{
    private $jiraUrl = true;
    private $jiraUser = true;
    private $jiraPass = true;
    private $jiraProject = true;

    private $queuedIssues = [];

    private $queueFilePath;
    private $envFilePath;

    public function __construct($queueFilePath = null, $envFilePath = null )
    {
        $this->queueFilePath = ($queueFilePath === null) ?  __DIR__ . '/queue.json' : $queueFilePath;
        $this->envFilePath = ($envFilePath === null) ? __DIR__ . '/env' : $envFilePath;

        if (!file_exists($this->envFilePath)) {
            throw new Exception('env file not found at ' . $this->envFilePath);
        }
        $env = file($this->envFilePath);
        foreach ($env as $item) {
            preg_match('%.*_(.+?)\=(.+?);%', $item, $matches);
            $property = $matches[1];
            $value = $matches[2];
            if (!isset($this->$property)) {
                throw new Exception('invalid env parameter ' . $property);
            }
            $this->$property = $value;
        }
        if (!file_exists($this->queueFilePath)) {
            $data = json_encode($this->queuedIssues);
            file_put_contents($this->queueFilePath, $data);
        } else {
            $queue = file_get_contents($this->queueFilePath);
            $this->queuedIssues = json_decode($queue, true);
        }
    }

    public function __destruct()
    {
        if (!empty($this->queuedIssues)) {
            file_put_contents($this->queueFilePath, json_encode($this->queuedIssues, JSON_FORCE_OBJECT));
        }
    }

    protected function createIssueData($title, $description, $parent = null)
    {
        $type = ($parent) ? 'Sub-task' : 'Task';
        $data = [
            'fields' => [
                'project' => [
                    'key' => $this->jiraProject
                ],
                "summary" => "$title",
                "description" => "$description",
                "issuetype" => [
                    "name" => "$type"
                ]
            ]
        ];
        if ($parent) {
            $data['fields']['parent']['key'] = $parent;
        }
        return $data;
    }

    protected function jsonEncodeData(array $data)
    {
        return json_encode($data, JSON_FORCE_OBJECT);
    }

    public function queueIssue($title, $description, array $subtasks = [])
    {
        $issue = [$title, $description, $subtasks];
        $this->queuedIssues[] = $issue;
    }

    public function flushQueue()
    {
        $queue = json_decode(file_get_contents($this->queueFilePath), true);
        foreach ($queue as $issue) {
            list($title, $description, $subtasks) = $issue;
            $this->createIssue($title, $description, $subtasks);
        }
    }

    public function createIssue($title, $description, array $subtasks = [], $parent = null)
    {
        $data = $this->createIssueData($title, $description, $parent);
        $json = json_encode($data, JSON_FORCE_OBJECT);
        $result = $this->sendIssue($json);
        if ($subtasks) {
            foreach ($subtasks as $subtask) {
                list($subtaskTitle, $subtaskDescription) = $subtask;
                $this->createIssue($subtaskTitle, $subtaskDescription, [], $result->key);
            }
        }
        return $result;
    }


    public function sendQueue()
    {
        $queue = json_decode(file_get_contents($this->queueFilePath), true);
        foreach ($queue as $issue) {
            $this->sendIssue($this->jsonEncodeData($issue));
        }
    }

    protected function sendIssue($json)
    {
        $ch = curl_init(rtrim($this->jiraUrl, '/') . '/rest/api/2/issue/');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->jiraUser . ":" . $this->jiraPass);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $json = curl_exec($ch);
        $error_number = curl_errno($ch);
        if ($error_number > 0) {
            throw new Exception(
                sprintf('Jira request failed: code = %s, "%s"', $error_number, curl_error($ch))
            );
        }
        curl_close($ch);
        $obj = json_decode($json);
        if (isset($obj->errors)) {
            throw new Exception('Errors in Jira Respose: ' . print_r($obj));
        }
        return $obj;
    }

    /**
     * Prints the Jira Issue Queue to a sane format for preview purposes
     */
    public function printQueue()
    {
        if(!is_array($this->queuedIssues))
        {
            echo "No issues found".PHP_EOL;
            return;
        }

        echo 'Queued Jira Issues'.PHP_EOL;
        echo '#########################'.PHP_EOL;

        foreach($this->queuedIssues as $queuedIssue)
        {
            echo '-----------------------------------------------------------------------------------------------'.PHP_EOL;
            $this->printIssue($queuedIssue);
        }
    }

    public function printIssue(array $task)
    {
        echo 'Name: '.$task[0].PHP_EOL;
        echo 'Description: '.$task[1].PHP_EOL;
    }
}


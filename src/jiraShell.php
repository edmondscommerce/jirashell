<?php

class jiraShell
{
    private $jiraUrl = true;
    private $jiraUser = true;
    private $jiraPass = true;
    private $jiraProject = true;

    private $queuedIssues = [];

    const queueFilePath = __DIR__ . '/queue.json';
    const envFilePath = __DIR__ . '/env';

    public function __construct()
    {
        if (!file_exists(self::envFilePath)) {
            throw new Exception('env file not found at ' . self::envFilePath);
        }
        $env = file(self::envFilePath);
        foreach ($env as $item) {
            preg_match('%.*_(.+?)\=(.+?);%', $item, $matches);
            $property = $matches[1];
            $value = $matches[2];
            if (!isset($this->$property)) {
                throw new Exception('invalid env parameter ' . $property);
            }
            $this->$property = $value;
        }
        if (!file_exists(self::queueFilePath)) {
            $data = json_encode($this->queuedIssues);
            file_put_contents(self::queueFilePath, $data);
        } else {
            $queue = file_get_contents(self::queueFilePath);
            $this->queuedIssues = json_decode($queue, true);
        }
    }

    public function __destruct()
    {
        if (!empty($this->queuedIssues)) {
            file_put_contents(self::queueFilePath, json_encode($this->queuedIssues, JSON_FORCE_OBJECT));
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
        $queue = json_decode(file_get_contents(self::queueFilePath), true);
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
        $queue = json_decode(file_get_contents(self::queueFilePath), true);
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
}

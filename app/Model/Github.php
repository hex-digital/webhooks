<?php

namespace App\Model;

use DateTime;
use Illuminate\Http\Request;

class Github
{
    /**
     * Sends a notifiction to Slack to tell the developer the error
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return void
     */
    protected function sendNotification($message)
    {
        $token    = env('SLACK_TOKEN');
        $team     = env('SLACK_TEAM');
        $username = env('SLACK_USERNAME');
        $icon     = env('SLACK_GITHUB_ICON');
        $channel  = env('SLACK_GITHUB_CHANNEL');

        $slack = new Slack($token, $team, $username, $icon);
        $slack->send($message, $channel);
    }

    /**
     * Returns http headers depending on whether the deployment is allowed
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  boolean  $delivered
     * @return void
     */
    protected function returnDeliveryStatus($delivered)
    {
        if ($delivered) {
            header('HTTP/1.1 200 OK');
        } else {
            header('HTTP/1.1 404 Not Found');
        }

        exit;
    }

    /**
     * Returns the branch name from the GitHub payload
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  object  $payload The payload sent from GitHub
     * @return string|false  The name of the branch or false upon failure
     */
    protected function getBranchName($payload)
    {
        if (!$branch = $payload->get('ref')) {
            return false;
        }

        return substr($branch, strlen('refs/heads/'));
    }

    /**
     * Checks the branch name against the Git Flow naming conventions
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  object  $payload The payload sent from GitHub
     * @return boolean Whether the branch name is valid
     */
    protected function checkBranchName($payload)
    {
        if (!$branch = $this->getBranchName($payload)) return false;

        $failed = 0;

        $branches = [
            'master',
            'production',
            'staging',
            'development'
        ];

        $namedBranches = [
            'change',
            'feature',
            'hotfix',
            'release'
        ];

        if (!in_array($branch, $branches)) {

            $namedBranchCheck = 0;
            $branchIdentifierCheck = 0;

            // Check to see if the dash exists in the branch name
            if (strpos($branch, '-') === false) $failed++;

            // Get the branch parts and identifier
            $branchParts = explode('-', $branch);
            $branchIdentifier = end($branchParts);

            // Check if the branch ends in an integer
            if ((int) $branchIdentifier > 0) $branchIdentifierCheck++;

            // Check the first part of the branch matches naming conventions
            foreach ($namedBranches as $namedBranch) {
                if ($branchParts[0] == $namedBranch) $namedBranchCheck++;
            }

            if ($namedBranchCheck === 0) $failed++;
            if ($branchIdentifierCheck === 0) $failed++;

        }

        if ($failed > 0) {
            $message = sprintf(env('SLACK_GITHUB_BRANCHING_MESSAGE'), $branch);
            $this->sendNotification($message);

            return false;
        }

        return true;
    }

    /**
     * Checks modified files (ideally just the diff) for any conflict messages
     *
     * This helps prevent any production syntax errors or issues later on down
     * the line where a developer may have accidently committed conflict
     * messages after fixing a conflict locally.
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  object  $payload The payload sent from GitHub
     * @return boolean Whether files contained conflict messages or not
     */
    protected function checkForConflictMessages($payload)
    {
        if (!$branch = $this->getBranchName($payload)) return false;

        $failed = 0;
        $files = [];

        $commits = $payload->get('commits');

        foreach ($commits as $commit) {

            foreach ($commit['added'] as $added) {
                $files[] = $added;
            }

            foreach ($commit['modified'] as $modified) {
                $files[] = $modified;
            }

        }

        // Save files to memory

        // Check files for conflict messages

        if ($failed > 0) {
            $message = sprintf(env('SLACK_GITHUB_CONFLICT_MESSAGE'), $branch);
            $this->sendNotification($message);

            return false;
        }

        return true;
    }

    /**
     * Runs the relevant checks using the payload data
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  object  $payload The payload data sent from GitHub
     * @return boolean Whether all checks ran successfully or not
     */
    protected function runChecks($payload)
    {
        $failed = 0;

        // Check naming conventions for new branches
        if (!$this->checkBranchName($payload)) $failed++;

        // Check to see if the task exists in external task monitoring system
        // ...

        // Check for conflict messages in the changed files
        // if (!$this->checkForConflictMessages($payload)) $failed++;

        // Check for pull request merges into development only
        // ...

        // Check for normal merges into staging and production only
        // ...

        // Check commit messages start with a capital letter
        // ...

        return ($failed === 0);
    }

    /**
     * Listens for the GitHub hook
     *
     * This method is used to read the payload data sent from GitHub
     * after an event. Upon successful delivery, this will return a
     * HTTP 200 page, otherwise a 404 page upon failure. This tells
     * GitHub whether the payload could be delivered or not.
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  Request  $request
     * @return void
     */
    public function listen(Request $request)
    {
        $webhook = $request->route()[2]['hash'];
        $event = $request->header('X-GitHub-Event');

        if ($webhook == env('GITHUB_WEBHOOK_URL')
            && $event == 'push'
            && $request->isJson()) {
            $payload = $request->json();
            $this->returnDeliveryStatus($this->runChecks($payload));
        }
    }
}

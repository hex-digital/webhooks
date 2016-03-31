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
     * Checks the branch name against the Git Flow naming conventions
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  object  $payload The payload sent from GitHub
     * @return boolean Whether the branch name is valid
     */
    protected function checkBranchName($payload)
    {
        if (!$branch = $payload->get('ref')) return false;
        $branch = substr($branch, strlen('refs/heads/'));

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
            'hotfix'
        ];

        if (!in_array($branch, $branches)) {
            foreach ($namedBranches as $namedBranch) {
                if (strpos($branch, $namedBranch . '-') === false) $failed++;

                if (strpos('-', $branch) !== false) {
                    $branchIdentifier = explode('-', $branch);
                    $branchIdentifier = end($branchIdentifier);
                    if ((int) $branchIdentifier === 0) $failed++;
                }
            }
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
        // ...

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

        if ($webhook == env('GITHUB_WEBHOOK_URL'
            && $event == 'push')
            && $request->isJson()) {
            $payload = $request->json();
            $this->returnDeliveryStatus($this->runChecks($payload));
        }
    }
}

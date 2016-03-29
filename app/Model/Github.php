<?php

namespace App\Model;

use DateTime;
use Illuminate\Http\Request;

class Github
{
    /**
     * Checks to see if we are able to deploy by checking the current time and
     * the agreed deployment hours defined as a constant
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return bool Whether the deployment is allowed
     */
    protected function isDeploymentAllowed()
    {
        $date = new DateTime();

        if ($date->format('H') >= env('DEPLOYMENT_HOUR_FROM')
            && $date->format('H') <= env('DEPLOYMENT_HOUR_TO')) {
            return true;
        }

        return false;
    }

    /**
     * Sends a notifiction to Slack to tell the developer we cannot deploy
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return void
     */
    protected function sendNotification()
    {
        $token    = env('SLACK_TOKEN');
        $team     = env('SLACK_TEAM');
        $username = env('SLACK_USERNAME');
        $icon     = env('SLACK_DEPLOYMENT_ICON');

        $message  = env('SLACK_DEPLOYMENT_MESSAGE');
        $channel  = env('SLACK_DEPLOYMENT_CHANNEL');

        $slack = new Slack($token, $team, $username, $icon);
        $slack->send($message, $channel);
    }

    /**
     * Listens for the predeployment hook
     *
     * This method is used to check the current deployment state. If we are
     * allowed to deploy, this will return a HTTP 200 page, otherwise a 404
     * page. This stops deployments from happening outside agreed times.
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return void
     */
    public function listen(Request $request)
    {
        $webhook = app('request')->route()[2]['hash'];

        if ($webhook == env('GITHUB_WEBHOOK_URL')) {

            // if ($request->isJson()) {}

            file_put_contents(realpath(__DIR__ . '/../../storage/logs') . '/output.txt', print_r($request->json()->all(), true));
            dd($request->json()->all());

        }

        return true;
    }
}

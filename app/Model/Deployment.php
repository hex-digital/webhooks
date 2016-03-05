<?php

namespace App\Model;

use DateTime;

class Deployment
{
    /**
     * Checks to see if we are able to deploy by checking the current time and
     * the agreed deployment hours defined as a constant
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return bool Whether the deployment is allowed
     */
    public function isDeploymentAllowed()
    {
        $date = new DateTime();

        if ($date->format('H') >= env('DEPLOYMENT_HOUR_FROM')
            && $date->format('H') <= env('DEPLOYMENT_HOUR_TO')) {
            return true;
        }

        return false;
    }

    /**
     * Returns http headers depending on whether the deployment is allowed
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return void
     */
    public function returnHttpHeaders()
    {
        if ($this->isDeploymentAllowed()) {
            header('HTTP/1.1 200 OK');
        } else {
            header('HTTP/1.1 404 Not Found');

            $token    = env('SLACK_TOKEN');
            $team     = env('SLACK_TEAM');
            $username = env('SLACK_USERNAME');
            $icon     = env('SLACK_DEPLOYMENT_ICON');

            $message  = env('SLACK_DEPLOYMENT_MESSAGE');
            $channel  = env('SLACK_DEPLOYMENT_CHANNEL');

            $slack = new Slack($token, $team, $username, $icon);
            $slack->send($message, $channel);
        }

        exit;
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
    public function listen()
    {
        $webhook = app('request')->route()[2]['hash'];

        if ($webhook == env('DEPLOYMENT_WEBHOOK_URL')) {
            $this->returnHttpHeaders();
        }
    }
}

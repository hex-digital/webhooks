<?php

namespace App\Http\Controllers;

class WebhookController extends Controller
{
    public function handleWebhook($payload)
    {
        $this->handleGithubPush($payload);
        $this->handleDeploybotDeployment($payload);
    }

    /**
     * Handle a GitHub webhook.
     *
     * @param  array  $payload
     * @return Response
     */
    public function handleGithubPush($payload)
    {
        // Handle The Event
    }

    /**
     * Handle a DeployBot webhook.
     *
     * @param  array  $payload
     * @return Response
     */
    public function handleDeploybotDeployment($payload)
    {
        // Handle The Event
    }
}

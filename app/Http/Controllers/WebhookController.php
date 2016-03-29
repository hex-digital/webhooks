<?php

namespace App\Http\Controllers;

use App\Model\Deployment;
use App\Model\Github;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $this->handleGithubPush($request);
        $this->handleDeploybotDeployment($request);
    }

    /**
     * Handle a GitHub webhook.
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  Request  $request
     * @return Response
     */
    public function handleGithubPush(Request $request)
    {
        $github = new Github();
        $github->listen($request);
    }

    /**
     * Handle a DeployBot webhook.
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  Request  $request
     * @return Response
     */
    public function handleDeploybotDeployment(Request $request)
    {
        $deployment = new Deployment();
        $deployment->listen();
    }
}

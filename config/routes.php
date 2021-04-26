<?php

use Slim\App;

return function (App $app) {
	$app->get('/', \App\Action\HomeAction::class)->setName('home');
	$app->get('/api/v1/organization/fetch_opened', \App\Action\API\V1\Organization\FetchOpenedAction::class)->setName('api_v1_organization_fetch_opened');
	$app->get('/api/v1/organization/fetch_closed', \App\Action\API\V1\Organization\FetchClosedAction::class)->setName('api_v1_organization_fetch_closed');
};
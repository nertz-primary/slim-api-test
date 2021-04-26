<?php

namespace App\Action\API\V1\Organization;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Domain\Organization\Repository\OrganizationRepository;

final class FetchClosedAction
{
    private $userCreator;

    public function __construct(OrganizationRepository $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    public function __invoke(
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        // Collect input from the HTTP request
        $data = (array)$request->getParsedBody();

        // Invoke the Domain with inputs and retain the result
        $organizationClosedList = $this->organizationRepository->fetchClosed();

        // Transform the result into the JSON representation
        $result = [
			'status' => 'ok',
            'items' => $organizationClosedList
        ];

        // Build the HTTP response
        $response->getBody()->write((string)json_encode($result));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
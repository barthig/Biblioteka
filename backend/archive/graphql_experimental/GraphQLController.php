<?php

namespace App\Controller;

use Overblog\GraphQLBundle\Request\Executor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class GraphQLController extends AbstractController
{
    #[Route('/graphql', name: 'api_graphql', methods: ['POST', 'GET'])]
    #[OA\Post(
        path: '/api/graphql',
        summary: 'GraphQL API endpoint',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'query', type: 'string', example: '{ books { id title author } }'),
                    new OA\Property(
                        property: 'variables',
                        type: 'object',
                        example: ['limit' => 10]
                    ),
                ]
            )
        ),
        tags: ['GraphQL'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'GraphQL response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request, Executor $executor): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $query = $data['query'] ?? $request->query->get('query');
        $variables = $data['variables'] ?? $request->query->get('variables', []);
        
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?? [];
        }

        $result = $executor->execute(null, [
            'query' => $query,
            'variables' => $variables,
        ]);

        return new JsonResponse($result->toArray());
    }
}

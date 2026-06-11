<?php

declare(strict_types=1);

namespace App\Controller;

use App\GraphQl\CoffeeShopSchemaFactory;
use App\Http\Error\ApiErrorMapper;
use GraphQL\Error\FormattedError;
use GraphQL\GraphQL;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class GraphQlController
{
    public function __construct(
        private CoffeeShopSchemaFactory $schemaFactory,
        private ApiErrorMapper $mapper,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array{query?: string, variables?: array<string, mixed>|null} $input */
        $input = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $result = GraphQL::executeQuery(
            $this->schemaFactory->create(),
            $input['query'] ?? '',
            variableValues: $input['variables'] ?? null,
        );

        $result->setErrorFormatter($this->formatError(...));

        return new JsonResponse($result->toArray());
    }

    /**
     * @return array{message: string, locations?: array<int, array{line: int, column: int}>, path?: array<int, int|string>, extensions?: array<string, mixed>}
     */
    private function formatError(\Throwable $error): array
    {
        $previous = $error->getPrevious();

        // webonyx wraps resolver exceptions as previous; syntax/validation errors have none.
        if (!$previous instanceof \Throwable) {
            return FormattedError::createFromException($error);
        }

        $apiError = $this->mapper->map($previous);

        return [
            'message' => $apiError->message,
            'extensions' => ['code' => $apiError->code],
        ];
    }
}

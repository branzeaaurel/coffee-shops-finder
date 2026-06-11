<?php

declare(strict_types=1);

namespace App\Http\Error;

use App\Exception\CsvFetchException;
use App\Exception\InvalidCoordinatesException;
use App\Exception\NoValidLocationsException;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApiErrorMapper
{
    public function map(\Throwable $exception): ApiError
    {
        return match (true) {
            $exception instanceof InvalidCoordinatesException => new ApiError(
                'INVALID_COORDINATES',
                $exception->getMessage(),
                Response::HTTP_BAD_REQUEST,
            ),
            $exception instanceof CsvFetchException => new ApiError(
                'COFFEE_SHOPS_UNAVAILABLE',
                'Coffee shop data is temporarily unavailable. Please try again later.',
                Response::HTTP_SERVICE_UNAVAILABLE,
            ),
            $exception instanceof NoValidLocationsException => new ApiError(
                'NO_VALID_LOCATIONS',
                'No valid coffee shop locations are available.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ),
            default => new ApiError(
                'INTERNAL_ERROR',
                'An unexpected error occurred. Please try again later.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ),
        };
    }
}

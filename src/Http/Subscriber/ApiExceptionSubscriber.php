<?php

declare(strict_types=1);

namespace App\Http\Subscriber;

use App\Exception\CsvFetchException;
use App\Exception\InvalidCoordinatesException;
use App\Exception\NoValidLocationsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof InvalidCoordinatesException) {
            $event->setResponse($this->errorResponse(
                'INVALID_COORDINATES',
                $exception->getMessage(),
                Response::HTTP_BAD_REQUEST,
            ));

            return;
        }

        if ($exception instanceof CsvFetchException) {
            $event->setResponse($this->errorResponse(
                'COFFEE_SHOPS_UNAVAILABLE',
                'Coffee shop data is temporarily unavailable. Please try again later.',
                Response::HTTP_SERVICE_UNAVAILABLE,
            ));

            return;
        }

        if ($exception instanceof NoValidLocationsException) {
            $event->setResponse($this->errorResponse(
                'NO_VALID_LOCATIONS',
                'No valid coffee shop locations are available.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ));

            return;
        }

        $this->logger->critical('Unhandled exception.', ['exception' => $exception]);

        $event->setResponse($this->errorResponse(
            'INTERNAL_ERROR',
            'An unexpected error occurred. Please try again later.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
    }

    private function errorResponse(string $code, string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}

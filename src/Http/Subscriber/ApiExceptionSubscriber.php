<?php

declare(strict_types=1);

namespace App\Http\Subscriber;

use App\Http\Error\ApiErrorMapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ApiErrorMapper $mapper,
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
        $error = $this->mapper->map($exception);

        if ('INTERNAL_ERROR' === $error->code) {
            $this->logger->critical('Unhandled exception.', ['exception' => $exception]);
        }

        $event->setResponse(new JsonResponse([
            'error' => [
                'code' => $error->code,
                'message' => $error->message,
            ],
        ], $error->status));
    }
}

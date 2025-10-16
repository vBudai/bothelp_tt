<?php

namespace App\Controller;

use App\Bus\Event\EventMessage;
use App\Service\Events\DispatchEventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class EventsController extends AbstractController
{
    /**
     * @throws ExceptionInterface
     */
    #[Route('/api/event', methods: ['POST'])]
    public function createEvent(
        #[MapRequestPayload]
        EventMessage $request,
        DispatchEventService $service,
    ): JsonResponse {
        $service->dispatch($request);

        return $this->json([]);
    }
}

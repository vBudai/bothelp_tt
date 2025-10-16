<?php

namespace App\Controller;

use App\Bus\Event\EventMessage;
use App\Service\Events\DispatchEventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class EventsController extends AbstractController
{
    public function __construct(){}

    #[Route('/api/event', methods: ['POST'])]
    public function createEvent(
        #[MapRequestPayload]
        EventMessage $request,
        DispatchEventService $service,
    ): JsonResponse
    {
        try{
            $service->dispatch($request);
            return $this->json(['ok' => true]);
        } catch (ExceptionInterface $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], $e->getCode() != 0 ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

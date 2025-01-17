<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\DTO\IncomingDTO;
use Symfony\Component\HttpFoundation\Request;
use App\Service\IncomingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\Validations\ErrorExceptions;
use App\Controller\Validations\ValidationJson;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/receitas')]
final class IncomingController extends AbstractController
{

    private LoggerInterface $logger;
    private IncomingService $incomingService;
    private ValidatorInterface $validator;

    function __construct(LoggerInterface $logger, IncomingService $incomingService, ValidatorInterface $validator)
    {
        $this->logger = $logger;
        $this->incomingService = $incomingService;
        $this->validator = $validator;
    }

    #[Route(methods: ['POST'], name: 'incoming_save')]
    function save(Request $request): JsonResponse
    {
        $validationJson = new ValidationJson($this->validator, json_decode($request->getContent()));
        $incoming = $validationJson->createIncomingWithPayload();
        if ($incoming instanceof JsonResponse) {
            return $incoming;
        }

        try {
            return new JsonResponse($this->incomingService->save($incoming), 201);
        } catch (\RuntimeException $ex) {
            return ErrorExceptions::badRequestBuilder($ex->getMessage());
        }
    }

    #[Route(methods: ['GET'], name: 'incoming_get_all')]
    function getAll(Request $request): JsonResponse
    {
        $incomings = $this->verifyGetAllFilterDescription($request);
        return new JsonResponse(IncomingDTO::convertListToListDTO($incomings));
    }

    #[Route('/{id}', methods: ['GET'], name: 'incoming_find_by_id')]
    function findIncomingDetails(int $id): JsonResponse
    {
        try {
            $incoming = $this->incomingService->findById($id);
            return new JsonResponse(IncomingDTO::convertEntityToDTO($incoming));
        } catch (\RuntimeException $ex) {
            return ErrorExceptions::badRequestBuilder($ex->getMessage());
        }
    }
    
    #[Route('/{ano}/{mes}', methods: ['GET'], name: 'incoming_find_by_month')]
    function findAllByMonth(int $ano,int $mes)
    {
        if ($ano < 1970 || $ano > date('Y') || $ano == 0) {
            return ErrorExceptions::badRequestBuilder('ano informado é invalido');
        }
        
        if ($mes > 12 || $mes <= 0) {
            return ErrorExceptions::badRequestBuilder('mês informado é invalido');
        }
        
        $this->logger->info("findAllByMonth - ano: $ano, mês: $mes");
        $incomings = $this->incomingService->getAllByMonth($mes, $ano);
        return new JsonResponse(IncomingDTO::convertListToListDTO($incomings));
    }
    
    #[Route('/{id}', methods: ['PUT'], name: 'incoming_update')]
    function updateIncomingById(int $id, Request $request): JsonResponse
    {
        $validationJson = new ValidationJson($this->validator, json_decode($request->getContent()));
        $incomingUpdate = $validationJson->createIncomingWithPayload();
        if ($incomingUpdate instanceof JsonResponse) {
            return $incomingUpdate;
        }

        try {
            $incomingUpdate = $this->incomingService->update($id, $incomingUpdate);
            return new JsonResponse($incomingUpdate);
        } catch (\RuntimeException $ex) {
            return ErrorExceptions::badRequestBuilder($ex->getMessage());
        }
    }

    #[Route('/{id}', methods: ['DELETE'], name: 'incoming_delete_by_id')]
    function deleteIncomingById(int $id): JsonResponse
    {
        try {
            $this->incomingService->delete($id);
            return new JsonResponse('', 204);
        } catch (\RuntimeException $ex) {
            return ErrorExceptions::badRequestBuilder($ex->getMessage());
        }
    }

    private function verifyGetAllFilterDescription(Request $request)
    {
        $this->logger->info("verifyGetAllFilterDescription - verificar se possui filtro");
        if ($request->query->get('descricao') != null) {
            $description = $request->query->get('descricao');
            $this->logger->info("verifyGetAllFilterDescription - existe filtro ". $description);
            return $this->incomingService->getAllByDescription($description);
        }
        
        return $this->incomingService->getAll();
    }
}
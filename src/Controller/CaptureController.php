<?php

namespace App\Controller;

use App\Service\HttpCaptureService;
use App\Repository\HttpCaptureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 受信エンドポイント: GET/POST でアクセスされた内容を保存する
 */
#[Route('/in', name: 'capture_')]
class CaptureController extends AbstractController
{
    public function __construct(
        private HttpCaptureService $captureService,
        private HttpCaptureRepository $repository
    ) {
    }

    /**
     * 任意の GET/POST を受け付け、ヘッダ・ボディをシリアライズして保存し 200 を返す
     */
    #[Route('', name: 'receive', methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])]
    public function receive(Request $request): Response
    {
        $capture = $this->captureService->createCaptureFromRequest($request);
        $this->repository->save($capture, true);

        return new JsonResponse([
            'ok' => true,
            'id' => $capture->getId(),
            'message' => 'Captured',
        ], Response::HTTP_OK);
    }
}

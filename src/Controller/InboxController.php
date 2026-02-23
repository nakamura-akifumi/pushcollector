<?php

namespace App\Controller;

use App\Entity\HttpCapture;
use App\Repository\HttpCaptureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 保存したアクセス情報の一覧・詳細表示
 */
#[Route('/inbox', name: 'inbox_')]
class InboxController extends AbstractController
{
    public function __construct(
        private HttpCaptureRepository $repository
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $limit = min(100, max(10, (int) $request->query->get('limit', 50)));
        $captures = $this->repository->findLatest($limit);

        return $this->render('inbox/index.html.twig', [
            'captures' => $captures,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(HttpCapture $capture): Response
    {
        $payload = $capture->getPayloadDecoded();

        return $this->render('inbox/show.html.twig', [
            'capture' => $capture,
            'payload' => $payload,
        ]);
    }
}

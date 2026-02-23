<?php

namespace App\Controller;

use App\Entity\HttpCapture;
use App\Repository\HttpCaptureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * 保存したリクエストを別のURLへリレー送信
 */
#[Route('/relay', name: 'relay_')]
class RelayController extends AbstractController
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private HttpCaptureRepository $repository,
        ?HttpClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    #[Route('/{id}', name: 'form', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function form(HttpCapture $capture): Response
    {
        return $this->render('relay/form.html.twig', [
            'capture' => $capture,
        ]);
    }

    #[Route('/{id}/send', name: 'send', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function send(HttpCapture $capture, Request $request): Response
    {
        $targetUrl = trim((string) $request->request->get('target_url', ''));
        if ($targetUrl === '') {
            $this->addFlash('error', '送信先URLを入力してください。');
            return $this->redirectToRoute('relay_form', ['id' => $capture->getId()]);
        }

        if (!preg_match('#^https?://#i', $targetUrl)) {
            $this->addFlash('error', 'URLは http:// または https:// で始めてください。');
            return $this->redirectToRoute('relay_form', ['id' => $capture->getId()]);
        }

        $payload = $capture->getPayloadDecoded();
        $headers = $payload['headers'] ?? [];
        $bodyRaw = $payload['body_raw'] ?? '';
        $method = $capture->getMethod();

        $options = [];
        if ($bodyRaw !== '' && \in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $options['body'] = $bodyRaw;
        }
        $filteredHeaders = $this->filterHeadersForRelay($headers);
        if ($filteredHeaders !== []) {
            $options['headers'] = $filteredHeaders;
        }

        try {
            $response = $this->httpClient->request($method, $targetUrl, $options);
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getContent(false);

            $this->addFlash('success', "送信しました。HTTP {$statusCode}");

            return $this->render('relay/result.html.twig', [
                'capture' => $capture,
                'target_url' => $targetUrl,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_headers' => $response->getHeaders(),
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('error', '送信に失敗しました: ' . $e->getMessage());
            return $this->redirectToRoute('relay_form', ['id' => $capture->getId()]);
        }
    }

    /**
     * リレー時に送るヘッダを選別（Host 等は送信先に任せる）
     */
    private function filterHeadersForRelay(array $headers): array
    {
        $skip = ['host', 'content-length', 'connection', 'accept-encoding'];
        $out = [];
        foreach ($headers as $name => $value) {
            $lower = strtolower($name);
            if (\in_array($lower, $skip, true)) {
                continue;
            }
            $out[$name] = \is_array($value) ? $value[0] ?? '' : $value;
        }
        return $out;
    }
}

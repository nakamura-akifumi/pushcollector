<?php

namespace App\Service;

use App\Entity\HttpCapture;
use Symfony\Component\HttpFoundation\Request;

/**
 * アクセス元IP・ヘッダ・ボディを解析し、シリアライズ用の配列を組み立てる
 */
class HttpCaptureService
{
    /**
     * リクエストからキャプチャ用のペイロード配列を生成
     */
    public function buildPayloadFromRequest(Request $request): array
    {
        $headers = $this->parseHeaders($request);
        $body = $this->getRequestBody($request);
        $parsedBody = $this->parseBody($request, $body);

        return [
            'headers' => $headers,
            'body_raw' => $body,
            'body_parsed' => $parsedBody,
            'content_type' => $request->headers->get('Content-Type', ''),
            'query' => $request->query->all(),
            'server' => $this->getRelevantServerVars($request),
        ];
    }

    /**
     * ヘッダ情報を連想配列に（重複キーは配列で保持）
     */
    public function parseHeaders(Request $request): array
    {
        $out = [];
        foreach ($request->headers->all() as $name => $values) {
            $out[$name] = \count($values) === 1 ? $values[0] : $values;
        }
        return $out;
    }

    /**
     * 生のボディ文字列を取得
     */
    public function getRequestBody(Request $request): string
    {
        $content = $request->getContent();
        return \is_string($content) ? $content : '';
    }

    /**
     * Content-Type に応じてボディを解析（JSON / form-urlencoded / multipart 等）
     */
    public function parseBody(Request $request, string $rawBody): array|string|null
    {
        $contentType = $request->headers->get('Content-Type', '');
        if ($rawBody === '') {
            return [];
        }

        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($rawBody, true);
            return \is_array($decoded) ? $decoded : ['_raw' => $rawBody];
        }

        if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($rawBody, $parsed);
            return $parsed;
        }

        if (stripos($contentType, 'multipart/form-data') !== false) {
            return $request->request->all();
        }

        return ['_raw' => $rawBody];
    }

    /**
     * リクエストに関連するサーバ変数のみ（IP・ホスト等）を取得
     */
    private function getRelevantServerVars(Request $request): array
    {
        $vars = [
            'REMOTE_ADDR' => $request->server->get('REMOTE_ADDR'),
            'HTTP_X_FORWARDED_FOR' => $request->headers->get('X-Forwarded-For'),
            'HTTP_X_REAL_IP' => $request->headers->get('X-Real-IP'),
            'HTTP_HOST' => $request->getHost(),
            'REQUEST_SCHEME' => $request->getScheme(),
            'SERVER_PROTOCOL' => $request->server->get('SERVER_PROTOCOL'),
        ];
        return array_filter($vars, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * リクエストから HttpCapture エンティティを生成して返す（永続化は呼び出し元で）
     */
    public function createCaptureFromRequest(Request $request): HttpCapture
    {
        $payload = $this->buildPayloadFromRequest($request);
        $clientIp = $this->resolveClientIp($request);

        $capture = new HttpCapture();
        $capture->setMethod($request->getMethod())
            ->setRequestUri($request->getRequestUri())
            ->setClientIp($clientIp)
            ->setPayloadFromArray($payload);

        return $capture;
    }

    /**
     * アクセス元IPを解決（プロキシ考慮）
     */
    public function resolveClientIp(Request $request): string
    {
        if ($request->headers->get('X-Forwarded-For')) {
            $ips = array_map('trim', explode(',', $request->headers->get('X-Forwarded-For')));
            return $ips[0];
        }
        if ($request->headers->get('X-Real-IP')) {
            return trim($request->headers->get('X-Real-IP'));
        }
        return $request->server->get('REMOTE_ADDR', '0.0.0.0');
    }
}

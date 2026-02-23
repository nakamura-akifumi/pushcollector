<?php

namespace App\Entity;

use App\Repository\HttpCaptureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HttpCaptureRepository::class)]
class HttpCapture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $method = 'GET';

    #[ORM\Column(length: 2048)]
    private string $requestUri = '';

    #[ORM\Column(length: 45)]
    private string $clientIp = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $capturedAt = null;

    /**
     * シリアライズ済みペイロード（JSON: headers, body, contentType 等）
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $payload = '';

    public function __construct()
    {
        $this->capturedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    public function setRequestUri(string $requestUri): static
    {
        $this->requestUri = $requestUri;
        return $this;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function setClientIp(string $clientIp): static
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    public function getCapturedAt(): ?\DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function setCapturedAt(\DateTimeImmutable $capturedAt): static
    {
        $this->capturedAt = $capturedAt;
        return $this;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): static
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * デシリアライズしたペイロードを配列で返す
     */
    public function getPayloadDecoded(): array
    {
        $decoded = json_decode($this->payload, true);
        return \is_array($decoded) ? $decoded : [];
    }

    public function setPayloadFromArray(array $data): static
    {
        $this->payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }
}

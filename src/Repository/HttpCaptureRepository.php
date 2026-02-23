<?php

namespace App\Repository;

use App\Entity\HttpCapture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HttpCapture>
 */
class HttpCaptureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HttpCapture::class);
    }

    /**
     * 新しい順で一覧取得
     *
     * @return HttpCapture[]
     */
    public function findLatest(int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.capturedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(HttpCapture $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}

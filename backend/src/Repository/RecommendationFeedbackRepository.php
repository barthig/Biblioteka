<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\RecommendationFeedback;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecommendationFeedback>
 */
class RecommendationFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecommendationFeedback::class);
    }

    /**
     * Get list of book IDs that user dismissed
     * @return int[]
     */
    public function getDismissedBookIdsByUser(User $user): array
    {
        $results = $this->createQueryBuilder('rf')
            ->select('IDENTITY(rf.book) as bookId')
            ->where('rf.user = :user')
            ->andWhere('rf.feedbackType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', RecommendationFeedback::TYPE_DISMISS)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => (int) $r['bookId'], $results);
    }

    /**
     * Get list of book IDs that user marked as interested
     * @return int[]
     */
    public function getInterestedBookIdsByUser(User $user): array
    {
        $results = $this->createQueryBuilder('rf')
            ->select('IDENTITY(rf.book) as bookId')
            ->where('rf.user = :user')
            ->andWhere('rf.feedbackType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', RecommendationFeedback::TYPE_INTERESTED)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => (int) $r['bookId'], $results);
    }
}

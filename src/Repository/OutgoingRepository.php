<?php

namespace App\Repository;

use App\Entity\Outgoing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Outgoing|null find($id, $lockMode = null, $lockVersion = null)
 * @method Outgoing|null findOneBy(array $criteria, array $orderBy = null)
 * @method Outgoing[]    findAll()
 * @method Outgoing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OutgoingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Outgoing::class);
    }
    
    public function findByDescriptionByIsMothCurrenty($description, \DateTime $date)
    {
        $month = $date->format('m');
        $year  = $date->format('Y');
        $dateIntialMonth = date($year.'-'.$month.'-01');
        $dateFinalMotnh = date($year.'-'.$month.'-t');
        
        return $this->createQueryBuilder('O')
        ->where('o.description = :description')
        ->andWhere('o.date between :dateIn AND :dateFinal')
        ->setParameter('description', $description)
        ->setParameter('dateIn', $dateIntialMonth)
        ->setParameter('dateFinal', $dateFinalMotnh)
        ->getQuery()->getOneOrNullResult();
    }
}
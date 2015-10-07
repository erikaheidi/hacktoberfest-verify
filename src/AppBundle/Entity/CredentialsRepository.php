<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CredentialsRepository extends EntityRepository
{
    function getGithubToken($userId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $credentials = $qb->select('c')
            ->from('AppBundle:Credentials', 'c')
            ->where('c.user = ?1')
            ->andWhere('c.serviceName = ?2')
            ->setParameter(1, $userId)
            ->setParameter(2, 'github')
            ->getQuery()
            ->getOneOrNullResult();

        if ($credentials) {
            $token = unserialize($credentials->getServiceTokens());

            return $token;
        }

        return null;
    }
}

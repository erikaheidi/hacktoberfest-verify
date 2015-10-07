<?php

namespace AppBundle\Entity;

use HeidiLabs\SauthBundle\Model\AbstractUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User extends AbstractUser
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

}
<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use Stevenmaguire\OAuth2\Client\Provider\MicrosoftResourceOwner;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findOrCreateFromGithubOauth(GithubResourceOwner $owner)
    {
        /** @var User $user */
        $user = $this->createQueryBuilder('u')
            ->where('u.githubId = :githubId')
            ->orWhere('u.email = :email')
            ->setParameters([
                'email' => $owner->getEmail(),
               'githubId' => $owner->getId(),
            ])
            ->getQuery()
            ->getOneOrNullResult();

        if($user){
            if ($user->getGithubId() ===  null){
                $user->setGithubId($owner->getId());
                $this->getEntityManager()->flush();
            }
            return $user;
        }
        $user = (new User())
            ->setRoles(['ROLE_USER'])
            ->setGithubId($owner->getId())
            ->setEmail($owner->getEmail());

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function findOrCreateFromGoogleOauth(GoogleUser $owner)
    {
        /** @var User $user */
        $user = $this->createQueryBuilder('u')
            ->where('u.googleId = :googleId')
            ->orWhere('u.email = :email')
            ->setParameters([
                'email' => $owner->getEmail(),
                'googleId' => $owner->getId(),
            ])
            ->getQuery()
            ->getOneOrNullResult();

        if($user){
            if ($user->getGoogleId() ===  null){
                $user->setGoogleId($owner->getId());
                $this->getEntityManager()->flush();
            }
            return $user;
        }
        $user = (new User())
            ->setRoles(['ROLE_USER'])
            ->setGoogleId($owner->getId())
            ->setEmail($owner->getEmail());

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function findOrCreateFromMicrosoftOauth(MicrosoftResourceOwner $owner)
    {
        /** @var User $user */
        $user = $this->createQueryBuilder('u')
            ->where('u.microsoftId = :microsoftId')
            ->orWhere('u.email = :email')
            ->setParameters([
                'email' => $owner->getEmail(),
                'microsoftId' => $owner->getId(),
            ])
            ->getQuery()
            ->getOneOrNullResult();

        if($user){
            if ($user->getMicrosoftId() ===  null){
                $user->setMicrosoftId($owner->getId());
                $this->getEntityManager()->flush();
            }
            return $user;
        }
        $user = (new User())
            ->setRoles(['ROLE_USER'])
            ->setMicrosoftId($owner->getId())
            ->setEmail($owner->getEmail());

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}

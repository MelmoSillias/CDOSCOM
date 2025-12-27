<?php

namespace App\Fixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // create 20 users with random data 
        $user = new User();
        $user->setUserName('Admin');
        $user->setPassword('$2y$13\$RMKoVIjd75t7pm8b8rFiteGTwkYJPz/t5.mbJ4F7bLq6W5pNloypO');
        $manager->persist($user); 

        $manager->flush();
    }
}
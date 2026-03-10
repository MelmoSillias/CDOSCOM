<?php

namespace App\DataFixtures;

use App\Entity\Actualite;
use App\Entity\Message;
use App\Entity\Personnel;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }
    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();

        $user = new User();
        $user->setUserName('Admin');
        $user->setPassword($this->hasher->hashPassword($user, '123'));
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        $actualites = [
            [
                'titre' => 'Nouvel equipement de blanchiment',
                'image' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=1470&q=80',
                'contenu' => '<p>Nous integrons un systeme de blanchiment LED basse chaleur pour plus de confort et une meilleure efficacite.</p>',
            ],
            [
                'titre' => 'Horaires d\'ete du cabinet',
                'image' => 'https://images.unsplash.com/photo-1598256989800-fe5f95da9787?auto=format&fit=crop&w=1470&q=80',
                'contenu' => '<p>Ouverture en continu du lundi au vendredi avec plages dediees aux urgences.</p>',
            ],
            [
                'titre' => 'Campagne prevention famille',
                'image' => 'https://images.unsplash.com/photo-1609840114035-3c981b782dfe?auto=format&fit=crop&w=1470&q=80',
                'contenu' => '<p>Bilan dentaire de prevention et conseils pratiques pour toute la famille.</p>',
            ],
        ];

        foreach ($actualites as $index => $item) {
            $actualite = new Actualite();
            $actualite->setTitre($item['titre']);
            $actualite->setSlug(strtolower((string) $slugger->slug($item['titre'])));
            $actualite->setDatePublication(new \DateTime(sprintf('-%d days', (3 - $index) * 5)));
            $actualite->setDelaiPublication(new \DateTime(sprintf('-%d days', (3 - $index) * 5)));
            $actualite->setImage($item['image']);
            $actualite->setContenu($item['contenu']);
            $actualite->setFaq([
                'Pourquoi cette actualite ?' => 'Pour ameliorer la qualite de prise en charge des patients.',
                'Ce que vous pouvez faire :' => 'Planifier votre visite et suivre les recommandations de l\'equipe.',
                'Conseil Pratique' => 'Gardez nos horaires et moyens de contact a portee de main.',
            ]);
            $manager->persist($actualite);
        }

        $personnels = [
            [
                'nom' => 'Sow',
                'prenom' => 'Ousmane',
                'poste' => 'Dentiste',
                'photo' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=800&q=80',
                'description' => 'Specialiste des soins conservateurs et de l\'esthetique dentaire.',
            ],
            [
                'nom' => 'Toure',
                'prenom' => 'Ousmane',
                'poste' => 'Dentiste',
                'photo' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=800&q=80',
                'description' => 'Prise en charge des traitements chirurgicaux et pediatriques.',
            ],
            [
                'nom' => 'Karembe',
                'prenom' => 'Ada',
                'poste' => 'Aide soignante',
                'photo' => 'https://images.unsplash.com/photo-1594824475317-e9ce51f29f06?auto=format&fit=crop&w=800&q=80',
                'description' => 'Accompagnement des patients et assistance au fauteuil.',
            ],
        ];

        foreach ($personnels as $p) {
            $personnel = new Personnel();
            $personnel->setNom($p['nom']);
            $personnel->setPrenom($p['prenom']);
            $personnel->setPoste($p['poste']);
            $personnel->setPhoto($p['photo']);
            $personnel->setDescription($p['description']);
            $personnel->setLiensSociaux([
                'LinkedIn' => 'https://linkedin.com/',
                'Twitter' => 'https://twitter.com/',
                'Facebook' => 'https://facebook.com/',
            ]);
            $manager->persist($personnel);
        }

        $message = new Message();
        $message->setFirstName('Fatoumata');
        $message->setLastName('Diallo');
        $message->setEmail('fatoumata@example.com');
        $message->setPhone('+22384075518');
        $message->setSubject('Demande de rendez-vous');
        $message->setContent('Je souhaite prendre rendez-vous pour un controle dentaire.');
        $message->setType('appointment');
        $message->setStatus('unread');
        $message->setStatutEnvoiMail('pending');
        $manager->persist($message);

        $manager->flush();
    }
}

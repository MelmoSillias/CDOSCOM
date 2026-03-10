<?php

namespace App\Controller\Api;

use App\Entity\Actualite;
use App\Entity\Personnel;
use App\Repository\ActualiteRepository;
use App\Repository\PersonnelRepository;
use App\Service\FileUploadService;
use App\Service\SiteConfigurationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/admin', name: 'api_admin_')]
class AdminContentController extends AbstractController
{
    public function __construct(private readonly FileUploadService $fileUploadService)
    {
    }

    #[Route('/actualites', name: 'actualites_list', methods: ['GET'])]
    public function listActualites(ActualiteRepository $actualiteRepository): JsonResponse
    {
        $actualites = $actualiteRepository->findBy([], ['datePublication' => 'DESC']);

        return $this->json([
            'data' => array_map([$this, 'serializeActualite'], $actualites),
        ]);
    }

    #[Route('/actualites', name: 'actualites_create', methods: ['POST'])]
    public function createActualite(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->getPayloadData($request);

        try {
            $actualite = new Actualite();
            $this->hydrateActualite($actualite, $data, $request->files->get('imageFile'));

            $entityManager->persist($actualite);
            $entityManager->flush();

            return $this->json(['message' => 'Actualite creee.', 'data' => $this->serializeActualite($actualite)], 201);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/actualites/{id}', name: 'actualites_update', methods: ['POST', 'PUT'])]
    public function updateActualite(int $id, Request $request, ActualiteRepository $actualiteRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $actualite = $actualiteRepository->find($id);
        if (!$actualite) {
            return $this->json(['error' => 'Actualite introuvable.'], 404);
        }

        $data = $this->getPayloadData($request);

        try {
            $this->hydrateActualite($actualite, $data, $request->files->get('imageFile'));
            $entityManager->flush();

            return $this->json(['message' => 'Actualite modifiee.', 'data' => $this->serializeActualite($actualite)]);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/actualites/{id}', name: 'actualites_delete', methods: ['DELETE'])]
    public function deleteActualite(int $id, ActualiteRepository $actualiteRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $actualite = $actualiteRepository->find($id);
        if (!$actualite) {
            return $this->json(['error' => 'Actualite introuvable.'], 404);
        }

        $entityManager->remove($actualite);
        $entityManager->flush();

        return $this->json(['message' => 'Actualite supprimee.']);
    }

    #[Route('/personnels', name: 'personnels_list', methods: ['GET'])]
    public function listPersonnels(PersonnelRepository $personnelRepository): JsonResponse
    {
        $personnels = $personnelRepository->findOrdered();

        return $this->json([
            'data' => array_map([$this, 'serializePersonnel'], $personnels),
        ]);
    }

    #[Route('/personnels', name: 'personnels_create', methods: ['POST'])]
    public function createPersonnel(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->getPayloadData($request);

        try {
            $personnel = new Personnel();
            $this->hydratePersonnel($personnel, $data, $request->files->get('photoFile'));

            $entityManager->persist($personnel);
            $entityManager->flush();

            return $this->json(['message' => 'Personnel cree.', 'data' => $this->serializePersonnel($personnel)], 201);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/personnels/{id}', name: 'personnels_update', methods: ['POST', 'PUT'])]
    public function updatePersonnel(int $id, Request $request, PersonnelRepository $personnelRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $personnel = $personnelRepository->find($id);
        if (!$personnel) {
            return $this->json(['error' => 'Personnel introuvable.'], 404);
        }

        $data = $this->getPayloadData($request);

        try {
            $this->hydratePersonnel($personnel, $data, $request->files->get('photoFile'));
            $entityManager->flush();

            return $this->json(['message' => 'Personnel modifie.', 'data' => $this->serializePersonnel($personnel)]);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/personnels/{id}', name: 'personnels_delete', methods: ['DELETE'])]
    public function deletePersonnel(int $id, PersonnelRepository $personnelRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $personnel = $personnelRepository->find($id);
        if (!$personnel) {
            return $this->json(['error' => 'Personnel introuvable.'], 404);
        }

        $entityManager->remove($personnel);
        $entityManager->flush();

        return $this->json(['message' => 'Personnel supprime.']);
    }

    #[Route('/configuration', name: 'configuration_get', methods: ['GET'])]
    public function getConfiguration(SiteConfigurationService $siteConfigurationService): JsonResponse
    {
        return $this->json([
            'data' => $siteConfigurationService->getAll(),
        ]);
    }

    #[Route('/configuration', name: 'configuration_update', methods: ['POST', 'PUT'])]
    public function updateConfiguration(Request $request, SiteConfigurationService $siteConfigurationService): JsonResponse
    {
        $data = $this->getPayloadData($request);

        $existingConfig = $siteConfigurationService->getAll();
        $existingGallery = $existingConfig['gallery_images'] ?? [];

        $galleryImages = [];
        for ($index = 1; $index <= 4; $index++) {
            $file = $request->files->get(sprintf('galleryFile%d', $index));
            if ($file instanceof UploadedFile) {
                $galleryImages[] = $this->fileUploadService->upload($file, 'gallery');
                continue;
            }

            $existing = trim((string) ($data['gallery_existing_' . $index] ?? ''));
            if ($existing !== '') {
                $galleryImages[] = $existing;
                continue;
            }

            if (isset($existingGallery[$index - 1]) && is_string($existingGallery[$index - 1])) {
                $galleryImages[] = $existingGallery[$index - 1];
            }
        }

        $payload = [
            'social_links' => [
                'facebook' => (string) ($data['social_facebook'] ?? ''),
                'instagram' => (string) ($data['social_instagram'] ?? ''),
                'linkedin' => (string) ($data['social_linkedin'] ?? ''),
                'tiktok' => (string) ($data['social_tiktok'] ?? ''),
                'whatsapp' => (string) ($data['social_whatsapp'] ?? ''),
            ],
            'contact' => [
                'phones' => array_values(array_filter([
                    trim((string) ($data['contact_phone_1'] ?? '')),
                    trim((string) ($data['contact_phone_2'] ?? '')),
                ])),
                'email' => (string) ($data['contact_email'] ?? ''),
                'address' => (string) ($data['contact_address'] ?? ''),
            ],
            'opening_hours' => [
                'lundi-vendredi' => (string) ($data['hours_week'] ?? ''),
                'samedi' => (string) ($data['hours_sat'] ?? ''),
                'dimanche' => (string) ($data['hours_sun'] ?? ''),
            ],
            'gallery_images' => array_values(array_filter($galleryImages)),
            'mail' => [
                'recipient' => (string) ($data['mail_recipient'] ?? ''),
            ],
        ];

        $siteConfigurationService->save($payload);

        return $this->json(['message' => 'Configuration mise a jour.', 'data' => $payload]);
    }

    private function hydrateActualite(Actualite $actualite, array $data, ?UploadedFile $imageFile): void
    {
        foreach (['titre', 'datePublication', 'delaiPublication', 'contenu'] as $requiredField) {
            if (empty($data[$requiredField])) {
                throw new \InvalidArgumentException(sprintf('Le champ %s est obligatoire.', $requiredField));
            }
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slugger = new AsciiSlugger();
            $slug = strtolower((string) $slugger->slug((string) $data['titre']));
        }

        $actualite->setTitre((string) $data['titre']);
        $actualite->setSlug($slug);
        $actualite->setDatePublication(new \DateTime((string) $data['datePublication']));
        $actualite->setDelaiPublication(new \DateTime((string) $data['delaiPublication']));
        $actualite->setContenu((string) $data['contenu']);

        $image = trim((string) ($data['image'] ?? ''));
        if ($imageFile instanceof UploadedFile) {
            $image = $this->fileUploadService->upload($imageFile, 'actualites');
        }
        if ($image === '') {
            throw new \InvalidArgumentException('Le champ image est obligatoire.');
        }
        $actualite->setImage($image);

        $actualite->setFaq([
            'Pourquoi cette actualite ?' => (string) ($data['faqPourquoi'] ?? $data['faq']['Pourquoi cette actualite ?'] ?? ''),
            'Ce que vous pouvez faire :' => (string) ($data['faqAction'] ?? $data['faq']['Ce que vous pouvez faire :'] ?? ''),
            'Conseil Pratique' => (string) ($data['faqConseil'] ?? $data['faq']['Conseil Pratique'] ?? ''),
        ]);
        $actualite->setUpdatedAt(new \DateTime());
    }

    private function hydratePersonnel(Personnel $personnel, array $data, ?UploadedFile $photoFile): void
    {
        foreach (['nom', 'prenom', 'poste', 'description'] as $requiredField) {
            if (empty($data[$requiredField])) {
                throw new \InvalidArgumentException(sprintf('Le champ %s est obligatoire.', $requiredField));
            }
        }

        $personnel->setNom((string) $data['nom']);
        $personnel->setPrenom((string) $data['prenom']);
        $personnel->setPoste((string) $data['poste']);

        $photo = trim((string) ($data['photo'] ?? ''));
        if ($photoFile instanceof UploadedFile) {
            $photo = $this->fileUploadService->upload($photoFile, 'personnels');
        }
        if ($photo === '') {
            throw new \InvalidArgumentException('Le champ photo est obligatoire.');
        }
        $personnel->setPhoto($photo);

        $personnel->setDescription((string) $data['description']);
        $personnel->setLiensSociaux([
            'LinkedIn' => (string) ($data['linkedin'] ?? $data['liensSociaux']['LinkedIn'] ?? ''),
            'Twitter' => (string) ($data['twitter'] ?? $data['liensSociaux']['Twitter'] ?? ''),
            'Facebook' => (string) ($data['facebook'] ?? $data['liensSociaux']['Facebook'] ?? ''),
        ]);
        $personnel->setUpdatedAt(new \DateTime());
    }

    private function getPayloadData(Request $request): array
    {
        if (str_starts_with((string) $request->headers->get('Content-Type', ''), 'application/json')) {
            $decoded = json_decode($request->getContent(), true);

            return is_array($decoded) ? $decoded : [];
        }

        return $request->request->all();
    }

    private function serializeActualite(Actualite $actualite): array
    {
        return [
            'id' => $actualite->getId(),
            'titre' => $actualite->getTitre(),
            'slug' => $actualite->getSlug(),
            'datePublication' => $actualite->getDatePublication()?->format('Y-m-d\TH:i'),
            'delaiPublication' => $actualite->getDelaiPublication()?->format('Y-m-d\TH:i'),
            'contenu' => $actualite->getContenu(),
            'image' => $actualite->getImage(),
            'faq' => $actualite->getFaq(),
        ];
    }

    private function serializePersonnel(Personnel $personnel): array
    {
        return [
            'id' => $personnel->getId(),
            'nom' => $personnel->getNom(),
            'prenom' => $personnel->getPrenom(),
            'poste' => $personnel->getPoste(),
            'photo' => $personnel->getPhoto(),
            'description' => $personnel->getDescription(),
            'liensSociaux' => $personnel->getLiensSociaux(),
        ];
    }
}

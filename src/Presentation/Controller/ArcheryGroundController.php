<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Domain\ValueObject\TargetType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use ValueError;

use function array_map;
use function array_values;
use function in_array;
use function is_numeric;
use function str_starts_with;
use function strtolower;
use function trim;

final class ArcheryGroundController extends AbstractController
{
    public function __construct(private readonly ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return $this->redirectToRoute('archery_ground_index');
    }

    #[Route('/archery-grounds', name: 'archery_ground_index', methods: ['GET'])]
    public function index(): Response
    {
        $grounds = $this->archeryGroundRepository->findAll();

        return $this->render('archery_ground/index.html.twig', ['grounds' => $grounds]);
    }

    #[Route('/archery-grounds/new', name: 'archery_ground_new', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));
            if ($name !== '') {
                $archeryGround = new ArcheryGround(
                    id: $this->archeryGroundRepository->nextIdentity(),
                    name: $name,
                );
                $this->archeryGroundRepository->save($archeryGround);
                $this->addFlash('success', 'Archery ground created.');

                return $this->redirectToRoute('archery_ground_show', ['id' => $archeryGround->id()]);
            }

            $error = 'Please provide a name for the archery ground.';
        }

        return $this->render('archery_ground/new.html.twig', ['error' => $error]);
    }

    #[Route('/archery-grounds/{id}', name: 'archery_ground_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $archeryGround = $this->requireArcheryGround($id);

        return $this->render('archery_ground/show.html.twig', [
            'archeryGround' => $archeryGround,
            'targetTypes' => TargetType::cases(),
            'errors' => [],
        ]);
    }

    #[Route('/archery-grounds/{id}/rename', name: 'archery_ground_rename', methods: ['POST'])]
    public function rename(Request $request, string $id): Response
    {
        $archeryGround = $this->requireArcheryGround($id);
        $name          = trim((string) $request->request->get('name', ''));

        if ($name === '') {
            return $this->renderShowWithErrors($archeryGround, ['ground' => 'Name cannot be empty.']);
        }

        $updated = new ArcheryGround(
            id: $archeryGround->id(),
            name: $name,
            targetStorage: $archeryGround->targetStorage(),
            shootingLanes: $archeryGround->shootingLanes(),
        );
        $this->archeryGroundRepository->save($updated);
        $this->addFlash('success', 'Archery ground renamed.');

        return $this->redirectToRoute('archery_ground_show', ['id' => $archeryGround->id()]);
    }

    #[Route('/archery-grounds/{id}/delete', name: 'archery_ground_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        $this->archeryGroundRepository->delete($id);
        $this->addFlash('success', 'Archery ground deleted.');

        return $this->redirectToRoute('archery_ground_index');
    }

    #[Route('/archery-grounds/{id}/lanes', name: 'archery_ground_add_lane', methods: ['POST'])]
    public function addLane(Request $request, string $id): Response
    {
        $archeryGround    = $this->requireArcheryGround($id);
        $name             = trim((string) $request->request->get('name', ''));
        $maxDistanceInput = trim((string) $request->request->get('max_distance', ''));

        if ($name === '') {
            return $this->renderShowWithErrors($archeryGround, ['lane' => 'Lane name is required.']);
        }

        if ($maxDistanceInput === '' || ! is_numeric($maxDistanceInput)) {
            return $this->renderShowWithErrors($archeryGround, ['lane' => 'Max distance must be a number.']);
        }

        $maxDistance = (float) $maxDistanceInput;
        if ($maxDistance <= 0) {
            return $this->renderShowWithErrors($archeryGround, ['lane' => 'Max distance must be greater than zero.']);
        }

        $lane = new ShootingLane(
            id: $this->archeryGroundRepository->nextIdentity(),
            name: $name,
            maxDistance: $maxDistance,
        );

        $this->archeryGroundRepository->addShootingLane($archeryGround->id(), $lane);
        $this->addFlash('success', 'Lane added.');

        return $this->redirectToRoute('archery_ground_show', ['id' => $archeryGround->id()]);
    }

    #[Route('/archery-grounds/{id}/lanes/{laneId}/delete', name: 'archery_ground_delete_lane', methods: ['POST'])]
    public function deleteLane(string $id, string $laneId): Response
    {
        $this->archeryGroundRepository->removeShootingLane($laneId);
        $this->addFlash('success', 'Lane removed.');

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }

    #[Route('/archery-grounds/{id}/targets', name: 'archery_ground_add_target', methods: ['POST'])]
    public function addTarget(Request $request, string $id): Response
    {
        $archeryGround = $this->requireArcheryGround($id);
        $typeInput     = (string) $request->request->get('type', '');
        $name          = trim((string) $request->request->get('name', ''));
        $uploadedImage = $request->files->get('image');

        if ($name === '') {
            return $this->renderShowWithErrors($archeryGround, ['target' => 'Target name is required.']);
        }

        try {
            $type = TargetType::from($typeInput);
        } catch (ValueError) {
            return $this->renderShowWithErrors($archeryGround, ['target' => 'Target type is invalid.']);
        }

        if (! $uploadedImage instanceof UploadedFile) {
            return $this->renderShowWithErrors($archeryGround, ['target' => 'Please upload an image for the target.']);
        }

        if (! $uploadedImage->isValid()) {
            return $this->renderShowWithErrors($archeryGround, ['target' => 'The uploaded image could not be processed.']);
        }

        $extension         = $uploadedImage->guessExtension() ?: $uploadedImage->getClientOriginalExtension();
        $extension         = strtolower($extension);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
            return $this->renderShowWithErrors($archeryGround, ['target' => 'Image must be JPG, PNG, WEBP, or GIF.']);
        }

        $targetId  = $this->archeryGroundRepository->nextIdentity();
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/targets';
        (new Filesystem())->mkdir($uploadDir);

        $fileName = $targetId . '.' . $extension;
        $uploadedImage->move($uploadDir, $fileName);

        $target = new Target(
            id: $targetId,
            type: $type,
            name: $name,
            image: '/uploads/targets/' . $fileName,
        );

        $this->archeryGroundRepository->addTarget($archeryGround->id(), $target);
        $this->addFlash('success', 'Target added.');

        return $this->redirectToRoute('archery_ground_show', ['id' => $archeryGround->id()]);
    }

    #[Route('/archery-grounds/{id}/targets/{targetId}/delete', name: 'archery_ground_delete_target', methods: ['POST'])]
    public function deleteTarget(string $id, string $targetId): Response
    {
        $archeryGround = $this->requireArcheryGround($id);
        $imagePath     = null;

        foreach ($archeryGround->targetStorage() as $target) {
            if ($target->id() === $targetId) {
                $imagePath = $target->image();
                break;
            }
        }

        if ($imagePath !== null && str_starts_with($imagePath, '/uploads/targets/')) {
            $absolutePath = $this->getParameter('kernel.project_dir') . '/public' . $imagePath;
            (new Filesystem())->remove($absolutePath);
        }

        $this->archeryGroundRepository->removeTarget($targetId);
        $this->addFlash('success', 'Target removed.');

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }

    private function requireArcheryGround(string $id): ArcheryGround
    {
        $archeryGround = $this->archeryGroundRepository->find($id);
        if (! $archeryGround instanceof ArcheryGround) {
            throw $this->createNotFoundException('Archery ground not found.');
        }

        return $archeryGround;
    }

    /** @param array<string, string> $errors */
    private function renderShowWithErrors(ArcheryGround $archeryGround, array $errors): Response
    {
        return $this->render('archery_ground/show.html.twig', [
            'archeryGround' => $archeryGround,
            'targetTypes' => array_values(array_map(
                static fn (TargetType $type): TargetType => $type,
                TargetType::cases(),
            )),
            'errors' => $errors,
        ]);
    }
}

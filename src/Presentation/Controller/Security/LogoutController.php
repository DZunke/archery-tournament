<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Security;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function __invoke(): void
    {
        throw new LogicException('This method is intercepted by the logout firewall.');
    }
}

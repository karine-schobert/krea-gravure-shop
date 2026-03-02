<?php
// Route controlller Front/PagesController
// Mentions-légales
namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PagesController extends AbstractController
{
#[Route('/mentions-legales', name: 'front_mentions', methods: ['GET'])]
public function mentions(): Response
{
    return $this->render('front/pages/mentions.html.twig');
}

#[Route('/cgv', name: 'front_cgv', methods: ['GET'])]
public function cgv(): Response
{
    return $this->render('front/pages/cgv.html.twig');
}

#[Route('/confidentialite', name: 'front_privacy', methods: ['GET'])]
public function privacy(): Response
{
    return $this->render('front/pages/privacy.html.twig');
}

}

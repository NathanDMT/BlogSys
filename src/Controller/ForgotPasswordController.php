<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PasswordResetToken;
use App\Form\ForgotPasswordType;
use App\Service\MailService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ForgotPasswordController extends AbstractController
{

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $reset = $em->getRepository(PasswordResetToken::class)->findOneBy(['token' => $token]);

        if (!$reset || $reset->getExpiresAt() < new DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien est invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            if ($password) {
                $user = $reset->getUser();
                $user->setPassword($hasher->hashPassword($user, $password));

                $em->remove($reset); // Supprimer le token utilisé
                $em->flush();

                $this->addFlash('success', 'Mot de passe réinitialisé.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token
        ]);
    }
}

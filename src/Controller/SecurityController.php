<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'security_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user->isMustChangePassword()) {
                return $this->redirectToRoute('security_change_password');
            }
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/change-password', name: 'security_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('security_login');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($newPassword && $newPassword === $confirmPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $user->setMustChangePassword(false);
                $em->flush();

                $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
                return $this->redirectToRoute('dashboard');
            }

            $this->addFlash('error', 'Les mots de passe ne correspondent pas ou sont invalides.');
        }

        return $this->render('security/change_password.html.twig');
    }

    #[Route('/logout', name: 'security_logout', methods: ['POST'])]
    public function logout(): void
    {
        // Géré par Symfony Security
        throw new \LogicException('Ce contrôleur ne devrait pas être atteint directement.');
    }
}

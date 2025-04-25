<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/users', name: 'admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/toggle', name: 'admin_user_toggle')]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleUser(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $user->setIsBlocked(!$user->isBlocked());
        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_user_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    #[Route("/admin/user/{id}/update", name: "admin_user_update", methods: ["POST"])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Request $request,
        User $user,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $submittedToken = $request->request->get('_token');

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('admin_user_update_' . $user->getId(), $submittedToken))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setPseudo($request->request->get('pseudo'));
        $user->setEmail($request->request->get('email'));

        if ($password = $request->request->get('password')) {
            $hashed = $hasher->hashPassword($user, $password);
            $user->setPassword($hashed);
        }

        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/users/{id}/toggle-admin', name: 'admin_user_toggle_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleAdmin(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $user->setIsAdmin(!$user->isAdmin());

        if ($user->isAdmin()) {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }

        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/posts', name: 'admin_posts')]
    #[IsGranted('ROLE_ADMIN')]
    public function listPosts(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findAll();
        return $this->render('admin/posts.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/admin/posts/{id}/delete', name: 'admin_post_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deletePost(Post $post, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($post);
        $em->flush();

        return $this->redirectToRoute('admin_posts');
    }
}

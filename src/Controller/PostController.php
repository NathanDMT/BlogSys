<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Like;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

final class PostController extends AbstractController
{

    // CREATION D'UN POST
    #[Route('/post/new', name: 'app_post_new')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Définit automatiquement l'auteur
            $post->setAuthor($this->getUser());

            // Les dates sont gérées automatiquement
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // AFFICHAGE D'UN POST
    #[Route('/post/{id}', name: 'app_post_show')]
    public function show(
        int $id,
        PostRepository $postRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post introuvable.');
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($this->getUser());
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $form->createView(),
        ]);
    }

    // LIKE DE POST
    #[Route('/post/{id}/like', name: 'app_post_like', methods: ['POST'])]
    public function like(Post $post, Request $request, EntityManagerInterface $em, LikeRepository $likeRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérification du token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('like' . $post->getId(), $submittedToken)) {
            throw new InvalidCsrfTokenException('CSRF token invalide.');
        }

        $existingLike = $likeRepository->findOneBy([
            'post' => $post,
            'user' => $user,
        ]);

        if ($existingLike) {
            $em->remove($existingLike);
            $em->flush();
            $this->addFlash('success', 'Like retiré.');
        } else {
            $like = new Like();
            $like->setPost($post);
            $like->setUser($user);

            $em->persist($like);
            $em->flush();
            $this->addFlash('success', 'Article liké !');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }


    // LISTAGE DE TOUS LES POSTS
    #[Route('/post', name: 'app_post_index')]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit')]
    public function edit(
        Post $post,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Vérification que l'utilisateur connecté est l'auteur
        if ($this->getUser() !== $post->getAuthor()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres articles.');
        }

        // Création du formulaire lié à l'article existant
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Le champ updatedAt se met automatiquement grâce à @PreUpdate
            $em->flush();

            // Redirection après modification
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        // Sinon, on affiche le formulaire
        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }
}

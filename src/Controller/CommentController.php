<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/post/{id}', name: 'app_post_show', methods: ['GET', 'POST'])]
    public function show(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour commenter.');
                return $this->redirectToRoute('app_login');
            }

            $comment->setAuthor($user);
            $comment->setPost($post);
            $comment->setCreatedAt(new \DateTimeImmutable());

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire publié !');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('show.html.twig', [
            'post' => $post,
            'commentForm' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_comment_delete', methods: ['GET'])]
    public function delete(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user || ($comment->getAuthor() !== $user && !$user->isAdmin())) {
            throw $this->createAccessDeniedException("Action non autorisée.");
        }

        $postId = $comment->getPost()->getId();

        $entityManager->remove($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire supprimé.');

        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }
}
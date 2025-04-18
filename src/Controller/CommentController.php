<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/new/{id}', name: 'app_comment_new', methods: ['POST'])]
    public function new(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'utilisateur est bien connecté
            if ($this->getUser()) {
                $user = $this->getUser();
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setAuthor($user);
                $comment->setPost($post);

                // Enregistrer le commentaire
                $entityManager->persist($comment);
                $entityManager->flush();

                $this->addFlash('success', 'Commentaire ajouté avec succès.');
            } else {
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
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
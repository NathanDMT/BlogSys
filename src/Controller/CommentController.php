<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    #[Route('/comment', name: 'app_comment')]
    public function index(): Response
    {
        return $this->render('comment/index.html.twig', [
            'controller_name' => 'CommentController',
        ]);
    }

    // SUPPRIMER SON COMMENTAIRE (OU UN COMMENTAIRE SI ADMIN)
    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Comment $comment, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();

        // Protection : seul l'auteur du commentaire peut supprimer
        if (!$user || $comment->getAuthor() !== $user->getUserIdentifier()) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer ce commentaire.");
        }

        if ($this->isCsrfTokenValid('delete_comment_' . $comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire supprimé.');
        }

        return $this->redirectToRoute('app_post_show', [
            'id' => $comment->getPost()->getId(),
        ]);
    }
}

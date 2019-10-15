<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Entity\ArticleLike;
use Doctrine\ORM\EntityManager;
/*use Doctrine\DBAL\Types\TextType;*/
use App\Repository\ArticleRepository;
use App\Repository\ArticleLikeRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BlogController extends AbstractController
{
    /**
     * Permet de liker et unliker un article
     * 3 cas à gérer : utilisateur non connecté ; utilisateur connecté qui like, ou qui unlike
     *
     * @Route("/blog/{id}/like", name="article_like")
     * 
     * @param Article $article
     * @param ObjectManager $manager
     * @param ArticleLikeRepository $likeRepo
     * @return Response
     */
    public function like(Article $article, ObjectManager $manager, ArticleLikeRepository $likeRepo): Response
    {
        $user = $this->getUser();

        /* si utilisateur non connecté */
        if (!$user) return $this->json(['code' => 403, 'message' => "unauthorized"], 403);

        /* si l'article est déjà liké, on unlike (supprime le like) */
        if ($article->isLikedByUser($user)) {
            $like = $likeRepo->findOneBy([
                'article' => $article,
                'user' => $user
            ]);

            $manager->remove($like);
            $manager->flush();

            return $this->json([
                'code' => 200,
                'message' => "Like removed",
                'likes' => $likeRepo->count(['article' => $article])
            ], 200);
        }

        /* si on n'est pas passé dans le return plus haut, c'est que l'article n'est pas liké : on crée le like */

        $like = new ArticleLike();
        $like->setArticle($article)
            ->setUser($user);

        $manager->persist($like);
        $manager->flush();

        return $this->json([
            'code' => 200,
            'message' => 'Like added',
            'likes' => $likeRepo->count(['article' => $article])
        ], 200);
    }

    /**
     * @Route("/blog", name="blog")
     * route qui affiche tous les articles
     */
    public function index(ArticleRepository $repo)
    {
        $articles = $repo->findAll();

        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/", name="home")
     * route qui affiche la page d'accueil
     */
    public function home()
    {
        return $this->render('blog/home.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    /**
     * @Route("/blog/new", name="create")
     * @Route("/blog/{id}/edit", name="edit")
     * route pour le formulaire de création ou d'édition d'un article
     */
    public function form(Article $article = null, Request $request, ObjectManager $manager)
    {
        if (!$article) {
            $article = new Article();
        }

        /* créer un form bindé à l'article, sans make:form */
        // $form = $this->createFormBuilder($article)
        //     ->add('title')
        //     ->add('content')
        //     ->add('image')
        //     ->getForm();

        /* créer un form bindé à l'article, avec make:form (il est dans le dossier form) */
        $form = $this->createForm(ArticleType::class, $article);

        // analyser si le formulaire est soumis et valide
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // si l'article n'existe pas déjà
            if (!$article->getId()) {
                $article->setCreatedAt(new \DateTime());
            }

            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }

        /* passer le form à twig */
        return $this->render('blog/create.html.twig', [
            'formArticle' => $form->createView(),
            'editMode' => $article->getId() !== null
        ]);
    }

    /**
     * @Route("/blog/{id}", name="blog_show")
     * route qui affiche un article
     */
    public function show(Article $article, Request $request, ObjectManager $manager)
    {
        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime())
                ->setArticle($article);

            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView()
        ]);
    }
}

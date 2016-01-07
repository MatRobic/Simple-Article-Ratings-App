<?php

namespace IronWeb\BlogBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use IronWeb\BlogBundle\Entity\Article;
use IronWeb\BlogBundle\Form\ArticleType;
use IronWeb\BlogBundle\Entity\Comment;
use IronWeb\BlogBundle\Form\CommentType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Article controller.
 *
 * @Route("/article")
 */
class ArticleController extends Controller
{
    /**
     * Lists all Article entities.
     *
     * @Route("/", name="article_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $articles = $em->getRepository('IronWebBlogBundle:Article')->findAll();

        return $this->render('IronWebBlogBundle:article:index.html.twig', array(
            'articles' => $articles,
        ));
    }

    /**
     * Creates a new Article entity.
     *
     * @Route("/new", name="article_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $article = new Article();
        $form = $this->createForm('IronWeb\BlogBundle\Form\ArticleType', $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('iron_web_blog_article_show', array('slug' => $article->getSlug()));
        }

        return $this->render('IronWebBlogBundle:article:new.html.twig', array(
            'article' => $article,
            'form' => $form->createView(),
        ));
    }

    /**
     * Get a Article by slug
     */
    public function getArticle($slug)
    {
        $article = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('IronWebBlogBundle:Article')
                    ->findOneBySlug($slug);        
        
        return $article;
    }

    /**
     * Finds and displays a Article entity.
     *
     * @Route("/{slug}", name="article_show")
     * @Method("GET")
     */
    public function showAction($slug, Request $request)
    {
        $article = $this->getArticle($slug);       

        $comment = new Comment;
        $form = $this->createForm(new CommentType, $comment);

        if ($request->getMethod() == 'POST' && $request->isXmlHttpRequest()) {      
            $form->bind($request);

            if ($form->isValid()) {
                
                $comment->setArticle($article);  
               
                $em = $this->getDoctrine()->getManager();
                $em->persist($comment);
                $em->persist($article);
                $em->flush();
            }            
        }

        return $this->render('IronWebBlogBundle:article:show.html.twig', array(       
            'article' => $article,
            'form' => $form->createView(),
        )); 
    }

    /**
     * Generate api Article by slug
     */
    public function apiArticleAction($slug, Request $request)
    {    
        $article = $this->getArticle($slug);
        $comments = $article->getComments();
        $article->updateRating();
        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();
        $rating = $article->getRating();

        $content = array(
            'comments' => $comments,
            'rating' => $rating
        );

        $serializer = $this->container->get('serializer');
        $jsonContent = $serializer->serialize($content, 'json');
        return new Response($jsonContent);
    }

    /**
     * Displays a form to edit an existing Article entity.
     *
     * @Route("/{id}/edit", name="article_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Article $article)    {
        
        $editForm = $this->createForm('IronWeb\BlogBundle\Form\ArticleType', $article);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('iron_web_blog_article_edit', array('id' => $article->getId()));
        }

        return $this->render('IronWebBlogBundle:article:edit.html.twig', array(
            'article' => $article,
            'edit_form' => $editForm->createView()
        ));
    }

    /**
     * Deletes a Article entity.
     *
     * @Route("/{id}", name="article_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Article $article)
    {        
        $em = $this->getDoctrine()->getManager();
        $em->remove($article);
        $em->flush();

        $em = $this->getDoctrine()->getManager();

        $articles = $em->getRepository('IronWebBlogBundle:Article')->findAll();

        return $this->render('IronWebBlogBundle:article:index.html.twig', array(
            'articles' => $articles,
        ));
    }

    /**
     * Creates a form to delete a Article entity.
     *
     * @param Article $article The Article entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Article $article)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('iron_web_blog_article_delete', array('slug' => $article->getSlug())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
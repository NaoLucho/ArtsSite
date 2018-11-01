<?php
namespace SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use SiteBundle\Entity\Comment;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
class ActuController extends Controller
{

    public function ShowAction( Request $request){
        $em = $this->getDoctrine()->getManager();

        // get username
        $username = $request->get("username");
        
        //find user:
        $user = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(["username"=>$username]);
        //dump($user);

        $actus = $em->getRepository('SiteBundle:Actu')->findBy(["author" => $user]);
        
        $commentforms = [];
        if(isset($user)){

            foreach($actus as $actu){
    
                //Form comments:
                $comment = new Comment();
                $commentforms[$actu -> getId()] = $this ->createActuCommentForm($comment, $actu, $user);
                
    
    
                $commentforms[$actu -> getId()]->handleRequest($request);
                //dump($form);
                if ($commentforms[$actu -> getId()]->isSubmitted() && $commentforms[$actu -> getId()]->isValid()) {
                    $comment = $commentforms[$actu -> getId()]->getData();
                    $comment->setActu($actu);
                    $comment->setAuthor($user);
                    $em->persist($comment);
                    $em->flush();
    
                    // Adding a success type message
                    // $flashbag = $this->get('session')->getFlashBag();
                    // $flashbag->add("success", "Votre commentaire est enregistré, il est en attente de validation par le modérateur.");
                    $this->addFlash("success", "Votre commentaire est enregistré.");
    
                    //VIDER Formulaire:
                    $commentforms[$actu -> getId()] = $this ->createActuCommentForm(new Comment(), $actu, $user);
                    //dump($comment);
                    // dump($this->get('session')->getFlashBag());
                }
                $commentforms[$actu -> getId()] = $commentforms[$actu -> getId()]->createView();
            }
        }
        
        
        return $this->render('SiteBundle::actus.html.twig', array(
            'actus' => $actus,
            'formComments' => $commentforms,
          ));
    }

    private function createActuCommentForm($comment, $actu, $user){
        //$comment->setActu($actu);
        $formFactory = $this->get('form.factory');
        $f_comment_builder = $formFactory->createNamedBuilder('fcom'.$actu->getId(), 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType',$comment);
        //$f_comment_builder = $this->createFormBuilder($comment);

        $optionAuthor = ['label' => "Auteur"];
        if (isset($user)) {
            $optionAuthor = array(
                'data' => $user->getUsername(),
                'attr' => array(
                    'readonly' => true,
                    'class' => 'align-middle'
                ),
                'label' => "Auteur"
            );
        }

        $f_comment_builder//->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
        //->add('authorName', TextType::class, $optionAuthor)
        ->add('content', TextareaType::class, ['label' => 'Contenu', 'attr' => array('maxlength' => 500)])
        ->add('save', SubmitType::class, array('label' => 'Commenter',
         'attr' => array('name' => 'formcomment'.$actu->getId())));

        return $f_comment_builder->getForm();
    } 
}
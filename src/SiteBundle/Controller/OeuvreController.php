<?php
namespace SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class OeuvreController extends Controller
{

    public function listAction( Request $request){
        $em = $this->getDoctrine()->getManager();

        $numpage = 1;
        $nbbypage = 10;
        
        // get username
        $username = $request->get("username");
        //find user:
        $qb = $em->getRepository('SiteBundle:Oeuvre')
            ->createQueryBuilder('oeuvre')
            ->leftJoin('oeuvre.artist', 'artist')
            ->where('artist.username = :artist_username')
            ->setParameter('artist_username', $username);
            //->setMaxResults(10)
        // $oeuvres = $oeuvresQB->getQuery()->getResult();
        //dump($oeuvres);


        $paginItems = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
        $totalItems = count($paginItems);

        $qb->setFirstResult($nbbypage * ($numpage - 1)) // set the offset
            ->setMaxResults($nbbypage);
        $items = $qb->getQuery()->getResult();



        $pages = ceil($totalItems / $nbbypage);
        
        //current user is owner?
        $user = $this->getUser();
        
        $isOwnerAccount = false;
        if($user != null && $user->getUsername() == $username)
        {
            $isOwnerAccount = true;
        }

        return $this->render('SiteBundle::oeuvres.html.twig', array(
            'nb_total_items' => $totalItems,
            'items' => $items,
            'pages' => $pages,
            'nbbypage' => $nbbypage,
            'numpage' => $numpage,
            'request' => $request,
            'isOwnerAccount' => $isOwnerAccount
          ));
    }

    public function showAction( Request $request){
        $em = $this->getDoctrine()->getManager();
        // get username
        $username = $request->get("username");
        //find user:
        $user = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(["username"=>$username]);
        //dump($user);
        
        return $this->render('SiteBundle::presentation.html.twig', array(
            'user' => $user
          ));
    }

    public function createAction( Request $request){
        $em = $this->getDoctrine()->getManager();
        // get username
        $username = $request->get("username");
        //find user:
        $user = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(["username"=>$username]);
        //dump($user);
        //check if $user is currentUser
        $userlogged = $this->getUser();
        if($userlogged == null or $userlogged->getId() != $user->getId()){
            return $this->redirect($this->generateUrl('builder_buildpage', array('slug' => 'oeuvres')));
        }

        // if ($id == null) {
        //     $entity = new Oeuvre();
        // } else {
        //     $entity = $em->getRepository('SiteBundle:Oeuvre')->find($id);
        //     if (!$entity) {
        //         return $this->redirect($this->generateUrl('builder_buildpage', array('slug' => 'liste-oeuvres')));
        //     }
        //     if ($user != null && $entity->getArtist()->getId() != $user->getId()) {
        //         return $this->redirect($this->generateUrl('builder_buildpage', array('slug' => 'liste-oeuvres')));
        //     }
        // }
        //Load F_FORM => SERVICE getform($formName, $MODE[VIEW/CREATE/EDIT], $current_user)
        $f_form = $em
            ->getRepository('BuilderFormBundle:F_Form')
            ->findOneBy(array('name' => 'oeuvre'));

        //MODE SANS P_ProgramFormType:
        $formBuilder = $this->get('form.factory')->createBuilder(
            FormType::class,
            new Oeuvre(),
            array(
                'validation_groups' => array('default', 'dynamic')
            )
        );

        $formBuilder = FormBuilder::createForm($f_form, $formBuilder, $user, $em);

        // À partir du formBuilder, on génère le formulaire
        $form = $formBuilder->getForm();

        // $form->add(spefield)

        if ($request->isMethod('POST')) {
            //SAVE SPECIFIC FIELDS VALUES:

            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {

                    $formData = $form->getData();

                    
                    $em->persist($entity);
                    $em->flush();

                    $url = $this->generateUrl(
                        'builder_buildpageid',
                        array(
                            'slug' => 'fiche-oeuvre',
                            'id' => $entity->getId()
                        ),
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    $this->addFlash("success", "Votre oeuvre est enregistrée.");
                    return $this->redirect($this->generateUrl('builder_buildpageid', array('slug' => 'fiche-oeuvre', 'id' => $entity->getId())));
                } else {
                    $errors = $form->getErrors(true, false);
                    $errormessage = "Formulaire invalide, veuillez corriger les champs indiqués par <span class='glyphicon glyphicon-exclamation-sign'>";
                }
            }
        }

        return $this->render('SiteBundle::add-oeuvre.html.twig', array(
            'user' => $user
          ));
    }

    public function editAction( Request $request){
        $em = $this->getDoctrine()->getManager();
        // get username
        $username = $request->get("username");
        //find user:
        $user = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(["username"=>$username]);
        //dump($user);

        return $this->render('SiteBundle::presentation.html.twig', array(
            'user' => $user
          ));
    }

}
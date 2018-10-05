<?php
namespace SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ActuController extends Controller
{

    public function ShowAction( Request $request){
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
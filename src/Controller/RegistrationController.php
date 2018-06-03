<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Visit;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class RegistrationController extends Controller
{
    public function new(Request $request)
    {
        // creates a task and gives it some dummy data for this example
        $reg = new User();

        $form = $this->createFormBuilder($reg)
            ->add('firstName', TextType::class)
            ->add('secondName', TextType::class)
            ->add('email', EmailType::class)
            ->add('city', TextType::class)
            ->add('country', TextType::class)
            ->add('birthDate', BirthdayType::class)
            ->add('submit', SubmitType::class, array('label' => 'Register'))
            ->getForm();
	
	$form->handleRequest($request);

	if ($form->isSubmitted() && $form->isValid()) {

	    $user = $form->getData();
	    $user->setCreated(new \DateTime('now'));

	    $entityManager = $this->getDoctrine()->getManager();
	    $entityManager->persist($user);
	    $entityManager->flush();

	    $visit = new Visit();
	    $visit->setIp($request->getClientIp());
	    $visit->setUserId($user->getId());
	    $visit->setDate(new \DateTime('now'));
	    $entityManager->persist($visit);

	    $entityManager->flush();

	    return $this->redirectToRoute('stats');
	}

        return $this->render('registration/index.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
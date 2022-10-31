<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\FormUserType;
use Doctrine\ORM\Mapping\Id;
use App\Form\FormRegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PublicationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(Request $request,AuthenticationUtils $authenticationUtils): Response
    {
        $error=$authenticationUtils->getLastAuthenticationError();
        $username = $authenticationUtils->getLastUsername();
        return $this->render('user/signin.html.twig', [
            'error'=>$error,
            "username"=> $username
        ]);
    }

    #[Route('/registration', name: 'registration')]
    public function registration(Request $request,EntityManagerInterface $manager, UserPasswordHasherInterface $encoder): Response
    {
        $utilisateur = new User();
        $registrationForm = $this->createForm(FormRegistrationType::class, $utilisateur);
        $registrationForm->handleRequest($request);
        if($registrationForm->isSubmitted() and $registrationForm->isValid()){
            $password = $encoder->hashPassword($utilisateur, $utilisateur->getPassword());
            $utilisateur->setPassword($password);
            $utilisateur->setState(0);
            $manager->persist($utilisateur);
            $manager->flush();
            return $this->redirectToRoute('login');
         }
        return $this->render('user/registration.html.twig', [
            'formRegistration'=> $registrationForm->createView()
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(){}


    #[Route('/voyageurs', name: 'voyageurs')]
    public function Voyageurs(UserRepository $userRepository):Response{
        $voyageurs = $userRepository->findBy(
            ["state"=>"1"]
            
        );
        return $this->render('user/index.html.twig',[
            "voyageurs"=>$voyageurs
        ]);
    }

    /**
     * @Route("Détailsvoyageur/{id<[0-9]+>}", name="voyageur_détatails")
     */
    public function afficherDétailsVoyageur(Request $request, ?User $user,PublicationRepository $publicationRepository):Response{

        if($user == null){
            throw $this->createNotFoundException(" voyageur n'existe pas");
        }
        $publications = $user->getPublications();
        return $this->render('user/DétailsVoyageur.html.twig',[
            'user' => $user,
            'publications' => $publications,
        ]);
    }

    #[Route('/monCompte', name: 'compte_voyageur')]
    public function modifierCompte(Request $request,UserPasswordHasherInterface $encoder,SluggerInterface $slugger,EntityManagerInterface $manager):Response{
        //création du formulaire
        $user= $this->getUser();
        $form = $this->createForm(FormUserType::class,$user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var UploadedFile $photo */
            $photo = $form->get('photo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $photo->move(
                        $this->getParameter('voyageurs_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'photo name' property to store the PDF file name
                // instead of its contents
                $user->setImage($newFilename);
            }else{
                $user->setImage($user->getImage());
            }
            $user->setPassword($this->getUser()->getPassword());
            
            $manager->persist($user);
            $manager->flush();
            $this->addFlash("_message", "Votre compte a bien été modifier");
            return $this->redirectToRoute('compte_voyageur');
        }
        $email= $user->getEmail();
        $name= $user->getName();
        $facebook =$user->getFb();
        $inst=$user->getInstagram();
        $description=$user->getDescription();
        $state=$user->getState();
        $pays=$user->getPays();
        $publications=$user->getPublications();



        return $this->render('user/compte.html.twig',[
            'form'=>$form->createView(),
            'email'=>$email,
            'name'=>$name,
            'facebook'=>$facebook,
            'inst'=>$inst,
            'description'=>$description,
            'state'=>$state,
            'pays'=>$pays,
            'publications' => $publications,
        ]);
    }


}

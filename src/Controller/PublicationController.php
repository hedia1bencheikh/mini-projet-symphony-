<?php

namespace App\Controller;

use DateTime;
use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Form\FormCommentaireType;
use App\Form\FormPublicationType;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PublicationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PublicationController extends AbstractController
{
    /**
     * @route("/",name="Acceuil")
     */
    public function index(PublicationRepository $publicationRepository): Response
    {
        $publications = $publicationRepository->findBy(
            [],
            ["createdAt"=>"DESC"]
        );
        return $this->render('publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }
    
    /**
     * Afficher les détails d'une publication
     * @Route("/{slug<[a-z\-0-9]+>}/{id<[0-9]+>}", name="show_publication")
     */
    public function show_details(Request $request, ?Publication $publication,EntityManagerInterface $manager,CommentaireRepository $commentaireRepository): Response
    {
        if($publication == null){
            throw $this->createNotFoundException("La publication demandée n'existe pas !");
        }
        $commentaires = $commentaireRepository->findBy(
            ["publication"=> $publication],
            ["createdAt"=>"DESC"]
        );
        $commentaire=new Commentaire();
        $formCommentaire=$this->createForm(FormCommentaireType::class,$commentaire);
        $user=$publication->getUser();
        $formCommentaire->handleRequest($request);
        if($formCommentaire->isSubmitted()){
            $commentaire->setCreatedAt(new \DateTimeImmutable());
            $user = $this->getUser();
            $commentaire->setUser($user); 
            $manager->persist($commentaire);
            $manager->flush();
            $this->addFlash("_message", "Votre commentaire a bien été publiée");
            return $this->redirectToRoute("show_publication",[
                "slug"=> $request->get("slug"),
                "id"=> $publication->getId()
            ]);
        }
        return $this->render('publication/détails.html.twig', [
            'publication' => $publication,
            'formCommentaire'=>$formCommentaire->createView(),
            'user'=>$user,
            'commentaires' =>$commentaires

        ]);
    }
    
    #[Route('/createPublication', name: 'create_publication')]
    public function create(Request $request,SluggerInterface $slugger,EntityManagerInterface $manager): Response
    {
        $publication=new Publication();
        $form=$this->createForm(FormPublicationType::class,$publication);
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
                        $this->getParameter('publication_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'photo name' property to store the PDF file name
                // instead of its contents
                $publication->setImage($newFilename);
            }
            $publication->setCreatedAt(new \DateTimeImmutable());
            $publication->setLikes(0);
            $publication->setDislikes(0);
            $publication->setUser($this->getUser());
            $manager->persist($publication);
            $manager->flush();
            $this->addFlash("_message", "Votre publication a bien été publiée");
            return $this->redirectToRoute('Acceuil');
        }
        return $this->render('publication/create.html.twig', [
            'form'=>$form->createView(),
        ]);
    }



   
  
    
  
}

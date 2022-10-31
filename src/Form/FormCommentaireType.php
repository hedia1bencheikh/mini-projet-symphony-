<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class FormCommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu',null,[
                "help"=>"Si vous avez un commentaire, n'hésitez pas à nous envoyer votre avis.",
                "attr"=>["rows"=>10,"cols"=>30,"placeholder"=>"votre message"]])
            ->add('envoyer',SubmitType::class,[
                "attr"=>["class"=>"btn_submit"]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}

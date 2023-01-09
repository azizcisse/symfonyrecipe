<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use DateTimeImmutable;
use App\Form\UserPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{

    /**
     * Pour Modifier le compte de l'utilisateur étant connecté
     *
     * @param User $user
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/utilisateur/edition/{id}', name:'user.edition', methods:['GET', 'POST'])]
function edit(User $user, Request $request, EntityManagerInterface $manager): Response
    {
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_security');
    }
    if ($this->getUser() !== $user) {
        return $this->redirectToRoute('app_recipe');
    }

    $form = $this->createForm(UserType::class, $user);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if ($hasher->isPasswordValid($user, $form->getData()->getPlainPassword())) {
            $user = $form->getData();

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Compte Modifié avec Succès'
            );

        } else {
            $this->addFlash(
                'Warning',
                'Mot de passe incorrect'
            );
            return $this->redirectToRoute('app_recipe');
        }
    }

    return $this->render('pages/user/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}
 /**
     * This controller allow us to edit user's password
     *
     * @param User $choosenUser
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     */
   //#[Security("is_granted('ROLE_USER') and user === choosenUser")]
    #[Route('/utilisateur/edition-mot-de-passe/{id}', name:'user.edit.password', methods: ['GET', 'POST'])]
    public function editPassword(
        User $user,
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordHasherInterface $hasher
    ): Response {
        $form = $this->createForm(UserPasswordType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($hasher->isPasswordValid($user, $form->getData()['plainPassword'])) {
               // $user->setUpdatedAt(new DateTimeImmutable());
                $user->setPlainPassword(
                    $form->getData()['newPassword']
                );

                $this->addFlash(
                    'success',
                    'Le mot de passe a été modifié.'
                );

                $manager->persist($user);
                $manager->flush();

                return $this->redirectToRoute('app_recipe');
            } else {
                $this->addFlash(
                    'warning',
                    'Le mot de passe renseigné est incorrect.'
                );
            }
        }

        return $this->render('pages/user/edit_password.html.twig', [
            'form' => $form->createView()
        ]);
    }
}


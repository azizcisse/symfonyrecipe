<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Form\IngredientType;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IngredientController extends AbstractController
{
    /**
     * Ces fonctions servent à afficher tous les ingrédients
     *
     * @param IngredientRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
    #[IsGranted('ROLE_USER')]
#[Route('/ingredient', name:'app_ingredient', methods:['GET'])]
function index(IngredientRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {

    $ingredients = $paginator->paginate(
        $repository->findBy(['user' => $this->getUser()]),
        $request->query->getInt('page', 1),
        10
    );

    return $this->render('pages/ingredient/index.html.twig', [
        'ingredients' => $ingredients,

    ]);
}
/**
 * Pour l'ajout d'un nouveau ingrédient avec un formulaire
 *
 * @param Request $request
 * @param EntityManagerInterface $manager
 * @return Response
 */
#[Route('/ingredient/nouveau', 'ingredient.new', methods:['GET', 'POST'])]
#[IsGranted('ROLE_USER')]
function new (Request $request, EntityManagerInterface $manager): Response {
    $ingredient = new Ingredient();
    $form = $this->createForm(IngredientType::class, $ingredient);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $ingredient = $form->getData();
        $ingredient->setUser($this->getUser());

        $manager->persist($ingredient);
        $manager->flush();

        $this->addFlash(
            'success',
            'Ingrédient ajouté avec Succès!'
        );
        return $this->redirectToRoute('app_ingredient');
    }
    return $this->render('pages/ingredient/new.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Security("is_granted('ROLE_USER') and user === ingredient.getUser()")]
#[Route('/ingredient/edition/{id}', 'ingredient.edit', methods:['GET', 'POST'])]
/**
 * Pour modifier un ingrédient
 *
 * @param Ingredient $ingredient
 * @param Request $request
 * @param EntityManagerInterface $manager
 * @return Response
 */
function edit(Ingredient $ingredient, Request $request, EntityManagerInterface $manager): Response
    {
    $form = $this->createForm(IngredientType::class, $ingredient);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $ingredient = $form->getData();

        $manager->persist($ingredient);
        $manager->flush();

        $this->addFlash(
            'success',
            'Ingrédient Modifé avec Succès!'
        );
        return $this->redirectToRoute('app_ingredient');
    }

    return $this->render('pages/ingredient/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}
#[Security("is_granted('ROLE_USER') and user === ingredient.getUser()")]
#[Route('/ingredient/suppression/{id}', 'ingredient.delete', methods:['GET'])]
/**
 * Pour supprimer un ingrédient
 *
 * @param EntityManagerInterface $manager
 * @param Ingredient $ingredient
 * @return Response
 */
function delete(EntityManagerInterface $manager, Ingredient $ingredient): Response
    {
    if (!$ingredient) {
        $this->addFlash(
            'success',
            'Ingrédient Non Trouvé!'
        );
        return $this->redirectToRoute('app_ingredient');
    }
    $manager->remove($ingredient);
    $manager->flush();

    $this->addFlash(
        'success',
        'Ingrédient Supprimé!'
    );
    return $this->redirectToRoute('app_ingredient');
}
}

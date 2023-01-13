<?php

namespace App\Controller;

use App\Entity\Mark;
use App\Entity\Recipe;
use App\Form\MarkType;
use App\Form\RecipeType;
use App\Repository\MarkRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class RecipeController extends AbstractController
{
    /**
     * Liste des fonctions pour afficher les recettes
     *
     * @param RecipeRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
#[Route('/recipe', name:'app_recipe', methods:['GET'])]
#[IsGranted('ROLE_USER')]
function index(RecipeRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
    $recipes = $paginator->paginate(
        $repository->findBy(['user' => $this->getUser()]),
        $request->query->getInt('page', 1),
        10
    );

    return $this->render('pages/recipe/index.html.twig', [
        'recipes' => $recipes,
    ]);
}

/**
 * Afficher Recette pour tous les utilisateurs qui detiennent une compte
 *
 * @return Response
 */
#[IsGranted('ROLE_USER')]
#[Route("/recipe/publique", name:"recipe.index.public", methods:['GET'])]
function indexPublic(
    RecipeRepository $repository,
    PaginatorInterface $paginator,
    Request $request
): Response {
    $recipes = $paginator->paginate(
        $repository->findPublicRecipe(null),
        $request->query->getInt('page', 1),
        10
    );
    return $this->render('pages/recipe/index_public.html.twig', [
        'recipes' => $recipes,
    ]);
}

/**
 * Pour voir voir une recette
 *
 * @param Recipe $recipe
 * @return Response
 */
#[Security("is_granted('ROLE_USER') and (recipe.getIsPublic() === true || user === recipe.getUser())")]
#[Route("/recipe/{id}", name:"recipe.show", methods:['GET', 'POST'])]
function show(
    MarkRepository $markRepository,
    Recipe $recipe,
    Request $request,
    EntityManagerInterface $manager
): Response {
    $mark = new Mark();
    $form = $this->createForm(MarkType::class, $mark);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $mark->setUser($this->getUser())
            ->setRecipe($recipe);

        $existingMark = $markRepository->findOneBy(
            [
                'user' => $this->getUser(),
                'recipe' => $recipe,
            ]);
        if (!$existingMark) {
            $manager->persist($mark);
        }else {
            $existingMark->setMark(
                 $form->getData()->getMark()
            );
        }
        $manager->flush();
        $this->addFlash(
            'success',
            'La note a bien été ajouté prise en compte.'
        );
        return $this->redirectToRoute('recipe.show', ['id' => $recipe->getId()]);
    }
    return $this->render('pages/recipe/show.html.twig', [
        'recipe' => $recipe,
        'form' => $form->createView(),
    ]);
}
/**
 * Formulaire de creation de recette avec ces fonctions
 *
 * @param Request $request
 * @param EntityManagerInterface $manager
 * @return Response
 */
#[IsGranted('ROLE_USER')]
#[Route('/recipe/creation', name:'recipe.new', methods:['GET', 'POST'])]
function new (Recipe $recipe, Request $request, EntityManagerInterface $manager): Response {
    $recipe = new Recipe();
    $form = $this->createForm(RecipeType::class, $recipe);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $recipe = $form->getData();
        $recipe->setUser($this->getUser());

        $manager->persist($recipe);
        $manager->flush();

        $this->addFlash(
            'success',
            'Recette ajoutée avec succès !'
        );

        return $this->redirectToRoute('app_recipe');
    }

    return $this->render('pages/recipe/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
/**
 * Pour Modifier une recette
 *
 * @param Recipe $recipe
 * @param Request $request
 * @param EntityManagerInterface $manager
 * @return Response
 */
#[Route('/recipe/edition/{id}', 'recipe.edit', methods:['GET', 'POST'])]
#[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
function edit(Recipe $recipe, Request $request, EntityManagerInterface $manager): Response
    {
    $form = $this->createForm(RecipeType::class, $recipe);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $recipe = $form->getData();

        $manager->persist($recipe);
        $manager->flush();

        $this->addFlash(
            'success',
            'Recette Modifé avec Succès!'
        );
        return $this->redirectToRoute('app_recipe');
    }

    return $this->render('pages/recipe/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}

/**
 * Pour supprimer une recette
 *
 * @param EntityManagerInterface $manager
 * @param Recipe $recipe
 * @return Response
 */
#[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
#[Route('/recipe/suppression/{id}', 'recipe.delete', methods:['GET'])]
function delete(EntityManagerInterface $manager, Recipe $recipe): Response
    {
    if (!$recipe) {
        $this->addFlash(
            'success',
            'Recette Non Trouvé!'
        );
        return $this->redirectToRoute('app_recipe');
    }
    $manager->remove($recipe);
    $manager->flush();

    $this->addFlash(
        'success',
        'Recette Supprimé!'
    );
    return $this->redirectToRoute('app_recipe');
}
}

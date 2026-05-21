<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[IsGranted('ROLE_USER')]
final class CategoryController extends AbstractController
{
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
        
        $searchQuery = $request->query->get('search', '');

        // Both Admin and Staff can view ALL records
        if (!empty($searchQuery)) {
            $categories = $categoryRepository->search($searchQuery, null);
        } else {
            $categories = $categoryRepository->findAll();
        }

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
            'searchQuery' => $searchQuery,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure createdBy is set (in case form processing cleared it)
            $category->setCreatedBy($user);
            $entityManager->persist($category);
            $entityManager->flush();
            
            // Log the activity
            $activityLogService->logCategoryCreated($user, $category);
            
            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());

        // Both Admin and Staff can view all categories
        return $this->render('category/show.html.twig', [
            'category' => $category,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService
    ): Response {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
        
        // Staff restrictions: cannot edit admin records or other staff records
        if (!$isAdmin) {
            $categoryCreator = $category->getCreatedBy();
            if ($categoryCreator === null) {
                $this->addFlash('error', 'You do not have permission to edit this category.');
                return $this->redirectToRoute('app_category_index');
            }
            
            // Check if creator is admin
            $creatorIsAdmin = in_array('ROLE_ADMIN', $categoryCreator->getRoles());
            if ($creatorIsAdmin) {
                $this->addFlash('error', 'You cannot edit admin records.');
                return $this->redirectToRoute('app_category_index');
            }
            
            // Check if creator is another staff member
            if ($categoryCreator !== $user) {
                $this->addFlash('error', 'You can only edit your own records.');
                return $this->redirectToRoute('app_category_index');
            }
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            // Log the activity
            $activityLogService->logCategoryUpdated($user, $category);
            
            $this->addFlash('success', 'Category updated successfully!');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService
    ): Response {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
        
        // Staff restrictions: cannot delete admin records or other staff records
        if (!$isAdmin) {
            $categoryCreator = $category->getCreatedBy();
            if ($categoryCreator === null) {
                $this->addFlash('error', 'You do not have permission to delete this category.');
                return $this->redirectToRoute('app_category_index');
            }
            
            // Check if creator is admin
            $creatorIsAdmin = in_array('ROLE_ADMIN', $categoryCreator->getRoles());
            if ($creatorIsAdmin) {
                $this->addFlash('error', 'You cannot delete admin records.');
                return $this->redirectToRoute('app_category_index');
            }
            
            // Check if creator is another staff member
            if ($categoryCreator !== $user) {
                $this->addFlash('error', 'You can only delete your own records.');
                return $this->redirectToRoute('app_category_index');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            // Log the activity before deletion
            $activityLogService->logCategoryDeleted($user, $category);
            
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Category deleted successfully!');
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}

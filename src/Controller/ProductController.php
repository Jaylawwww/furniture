<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/product')]
#[IsGranted('ROLE_USER')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
        
        $searchQuery = $request->query->get('search', '');
        $categoryId = $request->query->get('category');
        $categoryId = $categoryId ? (int)$categoryId : null;

        // Both Admin and Staff can view ALL records
        if (!empty($searchQuery) || $categoryId !== null) {
            $products = $productRepository->search($searchQuery, $categoryId, null);
        } else {
            $products = $productRepository->createQueryBuilder('p')
                ->leftJoin('p.Category', 'c')->addSelect('c')
                ->leftJoin('p.createdBy', 'u')->addSelect('u')
                ->orderBy('p.id', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Get categories for filter dropdown (both admin and staff see all)
        $categories = $categoryRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'searchQuery' => $searchQuery,
            'selectedCategoryId' => $categoryId,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Get extension from original filename (doesn't require fileinfo extension)
                $extension = strtolower($imageFile->getClientOriginalExtension());
                
                // Validate extension is an image type
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $allowedExtensions)) {
                    $this->addFlash('error', 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.');
                    $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
                    return $this->render('product/new.html.twig', [
                        'product' => $product,
                        'form' => $form,
                        'isAdmin' => $isAdmin,
                    ]);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/img',
                        $newFilename
                    );
                    $product->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            // Ensure createdBy is set (in case form processing cleared it)
            $product->setCreatedBy($user);
            $entityManager->persist($product);
            $entityManager->flush();

            // Log the activity
            $activityLogService->logProductCreated($user, $product);

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());

        // Both Admin and Staff can view all products
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService
    ): Response {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
        
        // Staff restrictions: cannot edit admin records or other staff records
        if (!$isAdmin) {
            $productCreator = $product->getCreatedBy();
            if ($productCreator === null) {
                $this->addFlash('error', 'You do not have permission to edit this product.');
                return $this->redirectToRoute('app_product_index');
            }
            
            // Check if creator is admin
            $creatorIsAdmin = in_array('ROLE_ADMIN', $productCreator->getRoles());
            if ($creatorIsAdmin) {
                $this->addFlash('error', 'You cannot edit admin records.');
                return $this->redirectToRoute('app_product_index');
            }
            
            // Check if creator is another staff member
            if ($productCreator !== $user) {
                $this->addFlash('error', 'You can only edit your own records.');
                return $this->redirectToRoute('app_product_index');
            }
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        // Create delete form
        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_product_delete', ['id' => $product->getId()]))
            ->setMethod('POST')
            ->getForm();

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Delete old image if it exists
                $oldImage = $product->getImage();
                if ($oldImage && file_exists($this->getParameter('kernel.project_dir') . '/public/img/' . $oldImage)) {
                    unlink($this->getParameter('kernel.project_dir') . '/public/img/' . $oldImage);
                }

                // Get extension from original filename (doesn't require fileinfo extension)
                $extension = strtolower($imageFile->getClientOriginalExtension());
                
                // Validate extension is an image type
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $allowedExtensions)) {
                    $this->addFlash('error', 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.');
                    return $this->render('product/edit.html.twig', [
                        'product' => $product,
                        'form' => $form,
                        'delete_form' => $deleteForm,
                        'isAdmin' => $isAdmin,
                    ]);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/img',
                        $newFilename
                    );
                    $product->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            $entityManager->flush();
            
            // Log the activity
            $activityLogService->logProductUpdated($user, $product);
            
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
            'delete_form' => $deleteForm,
            'isAdmin' => $isAdmin,
        ]);
    }


    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService
    ): Response {
        $user = $this->getUser();
        $isAdmin = $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
        
        // Staff restrictions: cannot delete admin records or other staff records
        if (!$isAdmin) {
            $productCreator = $product->getCreatedBy();
            if ($productCreator === null) {
                $this->addFlash('error', 'You do not have permission to delete this product.');
                return $this->redirectToRoute('app_product_index');
            }
            
            // Check if creator is admin
            $creatorIsAdmin = in_array('ROLE_ADMIN', $productCreator->getRoles());
            if ($creatorIsAdmin) {
                $this->addFlash('error', 'You cannot delete admin records.');
                return $this->redirectToRoute('app_product_index');
            }
            
            // Check if creator is another staff member
            if ($productCreator !== $user) {
                $this->addFlash('error', 'You can only delete your own records.');
                return $this->redirectToRoute('app_product_index');
            }
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $token)) {
            // Log the activity before deletion
            $activityLogService->logProductDeleted($user, $product);
            
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}

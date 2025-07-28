<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product')]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
        ]);
    }

    #[Route('/product/new', name: 'app_product_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Získáme nahrané soubory z nemapovaného pole
            $imageFiles = $form->get('imageFiles')->getData();

            // Projdeme všechny nahrané soubory
            foreach ($imageFiles as $imageFile) {
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    // "Očistíme" název souboru pro bezpečné použití v URL
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        // Přesuneme soubor do adresáře, který jsme si definovali
                        $imageFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // Zde můžeš zpracovat chybu, pokud se nahrávání nepovede
                        $this->addFlash('error', 'Obrázek se nepodařilo nahrát.');
                    }

                    // Vytvoříme novou entitu Image
                    $image = new Image();
                    $image->setFilename($newFilename);

                    // Propojíme obrázek s produktem
                    $product->addImage($image);
                }
            }

            // Uložíme produkt (a díky cascade:['persist'] i všechny nové obrázky)
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produkt byl úspěšně vytvořen!');

            // Přesměrujeme například na detail produktu (zatím nemáme, tak na hlavní stránku)
            return $this->redirectToRoute('app_product_new');
        }
        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);

    }
}

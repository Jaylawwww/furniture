<?php

namespace App\Controller;

use App\Form\ContactMessageType;
use App\Service\ContactMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, ContactMailService $contactMailService): Response
    {
        $category = (string) $request->query->get('category', '');
        $allowed = ['support', 'visit', 'business'];
        $defaults = \in_array($category, $allowed, true) ? ['category' => $category] : [];

        $form = $this->createForm(ContactMessageType::class, $defaults);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Simple honeypot anti-spam: if filled, pretend success.
            $website = (string) ($form->get('website')->getData() ?? '');
            if (trim($website) !== '') {
                $this->addFlash('success', 'Thanks! Your message has been sent.');
                return new RedirectResponse($this->generateUrl('app_contact') . '#message');
            }

            $data = $form->getData();

            try {
                $contactMailService->send([
                    'category' => (string) $data['category'],
                    'name' => (string) $data['name'],
                    'email' => (string) $data['email'],
                    'subject' => (string) $data['subject'],
                    'message' => (string) $data['message'],
                ]);

                $this->addFlash('success', 'Thanks! Your message has been sent. We’ll get back to you soon.');
                return new RedirectResponse($this->generateUrl('app_contact') . '#message');
            } catch (\Throwable) {
                $this->addFlash('error', 'Sorry—something went wrong sending your message. Please try again in a moment.');
                return new RedirectResponse($this->generateUrl('app_contact') . '#message');
            }
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }

    #[Route('/contact/support', name: 'app_contact_support')]
    public function support(): Response
    {
        return $this->render('contact/support.html.twig');
    }

    #[Route('/contact/visit', name: 'app_contact_visit')]
    public function visit(): Response
    {
        return $this->render('contact/visit.html.twig');
    }

    #[Route('/contact/business', name: 'app_contact_business')]
    public function business(): Response
    {
        return $this->render('contact/business.html.twig');
    }
}


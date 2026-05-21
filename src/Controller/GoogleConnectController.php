<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleConnectController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(Request $request, ClientRegistry $clientRegistry): RedirectResponse
    {
        // Used only to control UI messaging after OAuth returns.
        // Example values: "login", "register"
        $source = (string) $request->query->get('source', 'login');
        $session = $request->getSession();
        $session->start();
        $session->set('google_oauth_source', $source);

        // Always show Google's account picker (avoids silent auto-login with prompt=none).
        return $clientRegistry
            ->getClient('google')
            ->redirect(
                ['openid', 'email', 'profile'],
                ['prompt' => 'select_account']
            );
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(): Response
    {
        // This route is handled by the OAuth2 authenticator.
        // It only exists so Symfony routing can set the `_route` attribute.
        return new Response('');
    }
}


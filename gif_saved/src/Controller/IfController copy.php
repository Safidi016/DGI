<?php
// src/Controller/IfController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class IfController extends AbstractController
{
    /**
     * @Route("/if/{token}", name="if_info")
     */
    public function index(string $token, Request $request)
    {
        $apiUrl = "https://portal.impots.mg/databridge/hetraonline/if/infos";
        
        try {
            // Configuration spéciale pour contourner les problèmes de certificat
            $client = HttpClient::create([
                'verify_peer' => false,
                'verify_host' => false,
                'timeout' => 15
            ]);
            
            $response = $client->request('GET', $apiUrl, [
                'query' => [
                    'tk' => $token,
                    'token' => $token
                ]
            ]);
            
            // Vérification du code de statut
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return new JsonResponse([
                    'error' => 'Erreur API',
                    'message' => "L'API a retourné le code $statusCode"
                ], $statusCode);
            }
            
            // Retourne la réponse de l'API
            return new JsonResponse($response->toArray());
            
        } catch (TransportExceptionInterface $e) {
            // Erreur de connexion
            return new JsonResponse([
                'error' => 'Erreur de connexion',
                'message' => 'Impossible de se connecter au serveur des impôts'
            ], 502);
            
        } catch (\Exception $e) {
            // Autres erreurs
            return new JsonResponse([
                'error' => 'Erreur interne',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
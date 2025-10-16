<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AvisIfController extends AbstractController
{
    private $client;
    private $logger;
    private $apiUrl;
    private $apiKey;
    private $recaptchaSecret;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->apiUrl = $_ENV['API_IMPO_MG_URL'];
        $this->apiKey = $_ENV['API_IMPO_MG_KEY'];
        $this->recaptchaSecret = $_ENV['RECAPTCHA_SECRET'];
    }

    /**
     * @Route("/avis-if", name="avis_if_index")
     */
    public function index(): Response
    {
        return $this->render('avis_if/index.html.twig');
    }

    /**
     * @Route("/avis-if/search", name="avis_if_search", methods={"POST"})
     */
    public function search(Request $request): JsonResponse
    {
        // Vérification reCAPTCHA
        $recaptchaResponse = $request->request->get('g-recaptcha-response');
        if (!$this->verifyRecaptcha($recaptchaResponse, $request->getClientIp())) {
            return $this->json([
                'status' => 'error',
                'message' => 'Validation reCAPTCHA échouée. Veuillez prouver que vous n\'êtes pas un robot.'
            ], 400);
        }

        $numAvis = trim($request->request->get('num_avis'));

        if (empty($numAvis)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Le numéro d\'avis est requis'
            ], 400);
        }

        try {
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => [
                    'key' => $this->apiKey,
                    'avis' => $numAvis
                ],
                'timeout' => 15,
                'verify_peer' => false
            ]);

            $data = $response->toArray();

            if (!isset($data['data']) || !is_array($data['data'])) {
                throw new \Exception('Structure de données API invalide');
            }

            $formattedData = array_map(function($item) {
                return [
                    'num_avis' => $item['NUM_AVIS'] ?? 'N/A',
                    'nom' => $item['NOM_REDEVABLE'] ?? 'N/A',
                    'adresse' => $item['ADRESSE_REDEVABLE'] ?? 'N/A',
                    'montant' => isset($item['MONTANT_APAYER']) ? 
                        number_format($item['MONTANT_APAYER'], 0, ',', ' ') : '0',
                    'annee' => $item['ANNEE_EXO'] ?? 'N/A',
                    'adresse_bien' => $item['ADRESSE_BIEN'] ?? 'N/A',
                    'commune' => $item['COMMUNE'] ?? 'N/A',
                    'centrefiscal' => $item['CF_BENEFICIAIRE'] ?? 'N/A',
                    'cin' => $item['CIN_REDEVABLE'] ?? 'N/A',
                    'numimpot' => $item['NUM_IMPOT'] ?? 'N/A'
                ];
            }, $data['data']);

            return $this->json([
                'status' => 'success',
                'data' => $formattedData,
                'draw' => (int) $request->request->get('draw', 1),
                'recordsTotal' => count($formattedData),
                'recordsFiltered' => count($formattedData),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur API: '.$e->getMessage());
            
            return $this->json([
                'status' => 'error',
                'message' => 'Vérifier le numéro d\'avis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie la réponse reCAPTCHA avec Google
     */
    private function verifyRecaptcha(string $recaptchaResponse, string $remoteIp): bool
    {
        try {
            $response = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->recaptchaSecret,
                    'response' => $recaptchaResponse,
                    'remoteip' => $remoteIp
                ],
                'timeout' => 5
            ]);

            $content = $response->toArray();
            return $content['success'] ?? false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur reCAPTCHA: '.$e->getMessage());
            return false;
        }
    }
}
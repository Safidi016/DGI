<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class IfController extends AbstractController
{
    /**
     * @Route("/if/{token}", name="if_info")
     */
    public function index(string $token): Response
    {
        $apiUrl = "https://portal.impots.mg/databridge/hetraonline/if/infos";
        
        try {
            $client = HttpClient::create([
                'verify_peer' => false,
                'verify_host' => false,
                'timeout' => 15
            ]);
            
            $response = $client->request('GET', $apiUrl, [
                'query' => ['tk' => $token]
            ]);
            
            if ($response->getStatusCode() !== 200) {
               // throw new \Exception("Erreur API: Statut ".$response->getStatusCode());
               throw new \Exception("Numéro d'avis d'imposition incorrect.");
            }

            $apiData = $response->toArray();
            
            // Formatage exact comme demandé
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
            }, $apiData['data']);

            return $this->render('if/index.html.twig', [
                'donnees' => $formattedData,
                'token' => $token
            ]);
            
        } catch (TransportExceptionInterface $e) {
            return $this->render('if/error.html.twig', [
                'error_message' => 'Erreur de connexion: '.$e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->render('if/error.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }
    }
}
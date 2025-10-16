<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ApiController extends AbstractController
{
    
    #[Route('/recherche-avis', name: 'recherche_avis', methods: ['GET', 'POST'])]

    public function recherche(Request $request)
    {
        // Création du formulaire pour entrer le numéro d'avis
        $form = $this->createFormBuilder()
            ->add('num_avis', TextType::class, [
                'label' => 'Numéro d\'avis',
                'required' => true,
            ])
            ->add('Rechercher', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        $resultat = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $numAvis = $form->get('num_avis')->getData();

            $client = HttpClient::create();

            try {
                $response = $client->request('GET', 'https://portal.impots.mg/databridge/hetraonline/if/avisif', [
                    'headers' => [
                        'Accept' => 'application/json',
                        'x-api-key' => 'KjMxxr0lfco6Fcahd8zwXveW4LhC2q',
                    ],
                    'query' => [
                        'avis' => $numAvis,
                    ],
                ]);

                $data = $response->toArray();

                // Optionnel : si l'API renvoie une liste, prendre le premier élément
                $resultat = is_array($data) && isset($data[0]) ? $data[0] : $data;

            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur API : ' . $e->getMessage());
            }
        }

        return $this->render('api/recherche.html.twig', [
            'form' => $form->createView(),
            'avis' => $resultat,
        ]);
    }
}

<?php
// src/Controller/IrsaController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IrsaController extends AbstractController
{
    #[Route('/simulateur-irsa', name: 'simulateur_irsa')]
    public function index(Request $request): Response
    {
        $result = null;
        $form = $this->createFormBuilder()
            ->add('salaire_brut', NumberType::class, [
                'label' => 'Salaire brut mensuel (MGA)',
                'required' => true,
                'invalid_message' => 'Veuillez entrer un nombre valide',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => 1000,
                    'placeholder' => 'Ex: 500000'
                ],
                'html5' => true
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $salaireBrut = (float)$form->get('salaire_brut')->getData();
                $result = $this->calculerIrsa($salaireBrut);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur de calcul: '.$e->getMessage());
            }
        }

        return $this->render('irsa/simulateur.html.twig', [
            'form' => $form->createView(),
            'result' => $result
        ]);
    }

    private function calculerIrsa(float $salaireBrutMensuel): array
    {
        if ($salaireBrutMensuel <= 0) {
            throw new \InvalidArgumentException("Le salaire doit être positif");
        }

        // Cotisations sociales (CNaPS 1% + OSTIE 1%)
        $cotisations = $salaireBrutMensuel * 0.02;
        $salaireNetImposableMensuel = $salaireBrutMensuel - $cotisations;
        $salaireNetImposableAnnuel = $salaireNetImposableMensuel * 12;

        // Barème IRSA 2024 (seuils annuels)
        $bareme = [
            ['seuil' => 350000, 'taux' => 0.00],
            ['seuil' => 400000, 'taux' => 0.05],
            ['seuil' => 500000, 'taux' => 0.10],
            ['seuil' => 600000, 'taux' => 0.15],
            ['seuil' => PHP_INT_MAX, 'taux' => 0.20]
        ];

        $irsaAnnuel = 0;
        $resteImposable = $salaireNetImposableAnnuel;
        $previousSeuil = 0;

        foreach ($bareme as $tranche) {
            if ($resteImposable <= 0) break;

            $montantTranche = min($tranche['seuil'] - $previousSeuil, $resteImposable);
            $irsaAnnuel += $montantTranche * $tranche['taux'];
            $resteImposable -= $montantTranche;
            $previousSeuil = $tranche['seuil'];
        }

        $irsaMensuel = $irsaAnnuel / 12;

        return [
            'salaire_brut' => $salaireBrutMensuel,
            'cotisations' => $cotisations,
            'salaire_net_imposable' => $salaireNetImposableMensuel,
            'irsa_mensuel' => $irsaMensuel,
            'irsa_annuel' => $irsaAnnuel,
            'salaire_net' => $salaireNetImposableMensuel - $irsaMensuel,
            'taux_effectif' => ($salaireNetImposableAnnuel > 0) ? ($irsaAnnuel / $salaireNetImposableAnnuel) * 100 : 0
        ];
    }
}
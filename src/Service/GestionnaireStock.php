<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Materiel;
use App\Entity\MaterielIntervention;
use App\Exception\QuantiteInvalideException;
use App\Exception\StockInsuffisantException;

/**
 * Applique la règle de gestion RG4 : la quantité de matériel affectée à une intervention
 * ne peut jamais excéder le stock disponible.
 *
 * Cette logique est isolée du contrôleur pour deux raisons : elle doit s'appliquer quelle que
 * soit l'interface qui déclenche l'affectation (formulaire web aujourd'hui, API demain), et
 * elle doit pouvoir être vérifiée unitairement, sans base de données ni requête HTTP.
 *
 * Le service ne persiste rien : il modifie les entités en mémoire. Le flush reste
 * de la responsabilité de l'appelant.
 */
final class GestionnaireStock
{
    /**
     * Agrège les quantités demandées par matériel.
     *
     * L'agrégation est indispensable : une même référence peut apparaître sur plusieurs lignes
     * d'une même intervention. Valider chaque ligne isolément laisserait passer deux lignes de
     * 3 unités sur un stock de 5, et produirait un stock négatif après déduction.
     *
     * Les entrées sont indexées par identifiant d'objet et non par identifiant de base, afin que
     * la méthode fonctionne sur des entités non encore persistées — condition nécessaire aux
     * tests unitaires.
     *
     * @param iterable<MaterielIntervention> $lignes
     *
     * @return array<int, array{materiel: Materiel, quantite: int}>
     */
    public function agreger(iterable $lignes): array
    {
        $agrege = [];

        foreach ($lignes as $ligne) {
            $materiel = $ligne->getMateriel();

            if (null === $materiel) {
                continue;
            }

            $cle = spl_object_id($materiel);

            if (!isset($agrege[$cle])) {
                $agrege[$cle] = ['materiel' => $materiel, 'quantite' => 0];
            }

            $agrege[$cle]['quantite'] += $ligne->getQuantite();
        }

        return $agrege;
    }

    /**
     * Restitue les quantités précédemment réservées, puis déduit les nouvelles.
     *
     * Déroulé en trois temps, dans cet ordre impératif :
     *
     * 1. calcul du stock réellement disponible, égal au stock courant augmenté des quantités
     *    que cette intervention avait déjà réservées ;
     * 2. validation intégrale de la demande contre ce stock disponible ;
     * 3. application, uniquement si l'étape 2 est entièrement passée.
     *
     * La restitution de l'étape 1 est ce qui empêche une double déduction : sans elle, faire
     * passer une affectation de 3 à 4 unités retirerait 4 unités supplémentaires au lieu d'une.
     *
     * La séparation entre validation et application garantit l'absence d'écriture partielle :
     * si une seule ligne est invalide, aucun stock n'est modifié.
     *
     * Un matériel présent dans $anciennes mais absent des nouvelles lignes est intégralement
     * restitué : c'est le cas d'un retrait de matériel lors d'une modification d'affectation.
     *
     * @param array<int, array{materiel: Materiel, quantite: int}> $anciennes Instantané produit
     *                                                                        par agreger() AVANT
     *                                                                        traitement du formulaire
     * @param iterable<MaterielIntervention>                       $nouvelles Lignes issues du formulaire
     *
     * @throws QuantiteInvalideException Si une quantité demandée est nulle ou négative.
     *                                   Aucun stock n'est modifié.
     * @throws StockInsuffisantException Si une quantité demandée dépasse le stock disponible.
     *                                   Aucun stock n'est modifié.
     */
    public function appliquer(array $anciennes, iterable $nouvelles): void
    {
        $demandes = $this->agreger($nouvelles);

        // Étape 1 — stock disponible, réservations de cette intervention réintégrées
        $materiels = [];
        foreach ($anciennes as $cle => $entree) {
            $materiels[$cle] = $entree['materiel'];
        }
        foreach ($demandes as $cle => $entree) {
            $materiels[$cle] = $entree['materiel'];
        }

        $disponible = [];
        foreach ($materiels as $cle => $materiel) {
            $disponible[$cle] = $materiel->getQuantiteStock() + ($anciennes[$cle]['quantite'] ?? 0);
        }

        // Étape 2 — validation complète avant toute écriture
        foreach ($demandes as $cle => $entree) {
            if ($entree['quantite'] <= 0) {
                throw new QuantiteInvalideException($entree['materiel'], $entree['quantite']);
            }

            if ($entree['quantite'] > $disponible[$cle]) {
                throw new StockInsuffisantException($entree['materiel'], $entree['quantite'], $disponible[$cle]);
            }
        }

        // Étape 3 — application
        foreach ($materiels as $cle => $materiel) {
            $materiel->setQuantiteStock($disponible[$cle] - ($demandes[$cle]['quantite'] ?? 0));
        }
    }

    /**
     * Restitue au stock l'intégralité des quantités réservées par une intervention.
     *
     * Utilisé lors de l'annulation d'une intervention planifiée : le matériel réservé
     * redevient disponible pour d'autres interventions.
     *
     * @param iterable<MaterielIntervention> $lignes
     */
    public function restituer(iterable $lignes): void
    {
        foreach ($this->agreger($lignes) as $entree) {
            $entree['materiel']->setQuantiteStock(
                $entree['materiel']->getQuantiteStock() + $entree['quantite'],
            );
        }
    }
}

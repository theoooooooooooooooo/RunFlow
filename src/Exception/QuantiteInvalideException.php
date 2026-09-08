<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Materiel;

/**
 * Levée lorsqu'une quantité de matériel demandée est nulle ou négative.
 *
 * Une contrainte de validation existe déjà sur le formulaire ; ce contrôle est un second
 * verrou côté métier, appliqué quelle que soit l'origine de la demande (style défensif).
 */
final class QuantiteInvalideException extends \RuntimeException
{
    public function __construct(
        private readonly Materiel $materiel,
        private readonly int $quantite,
    ) {
        parent::__construct(sprintf(
            'Quantité invalide pour "%s" : %d. La quantité doit être strictement positive.',
            $materiel->getNom(),
            $quantite,
        ));
    }

    public function getMateriel(): Materiel
    {
        return $this->materiel;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }
}

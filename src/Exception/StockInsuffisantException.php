<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Materiel;

/**
 * Levée lorsqu'une affectation de matériel dépasse le stock disponible.
 *
 * Aucune modification de stock n'a été appliquée lorsque cette exception est levée :
 * la validation est intégralement effectuée avant la moindre écriture (RG4).
 */
final class StockInsuffisantException extends \RuntimeException
{
    public function __construct(
        private readonly Materiel $materiel,
        private readonly int $quantiteDemandee,
        private readonly int $quantiteDisponible,
    ) {
        parent::__construct(sprintf(
            'Stock insuffisant pour "%s" (demandé : %d, disponible : %d).',
            $materiel->getNom(),
            $quantiteDemandee,
            $quantiteDisponible,
        ));
    }

    public function getMateriel(): Materiel
    {
        return $this->materiel;
    }

    public function getQuantiteDemandee(): int
    {
        return $this->quantiteDemandee;
    }

    public function getQuantiteDisponible(): int
    {
        return $this->quantiteDisponible;
    }
}

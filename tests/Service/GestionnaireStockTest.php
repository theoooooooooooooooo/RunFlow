<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Materiel;
use App\Entity\MaterielIntervention;
use App\Exception\QuantiteInvalideException;
use App\Exception\StockInsuffisantException;
use App\Service\GestionnaireStock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la règle de gestion RG4 : la quantité affectée ne peut jamais
 * excéder le stock disponible, et une modification d'affectation ne doit pas déduire
 * une seconde fois les quantités déjà réservées.
 *
 * Ces tests sont unitaires au sens strict : ils étendent TestCase et non KernelTestCase,
 * n'ouvrent aucune connexion à la base et ne démarrent pas le noyau Symfony. Ils vérifient
 * la logique métier isolée, en quelques millisecondes.
 */
#[CoversClass(GestionnaireStock::class)]
final class GestionnaireStockTest extends TestCase
{
    private GestionnaireStock $gestionnaire;

    protected function setUp(): void
    {
        $this->gestionnaire = new GestionnaireStock();
    }

    public function testDeductionSimpleRetireLaQuantiteDemandee(): void
    {
        // Arrange — un joint en stock à 10 unités, aucune réservation antérieure
        $joint = $this->creerMateriel('Joint torique', 10);
        $lignes = [$this->creerLigne($joint, 3)];

        // Act
        $this->gestionnaire->appliquer([], $lignes);

        // Assert
        self::assertSame(7, $joint->getQuantiteStock());
    }

    public function testModificationRestitueAvantDeDeduire(): void
    {
        // Arrange — 3 unités déjà réservées sur un stock affiché à 7 (10 à l'origine)
        $joint = $this->creerMateriel('Joint torique', 7);
        $anciennes = $this->gestionnaire->agreger([$this->creerLigne($joint, 3)]);

        // Act — l'administrateur passe la quantité de 3 à 4
        $this->gestionnaire->appliquer($anciennes, [$this->creerLigne($joint, 4)]);

        // Assert — une seule unité supplémentaire est retirée, pas quatre.
        // C'est le bug historique du projet : sans restitution, le stock tombait à 3.
        self::assertSame(6, $joint->getQuantiteStock());
    }

    public function testStockInsuffisantLeveUneExceptionEtNeModifieRien(): void
    {
        // Arrange — 3 unités disponibles, 5 demandées
        $joint = $this->creerMateriel('Joint torique', 3);
        $vanne = $this->creerMateriel('Vanne', 8);
        $lignes = [$this->creerLigne($vanne, 2), $this->creerLigne($joint, 5)];

        // Act + Assert
        try {
            $this->gestionnaire->appliquer([], $lignes);
            self::fail('Une StockInsuffisantException était attendue.');
        } catch (StockInsuffisantException $e) {
            self::assertSame($joint, $e->getMateriel());
            self::assertSame(5, $e->getQuantiteDemandee());
            self::assertSame(3, $e->getQuantiteDisponible());
        }

        // Aucune écriture partielle : la vanne, pourtant valide et traitée avant le joint,
        // n'a pas été déduite.
        self::assertSame(3, $joint->getQuantiteStock());
        self::assertSame(8, $vanne->getQuantiteStock());
    }

    public function testLignesDupliqueesSontAgregeesAvantValidation(): void
    {
        // Arrange — 5 unités en stock, demandées sur deux lignes de 3 pour le même matériel
        $joint = $this->creerMateriel('Joint torique', 5);
        $lignes = [$this->creerLigne($joint, 3), $this->creerLigne($joint, 3)];

        // Act + Assert — le total demandé est 6, supérieur au stock : la demande est rejetée.
        // Valider ligne par ligne aurait laissé passer les deux (3 <= 5 chacune)
        // et produit un stock négatif.
        $this->expectException(StockInsuffisantException::class);
        $this->gestionnaire->appliquer([], $lignes);
    }

    public function testQuantiteNulleOuNegativeEstRefusee(): void
    {
        $joint = $this->creerMateriel('Joint torique', 10);

        $this->expectException(QuantiteInvalideException::class);
        $this->gestionnaire->appliquer([], [$this->creerLigne($joint, 0)]);
    }

    public function testMaterielRetireEstIntegralementRestitue(): void
    {
        // Arrange — 2 vannes réservées, stock affiché à 6
        $vanne = $this->creerMateriel('Vanne', 6);
        $anciennes = $this->gestionnaire->agreger([$this->creerLigne($vanne, 2)]);

        // Act — l'administrateur retire complètement la vanne de l'intervention
        $this->gestionnaire->appliquer($anciennes, []);

        // Assert — les 2 unités redeviennent disponibles
        self::assertSame(8, $vanne->getQuantiteStock());
    }

    public function testAffectationExacteDuStockDisponibleEstAcceptee(): void
    {
        // Arrange — cas limite : on demande exactement le stock restant
        $joint = $this->creerMateriel('Joint torique', 4);

        // Act
        $this->gestionnaire->appliquer([], [$this->creerLigne($joint, 4)]);

        // Assert — accepté, et le stock tombe à zéro sans passer en négatif
        self::assertSame(0, $joint->getQuantiteStock());
    }

    private function creerMateriel(string $nom, int $stock): Materiel
    {
        $materiel = new Materiel();
        $materiel->setNom($nom);
        $materiel->setQuantiteStock($stock);

        return $materiel;
    }

    private function creerLigne(Materiel $materiel, int $quantite): MaterielIntervention
    {
        $ligne = new MaterielIntervention();
        $ligne->setMateriel($materiel);
        $ligne->setQuantite($quantite);

        return $ligne;
    }
}

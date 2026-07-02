<?php

namespace App\Entity;

use App\Enum\StatutInterventionEnum;
use App\Repository\InterventionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_demande = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_souhaitee = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $date_planifiee = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree_estimee = null; // en minutes

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(enumType: StatutInterventionEnum::class)]
    private ?StatutInterventionEnum $statut = StatutInterventionEnum::EN_ATTENTE;

    #[ORM\ManyToOne(inversedBy: 'interventionsClient')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $client = null;

    #[ORM\ManyToOne(inversedBy: 'interventionsTechnicien')]
    private ?Utilisateur $technicien = null;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adresse $adresse = null;

    /**
     * @var Collection<int, Materiel>
     */
    #[ORM\ManyToMany(targetEntity: Materiel::class, inversedBy: 'interventions')]
    private Collection $materiels;

    #[ORM\OneToOne(mappedBy: 'intervention', cascade: ['persist', 'remove'])]
    private ?Commentaire $commentaire = null;

    public function __construct()
    {
        $this->materiels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDemande(): ?\DateTimeImmutable
    {
        return $this->date_demande;
    }

    public function setDateDemande(\DateTimeImmutable $date_demande): static
    {
        $this->date_demande = $date_demande;

        return $this;
    }

    public function getDateSouhaitee(): ?\DateTimeImmutable
    {
        return $this->date_souhaitee;
    }

    public function setDateSouhaitee(\DateTimeImmutable $date_souhaitee): static
    {
        $this->date_souhaitee = $date_souhaitee;

        return $this;
    }

    public function getDatePlanifiee(): ?\DateTimeImmutable
    {
        return $this->date_planifiee;
    }

    public function setDatePlanifiee(\DateTimeImmutable $date_planifiee): static
    {
        $this->date_planifiee = $date_planifiee;

        return $this;
    }

    public function getDureeEstimee(): ?int
    {
        return $this->duree_estimee;
    }

    public function setDureeEstimee(?int $duree_estimee): static
    {
        $this->duree_estimee = $duree_estimee;
        return $this;
    }

    /**
     * Calcule la date de fin d'intervention à partir de la date planifiée + durée
     */
    public function getDateFinPlanifiee(): ?\DateTimeImmutable
    {
        if (!$this->date_planifiee) {
            return null;
        }

        $duree = $this->duree_estimee ?? 120; // 2h par défaut si non renseignée
        return $this->date_planifiee->modify("+{$duree} minutes");
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatut(): ?StatutInterventionEnum
    {
        return $this->statut;
    }

    public function setStatut(StatutInterventionEnum $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getClient(): ?Utilisateur
    {
        return $this->client;
    }

    public function setClient(?Utilisateur $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getTechnicien(): ?Utilisateur
    {
        return $this->technicien;
    }

    public function setTechnicien(?Utilisateur $technicien): static
    {
        $this->technicien = $technicien;

        return $this;
    }

    public function getAdresse(): ?Adresse
    {
        return $this->adresse;
    }

    public function setAdresse(?Adresse $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * @return Collection<int, Materiel>
     */
    public function getMateriels(): Collection
    {
        return $this->materiels;
    }

    public function addMateriel(Materiel $materiel): static
    {
        if (!$this->materiels->contains($materiel)) {
            $this->materiels->add($materiel);
        }

        return $this;
    }

    public function removeMateriel(Materiel $materiel): static
    {
        $this->materiels->removeElement($materiel);

        return $this;
    }

    public function getCommentaire(): ?Commentaire
    {
        return $this->commentaire;
    }

    public function setCommentaire(?Commentaire $commentaire): static
    {
        // unset the owning side of the relation if necessary
        if ($commentaire === null && $this->commentaire !== null) {
            $this->commentaire->setIntervention(null);
        }

        // set the owning side of the relation if necessary
        if ($commentaire !== null && $commentaire->getIntervention() !== $this) {
            $commentaire->setIntervention($this);
        }

        $this->commentaire = $commentaire;

        return $this;
    }
}

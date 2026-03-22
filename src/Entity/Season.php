<?php

namespace App\Entity;

use App\Repository\SeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
class Season
{
    // =========================
    // ID UNIQUE (clé primaire)
    // =========================
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================
    // NOM AFFICHÉ (ex: Noël)
    // =========================
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    // =========================
    // SLUG (URL friendly)
    // ex: noel, fete-des-meres
    // =========================
    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = null;

    // =========================
    // RELATION MANY-TO-MANY
    // 1 saison peut contenir plusieurs produits
    // =========================
    #[ORM\ManyToMany(mappedBy: 'seasons', targetEntity: Product::class)]
    private Collection $products;

    // =========================
    // CONSTRUCTEUR
    // initialise la collection (OBLIGATOIRE)
    // =========================
    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    // =========================
    // GET ID
    // =========================
    public function getId(): ?int
    {
        return $this->id;
    }

    // =========================
    // GET / SET NAME
    // =========================
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        // trim = évite les espaces inutiles
        $this->name = trim($name);
        return $this;
    }

    // =========================
    // GET / SET SLUG
    // =========================
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        // slug en minuscule + propre
        $this->slug = strtolower(trim($slug));
        return $this;
    }

    // =========================
    // GET PRODUCTS
    // retourne tous les produits liés
    // =========================
    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    // =========================
    // ADD PRODUCT
    // ajoute un produit à la saison
    // =========================
    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);

            // synchronisation côté Product
            $product->addSeason($this);
        }

        return $this;
    }

    // =========================
    // REMOVE PRODUCT
    // retire un produit de la saison
    // =========================
    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // synchronisation côté Product
            $product->removeSeason($this);
        }

        return $this;
    }

    // =========================
    // AFFICHAGE ADMIN
    // permet d'afficher le nom dans EasyAdmin
    // =========================
    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
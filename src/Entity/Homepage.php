<?php

namespace App\Entity;

use App\Repository\HomepageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HomepageRepository::class)]
class Homepage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * =========================
     * HERO
     * =========================
     */

    #[ORM\Column(length: 100)]
    private ?string $heroEyebrow = null;

    #[ORM\Column(length: 255)]
    private ?string $heroTitle = null;

    #[ORM\Column(type: 'text')]
    private ?string $heroDescription = null;

    // 🔥 CTA PRINCIPAL (bouton)
    #[ORM\Column(length: 100)]
    private ?string $heroPrimaryCtaLabel = null;

    #[ORM\Column(length: 255)]
    private ?string $heroPrimaryCtaLink = null;

    // 🔥 CTA SECONDAIRE (lien)
    #[ORM\Column(length: 100)]
    private ?string $heroSecondaryCtaLabel = null;

    #[ORM\Column(length: 255)]
    private ?string $heroSecondaryCtaLink = null;

    #[ORM\Column(length: 255)]
    private ?string $heroImage = null;

    /**
     * =========================
     * ABOUT
     * =========================
     */

    #[ORM\Column(length: 255)]
    private ?string $aboutTitle = null;

    #[ORM\Column(type: 'text')]
    private ?string $aboutText1 = null;

    #[ORM\Column(type: 'text')]
    private ?string $aboutText2 = null;

    #[ORM\Column(length: 255)]
    private ?string $aboutImage = null;

    #[ORM\Column(type: 'json')]
    private array $benefits = [];

    /**
     * =========================
     * BOUTIQUE (SECTION PRODUITS)
     * =========================
     */

    #[ORM\Column(length: 255)]
    private ?string $shopTitle = null;

    #[ORM\Column(type: 'text')]
    private ?string $shopSubtitle = null;

    #[ORM\Column(type: 'text')]
    private ?string $shopDescription = null;

    #[ORM\ManyToMany(targetEntity: Product::class)]
    private Collection $featuredProducts;

    public function __construct()
    {
        $this->featuredProducts = new ArrayCollection();
    }

    /**
     * =========================
     * GETTERS / SETTERS
     * =========================
     */

    public function getId(): ?int
    {
        return $this->id;
    }

    // ================= HERO =================

    public function getHeroEyebrow(): ?string
    {
        return $this->heroEyebrow;
    }

    public function setHeroEyebrow(string $heroEyebrow): static
    {
        $this->heroEyebrow = $heroEyebrow;
        return $this;
    }

    public function getHeroTitle(): ?string
    {
        return $this->heroTitle;
    }

    public function setHeroTitle(string $heroTitle): static
    {
        $this->heroTitle = $heroTitle;
        return $this;
    }

    public function getHeroDescription(): ?string
    {
        return $this->heroDescription;
    }

    public function setHeroDescription(string $heroDescription): static
    {
        $this->heroDescription = $heroDescription;
        return $this;
    }

    public function getHeroPrimaryCtaLabel(): ?string
    {
        return $this->heroPrimaryCtaLabel;
    }

    public function setHeroPrimaryCtaLabel(string $label): static
    {
        $this->heroPrimaryCtaLabel = $label;
        return $this;
    }

    public function getHeroPrimaryCtaLink(): ?string
    {
        return $this->heroPrimaryCtaLink;
    }

    public function setHeroPrimaryCtaLink(string $link): static
    {
        $this->heroPrimaryCtaLink = $link;
        return $this;
    }

    public function getHeroSecondaryCtaLabel(): ?string
    {
        return $this->heroSecondaryCtaLabel;
    }

    public function setHeroSecondaryCtaLabel(string $label): static
    {
        $this->heroSecondaryCtaLabel = $label;
        return $this;
    }

    public function getHeroSecondaryCtaLink(): ?string
    {
        return $this->heroSecondaryCtaLink;
    }

    public function setHeroSecondaryCtaLink(string $link): static
    {
        $this->heroSecondaryCtaLink = $link;
        return $this;
    }
    /**
     * Get hero image path
     */
    public function getHeroImage(): ?string
    {
        return $this->heroImage;
    }

    /**
     * Set hero image path
     * 👉 nettoyage + gestion null comme Product
     */
    public function setHeroImage(?string $heroImage): static
    {
        $heroImage = $heroImage !== null ? trim($heroImage) : null;
        $this->heroImage = $heroImage === '' ? null : $heroImage;

        return $this;
    }
    /**
     * Retourne le chemin public complet
     */
    public function getHeroImagePath(): ?string
    {
       return $this->heroImage ? '/images/' . $this->heroImage : null;
     
    }

    // ================= ABOUT =================

    public function getAboutTitle(): ?string
    {
        return $this->aboutTitle;
    }

    public function setAboutTitle(string $aboutTitle): static
    {
        $this->aboutTitle = $aboutTitle;
        return $this;
    }

    public function getAboutText1(): ?string
    {
        return $this->aboutText1;
    }

    public function setAboutText1(string $aboutText1): static
    {
        $this->aboutText1 = $aboutText1;
        return $this;
    }

    public function getAboutText2(): ?string
    {
        return $this->aboutText2;
    }

    public function setAboutText2(string $aboutText2): static
    {
        $this->aboutText2 = $aboutText2;
        return $this;
    }

   public function getAboutImage(): ?string
{
    return $this->aboutImage;
}

    public function setAboutImage(?string $aboutImage): static
    {
        $aboutImage = $aboutImage !== null ? trim($aboutImage) : null;
        $this->aboutImage = $aboutImage === '' ? null : $aboutImage;

        return $this;
    }

    public function getAboutImagePath(): ?string
    {
    
         return $this->aboutImage ? '/images/' . $this->aboutImage : null;
    }

    public function getBenefits(): array
    {
        return $this->benefits;
    }

    public function setBenefits(array $benefits): static
    {
        $this->benefits = $benefits;
        return $this;
    }

    // ================= SHOP =================

    public function getShopTitle(): ?string
    {
        return $this->shopTitle;
    }

    public function setShopTitle(string $shopTitle): static
    {
        $this->shopTitle = $shopTitle;
        return $this;
    }

    public function getShopSubtitle(): ?string
    {
        return $this->shopSubtitle;
    }

    public function setShopSubtitle(string $shopSubtitle): static
    {
        $this->shopSubtitle = $shopSubtitle;
        return $this;
    }

    public function getShopDescription(): ?string
    {
        return $this->shopDescription;
    }

    public function setShopDescription(string $shopDescription): static
    {
        $this->shopDescription = $shopDescription;
        return $this;
    }

    // ================= FEATURED PRODUCTS =================

    public function getFeaturedProducts(): Collection
    {
        return $this->featuredProducts;
    }

    public function addFeaturedProduct(Product $product): static
    {
        if (!$this->featuredProducts->contains($product)) {
            $this->featuredProducts->add($product);
        }

        return $this;
    }

    public function removeFeaturedProduct(Product $product): static
    {
        $this->featuredProducts->removeElement($product);

        return $this;
    }
}
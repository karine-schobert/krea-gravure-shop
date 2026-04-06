<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $url = $adminUrlGenerator
            ->unsetAll()
            ->setController(ProductCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Krea-Gravure Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        // =========================================================
        // URLS EXISTANTES
        // =========================================================

        $productUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ProductCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $productOfferUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ProductOfferCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $categoryUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(CategoryCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $productCollectionUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ProductCollectionCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $seasonUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(SeasonCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $shopPageSettingsUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ShopPageSettingsCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $orderUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(OrderCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $customerUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(CustomerCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $adminUserUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(AdminCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $homepageUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(HomepageCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $reviewUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ReviewCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $supportTicketUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(SupportTicketCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $staticPageUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(StaticPageCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $workshopRequestUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(WorkshopRequestCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        
        $shipmentUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ShipmentCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        // =========================================================
        // CATALOGUE
        // =========================================================

        yield MenuItem::section('Catalogue');

        yield MenuItem::subMenu('Catalogue produit', 'fa fa-box')->setSubItems([
            MenuItem::linkToUrl('Produits', 'fa fa-box', $productUrl),
            MenuItem::linkToUrl('Offres produit', 'fa fa-percent', $productOfferUrl),
            MenuItem::linkToUrl('Catégories', 'fa fa-tags', $categoryUrl),
            MenuItem::linkToUrl('Collections', 'fa fa-layer-group', $productCollectionUrl),
            MenuItem::linkToUrl('Saisons', 'fa fa-calendar', $seasonUrl),
            MenuItem::linkToUrl('Page boutique', 'fa fa-store', $shopPageSettingsUrl),
        ]);

        // =========================================================
        // VENTES
        // =========================================================

        yield MenuItem::section('Ventes');

        yield MenuItem::subMenu('Gestion commerciale', 'fa fa-shopping-cart')->setSubItems([
            MenuItem::linkToUrl('Commandes', 'fa fa-shopping-cart', $orderUrl),
            MenuItem::linkToUrl('Expéditions', 'fa fa-truck', $shipmentUrl),
            MenuItem::linkToUrl('Clients', 'fa fa-users', $customerUrl),
]);
    

        // =========================================================
        // CONTENU
        // =========================================================

        yield MenuItem::section('Contenu');

        yield MenuItem::subMenu('Pages du site', 'fa fa-desktop')->setSubItems([
            MenuItem::linkToUrl('Homepage', 'fa fa-home', $homepageUrl),
            MenuItem::linkToUrl('Page boutique', 'fa fa-store', $shopPageSettingsUrl),
            MenuItem::linkToUrl('Pages statiques', 'fa fa-file-alt', $staticPageUrl),
        ]);

        // =========================================================
        // PERSONNALISATION
        // =========================================================

        yield MenuItem::section('Personnalisation');

        yield MenuItem::subMenu('Produits personnalisables', 'fa fa-magic')->setSubItems([
            MenuItem::linkToUrl('Produits', 'fa fa-box', $productUrl),
            MenuItem::linkToUrl('Offres produit', 'fa fa-percent', $productOfferUrl),
        ]);

        // =========================================================
        // ATELIER
        // =========================================================

        yield MenuItem::section('Atelier');

        yield MenuItem::subMenu('Demandes atelier', 'fa fa-screwdriver-wrench')->setSubItems([
            MenuItem::linkToUrl('Demandes atelier', 'fa fa-envelope-open-text', $workshopRequestUrl),
        ]);

        // =========================================================
        // MARKETING
        // =========================================================

        yield MenuItem::section('Marketing');

        yield MenuItem::subMenu('Marketing & relation client', 'fa fa-bullhorn')->setSubItems([
            MenuItem::linkToUrl('Avis clients', 'fa fa-star', $reviewUrl),
            MenuItem::linkToUrl('Tickets support', 'fa fa-life-ring', $supportTicketUrl),
        ]);

        // =========================================================
        // ADMINISTRATION
        // =========================================================

        yield MenuItem::section('Administration');

        yield MenuItem::subMenu('Administration', 'fa fa-user-shield')->setSubItems([
            MenuItem::linkToUrl('Administrateurs', 'fa fa-user-shield', $adminUserUrl),
        ]);

        // =========================================================
        // IDÉES DE BLOCS FUTURS
        // =========================================================

        /*
        $faqUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(FaqCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $promoCodeUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(PromoCodeCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $customizationOptionUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(CustomizationOptionCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

            yield MenuItem::subMenu('Contenu avancé', 'fa fa-file-alt')->setSubItems([
                MenuItem::linkToUrl('FAQ', 'fa fa-question-circle', $faqUrl),
            ]);

            yield MenuItem::subMenu('Promotions', 'fa fa-ticket-alt')->setSubItems([
                MenuItem::linkToUrl('Codes promo', 'fa fa-tags', $promoCodeUrl),
            ]);

            yield MenuItem::subMenu('Personnalisation avancée', 'fa fa-pencil-ruler')->setSubItems([
                MenuItem::linkToUrl('Options de personnalisation', 'fa fa-sliders-h', $customizationOptionUrl),
        ]);
        */
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('admin/admin.css')
            ->addJsFile('admin/support-ticket-templates.js');
    }
}

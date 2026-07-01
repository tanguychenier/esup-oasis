<?php

namespace App\Tests;

use App\Entity\Utilisateur;
use App\Service\LdapService;
use App\Service\SiScol\FakeSiScolDataProvider;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UtilisateurManagerTest extends ApiTestCaseCustom
{
    public function testInitNumeroAnonyme(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire']);
        
        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        
        $manager->initNumeroAnonyme($user);
        
        $this->assertNotNull($user->getNumeroAnonyme());
        $this->assertStringStartsWith(date('Y'), (string)$user->getNumeroAnonyme());
    }

    public function testParRole(): void
    {
        $container = static::getContainer();
        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        
        $admins = $manager->parRole('ROLE_ADMIN');
        $this->assertNotEmpty($admins);
        // Find 'admin' in the list
        $uids = array_map(fn($u) => $u->getUid(), $admins);
        $this->assertContains('admin', $uids);
    }

    public function testMajInscriptionsProjetteSituationSociale(): void
    {
        // OBC-1 critère 2 — la projection situation sociale est alimentée par le
        // SiScolDataProvider. En env de test, AbstractSiScolDataProvider est aliasé
        // sur FakeSiScolDataProvider qui renvoie codeSituationSociale "NO"/"Normal"
        // et boursier=false. On valide que la projection recopie ces champs.
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        // 'demandeur' porte un numeroEtudiant (123456), donc la MAJ scol se déclenche.
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);

        $debut = new \DateTime('2024-09-01');
        $fin = new \DateTime('2025-08-31');
        $manager->majInscriptionsEtIdentite($user, $debut, $fin);

        $this->assertSame('NO', $user->getCodeSituationSociale());
        $this->assertSame('Normal', $user->getLibelleSituationSociale());
        $this->assertFalse($user->isBoursier(), 'Le code NO ne doit pas dériver boursier');
    }

    public function testMajInscriptionsCodeBoDeriveBoursier(): void
    {
        // OBC-1 critère 2 — coeur de la règle : un code situation sociale "BO"
        // doit dériver boursier = true, MÊME si le témoin Apogée legacy est faux.
        // On exerce ici le VRAI UtilisateurManager (pas une réplique) via le
        // FakeSiScolDataProvider configuré pour renvoyer "BO".
        FakeSiScolDataProvider::$boursier = false;
        FakeSiScolDataProvider::$codeSituationSociale = 'BO';
        FakeSiScolDataProvider::$libelleSituationSociale = 'Boursier';

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        $manager->majInscriptionsEtIdentite($user, new \DateTime('2024-09-01'), new \DateTime('2025-08-31'));

        $this->assertSame('BO', $user->getCodeSituationSociale());
        $this->assertSame('Boursier', $user->getLibelleSituationSociale());
        $this->assertTrue($user->isBoursier(), 'Le code BO doit dériver boursier = true');
    }

    public function testMajInscriptionsTemoinLegacyResteBoursier(): void
    {
        // Rétrocompat : le témoin boursier legacy d'Apogée reste honoré même quand
        // le code situation sociale n'est pas "BO".
        FakeSiScolDataProvider::$boursier = true;
        FakeSiScolDataProvider::$codeSituationSociale = 'NO';
        FakeSiScolDataProvider::$libelleSituationSociale = 'Normal';

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        $manager->majInscriptionsEtIdentite($user, new \DateTime('2024-09-01'), new \DateTime('2025-08-31'));

        $this->assertTrue($user->isBoursier(), 'Le témoin legacy boursier doit rester honoré');
    }

    protected function tearDown(): void
    {
        // Réinitialise le mock situation sociale pour ne pas polluer les autres tests.
        FakeSiScolDataProvider::$boursier = false;
        FakeSiScolDataProvider::$codeSituationSociale = 'NO';
        FakeSiScolDataProvider::$libelleSituationSociale = 'Normal';
        parent::tearDown();
    }

    public function testCreerBeneficiairePourDemande(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        
        // On crée une demande pour un type qui n'a qu'un profil (artiste, id 2)
        $typeDemande = $em->getRepository(\App\Entity\TypeDemande::class)->find(2);
        $campagne = $typeDemande->getCampagnes()->first();
        $demandeur = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur2']);
        
        $demande = new \App\Entity\Demande();
        $demande->setCampagne($campagne);
        $demande->setDemandeur($demandeur);
        $demande->setEtat($em->getRepository(\App\Entity\EtatDemande::class)->find(\App\Entity\EtatDemande::RECEPTIONNEE));
        $demande->setDateDepot(new \DateTime());
        
        $em->persist($demande);
        $em->flush();
        
        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        
        $beneficiaire = $manager->creerBeneficiairePourDemande($demande, null, 'gestionnaire');
        
        $this->assertNotNull($beneficiaire);
        $this->assertEquals($demandeur, $beneficiaire->getUtilisateur());
        $this->assertEquals(6, $beneficiaire->getProfil()->getId()); // profil6 for artistes
    }
}

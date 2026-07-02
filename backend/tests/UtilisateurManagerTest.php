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

    public function testMajInscriptionsRafraichitInscriptionExistante(): void
    {
        // OBC-3 : le compteur d'inscriptions à l'étape et le cursus aménagé
        // évoluent côté SI en cours d'année pour une inscription déjà connue :
        // la MAJ doit rafraîchir ces champs sur l'inscription EXISTANTE, sans
        // la recréer. On exerce le vrai UtilisateurManager via le
        // FakeSiScolDataProvider reconfiguré entre les deux appels.
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        // 'demandeur' porte un numeroEtudiant (123456), donc la MAJ scol se déclenche.
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);

        $debut = new \DateTime('2024-09-01');
        $fin = new \DateTime('2025-08-31');

        // Premier appel : création de l'inscription renvoyée par le mock
        FakeSiScolDataProvider::$codeEtape = 'ETP1';
        FakeSiScolDataProvider::$nombreInscriptionsEtape = 1;
        FakeSiScolDataProvider::$codeCursusAmenage = null;
        FakeSiScolDataProvider::$libelleCursusAmenage = null;
        $manager->majInscriptionsEtIdentite($user, $debut, $fin);

        $this->assertCount(1, $user->getInscriptions());
        $inscription = $user->getInscriptions()->first();
        $idInitial = $inscription->getId();
        $this->assertSame('ETP1', $inscription->getCodeEtape());
        $this->assertSame(1, $inscription->getNombreInscriptionsEtape());
        $this->assertNull($inscription->getCodeCursusAmenage());

        // Second appel : le SI a incrémenté le compteur et posé un cursus aménagé
        FakeSiScolDataProvider::$nombreInscriptionsEtape = 2;
        FakeSiScolDataProvider::$codeCursusAmenage = 'CA';
        FakeSiScolDataProvider::$libelleCursusAmenage = 'Cursus aménagé handicap';
        $manager->majInscriptionsEtIdentite($user, $debut, $fin);

        $this->assertCount(1, $user->getInscriptions());
        $inscription = $user->getInscriptions()->first();
        $this->assertSame($idInitial, $inscription->getId(), "L'inscription ne doit pas être recréée");
        $this->assertSame('ETP1', $inscription->getCodeEtape());
        $this->assertSame(2, $inscription->getNombreInscriptionsEtape());
        $this->assertSame('CA', $inscription->getCodeCursusAmenage());
        $this->assertSame('Cursus aménagé handicap', $inscription->getLibelleCursusAmenage());
    }

    protected function tearDown(): void
    {
        // Réinitialise le mock des données d'étape pour ne pas polluer les autres tests.
        FakeSiScolDataProvider::$codeEtape = null;
        FakeSiScolDataProvider::$nombreInscriptionsEtape = null;
        FakeSiScolDataProvider::$codeCursusAmenage = null;
        FakeSiScolDataProvider::$libelleCursusAmenage = null;
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

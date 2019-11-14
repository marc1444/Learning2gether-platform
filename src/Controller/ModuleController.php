<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\Badgr;
use App\Domain\MdParser;
use App\Entity\LearningModule;
use App\Entity\Language;
use Parsedown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModuleController extends AbstractController
{
    /**
     * @Route("/{_locale}/module", name="module")
     */
    public function module(Request $request): Response
    {
//        //initialise badgr object
//        $badgrObj = new Badgr;
//
//        function getSession(Badgr $badgrObj){
//            //check if we already have refreshtoken
//            if(isset($_SESSION['refreshToken'])){
//                $refreshToken = $_SESSION['refreshToken'];
//                $badgrObj->getTokenData($refreshToken);
//            }
//            //if we don't, do the initial authentication to get it
//            else{
//                //this getPassword is so I don't reveal my personal pass, we use my (Tim) account for badgr atm
//                $password = $badgrObj->getPassword();
//                $badgrObj->initialise($password);
//            }
//        }
//
//        function getTokens(Badgr $badgrObj){
//            $accessToken = $_SESSION['accessToken'];
//            $refreshToken = $_SESSION['refreshToken'];
//        }
//
//        getSession($badgrObj);
//        getTokens($badgrObj);


        //user = logged in user
        $user = $this->getUser();

        //get user language, put your user language in database to id 1
        $languageId = $user->getLanguage()->getId();
        $language = $this->getDoctrine()->getRepository(Language::class)->find($languageId);

        // check the $_GET['module'], has to be set, and an integer, if not, redirects back to portal
        if (isset($_GET['module']) && ctype_digit((string)$_GET['module'])) {
            //get this Module
            $moduleID = $_GET['module'];
        } else {
            return $this->redirectToRoute('partner');
        }

        //initialise badgr object
        $badgrObj = new Badgr;

        $language = $this->getDoctrine()->getRepository(Language::class)->findOneBy([
            'code' => $request->getLocale()
        ]);

        //user = logged in user
        $user = $this->getUser();

        $module = $this->getDoctrine()->getRepository(LearningModule::class)->find($moduleID);

        //$moduleBadge = $module->getBadge();

        //when module completed, give badge
//        $completed = false;
//        if($completed === true){
//            //add badge from this module to user
//            $badgrObj->addBadgeToUser($module, $user, $accessToken);
//            $user->addBadge($module);
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->persist($user);
//            $entityManager->flush();
//        }

        // create the classes needed for parsing markdown to html, and finding and replacing yt links with an iplayer
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $mdParser = new MdParser();

        return $this->render('module/index.html.twig', [
            'controller_name' => 'ModuleController',
            'language' => $language,
            'module' => $module,
            'parsedown' => $parsedown,
            'mdparser' => $mdParser,
        ]);
    }
}
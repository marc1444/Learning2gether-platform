<?php

namespace App\Controller;

use App\Domain\ImageManager;
use App\Entity\Chapter;
use App\Entity\ChapterTranslation;
use App\Entity\Image;
use App\Entity\Language;
use App\Entity\LearningModule;
use App\Entity\LearningModuleTranslation;
use App\Entity\Quiz;
use App\Entity\User;
use App\Form\CreateChapterType;
use App\Form\EditModuleTranslationsType;
use App\Form\EditModuleType;
use App\Form\ImageUploaderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditModuleController extends AbstractController
{
    /**
     * @Route("/partner/edit/module/{module}", name="edit_module", requirements={"module"= "\d+"})
     * @param Request $request
     * @param LearningModule $module
     * @return Response
     */
    public function index(Request $request, LearningModule $module): Response
    {
        $english = $this->getDoctrine()->getRepository(Language::class)->findOneBy(['code' => 'en']);
        $englishTranslation = $module->getTranslations()->filter(static function ($entry) use ($english){
            return in_array($entry->getLanguage()->getCode(), (array)$english->getCode() , true);
        });

        $moduleForm = $this->createForm(EditModuleType::class, $module);
        $moduleForm->handleRequest($request);

        $moduleTLForm = $this->createForm(EditModuleTranslationsType::class, $englishTranslation[0]);
        $moduleTLForm->handleRequest($request);

        $addNewChapterBtn = $this->createFormBuilder()->getForm();
        $addNewChapterBtn->handleRequest($request);

        $moduleImageForm = $this->createForm(ImageUploaderType::class);
        $moduleImageForm->handleRequest($request);

        if ($moduleForm->isSubmitted() && $moduleForm->isValid()) {
            $module = $moduleForm->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($module);
            $em->flush();
            $this->addFlash('success', 'Changes saved!');
        }

        if ($moduleTLForm->isSubmitted() && $moduleTLForm->isValid()){
            $moduleTL = $moduleTLForm->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($moduleTL);
            $em->flush();
            $this->addFlash('success', 'Changes saved!');
        }

        if ($addNewChapterBtn->isSubmitted() && $addNewChapterBtn->isValid()){
            $languageAll = $this->getDoctrine()->getRepository(Language::class)->findAll();
            $newChapter = new Chapter($module);
            $newChapter = $this->makeChapterTranslations($newChapter, $languageAll);
            $quiz = new Quiz();
            $newChapter->setQuiz($quiz);
            $module->addChapter($newChapter);
            $em = $this->getDoctrine()->getManager();
            $em->persist($module);
            $em->flush();
            return $this->redirectToRoute('create_chapter', ['module' => $module->getId(), 'chapter' => $newChapter->getId()]);
        }

        if ($moduleImageForm->isSubmitted() && $moduleImageForm->isValid()){
            $imageManager = new ImageManager();

            /* @var User $user */
            $user = $this->getUser();

            $prevImage = $this->getDoctrine()->getRepository(Image::class)->findOneBy(['type' => 'module', 'src' => $module->getImage()]);
            $updatedModule = $imageManager->changeModuleImage($moduleImageForm->getData()['upload'], $prevImage, $module, $user, $this->getParameter('uploads_directory'));
            $this->flushUpdatedModule($updatedModule);
        }

        return $this->render('edit_module/index.html.twig', [
            'module' => $module,
            'english' => $english,
            'addNewChapterBtn' => $addNewChapterBtn->createView(),
            'moduleForm' => $moduleForm->createView(),
            'moduleTLForm' => $moduleTLForm->createView(),
            'moduleImageForm' => $moduleImageForm->createView(),
        ]);
    }

    /**
     * @param Chapter $chapter
     * @param array $languageAll
     * @return Chapter
     */
    private function makeChapterTranslations(Chapter $chapter, array $languageAll): Chapter
    {
        foreach ($languageAll as $language) {
            $translation = new ChapterTranslation($language, $chapter);
            $chapter->addTranslation($translation);
        }
        return $chapter;
    }

    private function flushUpdatedModule(LearningModule $updatedModule): void
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($updatedModule);
        $em->flush();
    }
}

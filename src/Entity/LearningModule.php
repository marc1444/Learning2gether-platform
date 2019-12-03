<?php

namespace App\Entity;

use App\Domain\LearningModuleType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LearningModuleRepository")
 */
class LearningModule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublished;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $badge;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $image;
    //link to the LM image on the server (for marketing prettifying purposes)

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;
    //defines the LM type, for example: the LM is for soft skills or hard skills

    /**
     * @ORM\OneToMany(targetEntity="LearningModuleTranslation", mappedBy="learningModule", orphanRemoval=true,cascade={"persist"})
     */
    private $translations;
    // cascade means a modules translations(titles and descriptions) can be inserted to the DB when their module is flushed. -jan

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Chapter", mappedBy="learningModule", orphanRemoval=true ,cascade={"persist"})
     * @ORM\OrderBy({"chapterNumber" = "ASC"})
     */
    private $chapters;

    //default for isPublished is set to false
    public function __construct(string $badge='', string $image='', LearningModuleType $type=null, bool $isPublished = false)
    {
        if(is_null($type)) {
            $type = LearningModuleType::hard();
        }

        $this->translations = new ArrayCollection();
        $this->chapters = new ArrayCollection();
        $this->badge = $badge;
        $this->image = $image;
        $this->type = $type;
        $this->isPublished = $isPublished;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getBadge(): string
    {
        return $this->badge;
    }

    public function setBadge(string $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    public function getType(): LearningModuleType
    {
        return $this->type;
    }

    public function setType(LearningModuleType $type): void
    {
        $this->type = $type;
    }

    public function addTranslation(LearningModuleTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setLearningModule($this);
        }
        return $this;
    }

    public function removeTranslation(LearningModuleTranslation $translation): self
    {
        if ($this->translations->contains($translation)) {
            $this->translations->removeElement($translation);
            // set the owning side to null (unless already changed)
            if ($translation->getLearningModule() === $this) {
                $translation->setLearningModule(null);
            }
        }
        return $this;
    }

    public function getTitle(Language $language)
    {
        foreach ($this->getTranslations() AS $translation) {
            if ($translation->getLanguage()->getName() === $language->getName()) {
                return $translation->getTitle();//change this line if needed when copied
            }
        }
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getDescription(Language $language)
    {
        foreach ($this->getTranslations() AS $translation) {
            if ($translation->getLanguage()->getName() === $language->getName()) {
                return $translation->getDescription();//change this line if needed when copied
            }
        }
    }

    /**
     * @return Collection|Chapter[]
     */
    public function getChapters(): Collection
    {
        return $this->chapters;
    }

    public function addChapter(Chapter $chapter): self
    {
        if (!$this->chapters->contains($chapter)) {
            if($chapter->getChapterNumber() === 0) {
                //we don't have a chapter number yet - create one based on the last chapter number + 1
                $chapter->setChapterNumber($this->fetchLastChapterNumber() + 1);
            }

            $this->chapters[] = $chapter;
        }
        return $this;
    }

    public function removeChapter(Chapter $chapter): self
    {
        if ($this->chapters->contains($chapter)) {
            $this->chapters->removeElement($chapter);
        }
        return $this;
    }

    //function to flag the module in order to show it requires more content before publishing
    public function flagPage()
    {
        //TODO flesh out this function to do stuff, (that's a separate ticket)
        //Same function for flagging/unflagging?
    }

    private function fetchLastChapterNumber() : int
    {
        $lastChapterNumber = 0;
        /** @var Chapter $chapter */
        foreach($this->chapters AS $chapter) {
            if($chapter->getChapterNumber() > $lastChapterNumber) {
                $lastChapterNumber = $chapter->getChapterNumber();
            }
        }
        return $lastChapterNumber;
    }

    /** @return UserChapter|array[] */
    public function getUserChapters(User $user) : array
    {
        $chaptersDone = $user->getProgressByLearningModule($this);

        $foundCurrentChapter = false;

        $listChapters = [];
        foreach($this->getChapters() AS $chapter) {
            $status = isset($chaptersDone[$chapter->getId()]);

            if(!$status && !$foundCurrentChapter) {
                $status = true;
                $foundCurrentChapter = true;
            }

            $listChapters[] = new UserChapter($chapter, $status);
        }

        return $listChapters;
    }
}
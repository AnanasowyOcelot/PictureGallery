<?php

namespace Vendor\GalleryBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity @ORM\Table(name="images")
 * @ORM\Entity(repositoryClass="Vendor\GalleryBundle\Repository\ImgRepository")
 **/
class Img
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue * */
    protected $id = 0;

    /** @ORM\Column(type="string") * */
    protected $path = '';

    /** @ORM\Column(type="string") * */
    protected $title = '';

    /** @ORM\Column(type="string") * */
    protected $fileName = '';

    /**
     * File(maxSize="6000000")
     */
    private $file;


    /**
     * @var /datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var /datetime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;



    /**
     * @var /Vendor/GalleryBundle/Entity/User $createdBy
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="\Vendor\GalleryBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var /Vendor/GalleryBundle/Entity/User $updatedBy
     *
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="\Vendor\GalleryBundle\Entity\User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    private $updatedBy;


    public function getId()
    {
        return $this->id;
    }
    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }
    /**
     * @ORM\OneToMany(targetEntity="ImgThumbnail", mappedBy="image", cascade={"all"})
     * @var ImgThumbnail[]
     **/
    protected $thumbnails;

    /**
     * @ORM\OneToMany(targetEntity="ImgVote", mappedBy="img", cascade={"all"})
     * @var ImgVote[]
     **/
    protected $votes;

    /**
     * @ORM\OneToOne(targetEntity="VotesSum", mappedBy="image", cascade={"all"})
     * @var VotesSum
     **/
    protected $votesSum;

    /**
     * @return int
     */
    public function getVotesSum()
    {
        return $this->votesSum->getValue();
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getPath($width = 0, $height = 0)
    {
        if ($width != 0 && $height != 0) {
            return $this->getThumbnailByWidthAndHeight($width, $height)->getPath();
        } else {
            return '/bundles/vendorgallery/' . $this->path;
        }
    }

    public function getOriginalPath(){
        return $this->path;
    }



    /**
     * @param \Vendor\GalleryBundle\Entity\ImgThumbnail[] $thumbnails
     */
    public function setThumbnails($thumbnails)
    {
        $this->thumbnails = $thumbnails;
    }

    public function addThumbnail(ImgThumbnail $thumbnail)
    {
        $this->thumbnails[] = $thumbnail;
    }

    public function addVote(ImgVote $vote){
        $this->votes[] = $vote;
    }

    /**
     * @param $width
     * @param $height
     * @return ImgThumbnail
     */
    public function getThumbnailByWidthAndHeight($width, $height)
    {
        $thumbnails = $this->thumbnails;

        foreach ($thumbnails as $thumbnail) {
            if ($thumbnail->getWidth() == $width && $thumbnail->getHeight() == $height) {
                return $thumbnail;
            }

        }
        return $this;

    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return \Vendor\GalleryBundle\Entity\datetime
     */
    public function getContentChanged()
    {
        return $this->contentChanged;
    }

    /**
     * @return \Vendor\GalleryBundle\Entity\datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \Vendor\GalleryBundle\Entity\datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }




}
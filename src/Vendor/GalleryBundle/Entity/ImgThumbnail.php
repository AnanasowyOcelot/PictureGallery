<?php


namespace Vendor\GalleryBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity @ORM\Table(name="imgThumbnails")
 **/
class ImgThumbnail
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue * */
    protected $id = 0;

    /** @ORM\Column(type="string") * */
    protected $height = 0;

    /** @ORM\Column(type="string") * */
    protected $width = 0;

    /** @ORM\Column(type="string") * */
    protected $path = '';

    /**
     * @ORM\ManyToOne(targetEntity="Img", inversedBy="thumbnails")
     **/
    protected $image;

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param mixed $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }


} 
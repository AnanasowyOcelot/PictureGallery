<?php

namespace Vendor\GalleryBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Uploadable\Fixture\Entity\File;
use Vendor\GalleryBundle\Entity\Img;
use Vendor\GalleryBundle\Entity\ImgThumbnail;
use Vendor\GalleryBundle\ViewModel\Image as ViewModel;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\SecurityContextInterface;

class Image
{
    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $imgRepository;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $voteRepository;

    function __construct(ObjectRepository $repository, SecurityContextInterface $securityContext, ObjectRepository $voteRepo)
    {
        $this->imgRepository = $repository;
        $this->securityContext = $securityContext;
        $this->voteRepository = $voteRepo;
    }

    public function getImagesList()
    {
        $imageViewModels = array();
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {


            $images = $this->imgRepository->findAll();
            foreach ($images as $image) {

                $votes = $this->getVotesForImage($image);

                /** @var $image \Vendor\GalleryBundle\Entity\Img */
                $imageViewModels[] = new ViewModel(
                    $image->getId(),
                    $image->getPath(200, 200),
                    $votes);
            }
        }
        return $imageViewModels;
    }

    public function getImageById($id)
    {

        /** @var $image \Vendor\GalleryBundle\Entity\Img */
        $image = $this->imgRepository->findOneBy(array('id' => $id));
        $votes = $this->getVotesForImage($image);

        $imageViewModel = new ViewModel(
            $image->getId(),
            $image->getPath(),
            $votes);
        $imageViewModel->setTitle($image->getTitle());
        $imageViewModel->setCreatedBy(($image->getCreatedBy()));


        return $imageViewModel;
    }

    public function getImagesByUserId($userId)
    {

        $images = $this->imgRepository->findBy(array('createdBy' => $userId));

        $imageViewModels = array();
        foreach ($images as $image) {
            $votes = $this->getVotesForImage($image);
            /**
             * @var $image Img
             */
            $imageViewModel = new ViewModel(
                $image->getId(),
                $image->getPath(200, 200),
                $votes);
            $imageViewModel->setTitle($image->getTitle());

            $imageViewModels[] = $imageViewModel;
        }

        return $imageViewModels;

    }

    private function getVotesForImage(Img $image)
    {
        $votes = $this->voteRepository->findBy(array("img" => $image));
        $votesSum = 0;
        foreach ($votes as $vote) {
            $votesSum += $vote->getValue();
        }
        return $votesSum;
    }

    public function uploadPictureAndReturnUrl(UploadedFile $file)
    {

        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getClientSize();
        $fileType = $file->getClientMimeType();
        $fileError = $file->getError();
        $fileTmpName = $file->getRealPath();
        $webPath = 'images/';

        $allowedExts = array("gif", "jpeg", "jpg", "png");
        $allowedTypes = array("image/gif", "image/jpeg", "image/pjpeg", "image/x-png", "image/png");
        $maxFileSize = 200000;
        $imgDirPath = __DIR__ . '/../../../../web/bundles/vendorgallery/images/';


        $temp = explode(".", $fileName);
        $extension = end($temp);

        $filePath = $imgDirPath . $fileName;

        if (in_array($fileType, $allowedTypes)
            && in_array($extension, $allowedExts)
            && ($fileSize < $maxFileSize)
        ) {
            if ($fileError > 0) {
                echo "Return Code: " . $fileError . "<br>";
            } else {
                move_uploaded_file($fileTmpName,
                    $filePath);

            }
        } else {
            echo "Invalid file";
        }


        return $webPath . $fileName;
    }

    public function createThumbnail($img, $newHeight, $newWidth)
    {
        $imgPath = $img->getPath();
        $fullPath = __DIR__ . '/../../../../web' . $imgPath;
        var_dump($fullPath);
        $image = $this->imageCreateFromFile($fullPath);

//        echo($image);
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        $imgPath = __DIR__ . '/../../../../web/bundles/vendorgallery/images/' . $newHeight . 'x' . $newWidth . $img->getFileName();
        $webPath = '/bundles/vendorgallery/images/' . $newHeight . 'x' . $newWidth . $img->getFileName();

        var_dump($image);

        $this->scale_image($image, $newImage);
        imagejpeg($newImage, $imgPath);

        $thumbnail = new ImgThumbnail();
        $thumbnail->setImage($img);
        $thumbnail->setHeight($newHeight);
        $thumbnail->setWidth($newWidth);
        $thumbnail->setPath($webPath);

        return $thumbnail;
    }

    function imageCreateFromFile($path)
    {
        $info = @getimagesize($path);
        if (!$info) {
            return false;
        }
        $functions = array(IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_WBMP => 'imagecreatefromwbmp',
            IMAGETYPE_XBM => 'imagecreatefromwxbm',);
        if (!$functions[$info[2]]) {
            return false;
        }
        if (!function_exists($functions[$info[2]])) {
            return false;
        }
        return $functions[$info[2]]($path);
    }

    function scale_image($src_image, $dst_image, $op = 'fit')
    {

        $src_width = imagesx($src_image);
        $src_height = imagesy($src_image);

        $dst_width = imagesx($dst_image);
        $dst_height = imagesy($dst_image);

        // Try to match destination image by width
        $new_width = $dst_width;
        $new_height = round($new_width * ($src_height / $src_width));
        $new_x = 0;
        $new_y = round(($dst_height - $new_height) / 2);

        // FILL and FIT mode are mutually exclusive
        if ($op == 'fill')
            $next = $new_height < $dst_height;
        else
            $next = $new_height > $dst_height;

        // If match by width failed and destination image does not fit, try by height
        if ($next) {
            $new_height = $dst_height;
            $new_width = round($new_height * ($src_width / $src_height));
            $new_x = round(($dst_width - $new_width) / 2);
            $new_y = 0;
        }

        // Copy image on right place
        imagecopyresampled($dst_image, $src_image, $new_x, $new_y, 0, 0, $new_width, $new_height, $src_width, $src_height);
    }

    protected
    function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    protected
    function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'images';
    }
} 
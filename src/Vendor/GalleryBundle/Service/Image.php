<?php

namespace Vendor\GalleryBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Vendor\GalleryBundle\Entity\Img;
use Vendor\GalleryBundle\Entity\ImgThumbnail;
use Vendor\GalleryBundle\Entity\VotesSum;
use Vendor\GalleryBundle\Exception\FileUploadException;
use Vendor\GalleryBundle\Model\ListParams;
use Vendor\GalleryBundle\ViewModel\Image as ViewModel;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\SecurityContextInterface;

class Image
{
    /**
     * @var \Vendor\GalleryBundle\Repository\ImgRepository
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

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $objectManager;

    function __construct(
        ObjectRepository $repository,
        SecurityContextInterface $securityContext,
        ObjectRepository $voteRepo,
        ObjectManager $om
    )
    {
        $this->imgRepository = $repository;
        $this->securityContext = $securityContext;
        $this->voteRepository = $voteRepo;
        $this->objectManager = $om;
    }


    public function updateVotesSum($votesNumber, Img $image)
    {

        $votesSum = $this->objectManager->getRepository('VendorGalleryBundle:VotesSum')->findOneBy(array("image" => $image->getId()));
        if ($votesSum == null) {
            $votesSum = new VotesSum();
        }
        $votesSum->setValue($votesNumber);
        $votesSum->setImage($image);

        $this->objectManager->persist($votesSum);
        $this->objectManager->flush();
    }

    public function getImagesList(ListParams $listParams)
    {
        $imageViewModels = array();
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $images = $this->imgRepository->findByParams($listParams);

            foreach ($images as $image) {
                $imageViewModels[] = $this->createViewModel($image);
            }
        }
        return $imageViewModels;
    }

    public function countImagesList($listParams) {
        return $this->imgRepository->count($listParams);
    }

    /**
     * @param Img $image
     * @param int $width
     * @param int $height
     * @return ViewModel
     */
    private function createViewModel(Img $image, $width = 0, $height = 0)
    {
        $vm = new ViewModel(
            $image->getId(),
            $image->getPath($width, $height),
            $this->getVotesForImage($image)
        );
        $vm->setTitle($image->getTitle());
        $vm->setCreatedBy(($image->getCreatedBy()));
        return $vm;
    }

    public function getImageById($id)
    {
        /** @var $image \Vendor\GalleryBundle\Entity\Img */
        $image = $this->imgRepository->find($id);
        $imageViewModel = $this->createViewModel($image);
        return $imageViewModel;
    }

    public function getImagesByUserId($userId)
    {
        $images = $this->imgRepository->findBy(array('createdBy' => $userId));
        $imageViewModels = array();
        foreach ($images as $image) {
            $imageViewModels[] = $this->createViewModel($image);
        }
        return $imageViewModels;
    }

    public function getVotesForImage(Img $image)
    {
        $votes = $this->voteRepository->findBy(array("img" => $image));
        $votesSum = 0;
        foreach ($votes as $vote) {
            /** @var \Vendor\GalleryBundle\Entity\ImgVote $vote */
            $votesSum += $vote->getValue();
        }
        return $votesSum;
    }


    /**
     * @param UploadedFile $file
     * @return string
     * @throws \Vendor\GalleryBundle\Exception\FileUploadException
     */
    public function uploadPictureAndReturnUrl(UploadedFile $file)
    {
        $allowedExts = array("gif", "jpeg", "jpg", "png");
        $allowedTypes = array("image/gif", "image/jpeg", "image/pjpeg", "image/x-png", "image/png");
        $maxFileSize = 200000;

        $validationErrors = array();

        if (!$file->isValid()) {
            $validationErrors[] = "Return Code: " . $file->getError();
        }

        if (!in_array($file->getClientMimeType(), $allowedTypes)) {
            $validationErrors[] = 'Invalid file type.';
        }

        $temp = explode(".", $file->getClientOriginalName());
        $extension = end($temp);
        if (!in_array($extension, $allowedExts)) {
            $validationErrors[] = 'Invalid extension';
        }

        if ($file->getClientSize() > $maxFileSize) {
            $validationErrors[] = 'File too big (' . $file->getClientSize() . '). Max file size: ' . $maxFileSize;
        }

        if (count($validationErrors) > 0) {
            throw new FileUploadException(implode('\n', $validationErrors));
        }

        $imgDirPath = $this->getBaseWebPath() . $this->getBundleWebPath();
        $filePath = $imgDirPath . $file->getClientOriginalName();
        move_uploaded_file($file->getRealPath(), $filePath);

        return 'images/' . $file->getClientOriginalName();
    }

    public function uploadAndCreateThumbnail(Request $request)
    {
        /** @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        $file = $request->files->get('file0');
        $img = new Img();

        $img->setTitle($request->request->get('title'));

        $path = $this->uploadPictureAndReturnUrl($file);
        $img->setPath($path);

        $img->setFileName($file->getClientOriginalName());

        $thumbNail = $this->createThumbnail($img, 200, 200);
        $img->setThumbnails(array($thumbNail));

        $this->objectManager->persist($img);
        $this->objectManager->flush();

        return $img;
    }

    private function createThumbnail(Img $img, $newHeight, $newWidth)
    {
        $tmpImgPath = $img->getPath();
        $fullPath = $this->getBaseWebPath() . $tmpImgPath;
        $image = $this->imageCreateFromFile($fullPath);

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        $bundlePath = $this->getBaseWebPath() . $this->getBundleWebPath();
        $thumbnailName = $newHeight . 'x' . $newWidth . $img->getFileName();
        $serverPath = $bundlePath . $thumbnailName;
        $webPath = $this->getBundleWebPath() . $thumbnailName;

        $this->scaleImage($image, $newImage);
        imagejpeg($newImage, $serverPath);

        $thumbnail = new ImgThumbnail();
        $thumbnail->setImage($img);
        $thumbnail->setHeight($newHeight);
        $thumbnail->setWidth($newWidth);
        $thumbnail->setPath($webPath);

        return $thumbnail;
    }

    private function imageCreateFromFile($path)
    {
        $info = @getimagesize($path);
        if (!$info) {
            return false;
        }
        $functions = array(
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_WBMP => 'imagecreatefromwbmp',
            IMAGETYPE_XBM => 'imagecreatefromwxbm'
        );
        if (!$functions[$info[2]]) {
            return false;
        }
        if (!function_exists($functions[$info[2]])) {
            return false;
        }
        return $functions[$info[2]]($path);
    }

    private function scaleImage($src_image, $dst_image, $op = 'fit')
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
        if ($op == 'fill') {
            $next = $new_height < $dst_height;
        } else {
            $next = $new_height > $dst_height;
        }

        // If match by width failed and destination image does not fit, try by height
        if ($next) {
            $new_height = $dst_height;
            $new_width = round($new_height * ($src_width / $src_height));
            $new_x = round(($dst_width - $new_width) / 2);
            $new_y = 0;
        }

        // Copy image on right place
        imagecopyresampled(
            $dst_image, $src_image,
            $new_x, $new_y,
            0, 0,
            $new_width, $new_height,
            $src_width, $src_height
        );
    }

    private function getBaseWebPath()
    {
        return __DIR__ . '/../../../../web';
    }

    private function getBundleWebPath()
    {
        return '/bundles/vendorgallery/images/';
    }
}

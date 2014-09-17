<?php

namespace Vendor\GalleryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Vendor\GalleryBundle\Entity\Img;

class DefaultController extends Controller
{
    public function indexAction()
    {
        /**
         * @var $imgService \Vendor\GalleryBundle\Service\Image
         */
        $imgService = $this->get('vendor_galery.image_service');
        $images = $imgService->getImagesList();

        return $this->render('VendorGalleryBundle:Default:index.html.twig',
            array(
                'messages' => array(),
                'images' => $images
            ));
    }

    // TODO: przepisać resztę metod do serwisów i na viewModele
    public function uploadAction()
    {
        /**
         * @var $request Request
         */
        $request = $this->get('request');
        $post = $request->request->all();
        $file = $request->files->get('file0');

         /**
         * @var $em \Doctrine\ORM\EntityManager
         */
        $em = $this->get('doctrine')->getManager();


        if (empty($post)) {
            $img = new Img();


            return $this->render('VendorGalleryBundle:Default:upload.html.twig', array(
                "entity" => $img,
                'messages' => array()
            ));

        } else {
            $fileName = $file->getClientOriginalName();

            if (empty($post['title'])) {
                /**
                 * @var $user \Vendor\GalleryBundle\Entity\User
                 */
                $user = $this->get('security.context')->getToken()->getUser();
                $userName = $user->getUsername();

                $img = new Img();
                return $this->render('VendorGalleryBundle:Default:upload.html.twig', array(
                    "entity" => $img,
                    'messages' => array('info' => 'Uzupełnij tytuł panie ' . $userName)
                ));

            } elseif (!$fileName) {
//                var_dump($_FILES);
//                die();
                $user = $this->get('security.context')->getToken()->getUser();
                $userName = $user->getUsername();

                $img = new Img();
                $img->setTitle($post['title']);
                return $this->render('VendorGalleryBundle:Default:upload.html.twig', array(
                    "entity" => $img,
                    'messages' => array('info' => 'Uzupełnij zdjęcie panie ' . $userName)
                ));
            }

            $img = new Img();
            $img->setTitle($post['title']);

            $imgService = $this->get('vendor_galery.image_service');
            $path = $imgService->uploadPictureAndReturnUrl($file);
            $img->setPath($path);

            $img->setFileName($fileName);
            $thumbNail = $imgService->createThumbnail($img, 200, 200);

            $img->setThumbnails(array($thumbNail));

            //TODO jeśli pole będzie puste!
            $em->persist($img);
            $em->flush();

            return $this->redirect($this->generateUrl('vendor_gallery_homepage'));
        }
    }

    public function detailsAction($id)
    {
        /**
         * @var $imgService \Vendor\GalleryBundle\Service\Image
         */
        $imgService = $this->get('vendor_galery.image_service');
        $image = $imgService->getImageById($id);

        return $this->render('VendorGalleryBundle:Default:details.html.twig', array(
            'image' => $image,
            'messages' => array()
        ));

    }

    public function myImagesAction($userId)
    {
        /**
         * @var $imgService \Vendor\GalleryBundle\Service\Image
         */
        $imgService = $this->get('vendor_galery.image_service');
        $images = $imgService->getImagesByUserId($userId);

        return $this->render('VendorGalleryBundle:Default:myimages.html.twig', array(
            'images' => $images,
            'messages' => array()
        ));
    }

}

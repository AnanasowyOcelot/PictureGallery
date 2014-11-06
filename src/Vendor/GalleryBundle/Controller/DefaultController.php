<?php

namespace Vendor\GalleryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Vendor\GalleryBundle\Entity\Img;
use Vendor\GalleryBundle\Exception\FileUploadException;
use Vendor\GalleryBundle\Model\ListParams;
use Vendor\GalleryBundle\Service\Collection;

class DefaultController extends Controller
{
    public function searchAction()
    {
        /** @var $request Request */
        $request = $this->get('request');
        $data = $request->request->all();

        $listParameters = new ListParams($data);


        $url = $this->generateUrl('vendor_gallery_list', array('params' => $listParameters->toUrlString()));

        return $this->redirect($url);
    }

    public function indexAction($params = null)
    {
        $listParams = ListParams::fromString($params);

        $imgService = $this->getImageService();
        $count = $imgService->countImagesList($listParams);
        $images = $imgService->getImagesList($listParams);
        $paginationHtml = $this->getPaginationService()->createPagination($count, $listParams);


        return $this->render('VendorGalleryBundle:Default:index.html.twig', array(
            'messages' => array(),
            'images' => $images,
            'pagination' => $paginationHtml,
            'searchParams' => $listParams
        ));
    }

    public function uploadAction()
    {
        /** @var $request Request */
        $request = $this->get('request');

        /** @var $user \Vendor\GalleryBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        $userName = $user->getUsername();

        $data = $request->request->all();

        if (!$request->isMethod("post")) {
            return $this->renderUploadTemplate(new Img());
        }

        if (!$data['title']) {
            return $this->renderUploadTemplate(
                new Img(),
                array('info' => 'Uzupełnij tytuł użytkowniku ' . $userName)
            );
        }

        $file = $request->files->get('file0');
        if (!$file->getClientOriginalName()) {
            $img = new Img();
            $img->setTitle($data['title']);
            return $this->renderUploadTemplate(
                $img,
                array('info' => 'Uzupełnij zdjęcie użytkowniku ' . $userName)
            );
        }

        try {
            $this->getImageService()->uploadAndCreateThumbnail($request);

            return $this->redirect($this->generateUrl('vendor_gallery_homepage'));
        } catch (FileUploadException $e) {
            return $this->renderUploadTemplate(
                new Img(),
                array('errors' => $e->getMessage())
            );
        }
    }

    private function renderUploadTemplate(Img $img, array $messages = array())
    {
        return $this->render('VendorGalleryBundle:Default:upload.html.twig', array(
            "entity" => $img,
            'messages' => $messages
        ));
    }

    public function detailsAction($id)
    {
        $image = $this->getImageService()->getImageById($id);

        return $this->render('VendorGalleryBundle:Default:details.html.twig', array(
            'image' => $image,
            'messages' => array()
        ));
    }

    public function myImagesAction($userId)
    {
        $images = $this->getImageService()->getImagesByUserId($userId);

        return $this->render('VendorGalleryBundle:Default:myimages.html.twig', array(
            'images' => $images,
            'messages' => array()
        ));
    }

    /**
     * @return \Vendor\GalleryBundle\Service\Image
     */
    private function getImageService()
    {
        return $this->get('vendor_galery.image_service');
    }

    /**
     * @return \Vendor\GalleryBundle\Service\Pagination
     */
    private function getPaginationService()
    {
        return $this->get("vendor_galery.pagination_service");
    }
}

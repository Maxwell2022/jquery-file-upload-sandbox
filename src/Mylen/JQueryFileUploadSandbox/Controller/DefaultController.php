<?php

namespace Mylen\JQueryFileUploadSandbox\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Config\Definition\Exception\Exception;
use Mylen\JQueryFileUploadBundle\Services\FileUploader;
use Mylen\JQueryFileUploadSandbox\Service\FileService;
use Mylen\JQueryFileUploadSandbox\Entity\Document;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="default")
     * @Template
     */
    public function indexAction()
    {
        /** @var FileUploader */
        $uploader = $this->get('mylen.file_uploader');
        $webDir = $this->get('kernel')->getRootDir() . '/../web';
        $posting = new Document($webDir);

        $form = $this->createFormBuilder($posting)->add('name')->getForm();

        $request = $this->getRequest();
        $editId = $request->get('editId');
        if (!preg_match('/^\d+$/', $editId)) {
            $editId = sprintf('%09d', mt_rand(0, 1999999999));

            if ($posting->id) {
                $uploader
                        ->syncFiles(
                                array('from_folder' => 'attachments/' . $posting->id, 'to_folder' => 'tmp/attachments/' . $editId, 'create_to_folder' => true));
            } else {
                $isNew = true;
                $posting->id = 10;
            }
        }

        //        if ($this->getRequest()->isMethod('POST')) {
        //            $form->bind($this->getRequest());
        //            if ($form->isValid()) {
        //                $em = $this->getDoctrine()->getManager();
        //
        //                $em->persist($posting);
        //                $em->flush();
        //
        //                $this->redirect($this->generateUrl('files_uploaded'));
        //            }
        //        }

        return array('form' => $form->createView());
    }

    /**
     * 
     * @throws \Exception
     * @return \Mylen\JQueryFileUploadBundle\Services\IResponseContainer
     */
    protected function handleRequest()
    {
        /** @var FileUploader */
        $uploader = $this->get('mylen.file_uploader');
        //TODO Flashbag
        // $this->get('session')->getFlashBag()->add('notice', 'File upload as been cancelled!');        
        return $uploader->handleFileUpload($this->container->getParameter('app.data_dir'));
    }
    
    /**
     *
     * @Route("/upload", name="files_put")
     * @Method({"PATCH", "POST", "PUT"})
     */
    public function putAction()
    {
        $upload = $this->handleRequest();
        $upload->post();
        return new Response($upload->getBody(), $upload->getType(), $upload->getHeader());
    }
    
    /**
     *
     * @Route("/upload", name="files_head")
     * @Method("HEAD")
     */
    public function headAction()
    {
        $uploader = $this->handleRequest();
        $uploader->head();
        return new Response($uploader->getBody(), $uploader->getType(), $uploader->getHeader());
    }

    /**
     *
     * @Route("/upload", name="files_get")
     * @Method("GET")
     */
    public function getAction()
    {
        $upload = $this->handleRequest();
        $upload->get();
        return new Response($upload->getBody(), $upload->getType(), $upload->getHeader());
    }

    /**
     *
     * @Route("/upload", name="files_delete")
     * @Method("DELETE")
     */
    public function deleteAction()
    {
        $upload = $this->handleRequest();
        $upload->delete();
        return new Response($upload->getBody(), $upload->getType(), $upload->getHeader());
    }
}

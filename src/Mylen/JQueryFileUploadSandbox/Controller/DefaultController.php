<?php

namespace Mylen\JQueryFileUploadSandbox\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Config\Definition\Exception\Exception;
use Mylen\JQueryFileUploadBundle\Services\IFileUploader;
use Mylen\JQueryFileUploadSandbox\Entity\Document;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="default")
     * @Method({"GET"})
     * @Template
     */
    public function indexAction()
    {
        /** @var IFileUploader */
        $uploader = $this->get('mylen.file_uploader');
        $webDir = $uploader->getFileBasePath();

        $builder = $this->createFormBuilder();
        $builder->add('name');
        
        $form = $builder->getForm();

        return array('form' => $form->createView());
    }

    /**
     * @Route("/", name="save")
     * @Method({"POST"})
     */
    public function postAction()
    {
        /** @var IFileUploader */
        $uploader = $this->get('mylen.file_uploader');
        $webDir = $uploader->getFileBasePath();

        $form = $this->createFormBuilder()->add('name')->getForm();

        $form->bind($this->getRequest());
        if ($form->isValid()) {
            $this->get('session')->getFlashBag()->add('notice', 'File upload is valid!');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'File upload is invalid!');
        }
        return $this->redirect($this->generateUrl('default'));
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

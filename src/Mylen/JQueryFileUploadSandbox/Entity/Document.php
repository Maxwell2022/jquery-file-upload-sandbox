<?php
/**
 * Created by JetBrains PhpStorm.
 * User: A140980
 * Date: 13/11/12
 * Time: 09:07
 * To change this template use File | Settings | File Templates.
 */
namespace Mylen\JQueryFileUploadSandbox\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Document
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    public $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

    /**
     * @var UploadedFile
     * @Assert\File[](maxSize="6000000")
     */
    public $files;

    public $uploadDir;
    public $webDir;

    public function __construct($webDir, $uploadDir = 'uploads/documents')
    {
        $this->webDir = $webDir;
        $this->uploadDir = $uploadDir;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            // do whatever you want to generate a unique name
            $this->path = sha1(uniqid(mt_rand(), true)) . '.' . $this->file->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }
        $fname = pathinfo($this->path, PATHINFO_FILENAME);
        $ext = pathinfo($this->path, PATHINFO_EXTENSION);
        if ($ext === 'zip') {
            $zip = new \ZipArchive();
            $ret = $zip->open($this->file->getRealPath());
            if ($ret === TRUE) {
                $tmpdir = $this->getUploadRootDir() . '/tmp/' . $fname;
                $zip->extractTo($tmpdir);
                $zip->close();

                // validate files
                $finder = new \Symfony\Component\Finder\Finder();
                $finder->name('*.xml');
                foreach ($finder->files()->in($tmpdir) as $file) {
                    $this->file->move($this->getUploadRootDir(), $tmpdir . '/' . $file->getRelativePathname());
                }
            } else {
                throw new \RuntimeException('could not extract zip file');
            }
        }
        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->file->move($this->getUploadRootDir(), $this->path);

        unset($this->file);
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    public function getAbsolutePath()
    {
        return null === $this->path ? null : $this->getUploadRootDir() . '/' . $this->path;
    }

    public function getWebPath()
    {
        return null === $this->path ? null : $this->getUploadDir() . '/' . $this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded documents should be saved
        return $this->webDir . '/' . $this->getUploadDir();
    }

    protected function getUploadDir()
    {
        return $this->uploadDir;
    }
}
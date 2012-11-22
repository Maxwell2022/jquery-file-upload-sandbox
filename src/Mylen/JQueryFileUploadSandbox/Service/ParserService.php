<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandre Melard <alexandre.melard@atos.net>
 * Date: 24/10/12
 * Time: 17:27
 * ParserService class provides utility functions to work with parser xml files .
 */
namespace Mylen\JQueryFileUploadSandbox\Service;

use Mylen\JQueryFileUploadSandbox\DependencyInjection\CacheAware;
use Mylen\JQueryFileUploadSandbox\Entity\Parser;
use Mylen\JQueryFileUploadSandbox\Entity\EventIn;
use Mylen\JQueryFileUploadSandbox\Entity\EventOut;
use Monolog\Logger;

class ParserService extends CacheAware
{
    /**
     * @var Logger
     */
    protected $logger;
    protected $xsd;
    
    /**
     * @param Cache $c
     */
    public function __construct($c, $l, $x)
    {
        parent::__construct($c);
        $this->logger = $l; 
        $this->xsd = $x;
    }

    /**
     * Parse xml file to return Parser array type.
     * events -> in -> event
     *              -> out -> event
     * @param Parser $parser
     * @return Parser
     */
    public function parse(Parser $parser)
    {
        if ($parserString = $this->cache->fetch('parse' . $parser->file)) {
            $parser = unserialize($parserString);
        } else {
            $this->validate($parser->file, $parser->xsd);
            $xml = simplexml_load_file($parser->file);
            if (null != $xml->events->in) {
                foreach ($xml->events->in as $in) {
                    $eventIn = new EventIn((string) $in->event);
                    if (null !== $in->out->event) {
                        foreach ($in->out->event as $event) {
                            $eventIn->addEventOut(new EventOut((string) $event));
                        }
                    }
                    $parser->addEventIn($eventIn);
                }
            }
            $this->cache->save('parse' . $parser->file, serialize($parser));
        }
        return $parser;
    }

    /**
     * @param $dir path to xml directory
     * @return Parser[]
     * @throws \RuntimeException
     */
    public function parseDir($dir)
    {
        if (!is_dir($dir)) {
            throw new \RuntimeException("Directory does not exists: [" . $dir . "]");
        }
        if ($parsersString = $this->cache->fetch('parseDir' . $dir)) {
            $parsers = unserialize($parsersString);
        } else {
            /** @var $parsers Parser[] */
            $parsers = array();
            $files = scandir($dir);
            foreach ($files as $file) {
                if (substr($file, -3, 3) === "xml") {
                    array_push($parsers, $this->parse(new Parser($dir . DIRECTORY_SEPARATOR . $file, $this->xsd)));
                }
            }
            $this->cache->save('parseDir' . $dir, serialize($parsers));
        }
        return $parsers;
    }

    public function libxml_display_error($error)
    {
        $return = "\n";
        switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
        case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
        }
        $return .= trim($error->message);
        if ($error->file) {
            $return .= " in $error->file";
        }
        $return .= " on line $error->line\n";

        return $return;
    }

    public function libxml_display_errors($display_errors = true)
    {
        $errors = libxml_get_errors();
        $chain_errors = "";

        foreach ($errors as $error) {
            $chain_errors .= preg_replace('/( in\ \/(.*))/', '', strip_tags($this->libxml_display_error($error))) . "\n";
            if ($display_errors) {
                trigger_error($this->libxml_display_error($error), E_USER_WARNING);
            }
        }
        libxml_clear_errors();

        return $chain_errors;
    }

    /** Validate xml file regarding xsd
     * @param $file
     * @param $schema
     * @return bool
     * @throws Exception
     */
    public function validate($file, $schema)
    {
        $result = false;
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument("1.0");
        $dom->load($file);
        $validate = $dom->schemaValidate($schema);
        if ($validate) {
            $result = true;
        } else {
            $error = $this->libxml_display_errors();
            libxml_use_internal_errors(false);
            throw new Exception("DOMDocument::schemaValidate() Generated Errors : $error");
        }
        libxml_use_internal_errors(false);
        return $result;
    }
}

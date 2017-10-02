<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 20/07/2017
 * Time: 04:06 PM
 */

namespace Greenter\Factory;

use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\BaseResult;
use Greenter\Security\SignedXml;
use Greenter\Ws\Services\SenderInterface;
use Greenter\Xml\Builder\BuilderInterface;
use Greenter\Zip\ZipFactory;

/**
 * Class FeFactory
 * @package Greenter\Factory
 */
class FeFactory implements FactoryInterface
{
    /**
     * @var SignedXml
     */
    protected $signer;

    /**
     * @var ZipFactory
     */
    protected $zipper;

    /**
     * @var SenderInterface
     */
    protected $sender;

    /**
     * Ultimo xml generado.
     *
     * @var string
     */
    protected $lastXml;

    /**
     * @var BuilderInterface
     */
    private $builder;

    /**
     * BaseFactory constructor.
     */
    public function __construct()
    {
        $this->signer = new SignedXml();
        $this->zipper = new ZipFactory();
    }

    /**
     * @return BuilderInterface
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @return SenderInterface
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param SenderInterface $sender
     * @return FeFactory
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @param BuilderInterface $builder
     * @return FeFactory
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * @param DocumentInterface $document
     * @return BaseResult
     */
    public function send(DocumentInterface $document)
    {
        $xml = $this->builder->build($document);
        $this->lastXml = $this->getXmmlSigned($xml);
        $filename = $document->getName();

        $zip = $this->zipper->compress("$filename.xml", $this->lastXml);
        return $this->sender->send("$filename.zip", $zip);
    }

    /**
     * Set Certicated content
     * @param string $cert
     */
    public function setCertificate($cert)
    {
        $this->signer->setCertificate($cert);
    }

    /**
     * Get Last XML Signed.
     *
     * @return string
     */
    public function getLastXml()
    {
        return $this->lastXml;
    }

    /**
     * @param string $xml
     * @return string
     */
    private function getXmmlSigned($xml)
    {
        return $this->signer->sign($xml);
    }
}
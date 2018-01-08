<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 16/10/2017
 * Time: 04:23 PM
 */

namespace Greenter;

use Greenter\Builder\BuilderInterface;
use Greenter\Factory\FeFactory;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryV2;
use Greenter\Model\Voided\Reversion;
use Greenter\Model\Voided\Voided;
use Greenter\Services\SenderInterface;
use Greenter\Ws\Services\BillSender;
use Greenter\Ws\Services\ExtService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SummarySender;
use Greenter\XMLSecLibs\Sunat\SunatXmlSecAdapter;

/**
 * Sistema de Emision del Contribuyente.
 *
 * Class See
 * @package Greenter
 */
class See
{
    /**
     * @var FeFactory
     */
    private $factory;

    /**
     * @var SoapClient
     */
    private $wsClient;

    /**
     * @var array
     */
    private $builders;

    /**
     * @var array
     */
    private $summarys;

    /**
     * @var SunatXmlSecAdapter
     */
    private $signer;

    /**
     * Twig Render Options
     *
     * @var array
     */
    private $options = [];

    /**
     * See constructor.
     */
    public function __construct()
    {
        $this->factory = new FeFactory();
        $this->wsClient = new SoapClient();
        $this->signer = new SunatXmlSecAdapter();
        $this->builders = [
            Model\Sale\Invoice::class => Xml\Builder\InvoiceBuilder::class,
            Model\Sale\Note::class => Xml\Builder\NoteBuilder::class,
            Model\Summary\Summary::class => Xml\Builder\SummaryBuilder::class,
            Model\Summary\SummaryV2::class => Xml\Builder\SummaryV2Builder::class,
            Model\Voided\Voided::class => Xml\Builder\VoidedBuilder::class,
            Model\Despatch\Despatch::class => Xml\Builder\DespatchBuilder::class,
            Model\Retention\Retention::class => Xml\Builder\RetentionBuilder::class,
            Model\Perception\Perception::class => Xml\Builder\PerceptionBuilder::class,
            Model\Voided\Reversion::class => Xml\Builder\VoidedBuilder::class,
        ];
        $this->summarys = [Summary::class, SummaryV2::class, Voided::class, Reversion::class];
        $this->factory->setSigner($this->signer);
    }

    /**
     * @param string $directory
     */
    public function setCachePath($directory)
    {
        $this->options['cache'] = $directory;
    }

    /**
     * @param string $certificate
     */
    public function setCertificate($certificate)
    {
        $this->signer->setCertificate($certificate);
    }

    /**
     * @param string $user
     * @param string $password
     */
    public function setCredentials($user, $password)
    {
        $this->wsClient->setCredentials($user, $password);
    }

    /**
     * @param string $service
     */
    public function setService($service)
    {
        $this->wsClient->setService($service);
    }

    /**
     * Get signed xml from document.
     *
     * @param DocumentInterface $document
     * @return string
     */
    public function getXmlSigned(DocumentInterface $document)
    {
        $classDoc = get_class($document);

        return $this->factory
            ->setBuilder($this->getBuilder($classDoc))
            ->getXmmlSigned($document);
    }


    /**
     * Envia documento.
     *
     * @param DocumentInterface $document
     * @return Model\Response\BaseResult
     */
    public function send(DocumentInterface $document)
    {
        $classDoc = get_class($document);
        $this->factory
            ->setBuilder($this->getBuilder($classDoc))
            ->setSender($this->getSender($classDoc));

        return $this->factory->send($document);
    }

    /**
     * @param $ticket
     * @return Model\Response\StatusResult
     */
    public function getStatus($ticket)
    {
        $sender = new ExtService();
        $sender->setClient($this->wsClient);

        return $sender->getStatus($ticket);
    }

    /**
     * @return FeFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param string $class
     * @return BuilderInterface
     */
    private function getBuilder($class)
    {
        $builder = $this->builders[$class];

        return new $builder($this->options);
    }

    /**
     * @param string $class
     * @return SenderInterface
     */
    private function getSender($class)
    {
        $sender = in_array($class, $this->summarys) ? new SummarySender(): new BillSender();
        $sender->setClient($this->wsClient);

        return $sender;
    }
}
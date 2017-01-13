<?php

namespace Oro\Bundle\DPDBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Factory\OrderDPDShippingContextFactory;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\AttachmentBundle\Entity\File as AttachmentFile;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;


class OrderShippingDPDHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var DPDShippingMethodProvider
     */
    protected $shippingMethodProvider;


    /**
     * @param ManagerRegistry $doctrine
     * @param FileManager $fileManager
     * @param DPDShippingMethodProvider $shippingMethodProvider
     */
    public function __construct(
        ManagerRegistry $doctrine,
        FileManager $fileManager,
        DPDShippingMethodProvider $shippingMethodProvider
    ) {
        $this->doctrine = $doctrine;
        $this->fileManager = $fileManager;
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function shipOrder(Order $order)
    {
        $result = [];
        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($order->getShippingMethod());
        if (!$shippingMethod || !($shippingMethod instanceof DPDShippingMethod)) {
            return null;
        }

        $shippingMethodType = $shippingMethod->getType($order->getShippingMethodType());
        if (!$shippingMethodType) {
            return null;
        }

        $shipDate = $shippingMethodType->getNextPickupDay(new \DateTime('now'));//TODO: pass shipDate from UI?

        $response = $shippingMethodType->shipOrder($order, $shipDate);
        if ($response && $response->isSuccessful()) {
            $tmpFile = $this->fileManager->writeToTemporaryFile($response->getLabelPDF());
            $labelFile = new File();
            $labelFile->setFile($tmpFile);
            $labelFile->setOriginalFilename('label.pdf');//FIXME: better name?

            $dpdTransaction = (new DPDTransaction())
                ->setOrder($order)
                ->setCreatedAt($response->getTimeStamp())
                ->setLabelFile($labelFile)
                ->setParcelNumbers($response->getParcelNumbers());

            $em = $this->doctrine->getManagerForClass(DPDTransaction::class);
            $em->persist($dpdTransaction);
            $em->flush();

            $result['transaction'] = $dpdTransaction;
        }
        $result['successful'] = $response ? $response->isSuccessful() : false;
        $result['errors'] = $response ? $response->getErrorMessagesLong() : [];

        return $result;
    }

    public function linkLabelToOrder(Order $order, DPDTransaction $dpdTransaction, $labelComment = 'dpd label')
    {
        $attachment = new Attachment();
        $attachment->setTarget($order);
        $attachment->setFile($dpdTransaction->getLabelFile());
        $attachment->setComment($labelComment);

        $em = $this->doctrine->getManagerForClass(Attachment::class);
        $em->persist($attachment);
        $em->flush();
    }

    public function addTrackingNumbersToOrder(Order $order, DPDTransaction $dpdTransaction)
    {
        $em = $this->doctrine->getManagerForClass(OrderShippingTracking::class);
        foreach ($dpdTransaction->getParcelNumbers() as $parcelNumber) {
            $shippingTracking = new OrderShippingTracking();
            $shippingTracking->setMethod($order->getShippingMethod());
            $shippingTracking->setNumber($parcelNumber);
            $order->addShippingTracking($shippingTracking);
            $em->persist($shippingTracking);
        }
        $em->flush();
    }

    public function unlinkLabelFromOrder(Order $order, DPDTransaction $dpdTransaction)
    {
        $em = $this->doctrine->getManagerForClass(Attachment::class);
        $attachmentRepository = $em->getRepository(Attachment::class);
        $attachment = $attachmentRepository->findOneBy(['file' => $dpdTransaction->getLabelFile()]);
        if ($attachment) {
            $em->remove($attachment);
            $em->flush();
        }
    }

    public function removeTrackingNumbersFromOrder(Order $order, DPDTransaction $dpdTransaction)
    {
        $shippingTrackings = $order->getShippingTrackings();
        $trackingNumbersToRemove = $dpdTransaction->getParcelNumbers();
        foreach ($shippingTrackings as $shippingTracking) {
            if (in_array($shippingTracking->getNumber(), $trackingNumbersToRemove)) {
                $order->removeShippingTracking($shippingTracking);
            }
        }

        $em = $this->doctrine->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();
    }
}

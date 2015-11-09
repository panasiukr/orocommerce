<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Strategy;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategy extends LocalizedFallbackValueAwareStrategy
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var BusinessUnit */
    protected $owner;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade($securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param Product $entity
     * @return Product
     */
    protected function beforeProcessEntity($entity)
    {
        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch(ProductStrategyEvent::PROCESS_BEFORE, $event);

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param Product $entity
     * @return Product
     */
    protected function afterProcessEntity($entity)
    {
        $this->populateOwner($entity);

        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch(ProductStrategyEvent::PROCESS_AFTER, $event);

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param Product $entity
     */
    protected function populateOwner(Product $entity)
    {
        if (false === $this->owner) {
            return;
        }

        if ($this->owner) {
            $entity->setOwner($this->owner);

            return;
        }

        /** @var User $user */
        $user = $this->securityFacade->getLoggedUser();
        if (!$user) {
            $this->owner = false;

            return;
        }

        $this->owner = $this->databaseHelper->getEntityReference($user->getOwner());

        $entity->setOwner($this->owner);
    }
}

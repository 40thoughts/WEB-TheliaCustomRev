<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Controller\Api;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\Loop\Customer;
use Thelia\Model\CustomerQuery;

/**
 * Class CustomerController
 * @package Thelia\Controller\Api
 * @author Manuel Raynaud <manu@thelia.net>
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class CustomerController extends AbstractCrudApiController
{
    public function __construct()
    {
        parent::__construct(
            "customer",
            AdminResources::CUSTOMER,
            TheliaEvents::CUSTOMER_CREATEACCOUNT,
            TheliaEvents::CUSTOMER_UPDATEACCOUNT,
            TheliaEvents::CUSTOMER_DELETEACCOUNT,
            [],
            [
                "limit" => 10,
                "offset" => 0,
                "current" => false,
            ]
        );
    }


    /**
     * @return \Thelia\Core\Template\Element\BaseLoop
     *
     * Get the entity loop instance
     */
    protected function getLoop()
    {
        return new Customer($this->container);
    }

    /**
     * @param array $data
     * @return \Thelia\Form\BaseForm
     */
    protected function getCreationForm(array $data = array())
    {
        return $this->createForm("thelia.api.customer.create");
    }

    /**
     * @param array $data
     * @return \Thelia\Form\BaseForm
     */
    protected function getUpdateForm(array $data = array())
    {
        return $this->createForm(
            "thelia.api.customer.update",
            "form",
            [],
            ['method' => 'PUT']
        );
    }

    /**
     * @param Event $event
     * @return null|mixed
     *
     * Get the object from the event
     *
     * if return null or false, the action will throw a 404
     */
    protected function extractObjectFromEvent(Event $event)
    {
        return $event->getCustomer();
    }

    /**
     * @param array $data
     * @return \Symfony\Component\EventDispatcher\Event
     */
    protected function getCreationEvent(array &$data)
    {
        return $this->hydrateEvent($data);
    }

    /**
     * @param array $data
     * @return \Symfony\Component\EventDispatcher\Event
     */
    protected function getUpdateEvent(array &$data)
    {
        return $this->hydrateEvent($data);
    }

    /**
     * @param mixed $entityId
     * @return \Symfony\Component\EventDispatcher\Event
     */
    protected function getDeleteEvent($entityId)
    {
        $customer = CustomerQuery::create()->findPk($entityId);

        if (null === $customer) {
            throw new HttpException(404, sprintf('{"error": "customer with id %d not found"}', $entityId));
        }

        return new CustomerEvent($customer);
    }

    protected function hydrateEvent(array $data)
    {
        $customerCreateEvent = new CustomerCreateOrUpdateEvent(
            $data['title'],
            $data['firstname'],
            $data['lastname'],
            $data['address1'],
            $data['address2'],
            $data['address3'],
            $data['phone'],
            $data['cellphone'],
            $data['zipcode'],
            $data['city'],
            $data['country'],
            $data['email'],
            $data['password'],
            $data['lang'],
            isset($data["reseller"]) ? $data["reseller"] : null,
            isset($data["sponsor"]) ? $data["sponsor"] : null,
            isset($data["discount"]) ? $data["discount"] : null,
            $data['company'],
            null
        );

        if (isset($data["id"])) {
            $customerCreateEvent->setCustomer(CustomerQuery::create()->findPk($data["id"]));
        }

        return $customerCreateEvent;
    }

    public function deleteAction($entityId)
    {
        $query = CustomerQuery::create()
            ->joinOrder()
            ->filterById($entityId)
            ->findOne()
        ;

        if (null !== $query) {
            throw new HttpException(403, json_encode([
                "error" => sprintf("You can't delete the customer %d as he has orders", $entityId),
            ]));
        }

        return parent::deleteAction($entityId);
    }
}

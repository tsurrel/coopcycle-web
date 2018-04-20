<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use AppBundle\Entity\Task\CollectionInterface as TaskCollectionInterface;
use AppBundle\Validator\Constraints\Delivery as AssertDelivery;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @see http://schema.org/ParcelDelivery Documentation on Schema.org
 *
 * @ApiResource(iri="http://schema.org/ParcelDelivery",
 *   collectionOperations={},
 *   itemOperations={
 *     "get"={"method"="GET"}
 *   },
 *   attributes={
 *     "denormalization_context"={"groups"={"order_create"}},
 *     "normalization_context"={"groups"={"delivery", "place", "order"}}
 *   }
 * )
 *
 * @AssertDelivery
 */
class Delivery extends TaskCollection implements TaskCollectionInterface
{
    // default status when the delivery is created along the order
    const STATUS_WAITING    = 'WAITING';
    // the delivery has been accepted by a courier
    const STATUS_DISPATCHED = 'DISPATCHED';
    // the delivery has been picked by a courier
    const STATUS_PICKED     = 'PICKED';
    // delivered successfully
    const STATUS_DELIVERED  = 'DELIVERED';
    // the courier had an accident
    const STATUS_ACCIDENT   = 'ACCIDENT';
    // delivery was canceled (by an admin)
    const STATUS_CANCELED   = 'CANCELED';

    const VEHICLE_BIKE = 'bike';
    const VEHICLE_CARGO_BIKE = 'cargo_bike';

    const COLORS_LIST = ['#213ab2', '#b2213a', '#5221b2', '#93c63f', '#b22182', '#3ab221', '#b25221', '#2182b2', '#3ab221', '#9c21b2', '#c63f4f', '#b2217f', '#82b221', '#5421b2', '#3f93c6', '#21b252', '#c6733f'];

    /**
     * @Groups({"delivery"})
     */
    protected $id;

    /**
     * @Groups({"place", "order"})
     * @ApiProperty(iri="https://schema.org/Place")
     */
    private $originAddress;

    /**
     * @Groups({"order_create", "place", "order"})
     * @ApiProperty(iri="https://schema.org/Place")
     */
    private $deliveryAddress;

    private $order;

    /**
     * @var string
     *
     * @Groups({"delivery", "order"})
     */
    private $status;

    private $weight;

    private $vehicle = self::VEHICLE_BIKE;

    public function getOriginAddress()
    {
        return $this->originAddress;
    }

    public function setOriginAddress(Address $originAddress)
    {
        $this->originAddress = $originAddress;

        return $this;
    }

    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(Address $deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder(OrderInterface $order)
    {
        $this->order = $order;

        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    public function getVehicle()
    {
        return $this->vehicle;
    }

    public function setVehicle($vehicle)
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getPickup()
    {
        foreach ($this->getTasks() as $task) {
            if ($task->getType() === Task::TYPE_PICKUP) {
                return $task;
            }
        }
    }

    public function getDropoff()
    {
        foreach ($this->getTasks() as $task) {
            if ($task->getType() === Task::TYPE_DROPOFF) {
                return $task;
            }
        }
    }

    public static function createTasks(Delivery $delivery)
    {
        $dropoffDoneBefore = clone $delivery->getDate();

        $dropoffDoneAfter = clone $delivery->getDate();
        $dropoffDoneAfter->modify('-15 minutes');

        $dropoffTask = new Task();
        $dropoffTask->setType(Task::TYPE_DROPOFF);
        $dropoffTask->setAddress($delivery->getDeliveryAddress());
        $dropoffTask->setDoneAfter($dropoffDoneAfter);
        $dropoffTask->setDoneBefore($dropoffDoneBefore);

        $pickupDoneBefore = clone $delivery->getDate();
        $pickupDoneBefore->modify(sprintf('-%d seconds', $delivery->getDuration()));

        $pickupDoneAfter = clone $pickupDoneBefore;
        $pickupDoneAfter->modify('-15 minutes');

        $pickupTask = new Task();
        $pickupTask->setType(Task::TYPE_PICKUP);
        $pickupTask->setAddress($delivery->getOriginAddress());
        $pickupTask->setDoneAfter($pickupDoneAfter);
        $pickupTask->setDoneBefore($pickupDoneBefore);

        $dropoffTask->setPrevious($pickupTask);

        return [ $pickupTask, $dropoffTask ];
    }

    public static function create()
    {
        $pickup = new Task();
        $pickup->setType(Task::TYPE_PICKUP);

        $dropoff = new Task();
        $dropoff->setType(Task::TYPE_DROPOFF);

        $delivery = new self();
        $delivery->addTask($pickup);
        $delivery->addTask($dropoff);

        return $delivery;
    }

    public static function createWithDefaults()
    {
        $pickupDoneBefore = new \DateTime();
        $pickupDoneBefore->modify('+1 day');

        $dropoffDoneBefore = clone $pickupDoneBefore;
        $dropoffDoneBefore->modify('+1 hour');

        $delivery = self::create();

        $delivery->getPickup()->setDoneBefore($pickupDoneBefore);
        $delivery->getDropoff()->setDoneBefore($dropoffDoneBefore);

        return $delivery;
    }

    public function getColor()
    {
        if(!is_null($this->getId())) {
            return $this::COLORS_LIST[$this->getId() % count($this::COLORS_LIST)];
        }

    }
}

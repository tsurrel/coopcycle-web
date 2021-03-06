<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Base\CreativeWork;
use Doctrine\Common\Collections\ArrayCollection;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * A structured representation of food or drink items available from a FoodEstablishment.
 *
 * @see http://schema.org/Menu Documentation on Schema.org
 *
 * @ApiResource(iri="http://schema.org/Menu",
 *   attributes={
 *     "normalization_context"={"groups"={"restaurant"}}
 *   },
 *   collectionOperations={},
 *   itemOperations={
 *     "get"={"method"="GET"}
 *   }
 * )
 */
class Menu extends CreativeWork
{
    /**
     * @var int
     */
    private $id;

    /**
     * @Groups({"restaurant"})
     */
    private $sections;

    /**
     * @var string The menu of the restaurant.
     */
    private $restaurant;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function addSection($menuSection)
    {
        if ($menuSection instanceof MenuSection) {
            $section = new Menu\MenuSection();
            $section->setMenu($this);
            $section->setMenuSection($menuSection);

            $this->sections->add($section);
        }

        if ($menuSection instanceof Menu\MenuSection) {
            $menuSection->setMenu($this);
            $this->sections->add($menuSection);
        }
    }

    public function getAllItems()
    {
        $items = new ArrayCollection();

        foreach ($this->sections as $section) {
            foreach ($section->getItems() as $item) {
                $items->add($item);
            }
        }

        return $items;
    }

    public function getAllModifiers()
    {
        $modifiers = new ArrayCollection();

        foreach ($this->getAllItems() as $item) {
            foreach ($item->getModifiers() as $modifier) {
                $modifiers->add($modifier);
            }
        }

        return $modifiers;
    }

    public function getAllModifierChoices()
    {
        $modifierChoices = new ArrayCollection();

        foreach ($this->getAllModifiers() as $modifier) {
            foreach ($modifier->getModifierChoices() as $modifierChoice) {
                $modifierChoices->add($modifierChoice);
            }
        }

        return $modifierChoices;
    }

    /**
     * @return Restaurant
     */
    public function getRestaurant()
    {
        return $this->restaurant;
    }

    /**
     * @param Restaurant $restaurant
     */
    public function setRestaurant(Restaurant $restaurant)
    {
        $this->restaurant = $restaurant;
    }

}

<?php

namespace Modules\Inventory\App\Entities;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * BusinessProduction
 *
 * @ORM\Table(name ="inv_production")
 * @ORM\Entity()
 */
class BusinessProduction
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="businessProductions" )
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private  $product;

	 /**
     * @ORM\OneToMany(targetEntity="Modules\Inventory\App\Entities\BusinessProductionExpense", mappedBy="businessProduction" )
      **/
    private  $businessProductionExpense;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="process", type="string")
	 */
	private $process = 'created';

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float")
     */
    private $quantity;

    /**
     * @var float
     *
     * @ORM\Column(name="purchasePrice", type="float", nullable = true)
     */
    private $purchasePrice;

	/**
     * @var float
     *
     * @ORM\Column(name="purchaseSubTotal", type="float", nullable = true)
     */
    private $purchaseSubTotal;

    /**
     * @var float
     *
     * @ORM\Column(name="salesPrice", type="float", nullable = true)
     */
    private $salesPrice;

	/**
	 * @var float
	 *
	 * @ORM\Column(name="salesSubTotal", type="float", nullable = true)
	 */
	private $salesSubTotal;

	/**
	 * @var \DateTime
	 * @Gedmo\Timestampable(on="create")
	 * @ORM\Column(name="created", type="datetime",nullable=true)
	 */
	private $created;

	/**
	 * @var \DateTime
	 * @Gedmo\Timestampable(on="update")
	 * @ORM\Column(name="updated", type="datetime",nullable=true)
	 */
	private $updated;


	/**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $quantity
     */

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set purchasePrice
     * @param float $purchasePrice
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * Get purchasePrice
     *
     * @return float
     */
    public function getPurchasePrice()
    {
        return $this->purchasePrice;
    }

    /**
     * Set salesPrice
     * @param float $salesPrice
     */
    public function setSalesPrice($salesPrice)
    {
        $this->salesPrice = $salesPrice;
    }

    /**
     * Get salesPrice
     *
     * @return float
     */
    public function getSalesPrice()
    {
        return $this->salesPrice;
    }

	/**
	 * @return \DateTime
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param \DateTime $created
	 */
	public function setCreated($created)
	{
		$this->created = $created;
	}

	/**
	 * @return \DateTime
	 */
	public function getUpdated()
	{
		return $this->updated;
	}

	/**
	 * @param \DateTime $updated
	 */
	public function setUpdated($updated)
	{
		$this->updated = $updated;
	}

	/**
	 * @return float
	 */
	public function getPurchaseSubTotal(){
		return $this->purchaseSubTotal;
	}

	/**
	 * @param float $purchaseSubTotal
	 */
	public function setPurchaseSubTotal( float $purchaseSubTotal ) {
		$this->purchaseSubTotal = $purchaseSubTotal;
	}

	/**
	 * @return float
	 */
	public function getSalesSubTotal(){
		return $this->salesSubTotal;
	}

	/**
	 * @param float $salesSubTotal
	 */
	public function setSalesSubTotal( float $salesSubTotal ) {
		$this->salesSubTotal = $salesSubTotal;
	}

	/**
	 * @return Product
	 */
	public function getBusinessParticular() {
		return $this->businessParticular;
	}

	/**
	 * @param Product $product
	 */
	public function setBusinessParticular( $product ) {
		$this->businessParticular = $product;
	}

	/**
	 * @return string
	 */
	public function getProcess(){
		return $this->process;
	}

	/**
	 * @param string $process
	 */
	public function setProcess( string $process ) {
		$this->process = $process;
	}

	/**
	 * @return BusinessProductionExpense
	 */
	public function getBusinessProductionExpense() {
		return $this->businessProductionExpense;
	}


}


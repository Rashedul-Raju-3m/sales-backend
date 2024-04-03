<?php

namespace Modules\Inventory\App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Modules\Utility\App\Entities\ProductUnit;

/**
 * BusinessParticular
 *
 * @ORM\Table( name = "inv_product")
 * @ORM\Entity()
 */
class Product
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
     * @ORM\ManyToOne(targetEntity="Modules\Inventory\App\Entities\Config", cascade={"detach","merge"} )
     **/
    private  $config;


    /**
     * @ORM\ManyToOne(targetEntity="Modules\Utility\App\Entities\Setting")
     **/
    private  $productType;

    /**
     * @ORM\ManyToOne(targetEntity="Modules\Inventory\App\Entities\Category")
     * @ORM\OrderBy({"sorting" = "ASC"})
     **/
    private $category;

     /**
     * @ORM\ManyToOne(targetEntity="Brand")
     * @ORM\OrderBy({"sorting" = "ASC"})
     **/
    private $brand;

     /**
     * @ORM\ManyToOne(targetEntity="Modules\Inventory\App\Entities\WearHouse")
     * @ORM\OrderBy({"sorting" = "ASC"})
     **/
    private $wearHouse;

    /**
     * @ORM\ManyToOne(targetEntity="Modules\Inventory\App\Entities\WearHouse")
     **/
    private $businessParticularType;


     /**
     * @ORM\ManyToOne(targetEntity="Particular")
     **/
    private $rackNo;


    /**
     * @ORM\ManyToOne(targetEntity="Modules\Utility\App\Entities\ProductUnit")
     **/
    private  $unit;

    /**
     * @ORM\ManyToOne(targetEntity="Modules\Utility\App\Entities\ProductSize")
     **/
    private  $size;

    /**
     * @ORM\ManyToOne(targetEntity="Modules\Utility\App\Entities\ProductColor")
     **/
    private  $color;



    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;


    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $alternativeName;



    /**
     * @Gedmo\Slug(fields={"name"})
     * @Doctrine\ORM\Mapping\Column(length=255,unique=false)
     */
    private $slug;


    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30,nullable = true)
     */
    private $productionType;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $quantity = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $openingQuantity;


     /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $adjustmentQuantity;

    /**
     * @var boolean
     *
     * @ORM\Column( type="boolean",  nullable=true)
     */
    private $openingApprove = false;


    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minQuantity;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $reorderQuantity;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $transferQuantity;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $stockIn = 0;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $purchaseQuantity;

    /**
     * @var float
     *
     * @ORM\Column( type="float", nullable=true)
     */
    private $salesQuantity;


    /**
     * @var float
     *
     * @ORM\Column( type="float", nullable=true)
     */
    private $remainingQuantity = 0;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $purchaseReturnQuantity = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $salesReturnQuantity = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $damageQuantity = 0;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $bonusQuantity = 0;



    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $bonusAdjustment = 0;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $returnBonusQuantity = 0;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $bonusPurchaseQuantity = 0;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $bonusSalesQuantity = 0;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $purchasePrice = 0;


	/**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $avgPurchasePrice = 0;


	/**
	 * @var float
	 *
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $productionSalesPrice;

    /**
	 * @var float
	 *
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $tloPrice;



    /**
     * @var string
     *
     * @ORM\Column( type="text", nullable=true)
     */
    private $content;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $salesPrice = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $discountPrice;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $commission = 0;

    /**
     * @var float
     *
     * @ORM\Column( type="float", nullable=true)
     */
    private $minimumPrice = 0;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $sorting;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer",  nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $particularCode;


    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $sku;


     /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $barcode;


     /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $modelNo;


    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean" )
     */
    private $status= true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $path;


    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $updatedAt;


}


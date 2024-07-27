<?php

namespace Modules\Accounting\App\Entities;
use App\Entity\Domain\Vendor;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Terminalbd\GenericBundle\Entity\Particular;


/**
 * AccountHead
 *
 * @ORM\Table(name="acc_head_master")
 * @ORM\Entity()
 *
 */
class AccountMasterHead
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
     * @ORM\ManyToOne(targetEntity="AccountHead", inversedBy="children", cascade={"detach","merge"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;


    /**
     * @ORM\OneToMany(targetEntity="AccountHead" , mappedBy="parent")
     * @ORM\OrderBy({"name" = "ASC"})
     **/
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Setting")
     **/
    private $motherAccount;


	/**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20, nullable= true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

     /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", nullable=true)
     */
    private $amount = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="credit",type="float", nullable=true)
     */
    private $credit = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="debit",type="float", nullable=true)
     */
    private $debit = 0;

     /**
     * @var integer
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     */
    private $level = 3;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=30, nullable=true)
     */
    private $source;


    /**
     * @var string
     *
     * @ORM\Column(name="sourceId", type="string", length=30, nullable=true)
     */
    private $sourceId;


    /**
     * @Gedmo\Slug(fields={"name"})
     * @Doctrine\ORM\Mapping\Column(length=255)
     */
    private $slug;


    /**
     * @var string
     *
     * @ORM\Column(name="toIncrease", type="string", length=20, nullable=true)
     */
    private $toIncrease;


	/**
	 * @var integer
	 *
	 * @ORM\Column(name="sorting", type="integer", length=10, nullable=true)
	 */
	private $sorting;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isParent", type="boolean" , nullable=true)
     */
    private $isParent = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="showAmount", type="boolean" ,nullable=true)
     */
    private $showAmount = false;

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

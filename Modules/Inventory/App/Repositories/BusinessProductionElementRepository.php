<?php

namespace Modules\Inventory\App\Repositories;
use Modules\Inventory\App\Entities\Product;
use Modules\Inventory\App\Entities\BusinessProductionElement;
use Doctrine\ORM\EntityRepository;


/**
 * ProductionElementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessProductionElementRepository extends EntityRepository
{
    public function insertProductionElement($particular, $data)
    {
        $em = $this->_em;
	    $existParticular = $this->_em->getRepository('BusinessBundle:BusinessProductionElement')->findBy(array('businessParticular'=>$particular,'particular' => $data['particularId']));
	    if(empty($existParticular)){
		    $entity = new BusinessProductionElement();
		    $entity->setBusinessParticular($particular);
		    $particular = $this->_em->getRepository('BusinessBundle:BusinessParticular')->find($data['particularId']);
		    $entity->setParticular($particular);
		    $entity->setPurchasePrice($particular->getPurchasePrice());
		    $entity->setSalesPrice($data['price']);
		    $entity->setQuantity($data['quantity']);
		    $em->persist($entity);
		    $em->flush();
	    }


    }

    public function getProductPurchaseSalesPrice(Product $particular)
    {

	    $qb = $this->createQueryBuilder('e');
	    $qb->select('sum(e.purchasePrice * e.quantity) as purchasePrice, sum(e.salesPrice* e.quantity) as salesPrice');
	    $qb->where('e.businessParticular = :particular');
	    $qb->setParameter('particular', $particular);
	    return $qb->getQuery()->getOneOrNullResult();
    }

    public function particularProductionElements(Product $particular)
    {
        $entities = $particular->getProductionElements();
        $data = '';
        $i = 1;

        /* @var $entity BusinessProductionElement */

        foreach ($entities as $entity) {

            $subTotal = $entity->getSalesPrice() * $entity->getQuantity() ;
            $subPurchase = $entity->getPurchasePrice() * $entity->getQuantity();
	        $unit = !empty($entity->getParticular()->getUnit() && !empty($entity->getParticular()->getUnit()->getName())) ? $entity->getParticular()->getUnit()->getName():'Unit';
            $data .= "<tr id='remove-{$entity->getId()}'>";
            $data .= "<td class='span1' >{$i}</td>";
            $data .= "<td class='span1' >{$entity->getParticular()->getParticularCode()}</td>";
            $data .= "<td class='span3' >{$entity->getParticular()->getName()}</td>";
            $data .= "<td class='span1' >{$entity->getQuantity()}</td>";
            $data .= "<td class='span1' >{$unit}</td>";
            $data .= "<td class='span1' >{$entity->getPurchasePrice()}</td>";
            $data .= "<td class='span1' >{$subPurchase}</td>";
            $data .= "<td class='span1' >{$entity->getSalesPrice()}</td>";
            $data .= "<td class='span1' >{$subTotal}</td>";
            $data .= "<td class='span1' ><a id='{$entity->getId()}' data-url='/business/product-production/{$particular->getId()}/{$entity->getId()}/delete' href='javascript:' class='btn red mini delete' ><i class='icon-trash'></i></a></td>";
            $data .= '</tr>';
            $i++;
        }
        return $data;
    }
}

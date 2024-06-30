<?php

namespace Modules\Production\App\Repositories;
use Doctrine\ORM\EntityRepository;
use Modules\Production\App\Entities\Item;
use Modules\Production\App\Entities\ProductionStock;

/**
 * DamageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductionStockReturnRepository extends EntityRepository
{

    public function findWithSearch($inventory,$data)
    {

        $startDate = isset($data['startDate'])  ? $data['startDate'].' 00:00:00' :'';
        $endDate =   isset($data['endDate'])  ? $data['endDate'].' 23:59:59' :'';

        $item = isset($data['item'])? $data['item'] :'';
        $vendor = isset($data['vendor'])? $data['vendor'] :'';
        $qb = $this->createQueryBuilder('damage');
        $qb->where("damage.config = :inventory");
        $qb->setParameter('inventory', $inventory);

        if (!empty($startDate) and $startDate !="") {
            $qb->andWhere("damage.updated >= :startDate");
            $qb->setParameter('startDate', $startDate);
        }
        if (!empty($endDate)) {
            $qb->andWhere("damage.updated <= :endDate");
            $qb->setParameter('endDate', $endDate);
        }

        if (!empty($item)) {
            $qb->join('damage.item', 'item');
            $qb->andWhere("item.sku = :sku");
            $qb->setParameter('sku', $item);
        }

        if (!empty($vendor)) {
            $qb->join('damage.item.vendor', 'v');
            $qb->andWhere("v.companyName = :companyName");
            $qb->setParameter('companyName', $vendor);
        }

        $qb->orderBy('damage.id','DESC');
        $qb->getQuery();
        return  $qb;

    }

    public function insertUpdate(Production $config , Item $item)
    {

        $em = $this->_em;
        $stock =  $this->findOneBy(array('item'=> $item->getId()));
        if($stock){
         $entity = $stock;
        }else{
         $entity = new ProductionStock();
        }
        $unit = !empty($item->getUnit() && !empty($item->getUnit()->getName())) ? $item->getUnit()->getName():'';
        $entity->setName($item->getName());
        $entity->setItem($item);
        $entity->setConfig($config);
        $entity->setUom($unit);
        $em->persist($entity);
        $em->flush();
    }

}

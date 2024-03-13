<?php

namespace Modules\Inventory\App\Repositories;
use Modules\Inventory\App\Entities\Product;
use Modules\Inventory\App\Entities\BusinessStore;
use Doctrine\ORM\EntityRepository;
use Modules\Domain\App\Entities\GlobalOption;

/**
 * BusinessConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessConfigRepository extends EntityRepository
{


    public function businessReset(GlobalOption $option)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->_em;
        $config = $option->getBusinessConfig()->getId();

        $ledger = $em->createQuery("DELETE BusinessBundle:BusinessStoreLedger e WHERE e.businessConfig = {$config}");
        $ledger->execute();

        $history = $em->createQuery('DELETE BusinessBundle:BusinessStockHistory e WHERE e.businessConfig = '.$config);
        $history->execute();

        $damage = $em->createQuery("DELETE BusinessBundle:BusinessDamage e WHERE e.businessConfig = {$config}");
        $damage->execute();

        $DistributionReturnItem = $em->createQuery('DELETE BusinessBundle:BusinessDistributionReturnItem e WHERE e.businessConfig = '.$config);
        $DistributionReturnItem->execute();

        $salesReturn = $em->createQuery('DELETE BusinessBundle:BusinessInvoiceReturn e WHERE e.businessConfig = '.$config);
        $salesReturn->execute();

        $sales = $em->createQuery('DELETE BusinessBundle:BusinessInvoice e WHERE e.businessConfig = '.$config);
        $sales->execute();

        $PurchaseReturn = $em->createQuery('DELETE BusinessBundle:BusinessPurchaseReturn e WHERE e.businessConfig = '.$config);
        $PurchaseReturn->execute();

	    $purchase = $em->createQuery('DELETE BusinessBundle:BusinessVendorStock e WHERE e.businessConfig = '.$config);
	    $purchase->execute();

	    $batch = $em->createQuery('DELETE BusinessBundle:BusinessBatch e WHERE e.businessConfig = '.$config);
        $batch->execute();

	    $purchase = $em->createQuery('DELETE BusinessBundle:BusinessPurchase e WHERE e.businessConfig = '.$config);
	    $purchase->execute();

        $stockAdjustment = $em->createQuery('DELETE BusinessBundle:ItemStockAdjustment e WHERE e.config = '.$config);
        $stockAdjustment->execute();


        $items = $this->_em->getRepository('BusinessBundle:BusinessParticular')->findBy(array('businessConfig'=>$config));

	    /* @var Product $item */

	    foreach ($items as $item){

		    $bpx = $em->createQuery('DELETE BusinessBundle:BusinessProductionExpense e WHERE e.productionItem = '.$item->getId());
            $bpx->execute();

		    $bpe = $em->createQuery('DELETE BusinessBundle:BusinessProductionElement e WHERE e.businessParticular = '.$item->getId());
            $bpe->execute();

		    $bp = $em->createQuery('DELETE BusinessBundle:BusinessProduction e WHERE e.businessParticular = '.$item->getId());
            $bp->execute();

		    $ip = $em->createQuery('DELETE BusinessBundle:BusinessInvoiceParticular e WHERE e.businessParticular = '.$item->getId());
            $ip->execute();

		    $item->setQuantity(0);
		    $item->setPurchaseQuantity(0);
		    $item->setSalesQuantity(0);
		    $item->setSalesReturnQuantity(0);
		    $item->setPurchaseReturnQuantity(0);
		    $item->setDamageQuantity(0);
		    $item->setMinQuantity(0);
		    $item->setRemainingQuantity(0);
		    $item->setOpeningQuantity(0);
		    $item->setBonusQuantity(0);
		    $item->setBonusSalesQuantity(0);
		    $item->setBonusPurchaseQuantity(0);
		    $item->setOpeningApprove(0);
		    $this->_em->flush($item);
	    }

        $sales = $em->createQuery('DELETE BusinessBundle:BusinessInvoice e WHERE e.businessConfig = '.$config);
        $sales->execute();

        $items = $this->_em->getRepository('BusinessBundle:BusinessStore')->findBy(array('businessConfig'=>$config));

        /* @var BusinessStore $item */

        foreach ($items as $item){
            $item->setBalance(0);
            $this->_em->flush($item);
        }
        $particular = $em->createQuery('DELETE BusinessBundle:BusinessParticular e WHERE e.businessConfig = '.$config);
        $particular->execute();

    }

    public function businessDelete(GlobalOption $option)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->_em;
        $config = $option->getBusinessConfig()->getId();

        $ledger = $em->createQuery("DELETE BusinessBundle:BusinessStoreLedger e WHERE e.businessConfig = {$config}");
        $ledger->execute();

        $history = $em->createQuery('DELETE BusinessBundle:BusinessStockHistory e WHERE e.businessConfig = '.$config);
        $history->execute();

        $damage = $em->createQuery("DELETE BusinessBundle:BusinessDamage e WHERE e.businessConfig = {$config}");
        $damage->execute();

        $DistributionReturnItem = $em->createQuery('DELETE BusinessBundle:BusinessDistributionReturnItem e WHERE e.businessConfig = '.$config);
        $DistributionReturnItem->execute();

        $salesReturn = $em->createQuery('DELETE BusinessBundle:BusinessInvoiceReturn e WHERE e.businessConfig = '.$config);
        $salesReturn->execute();

        $sales = $em->createQuery('DELETE BusinessBundle:BusinessInvoice e WHERE e.businessConfig = '.$config);
        $sales->execute();

        $PurchaseReturn = $em->createQuery('DELETE BusinessBundle:BusinessPurchaseReturn e WHERE e.businessConfig = '.$config);
        $PurchaseReturn->execute();

        $purchase = $em->createQuery('DELETE BusinessBundle:BusinessVendorStock e WHERE e.businessConfig = '.$config);
        $purchase->execute();

        $purchase = $em->createQuery('DELETE BusinessBundle:BusinessPurchase e WHERE e.businessConfig = '.$config);
        $purchase->execute();

        $stockAdjustment = $em->createQuery('DELETE BusinessBundle:ItemStockAdjustment e WHERE e.config = '.$config);
        $stockAdjustment->execute();

        $sales = $em->createQuery('DELETE BusinessBundle:BusinessInvoice e WHERE e.businessConfig = '.$config);
        $sales->execute();

        $stores = $em->createQuery('DELETE BusinessBundle:BusinessStore e WHERE e.businessConfig = '.$config);
        $stores->execute();

        $particular = $em->createQuery('DELETE BusinessBundle:BusinessParticular e WHERE e.businessConfig = '.$config);
        $particular->execute();

        $particular = $em->createQuery('DELETE BusinessBundle:Category e WHERE e.businessConfig = '.$config);
        $particular->execute();

    }
}

<?php

namespace Modules\Inventory\App\Repositories;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityRepository;
use Modules\Inventory\App\Entities\Product;
use Modules\Inventory\App\Entities\StockItem;
use function Doctrine\Common\Collections\exists;

/**
 * ItemTypeGroupingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StockItemRepository extends EntityRepository
{

    /**
     * @param $qb
     * @param $data
     */
    protected function handleWithSearch($qb,$data)
    {
        if(!empty($data))
        {
            $item = isset($data['item'])? $data['item'] :'';
            $color = isset($data['color'])? $data['color'] :'';
            $size = isset($data['size'])? $data['size'] :'';
            $vendor = isset($data['vendor'])? $data['vendor'] :'';
            $brand = isset($data['brand'])? $data['brand'] :'';
            $category = isset($data['category'])? $data['category'] :'';
            $unit = isset($data['unit'])? $data['unit'] :'';
            $barcode = isset($data['barcode'])? $data['barcode'] :'';

            if (!empty($barcode)) {

                $qb->join('e.purchaseItem', 'p');
                $qb->andWhere("p.barcode = :barcode");
                $qb->setParameter('barcode', $barcode);
            }

            if (!empty($item)) {
                $qb->andWhere("m.name = :name");
                $qb->setParameter('name', $item);
            }
            if (!empty($color)) {
                $qb->join('item.color', 'c');
                $qb->andWhere("c.name = :color");
                $qb->setParameter('color', $color);
            }
            if (!empty($size)) {
                $qb->join('item.size', 's');
                $qb->andWhere("s.name = :size");
                $qb->setParameter('size', $size);
            }
            if (!empty($vendor)) {
                $qb->join('item.vendor', 'v');
                $qb->andWhere("v.companyName = :vendor");
                $qb->setParameter('vendor', $vendor);
            }

            if (!empty($brand)) {
                $qb->join('item.brand', 'b');
                $qb->andWhere("b.name = :brand");
                $qb->setParameter('brand', $brand);
            }

            if (!empty($category)) {
                $qb->join('m.category','cat');
                $qb->andWhere("cat.name = :category");
                $qb->setParameter('category', $category);
            }

            if (!empty($unit)) {
                $qb->join('m.productUnit','u');
                $qb->andWhere("b.name = :unit");
                $qb->setParameter('unit', $unit);
            }

        }

    }

    public function insertStockItem($id,$data){

        $em = $this->_em;
        /** @var  $product Product  */
        $product = $em->getRepository(Product::class)->find($id);
        if($product->getStockItems()){
            $entity = new StockItem();
            $entity->setConfig($product->getConfig());
            $entity->setProduct($product);
            $entity->setName($product->getName());
            $entity->setDisplayName($product->getName());
            $entity->setPurchasePrice($data['purchase_price']);
            $entity->setPrice($data['sales_price']);
            $entity->setSalesPrice($data['sales_price']);
            $entity->setMinQuantity($data['min_quantity']);
            $em->persist($entity);
            $em->flush();
        }
    }

    public function checkAvailable($config ,$data)
    {
        $process = "true";
        $name = isset($data['name']) ? $data['name'] : '';

        $qb = $this->createQueryBuilder('e');
        $qb->select('COUNT(e.id) as count');
        $qb->where("e.config = :inventory")->setParameter('inventory', $config);
        $qb->andWhere("e.name ='{$name}'");
        $count = $qb->getQuery()->getOneOrNullResult();
        if ($count['count'] == 1 ){
            $process = "false";
        }
        return $process;
    }

    public function finishGoods($inventory,$modes = array(),$data = array())
    {

        $qb = $this->createQueryBuilder('item');
        $qb->join('item.masterItem','mi');
        $qb->leftJoin('mi.productGroup','g');
        $qb->leftJoin('item.unit','u');
        $qb->where("item.config = :inventory")->setParameter('inventory', $inventory);
        $qb->andWhere("item.isDelete !=1");
        $qb->andWhere("g.slug IN (:slugs)")->setParameter('slugs',$modes);
        $this->handleWithSearch($qb,$data);
        $qb->orderBy('item.name','ASC');
        $result = $qb->getQuery()->getResult();
        return  $result;

    }


    public function getProductionItems($inventory,$modes = array())
    {
        $modes = array('raw-materials','mid-production');
        $qb = $this->createQueryBuilder('item');
        $qb->join('item.product','mi');
        $qb->join('mi.productType','g');
        $qb->join('g.setting','setting');
        $qb->select('item.id');
        $qb->where("mi.config = :inventory")->setParameter('inventory', $inventory);
        $qb->andWhere("g.slug IN (:slugs)")->setParameter('slugs',$modes);
     //   $qb->andWhere("item.status = 1");
        $result = $qb->getQuery()->getArrayResult();
        return  $result;

    }

    public function modeWiseStockItem($inventory,$modes = array(),$data = array())
    {

        $qb = $this->createQueryBuilder('item');
        $qb->join('item.masterItem','mi');
        $qb->leftJoin('mi.taxTariff','tt');
        $qb->leftJoin('mi.productGroup','g');
        $qb->leftJoin('item.unit','u');
        $qb->select('item.id as id','item.name as name','u.name as uom','mi.hsCode as hsCode','tt.name as hsName');
        $qb->where("item.config = :inventory")->setParameter('inventory', $inventory);
        $qb->andWhere("g.slug IN (:slugs)")->setParameter('slugs',$modes);
        $qb->andWhere("item.isDelete IS NULL")->orWhere("item.isDelete = 0");
        $qb->andWhere("item.status = 1");
        $this->handleWithSearch($qb,$data);
        $qb->orderBy('item.name','ASC');
        $result = $qb->getQuery()->getArrayResult();
        return  $result;

    }

    public function filterFrontendProductWithSearch($data , $limit = 0)
    {
        if (!empty($data['sortBy'])) {

            $sortBy = explode('=?=', $data['sortBy']);
            $sort = $sortBy[0];
            $order = $sortBy[1];
        }

        $qb = $this->createQueryBuilder('product');
        $qb->leftJoin("product.masterItem",'masterItem');
        $qb->leftJoin('product.goodsItems','goodsitems');
        $qb->where("product.isWeb = 1");
        $qb->andWhere("product.status = 1");
        $qb->andWhere("product.inventoryConfig = :inventory");
        $qb->setParameter('inventory', $inventory);

        if (!empty($data['brand'])) {
            $qb->andWhere("product.brand IN(:brand)");
            $qb->setParameter('brand',$data['brand']);
        }

        if (!empty($data['size'])) {
            $qb->andWhere("goodsitems.size IN(:size)");
            $qb->setParameter('size',$data['size']);
        }

        if (!empty($data['color'])) {
            $qb->leftJoin('goodsitems.colors','colors');
            $qb->andWhere("colors.id IN(:color)");
            $qb->setParameter('color',$data['color']);
        }

        if (!empty($data['promotion'])) {
            $qb->andWhere("product.promotion IN(:promotion)");
            $qb->setParameter('promotion',$data['promotion']);
        }

        if (!empty($data['tag'])) {
            $qb->andWhere("product.tag IN(:tag)");
            $qb->setParameter('tag',$data['tag']);
        }

        if (!empty($data['discount'])) {
            $qb->andWhere("product.discount IN(:discount)");
            $qb->setParameter('discount',$data['discount']);
        }

        if (!empty($data['priceStart'])) {
            $qb->andWhere(' product.salesPrice >= :priceStart');
            $qb->setParameter('priceStart',$data['priceStart']);
        }

        if (!empty($data['priceEnd'])) {
            $qb->andWhere(' product.salesPrice <= :priceEnd');
            $qb->setParameter('priceEnd',$data['priceEnd']);
        }

        if (empty($data['sortBy'])){
            $qb->orderBy('product.updated', 'DESC');
        }else{
            $qb->orderBy($sort ,$order);
        }
        if($limit > 0 ) {
            $qb->setMaxResults($limit);
        }
        $res = $qb->getQuery();
        return  $res;

    }

    public function getFeatureCategoryProduct($inventory,$data,$limit){


        $qb = $this->createQueryBuilder('product');
        $qb->leftJoin("product.masterItem",'masterItem');
        $qb->leftJoin('product.goodsItems','goodsitems');
        $qb->where("product.isWeb = 1");
        $qb->andWhere("product.status = 1");
        $qb->andWhere("product.inventoryConfig = :inventory");
        $qb->setParameter('inventory', $inventory);

        if (!empty($data['brand'])) {
            $qb->andWhere("product.brand IN(:brand)");
            $qb->setParameter('brand',$data['brand']);
        }
        if (!empty($data['promotion'])) {
            $qb->andWhere("product.promotion IN(:promotion)");
            $qb->setParameter('promotion',$data['promotion']);
        }

        if (!empty($data['tag'])) {
            $qb->andWhere("product.tag IN(:tag)");
            $qb->setParameter('tag',$data['tag']);
        }

        if (!empty($data['discount'])) {
            $qb->andWhere("product.discount IN(:discount)");
            $qb->setParameter('discount',$data['discount']);
        }

        if (!empty($data['category'])) {

            $qb
                ->join('masterItem.category', 'category')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('category.path', "'". intval($data['category']) . "/%'"),
                        $qb->expr()->like('category.path', "'%/" . intval($data['category']) . "/%'")
                    )
                );
        }
        $qb->orderBy('product.updated', 'DESC');
        if($limit > 0 ) {
            $qb->setMaxResults($limit);
        }
        $res = $qb->getQuery();
        return  $res;
    }


    public  function getSumPurchaseItem($inventory , $excelImporter = ''){

        $qb = $this->createQueryBuilder('item');
        $qb->join('item.purchaseItems', 'pItem');
        $qb->join('pItem.purchase', 'purchase');
        $qb->select('item.id as id');
        $qb->addSelect('SUM(pItem.quantity) as quantity ');
        $qb->where("purchase.inventoryConfig = :inventory");
        $qb->setParameter('inventory', $inventory);
        $qb->where("purchase.process = :process");
        $qb->setParameter('process', 'imported');

        $qb->groupBy('item.id');
        $result = $qb->getQuery()->getResult();
        foreach ($result as $row ){
            $entity = $this->find($row['id']);
            $entity->setPurchaseQuantity($row['quantity']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }

    }

    public function checkDuplicateSKU($config,$data)
    {


        $type = $data['item']['productType'];
        $masterItem = $data['item']['name'];
        $vendor     = isset($data['item']['vendor']) ? $data['item']['vendor'] :'NULL';
        $brand  = isset($data['item']['brand']) ? $data['item']['brand']:'NULL';
        $category  = isset($data['item']['brand']) ? $data['item']['category']:'NULL';

        $qb = $this->createQueryBuilder('e');
        $qb->select('COUNT(e.id) countid');
        $qb->where("e.config = :config");
        $qb->setParameter('config', $config);
        $qb->andWhere("e.productType = :type")->setParameter('type', $type);
        $qb->andWhere("e.name = :name")->setParameter('name', $masterItem);
        if($category){
            $qb->andWhere("e.category = :category")->setParameter('category', $category);
        }
        if($vendor){
            $qb->andWhere("e.vendor = :vendor")->setParameter('vendor', $vendor);
        }
        if($brand){
            $qb->andWhere("e.brand = :brand")->setParameter('brand', $brand);
        }
        $count = $qb->getQuery()->getOneOrNullResult();
        $result = $count['countid'];
        return $result;

    }


    public function findWithSearch( $config, $parameter , $data ): array
    {


        if (!empty($parameter['orderBy'])) {
            $sortBy = $parameter['orderBy'];
            $order = $parameter['order'];
        }
        $modes = array("production-item","finish-goods");
        $masterItem         = isset($data['masterName'])? $data['masterName'] :'';
        $item               = isset($data['name'])? $data['name'] :'';
        $productGroup       = isset($data['groupName'])? $data['groupName'] :'';
        $productType        = isset($data['productType'])? $data['productType'] :'';
        $category           = isset($data['category'])? $data['category'] :'';
        $hscode           = isset($data['hsCode'])? $data['hsCode'] :'';
        $qb = $this->createQueryBuilder('item');
        $qb->join('item.masterItem','master');
        $qb->leftJoin('item.brand','brand');
        $qb->leftJoin('master.productGroup','productGroup');
        $qb->leftJoin('master.productType','type');
        $qb->leftJoin('master.category','category');
        $qb->leftJoin('item.unit','unit');
        $qb->leftJoin('master.unit','masterUnit');
        $qb->select('item.id as id','item.name as name','item.purchasePrice as purchasePrice','item.productionPrice as salesPrice',"item.barcode as barcode","item.sku as sku");
        $qb->addSelect('item.remainingQuantity as remainingQuantity',
            '(COALESCE(item.purchaseQuantity,0) - COALESCE(item.purchaseReturnQuantity,0)) as purchaseQuantity',
            '(COALESCE(item.salesQuantity,0) - COALESCE(item.salesReturnQuantity,0)) as salesQuantity',
            '(COALESCE(item.productionBatchItemQuantity,0) - COALESCE(item.productionBatchItemReturnQuantity,0)) as productionReceiveQuantity',
            '(COALESCE(item.productionExpenseQuantity,0) - COALESCE(item.productionExpenseReturnQuantity,0)) as productionIssueQuantity',
            'item.vatPercent as vatPercent',
            'item.damageQuantity as damageQuantity',
            'item.openingQuantity as openingQuantity',
            'item.ongoingQuantity as ongoingQuantity');
        $qb->addSelect("master.name as masterName","master.hsCode as hsCode");
        $qb->addSelect("unit.name as unitName");
        $qb->addSelect("type.name as productType");
        $qb->addSelect("productGroup.name as groupName");
        $qb->addSelect("brand.name as brandName");
        $qb->addSelect("category.name as categoryName");
        $qb->where("item.status IS NOT NULL");
        $qb->andWhere("item.config = :config")->setParameter('config', $config);
        $qb->andWhere("productGroup.slug NOT IN (:slugs)")->setParameter('slugs',$modes);
        if (!empty($item)) {
            $qb->andWhere("item.slug LIKE :slug")->setParameter('slug', "%{$item}%");
        }
        if (!empty($masterItem)) {
            $qb->andWhere("master.id = :masterId")->setParameter('masterId', $masterItem);
        }
        if (!empty($category)) {
            $qb->andWhere("category.name LIKE :c")->setParameter('c', "%{$category}%");
        }
        if (!empty($hscode)) {
            $qb->join("master.taxTariff",'hs');
            $qb->andWhere("hs.hsCode LIKE :hsCode")->setParameter('hsCode', "%{$hscode}%");
        }
        if (!empty($productGroup)) {
            $qb->andWhere("productGroup.id = :pid")->setParameter('pid', $productGroup);
        }
        if (!empty($productType)) {
            $qb->andWhere("type.id = :tid")->setParameter('tid', $productType);
        }
        $qb->andWhere("item.isDelete IS NULL")->orWhere("item.isDelete = 0");
        $qb->setFirstResult($parameter['offset']);
        $qb->setMaxResults($parameter['limit']);
        if ($parameter['orderBy']){
            $qb->orderBy($sortBy, $order);
        }else{
            $qb->orderBy('item.name', 'ASC');
        }
        $result = $qb->getQuery()->getArrayResult();
        return  $result;

    }

    public function depreciationGenerate($data)
    {

        $item = isset($data['item'])? $data['item'] :'';
        $category = isset($data['category'])? $data['category'] :'';

        $qb = $this->createQueryBuilder('item');
        $qb->where("item.status IS NOT NULL");
        if (!empty($item)) {
            $qb->join('item.item', 'm');
            $qb->andWhere("m.id = :name");
            $qb->setParameter('name', $item);
        }
        if (!empty($category)) {
            $qb->join('item.category', 'c');
            $qb->andWhere("c.id = :category");
            $qb->setParameter('category', $category);
        }
        $qb->orderBy('item.updated','DESC');
        $qb->getQuery();
        return  $qb;

    }

    public function getInventoryExcel($inventory,$data){

        $item = isset($data['item'])? $data['item'] :'';
        $gpSku = isset($data['gpSku'])? $data['gpSku'] :'';
        $category = isset($data['category'])? $data['category'] :'';
        $brand = isset($data['brand'])? $data['brand'] :'';

        $qb = $this->createQueryBuilder('item');
        $qb->join('item.masterItem', 'm');
        $qb->where("item.inventoryConfig = :inventory");
        $qb->setParameter('inventory', $inventory);

        if (!empty($item)) {

            $qb->andWhere("m.name = :name");
            $qb->setParameter('name', $item);
        }
        if (!empty($gpSku)) {
            $qb->andWhere($qb->expr()->like("item.gpSku", "'%$gpSku%'"  ));
        }
        if (!empty($category)) {
            $qb->join('m.category', 'c');
            $qb->andWhere("c.name = :category");
            $qb->setParameter('category', $category);
        }
        if (!empty($brand)) {
            $qb->join('m.brand', 'b');
            $qb->andWhere("b.name = :brand");
            $qb->setParameter('brand', $brand);
        }
        $qb->orderBy('item.gpSku','ASC');
        $result = $qb->getQuery()->getResult();
        return  $result;
    }

    public function getLastId($inventory)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('count(item.id)');
        $qb->from('InventoryBundle:Item','item');
        $qb->where("item.inventoryConfig = :inventory");
        $qb->setParameter('inventory', $inventory);
        $count = $qb->getQuery()->getSingleScalarResult();
        if($count > 0 ){
            return $count+1;
        }else{
            return 1;
        }

    }

    public function searchAutoComplete($item, $config)
    {

        $search = strtolower($item);
        $query = $this->createQueryBuilder('i');
        $query->select('i.id as id');
        $query->addSelect('i.name as name');
        $query->addSelect('i.slug as text');
        $query->addSelect('i.sku as sku');
        $query->addSelect('i.remainingQuantity as remainingQuantity');
        $query->where($query->expr()->like("i.slug", "'$search%'"  ));
        $query->andWhere("i.remainingQuantity > 0 ");
        $query->andWhere("i.config = :config");
        $query->setParameter('config', $config);
        $query->orderBy('i.name', 'ASC');
        $query->setMaxResults( '30' );
        return $query->getQuery()->getResult();

    }

    public function searchAutoCompleteAllItem($item,  $inventory)
    {

        $search = strtolower($item);
        $query = $this->createQueryBuilder('i');
        $query->join('i.config', 'ic');
        $query->select('i.id as id');
        $query->addSelect('i.name as name');
        $query->addSelect('i.skuSlug as text');
        $query->addSelect('i.sku as sku');
        $query->where($query->expr()->like("i.skuSlug", "'%$search%'"  ));
        $query->andWhere("ic.id = :inventory");
        $query->setParameter('inventory', $inventory);
        $query->groupBy('i.id');
        $query->orderBy('i.name', 'ASC');
        $query->setMaxResults( '30' );
        return $query->getQuery()->getResult();

    }

    public function updateRemoveStockQuantity(Item $stock , $fieldName = '', $minStock = 0 ){

        $em = $this->_em;
        if($fieldName == 'sales'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'sales');
            $stock->setSalesQuantity(floatval($quantity));
            $avg = $em->getRepository('TerminalbdInventoryBundle:SalesItem')->getItemAvgprice($stock);
            $stock->setSalesAvgPrice($avg['salesPrice']);
        }elseif($fieldName == 'sales-return'){
            $quantity = $this->_em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'sales-return');
            $stock->setSalesReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'purchase'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'purchase');
            $stock->setPurchaseQuantity(floatval($quantity));
            $avg = $em->getRepository('TerminalbdInventoryBundle:PurchaseItem')->getItemAvgprice($stock);
            $stock->setPurchaseAvgPrice($avg['purchasePrice']);
            $stock->setProductionPrice($avg['purchasePrice']);
            if(empty($stock->getPurchasePrice())){
                $stock->setPurchasePrice($avg['purchasePrice']);
            }
        }elseif($fieldName == 'purchase-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'purchase-return');
            $stock->setPurchaseReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'damage'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'damage');
            $stock->setDamageQuantity(floatval($quantity));
        }elseif($fieldName == 'opening'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock->getId(),'opening');
            $stock->setOpeningQuantity(floatval($quantity));
        }elseif($fieldName == 'assets'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'assets');
            $stock->setAssetsQuantity(floatval($quantity));
        }elseif($fieldName == 'assets-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'assets-return');
            $stock->setAssetsReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'production-issue'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-issue');
            $stock->setProductionIssueQuantity(floatval($quantity));
        }elseif($fieldName == 'production-inventory-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-inventory-return');
            $stock->setProductionInventoryReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'production-stock'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-stock');
            $stock->setProductionBatchItemQuantity(floatval($quantity));
        }elseif($fieldName == 'production-stock-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-stock-return');
            $stock->setProductionBatchItemReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'production-expense'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-expense');
            $stock->setProductionExpenseQuantity(floatval($quantity));
        }elseif($fieldName == 'production-expense-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-expense-return');
            $stock->setProductionExpenseReturnQuantity(floatval($quantity));
        }
        $em->persist($stock);
        $em->flush();
        $this->remainingQnt($stock);
    }

    public function updateRemoveStockReturnQuantity($item , $fieldName = ''){

        $em = $this->_em;

        if($fieldName == 'sales-return'){
            $quantity = $this->_em->getRepository('TerminalbdInventoryBundle:StockItem')->getSalesReturnInsertQnt($stock,'sales-return');
            $stock->setSalesReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'purchase-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'purchase-return');
            $stock->setPurchaseReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'production-inventory-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-inventory-return');
            $stock->setProductionReturnQuantity(floatval($quantity));
        }elseif($fieldName == 'production-stock-return'){
            $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-stock-return');
            $stock->setProductionStReturnQuantity(floatval($quantity));
        }
        $em->persist($stock);
        $em->flush();
        $this->remainingQnt($stock);
    }

    public function remainingQnt(Item $stock)
    {
        $em = $this->_em;
        $qnt = ($stock->getPurchaseQuantity() + $stock->getSalesReturnQuantity()+ $stock->getProductionBatchItemQuantity() + $stock->getProductionInventoryReturnQuantity() + $stock->getProductionExpenseReturnQuantity()) - ($stock->getPurchaseReturnQuantity() + $stock->getSalesQuantity() + $stock->getDamageQuantity() + $stock->getProductionBatchItemReturnQuantity() + $stock->getProductionIssueQuantity() + $stock->getProductionExpenseQuantity());
        $stock->setRemainingQuantity($qnt);
        $em->persist($stock);
        $em->flush();
    }


    public function productionPriceUpdated(ProductionItem $stock)
    {
        $em = $this->_em;
        $item = $stock->getItem();
        $item->setProductionPrice(round($stock->getSubTotal()));
        $item->setSalesprice(round($stock->getSubTotal()));
        $em->persist($item);
        $em->flush();
    }


    public function getPurchaseUpdateQnt(Purchase $entity){

        $em = $this->_em;

        /** @var $item PurchaseItem  */

        if($entity->getPurchaseItems()){

            foreach($entity->getPurchaseItems() as $item ){
                /** @var  $stock Item */
                $stock = $item->getItem();
                $quantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'purchase');
                $stock->setPurchaseQuantity(floatval($quantity));
                $avg = $em->getRepository('TerminalbdInventoryBundle:PurchaseItem')->getItemAvgprice($stock);
                $stock->setPurchaseAvgPrice($avg['purchasePrice']);
                if(empty($stock->getProductionPrice())){
                    $stock->setProductionPrice($item->getActualPurchasePrice());
                }
                if(empty($stock->getPurchasePrice())){
                    $stock->setPurchasePrice($avg['purchasePrice']);
                }
                $em->persist($stock);
                $em->flush();
                $this->remainingQnt($stock);
            }
        }
    }

    public function openingPurchaseUpdateQnt(PurchaseItem $item){

        $em = $this->_em;

        /** @var  $stock Item */
        $stock = $item->getItem();
        $purchaseQuantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'purchase');
        $quantity = $item->getQuantity();
        $stock->setOpeningQuantity(floatval($quantity));
        $stock->setPurchaseQuantity(floatval($purchaseQuantity));
        if(empty($stock->getPurchasePrice())){
            $stock->setPurchasePrice($item->getPurchasePrice());
        }
        $em->persist($stock);
        $em->flush();
        $this->remainingQnt($stock);
    }

    public function openingProductionQnt(ProductionBatch $entity){

        $em = $this->_em;

        /* @var $item ProductionBatchItem */
        foreach ($entity->getBatchItems() as $item):
            if($item->getReceiveQuantity() > 0){
                /** @var  $stock Item */
                $stock = $item->getItem();
                $quantity = $item->getReceiveQuantity();
                $productionStockQuantity = $em->getRepository('TerminalbdInventoryBundle:StockItem')->getItemUpdateQuantity($stock,'production-stock');
                $stock->setOpeningQuantity(floatval($quantity));
                $stock->setProductionBatchItemQuantity(floatval($productionStockQuantity));
                $em->persist($stock);
                $em->flush();
                $this->remainingQnt($stock);
            }
        endforeach;
    }


    public function getPurchaseReturnUpdateQnt(PurchaseReturn $entity){

        $em = $this->_em;

        /** @var $item PurchaseReturnItem  */

        if($entity->getReturnItems()){

            foreach($entity->getReturnItems() as $item ){
                /** @var  $stock Item */
                $stock = $item->getItem();
                $this->updateRemoveStockQuantity($stock,'purchase-return');
            }
        }
    }


    public function getSalesUpdateQnt(Sales $entity){

        /** @var $item SalesItem  */

        if($entity->getSalesItems()){

            foreach($entity->getsalesItems() as $item ){
                /** @var  $stock Item */
                $stock = $item->getItem();
                $this->updateRemoveStockQuantity($stock,'sales');
            }
        }
    }

    public function getSalesReturnUpdateQnt(SalesReturn $entity){

        /** @var $item SalesReturnItem  */

        if($entity->getSalesReturnItems()){

            foreach($entity->getSalesReturnItems() as $item ){
                /** @var  $stock Item */
                $stock = $item->getItem();
                $this->updateRemoveStockQuantity($stock,'sales-return');
            }
        }
    }


    public function productionUpdateQnt(ProductionIssue $item){

        $em = $this->_em;

        /** @var $item ProductionIssue  */

        $stock = $item->getItem();
        $this->updateRemoveStockQuantity($stock,'production-issue');
    }

    public function productionInhouseExpenseReceiveQnt(ProductionBatch $entity){


        $em = $this->_em;

        /* @var $item ProductionBatchItem */

        foreach ($entity->getBatchItems() as $item):
            if($item->getReceiveQuantity() > 0){
                foreach ($item->getProductionExpenses() as $expense){
                    $this->updateRemoveStockQuantity($expense->getItem() ,'production-expense');
                }
                $stock = $item->getItem();
                $this->updateRemoveStockQuantity($stock,'production-stock');
            }
        endforeach;
    }

    public function contractualProductionStock(ProductionReceiveBatch $receiveBatch)
    {
        foreach ($receiveBatch->getReceiveItems() as $receiveItem ){
            $this->updateRemoveStockQuantity($receiveItem->getItem(),'production-stock');
        }
    }

    public function softDelete(Item $entity)
    {
        $em = $this->_em;
        $date = date('d-m-Y H:i:s');
        $name ="{$entity->getName()}-{$date}";
        $entity->setStatus(false);
        $entity->setName($name);
        $entity->setIsDelete(true);
        $em->persist($entity);
        $em->flush();

        $production = $entity->getProductionItem();
        $production->setIsDelete(true);
        $production->setStatus(false);
        $em->persist($production);
        $em->flush();
    }



}

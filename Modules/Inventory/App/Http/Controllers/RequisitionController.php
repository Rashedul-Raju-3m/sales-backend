<?php

namespace Modules\Inventory\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AppsApi\App\Services\JsonRequestResponse;
use Modules\Core\App\Models\UserModel;
use Modules\Core\App\Models\VendorModel;
use Modules\Inventory\App\Http\Requests\PurchaseRequest;
use Modules\Inventory\App\Http\Requests\RequisitionRequest;
use Modules\Inventory\App\Models\ConfigModel;
use Modules\Inventory\App\Models\InvoiceBatchItemModel;
use Modules\Inventory\App\Models\InvoiceBatchModel;
use Modules\Inventory\App\Models\ProductModel;
use Modules\Inventory\App\Models\PurchaseItemModel;
use Modules\Inventory\App\Models\PurchaseModel;
use Modules\Inventory\App\Models\RequisitionItemModel;
use Modules\Inventory\App\Models\RequisitionMatrixBoardModel;
use Modules\Inventory\App\Models\RequisitionModel;
use Modules\Inventory\App\Models\SalesItemModel;
use Modules\Inventory\App\Models\SalesModel;
use Modules\Inventory\App\Models\StockItemHistoryModel;
use Modules\Inventory\App\Models\StockItemModel;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class RequisitionController extends Controller
{
    protected $domain;

    public function __construct(Request $request)
    {
        $userId = $request->header('X-Api-User');
        if ($userId && !empty($userId)) {
            $userData = UserModel::getUserData($userId);
            $this->domain = $userData;
        }
    }

    public function index(Request $request)
    {
        $data = RequisitionModel::getRecords($request, $this->domain);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'message' => 'success',
            'status' => Response::HTTP_OK,
            'total' => $data['count'],
            'data' => $data['entities']
        ]));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RequisitionRequest $request)
    {
        DB::beginTransaction();

        try {
            $input = $request->validated();
            $input['config_id'] = $this->domain['config_id'];
            $input['status'] = true;

            $findVendor = VendorModel::find($input['vendor_id']);
            if (!$findVendor) {
                throw new \Exception("Vendor not found");
            }

            $input['customer_id'] = $findVendor->customer_id;
            $input['customer_config_id'] = $this->domain['config_id'];
            $input['vendor_config_id'] = ConfigModel::where('domain_id', $findVendor->sub_domain_id)
                ->first()
                ->id;

            $requisition = RequisitionModel::create($input);

            if (!empty($input['items'])) {
                $itemsToInsert = [];
                $total = 0;

                foreach ($input['items'] as $val) {
                    $customerStockItem = StockItemModel::find($val['product_id']);
                    if (!$customerStockItem) {
                        throw new \Exception("Stock item not found");
                    }

                    $findProduct = ProductModel::find($customerStockItem->product_id);
                    if (!$findProduct) {
                        throw new \Exception("Product not found");
                    }

                    $total += $val['sub_total'];
                    $itemsToInsert[] = [
                        'requisition_id' => $requisition->id,
                        'customer_stock_item_id' => $val['product_id'],
                        'vendor_stock_item_id' => $customerStockItem->parent_stock_item,
                        'vendor_config_id' => $requisition->vendor_config_id,
                        'customer_config_id' => $requisition->customer_config_id,
                        'barcode' => $customerStockItem->barcode,
                        'quantity' => $val['quantity'],
                        'display_name' => $val['display_name'],
                        'purchase_price' => $val['purchase_price'],
                        'sales_price' => $val['sales_price'],
                        'sub_total' => $val['sub_total'],
                        'unit_id' => $findProduct->unit_id,
                        'unit_name' => $customerStockItem->uom,
                        'created_at' => now(),
                    ];
                }

                if (!empty($itemsToInsert)) {
                    RequisitionItemModel::insert($itemsToInsert);
                }
            }

            $requisition->update(['sub_total' => $total, 'total' => $total]);

            DB::commit();

            return response()->json([
                'status' => ResponseAlias::HTTP_OK,
                'message' => 'Insert successfully',
                'data' => $requisition,
            ], ResponseAlias::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $service = new JsonRequestResponse();
        $entity = PurchaseModel::getShow($id, $this->domain);
        if (!$entity) {
            $entity = 'Data not found';
        }
        $data = $service->returnJosnResponse($entity);
        return $data;
    }

    /**
     * Show the specified resource.
     */
    public function edit($id)
    {
        $service = new JsonRequestResponse();
        $entity = PurchaseModel::getEditData($id, $this->domain);
        if (!$entity) {
            $entity = 'Data not found';
        }
        $data = $service->returnJosnResponse($entity);
        return $data;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseRequest $request, $id)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $getPurchase = PurchaseModel::findOrFail($id);
            $data['remark']=$request->narration;
            $data['due'] = ($data['total'] ?? 0) - ($data['payment'] ?? 0);
            $getPurchase->fill($data);
            $getPurchase->save();

            PurchaseItemModel::class::where('purchase_id', $id)->delete();
            if (sizeof($data['items'])>0){
                foreach ($data['items'] as $item){
                    $item['stock_item_id'] = $item['product_id'];
                    $item['config_id'] = $getPurchase->config_id;
                    $item['purchase_id'] = $id;
                    $item['quantity'] = $item['quantity'] ?? 0;
                    $item['purchase_price'] = $item['purchase_price'] ?? 0;
                    $item['sub_total'] = $item['sub_total'] ?? 0;
                    $item['mode'] = 'purchase';
                    PurchaseItemModel::create($item);
                }
            }
            DB::commit();

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'message' => 'success',
                'status' => Response::HTTP_OK,
//                'data' => $purchaseData ?? []
            ]));
            $response->setStatusCode(Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollback();
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'message' => 'error',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => $e->getMessage(),
            ]));
            $response->setStatusCode(Response::HTTP_OK);
        }

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $service = new JsonRequestResponse();
        PurchaseModel::find($id)->delete();
        $entity = ['message' => 'delete'];
        return $service->returnJosnResponse($entity);
    }

    /**
     * Approve the specified resource from storage.
     */
    public function approve($id)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        // Start the database transaction
        DB::beginTransaction();

        try {
            $purchase = PurchaseModel::find($id);
            $purchase->update(['approved_by_id' => $this->domain['user_id']]);
            if (sizeof($purchase->purchaseItems)>0){
                foreach ($purchase->purchaseItems as $item){
                    // get average price
                    $itemAveragePrice = StockItemModel::calculateStockItemAveragePrice($item->stock_item_id,$item->config_id,$item);
                    //set average price
                    StockItemModel::where('id', $item->stock_item_id)->where('config_id',$item->config_id)->update(['average_price' => $itemAveragePrice]);

                    $item->update(['approved_by_id' => $this->domain['user_id']]);
                    StockItemHistoryModel::openingStockQuantity($item,'purchase',$this->domain);
                }
            }
            // Commit the transaction after all updates are successful
            DB::commit();

            $response->setContent(json_encode([
                'status' => Response::HTTP_OK,
                'message' => 'Approved successfully',
            ]));
            $response->setStatusCode(Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            $response->setContent(json_encode([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ]));
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }


    public function matrixBoard(Request $request)
    {
        $expectedDate = $request->query('expected_date');
        $vendorConfigId = $this->domain['config_id'];

        $getItems = RequisitionItemModel::where([
            ['inv_requisition_item.vendor_config_id', $vendorConfigId],
            ['inv_requisition.expected_date', $expectedDate]
        ])
            ->select([
                'inv_requisition_item.id',
                'inv_requisition_item.vendor_config_id',
                'inv_requisition_item.customer_config_id',
                'inv_requisition_item.vendor_stock_item_id',
                'inv_requisition_item.customer_stock_item_id',
                'inv_requisition_item.quantity',
                'inv_requisition_item.barcode',
                'inv_requisition_item.purchase_price',
                'inv_requisition_item.sales_price',
                'inv_requisition_item.sub_total',
                'inv_requisition_item.display_name',
                'inv_requisition_item.unit_name',
                'inv_requisition_item.unit_id',
                'inv_requisition.customer_id',
                'cor_customers.name as customer_name',
                'cor_customers.mobile as customer_mobile',
                'inv_requisition.expected_date',
                'vendor_stock_item.remaining_quantity',
                'vendor_stock_item.quantity as vendor_stock_quantity',
            ])
            ->join('inv_requisition', 'inv_requisition.id', '=', 'inv_requisition_item.requisition_id')
            ->join('inv_stock as vendor_stock_item', 'vendor_stock_item.id', '=', 'inv_requisition_item.vendor_stock_item_id')
            ->join('cor_customers', 'cor_customers.id', '=', 'inv_requisition.customer_id')
            ->get()
            ->toArray();

        $groupedItems = $this->groupByCustomerStockItemIdAndConfigId($getItems);


        if (!empty($groupedItems)) {
            $itemsToInsert = [];

            foreach ($groupedItems as $val) {
                // Check if a record already exists
                $exists = RequisitionMatrixBoardModel::where('vendor_config_id', $val['vendor_config_id'])
                    ->where('expected_date', $val['expected_date'])
                    ->exists();

                if (!$exists) {
                    $itemsToInsert[] = [
                        'customer_stock_item_id' => $val['customer_stock_item_id'],
                        'vendor_stock_item_id' => $val['vendor_stock_item_id'],
                        'customer_config_id' => $val['customer_config_id'],
                        'vendor_config_id' => $val['vendor_config_id'],
                        'unit_id' => $val['unit_id'],
                        'barcode' => $val['barcode'],
                        'purchase_price' => $val['purchase_price'],
                        'sales_price' => $val['sales_price'],
                        'quantity' => $val['quantity'],
                        'requested_quantity' => $val['quantity'],
                        'approved_quantity' => $val['quantity'],
                        'sub_total' => $val['sub_total'],
                        'display_name' => $val['display_name'],
                        'unit_name' => $val['unit_name'],
                        'customer_id' => $val['customer_id'],
                        'customer_name' => $val['customer_name'],
                        'expected_date' => $val['expected_date'],
                        'vendor_stock_quantity' => $val['vendor_stock_quantity'],
                        'status' => true,
                        'process' => 'Generated',
                        'created_at' => now(),
                    ];
                }
            }

            if (!empty($itemsToInsert)) {
                RequisitionMatrixBoardModel::insert($itemsToInsert);
            }
        }

        // Fetch the latest data after insertion
        $getItemWiseProduct = RequisitionMatrixBoardModel::where([
            ['vendor_config_id', $vendorConfigId],
            ['expected_date', $expectedDate]
        ])->get()->toArray();

        $shops = $this->getCustomerNames($getItemWiseProduct);

        $transformedData = $this->formatMatrixBoardData($getItemWiseProduct, $shops);

        return response()->json([
            'status' => ResponseAlias::HTTP_OK,
            'message' => 'Insert successfully',
            'data' => $transformedData,
            'customers' => $shops,
        ], ResponseAlias::HTTP_OK);
    }

    private function getCustomerNames(array $data): array
    {
        return collect($data)
            ->pluck('customer_name')
            ->unique()
            ->values()
            ->toArray();
    }

    private function formatMatrixBoardData(array $data, array $shops): array
    {
        return collect($data)
            ->groupBy('display_name')
            ->map(function ($group) use ($shops) {
                $base = [
                    'process' => $group->first()['process'],
                    'vendor_stock_item_id' => $group->first()['vendor_stock_item_id'],
                    'customer_stock_item_id' => $group->first()['customer_stock_item_id'],
                    'product' => $group->first()['display_name'],
                    'vendor_stock_quantity' => $group->first()['vendor_stock_quantity'],
                    'total_approved_quantity' => $group->sum('approved_quantity'),
                ];

                foreach ($shops as $shop) {
                    $base[strtolower(str_replace(' ', '_', $shop))] = 0;
                }

                $totalRequestQuantity = 0;
                foreach ($group as $item) {
                    $customerName = strtolower(str_replace(' ', '_', $item['customer_name']));
                    $base[$customerName.'_id'] = $item['id'];
                    $base[$customerName.'_approved_quantity'] = $item['approved_quantity'];
                    $base[$customerName.'_requested_quantity'] = $item['requested_quantity'];
                    $base[$customerName] = $item['quantity'];
                    $totalRequestQuantity+=$item['requested_quantity'];
                }

                $base['total_request_quantity'] = $totalRequestQuantity;
                $base['remaining_quantity'] = $group->first()['vendor_stock_quantity']-$base['total_approved_quantity'];

                return $base;
            })
            ->values()
            ->toArray();
    }

    private function groupByCustomerStockItemIdAndConfigId(array $data): Collection
    {
        return collect($data)->groupBy(fn($item) => $item['customer_stock_item_id'] . '-' . $item['customer_config_id'])
            ->map(function ($group) {
                return [
                    'id' => $group->first()['id'],
                    'customer_stock_item_id' => $group->first()['customer_stock_item_id'],
                    'vendor_stock_item_id' => $group->first()['vendor_stock_item_id'],
                    'customer_config_id' => $group->first()['customer_config_id'],
                    'vendor_config_id' => $group->first()['vendor_config_id'],
                    'quantity' => $group->sum('quantity'),
                    'sub_total' => $group->sum('sub_total'),
                    'purchase_price' => $group->first()['purchase_price'],
                    'sales_price' => $group->first()['sales_price'],
                    'display_name' => $group->first()['display_name'],
                    'barcode' => $group->first()['barcode'],
                    'unit_name' => $group->first()['unit_name'],
                    'unit_id' => $group->first()['unit_id'],
                    'customer_id' => $group->first()['customer_id'],
                    'customer_name' => $group->first()['customer_name'],
                    'customer_mobile' => $group->first()['customer_mobile'],
                    'expected_date' => $group->first()['expected_date'],
                    'remaining_quantity' => $group->first()['remaining_quantity'],
                    'vendor_stock_quantity' => $group->first()['vendor_stock_quantity'],
                ];
            });
    }

    /**
     * @throws \Exception
     */
    public function matrixBoardQuantityUpdate(Request $request)
    {
        if (!$request->has('quantity') || empty($request->quantity)) {
            throw new \Exception("Quantity not found");
        }

        if (!$request->has('id') || empty($request->id)) {
            throw new \Exception("Update id not found");
        }

        $findBoardMatrix = RequisitionMatrixBoardModel::find($request->id);
        if (!$findBoardMatrix) {
            throw new \Exception("Board matrix not found");
        }

        $findBoardMatrix->update([
            'quantity' => $request->quantity,
            'approved_quantity' => $request->quantity,
            'sub_total' => $request->quantity*$findBoardMatrix->purchase_price,
        ]);

        return response()->json([
            'status' => ResponseAlias::HTTP_OK,
            'message' => 'Update successfully',
        ], ResponseAlias::HTTP_OK);
    }
    

    /**
     * @throws \Exception
     */
    public function matrixBoardBatchGenerate(Request $request)
    {
        if (!$request->has('expected_date') || empty($request->expected_date)) {
            throw new \Exception("Expected date not found");
        }

        $vendorConfigId = $request->config_id;
        if (!$request->has('config_id') || empty($request->config_id)) {
            $vendorConfigId = $this->domain['config_id'];
        }

        // Fetch the latest data
        $getMatrixs = RequisitionMatrixBoardModel::where([
            ['vendor_config_id', $vendorConfigId],
            ['expected_date', $request->expected_date],
            ['process', 'Generated']
        ])->get()->toArray();

        $inputs =  collect($getMatrixs)
        ->groupBy('vendor_config_id')
        ->map(function ($group) {
            $base = [
                'process' => 'New',
                'config_id' => $group->first()['vendor_config_id'],
                'created_by_id' => $this->domain['user_id'],
                'sales_by_id' => $this->domain['user_id'],
                'approved_by_id' => $this->domain['user_id'],
                'quantity' => $group->sum('quantity'),
                'sub_total' => $group->sum('sub_total'),
                'total' => $group->sum('sub_total'),
                'invoice_date' => now(),
            ];

            return $base;
        })
        ->values()->toArray()[0];

        $batch = InvoiceBatchModel::create($inputs);

        $items =  collect($getMatrixs)
            ->groupBy('id')
            ->map(function ($group) use ($batch) {
                $base = [
                    'invoice_batch_id' => $batch->id,
                    'quantity' => $group->first()['quantity'],
                    'sales_price' => $group->first()['sales_price'],
                    'purchase_price' => $group->first()['purchase_price'],
                    'price' => $group->first()['purchase_price'],
                    'sub_total' => $group->first()['sub_total'],
                    'stock_item_id' => $group->first()['vendor_stock_item_id'],
                    'uom' => $group->first()['unit_name'],
                    'name' => $group->first()['display_name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                return $base;
            })
            ->values()->toArray();

        InvoiceBatchItemModel::insert($items);

        // Example usage
        $groupedSales = $this->groupSalesByCustomer($getMatrixs,$batch);

        foreach ($groupedSales as $sale) {
            $sales = SalesModel::create($sale);

            $salesItems = [];
            foreach ($sale['items'] as $item) {
                $salesItems[] = array_merge($item, ['sale_id' => $sales->id]);
            }

            // Insert sales items in bulk
            SalesItemModel::insert($salesItems);
        }

        foreach ($getMatrixs as $matrix) {
            RequisitionMatrixBoardModel::find($matrix['id'])->update([
                'process' => 'Confirmed',
            ]);
        }



        return response()->json([
            'status' => ResponseAlias::HTTP_OK,
            'message' => 'success',
        ], ResponseAlias::HTTP_OK);
    }

    public function groupSalesByCustomer(array $salesData,$batch)
    {
        $groupedSales = [];

        foreach ($salesData as $item) {
            $customerId = $item['customer_id'];

            if (!isset($groupedSales[$customerId])) {
                $groupedSales[$customerId] = [
                    'customer_id' => $customerId,
                    'config_id' => $item['vendor_config_id'],
                    'invoice_batch_id' => $batch->id,
                    'created_by_id' => $this->domain['user_id'],
                    'sales_by_id' => $this->domain['user_id'],
                    'sub_total' => 0,
                    'total' => 0,
                    'items' => []
                ];
            }

            $groupedSales[$customerId]['items'][] = [
                'sale_id' => '',
                'uom' => $item['unit_name'],
                'name' => $item['display_name'],
                'quantity' => $item['quantity'],
                'sales_price' => $item['sales_price'],
                'purchase_price' => $item['purchase_price'],
                'price' => $item['purchase_price'],
                'sub_total' => $item['sub_total'],
                'stock_item_id' => $item['vendor_stock_item_id'],
                'config_id' => $this->domain['config_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $groupedSales[$customerId]['sub_total'] += $item['sub_total'];
            $groupedSales[$customerId]['total'] += $item['sub_total'];
        }

        return array_values($groupedSales);
    }
}

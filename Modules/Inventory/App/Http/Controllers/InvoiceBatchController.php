<?php

namespace Modules\Inventory\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\AppsApi\App\Services\JsonRequestResponse;
use Modules\Core\App\Models\UserModel;
use Modules\Inventory\App\Entities\InvoiceBatch;
use Modules\Inventory\App\Http\Requests\InvoiceBatchTransactionRequest;
use Modules\Inventory\App\Http\Requests\ProductRequest;
use Modules\Inventory\App\Http\Requests\SalesRequest;
use Modules\Inventory\App\Models\InvoiceBatchModel;
use Modules\Inventory\App\Models\InvoiceBatchTransactionModel;
use function Symfony\Component\HttpFoundation\Session\Storage\Handler\getInsertStatement;

class InvoiceBatchController extends Controller
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
        $data = InvoiceBatchModel::getRecords($request, $this->domain);
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
    public function store(Request $request, EntityManager $em)
    {
        $customer = $request['customer_id'];
        $items = $request['sales_id'];
        $service = new JsonRequestResponse();
        $config_id = $this->domain['config_id'];
        $entity = InvoiceBatchModel::insertBatch($config_id,$customer,$items);
        $em->getRepository(InvoiceBatch::class)->invoiceBatchInsert($entity['id']);
        $data = $service->returnJosnResponse($entity);
        return $data;

    }

     /**
     * Store a newly created resource in storage.
     */
    public function provisionBill(InvoiceBatchTransactionRequest $request)
    {
        $batch_id = $request['batch_id'];
        $service = new JsonRequestResponse();
        $input = $request->validated();
        $input['batch_id'] = $batch_id;
        $entity = InvoiceBatchTransactionModel::create($input);
        $data = $service->returnJosnResponse($entity);
        return $data;

    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $service = new JsonRequestResponse();
        $entity = InvoiceBatchModel::getEditData($id, $this->domain);
        if (!$entity) {
            $entity = 'Data not found';
        }
        $data = $service->returnJosnResponse($entity);
        return $data;
    }

    /**
     * Show the specified resource for edit.
     */
    public function edit($id)
    {

        $entity = InvoiceBatchModel::getEditData($id, $this->domain);
        $status = $entity ? Response::HTTP_OK : Response::HTTP_NOT_FOUND;
        return response()->json([
            'message' => 'success',
            'status' => $status,
            'data' => $entity ?? []
        ], Response::HTTP_OK);

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, $id)
    {
        $data = $request->validated();
        $entity = InvoiceBatchModel::find($id);
        $entity->update($data);

        $service = new JsonRequestResponse();
        return $service->returnJosnResponse($entity);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $service = new JsonRequestResponse();
        InvoiceBatchModel::find($id)->delete();
        $entity = ['message' => 'delete'];
        return $service->returnJosnResponse($entity);
    }

}

<?php

namespace Modules\Inventory\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Ramsey\Collection\Collection;

class SalesModel extends Model
{
    use HasFactory;

    protected $table = 'inv_sales';
    public $timestamps = true;
    protected $guarded = ['id'];

    protected $fillable = [];

    public static function boot() {
        parent::boot();
        self::creating(function ($model) {
            $model->invoice = self::quickRandom();
            $date =  new \DateTime("now");
            $model->created_at = $date;
        });

        self::updating(function ($model) {
            $model->invoice = self::quickRandom();
            $date =  new \DateTime("now");
            $model->updated_at = $date;
        });
    }

    public static function quickRandom($length = 12)
    {
        $pool = '0123456789';
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    public function salesItems()
    {
        return $this->hasMany(SalesItemModel::class, 'sale_id');
    }

    public function insertSalesItems($sales,$items)
    {
        $timestamp = Carbon::now();
        foreach ($items as &$record) {
            $record['sale_id'] = $sales->id;
            $record['created_at'] = $timestamp;
            $record['updated_at'] = $timestamp;
        }
        SalesItemModel::insert($items);
    }

    public static function getRecords($request,$domain)
    {
        $page =  isset($request['page']) && $request['page'] > 0?($request['page'] - 1 ) : 0;
        $perPage = isset($request['offset']) && $request['offset']!=''? (int)($request['offset']):50;
        $skip = isset($page) && $page!=''? (int)$page * $perPage:0;

        $entities = self::where([['inv_sales.config_id',$domain['config_id']]])
            ->leftjoin('users as createdBy','createdBy.id','=','inv_sales.created_by_id')
            ->leftjoin('users as salesBy','salesBy.id','=','inv_sales.sales_by_id')
            ->leftjoin('acc_transaction_mode','acc_transaction_mode.id','=','inv_sales.transaction_mode_id')
            ->leftjoin('cor_customers','cor_customers.id','=','inv_sales.customer_id')
            ->select([
                'inv_sales.id',
                DB::raw('DATE_FORMAT(inv_sales.created_at, "%d-%m-%Y") as created'),
                'inv_sales.invoice as invoice',
                'inv_sales.sub_total as sub_total',
                'inv_sales.total as total',
                'inv_sales.payment as payment',
                'inv_sales.discount as discount',
                'inv_sales.discount_calculation as discount_calculation',
                'inv_sales.discount_type as discount_type',
                'inv_sales.invoice_batch_id',
                'cor_customers.id as customerId',
                'cor_customers.name as customerName',
                'cor_customers.mobile as customerMobile',
                'createdBy.username as createdByUser',
                'createdBy.name as createdByName',
                'createdBy.id as createdById',
                'salesBy.id as salesById',
                'salesBy.username as salesByUser',
                'salesBy.name as salesByName',
                'inv_sales.process as process',
                'acc_transaction_mode.name as mode_name',
                'cor_customers.address as customer_address',
                'cor_customers.balance as balance',
            ])->with('salesItems');

        if (isset($request['term']) && !empty($request['term'])){
            $entities = $entities->whereAny(['inv_sales.invoice','cor_customers.name','cor_customers.mobile','salesBy.username','createdBy.username','acc_transaction_mode.name','inv_sales.total'],'LIKE','%'.$request['term'].'%');
        }

        if (isset($request['customer_id']) && !empty($request['customer_id'])){
            $entities = $entities->where('inv_sales.customer_id',$request['customer_id']);
        }
        if (isset($request['start_date']) && !empty($request['start_date']) && empty($request['end_date'])){
            $start_date = $request['start_date'].' 00:00:00';
            $end_date = $request['start_date'].' 23:59:59';
            $entities = $entities->whereBetween('inv_sales.created_at',[$start_date, $end_date]);
        }
        if (isset($request['start_date']) && !empty($request['start_date']) && isset($request['end_date']) && !empty($request['end_date'])){
            $start_date = $request['start_date'].' 00:00:00';
            $end_date = $request['end_date'].' 23:59:59';
            $entities = $entities->whereBetween('inv_sales.created_at',[$start_date, $end_date]);
        }

        $total  = $entities->count();
        $entities = $entities->skip($skip)
            ->take($perPage)
            ->orderBy('inv_sales.updated_at','DESC')
            ->get();

        $data = array('count'=>$total,'entities'=>$entities);
        return $data;
    }


    public static function getShow($id,$domain)
    {
        $entity = self::where([
            ['inv_sales.config_id', '=', $domain['config_id']],
            ['inv_sales.id', '=', $id]
        ])
            ->leftjoin('cor_customers','cor_customers.id','=','inv_sales.customer_id')
            ->leftjoin('users as createdBy','createdBy.id','=','inv_sales.created_by_id')
            ->leftjoin('users as salesBy','salesBy.id','=','inv_sales.sales_by_id')
            ->leftjoin('acc_transaction_mode as transactionMode','transactionMode.id','=','inv_sales.transaction_mode_id')
//            ->leftjoin('uti_transaction_method as method','method.id','=','acc_transaction_mode.method_id')
            ->select([
                'inv_sales.id',
                DB::raw('DATE_FORMAT(inv_sales.updated_at, "%d-%m-%Y") as created'),
                'inv_sales.invoice as invoice',
                'inv_sales.sub_total as sub_total',
                'inv_sales.total as total',
                'inv_sales.payment as payment',
                'inv_sales.discount as discount',
                'inv_sales.discount_calculation as discount_calculation',
                'inv_sales.discount_type as discount_type',
                'cor_customers.id as customer_id',
                'cor_customers.name as customer_name',
                'cor_customers.mobile as customer_mobile',
                'createdBy.username as created_by_user_name',
                'createdBy.name as created_by_name',
                'createdBy.id as created_by_id',
                'salesBy.id as sales_by_id',
                'salesBy.username as sales_by_username',
                'salesBy.name as sales_by_name',
                'transactionMode.name as mode_name',
                'inv_sales.transaction_mode_id as transaction_mode_id',
                'inv_sales.process as process_id',
            ])
            ->with(['salesItems' => function ($query) {
                $query->select([
                    'inv_sales_item.id',
                    'inv_sales_item.sale_id',
                    'inv_sales_item.product_id',
                    'inv_sales_item.unit_id',
                    'inv_sales_item.item_name',
                    'inv_sales_item.quantity',
                    'inv_sales_item.sales_price',
                    'inv_sales_item.purchase_price',
                    'inv_sales_item.price',
                    'inv_sales_item.sub_total',
                    'uti_product_unit.name as unit_name',
                ])->join('uti_product_unit','uti_product_unit.id','=','inv_sales_item.unit_id');
            }])
            ->first();

        return $entity;
    }


    public static function getEditData($id,$domain)
    {
        $entity = self::where([
            ['inv_sales.config_id', '=', $domain['config_id']],
            ['inv_sales.id', '=', $id]
        ])
            ->leftjoin('cor_customers','cor_customers.id','=','inv_sales.customer_id')
            ->leftjoin('users as createdBy','createdBy.id','=','inv_sales.created_by_id')
            ->leftjoin('users as salesBy','salesBy.id','=','inv_sales.sales_by_id')
            ->leftjoin('acc_transaction_mode as transactionMode','transactionMode.id','=','inv_sales.transaction_mode_id')
            ->leftjoin('uti_settings','uti_settings.id','=','inv_sales.process')
            ->select([
                'inv_sales.id',
                DB::raw('DATE_FORMAT(inv_sales.updated_at, "%d-%m-%Y") as created'),
                DB::raw('DATE_FORMAT(inv_sales.updated_at, "%d-%M-%Y") as created_date'),
                'inv_sales.invoice as invoice',
                'inv_sales.sub_total as sub_total',
                'inv_sales.total as total',
                'inv_sales.payment as payment',
                'inv_sales.discount as discount',
                'inv_sales.discount_calculation as discount_calculation',
                'inv_sales.discount_type as discount_type',
                'cor_customers.id as customer_id',
                'cor_customers.name as customer_name',
                'cor_customers.mobile as customer_mobile',
                'createdBy.username as created_by_user_name',
                'createdBy.name as created_by_name',
                'createdBy.id as created_by_id',
                'salesBy.id as sales_by_id',
                'salesBy.username as sales_by_username',
                'salesBy.name as sales_by_name',
                'transactionMode.name as mode_name',
                'inv_sales.transaction_mode_id as transaction_mode_id',
                'inv_sales.process as process_id',
                'uti_settings.name as process_name',
                'cor_customers.address as customer_address',
            ])
            ->with(['salesItems' => function ($query) {
                $query->select([
                    'inv_sales_item.id',
                    'inv_sales_item.sale_id',
                    'inv_sales_item.product_id',
                    'inv_sales_item.unit_id',
                    'inv_sales_item.item_name',
                    'inv_sales_item.quantity',
                    'inv_sales_item.sales_price',
                    'inv_sales_item.purchase_price',
                    'inv_sales_item.price',
                    'inv_sales_item.sub_total',
                    'uti_product_unit.name as unit_name',
                ])->join('uti_product_unit','uti_product_unit.id','=','inv_sales_item.unit_id');
            }])
            ->first();

        return $entity;
    }


}

<?php

namespace Modules\Production\App\Models;


use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\App\Entities\Product;
use Modules\Inventory\App\Models\ProductModel;


class ProductionElements extends Model
{
    use HasFactory;

    protected $table = 'pro_element';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $fillable = [

    ];


    public static function boot() {
        parent::boot();
        self::creating(function ($model) {
            $date =  new \DateTime("now");
            $model->created_at = $date;
        });

        self::updating(function ($model) {
            $date =  new \DateTime("now");
            $model->updated_at = $date;
        });
    }

    public static function getRecords($request,$domain){

        $page =  isset($request['page']) && $request['page'] > 0?($request['page'] - 1 ) : 0;
        $perPage = isset($request['offset']) && $request['offset']!=''? (int)($request['offset']):500;
        $skip = isset($page) && $page!=''? (int)$page*$perPage:0;

        $entity = self::where([
                    ['pro_element.status', '=', 1],
                    ['pro_config.domain_id', '=', $domain['global_id']],
                ])
            ->join('pro_config','pro_config.id','=','pro_element.config_id')
            ->join('inv_stock','inv_stock.id','=','pro_element.material_id')
            ->join('inv_product','inv_product.id','=','inv_stock.product_id')
            ->leftjoin('uti_product_unit','uti_product_unit.id','=','inv_product.unit_id')
            ->select([
                'pro_element.id',
                'inv_product.name as product_name',
                'uti_product_unit.name as unit_name',
                'pro_element.quantity',
                'pro_element.material_quantity',
                'pro_element.price',
                'pro_element.sub_total',
                'pro_element.wastage_quantity',
                'pro_element.wastage_percent',
                'pro_element.wastage_amount',
                'pro_element.status',
            ]);


        $total  = $entity->count();
        $entities = $entity->skip($skip)
            ->take($perPage)
            ->orderBy('pro_element.id','DESC')
            ->get();

        $data = array('count'=>$total,'entities' => $entities);
        return $data;
    }


}
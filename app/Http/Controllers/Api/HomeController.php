<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use \Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Http;
use DB;

class HomeController extends Controller
{
    public function get_tabs($user_id)
    {
        $sql = "select * from tabs where deleted = 0 and user_id=".$user_id;
        $result = DB::select($sql);

        if ($result) {
            return response()->json([
                'status'=> true,
                'data' => ['tabs' => $result]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data' => ['tabs' => []]
            ],200);
        }
        
    }

    public function add_tab(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id'=> 'required',
            'name' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        $tab_id = DB::table('tabs')->insertGetId($post_data);
        return response()->json([
            'status'=> true,
            'data'=> ['tab_id' => $tab_id]
        ],200);
    }

    public function update_tab(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id'=> 'required',
            'name' => 'required',
            'id' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        DB::table('tabs')->where('id', $post_data['id'])->update($post_data);
        return response()->json([
            'status'=> true
        ],200);
    }

    public function delete_tab(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id'=> 'required',
            'tab_id' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        DB::table('tabs')->where('id', $post_data['tab_id'])->where('user_id', $post_data['user_id'])->update(['deleted' => 1]);

        return response()->json([
            'status'=> true
        ],200);
    }

    public function get_tab_items($tab_id)
    {
        $sql = "select * from tab_items where deleted = 0 and tab_id=".$tab_id;
        $result = DB::select($sql);

        if ($result) {
            return response()->json([
                'status'=> true,
                'data' => ['tab_items' => $result]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data' => ['tab_items' => []]
            ],200);
        }
    }

    public function add_tab_items(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'tab_id'=> 'required',
            'type' => 'required',
            'content'=> 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        $tab_item_id = DB::table('tab_items')->insertGetId($post_data);
        return response()->json([
            'status'=> true,
            'data'=> ['tab_item_id' => $tab_item_id]
        ],200);
    }

    public function update_tab_items(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'id'=> 'required',
            'content' => 'required',
            'type' => 'required',
            'tab_id' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        DB::table('tab_items')->where('id', $post_data['id'])->update($post_data);
        return response()->json([
            'status'=> true
        ],200);
    }

    public function delete_tab_items(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
        
            'tab_id' => 'required',
            'item_id' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        DB::table('tab_items')->where('tab_id', $post_data['tab_id'])->where('id', $post_data['item_id'])->update(['deleted' => 1]);

        return response()->json([
            'status'=> true
        ],200);
    }

    public function keyboard_survey(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id'=> 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        $feedback_id = DB::table('feedbacks')->insertGetId($post_data);
        return response()->json([
            'status'=> true,
            'data'=> ['feedback_id' => $feedback_id]
        ],200);
    }

    public function analytics(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        
        foreach ($post_data as $data) {
            $arr = [
                'user_id' => $data['user_id'],
                'tab_item_id' => $data['item_id'],
                'count' => $data['count'],
                'date' => $data['date']
            ];
            $analytics_id = DB::table('analytics')->insertGetId($arr);
        }
        
        return response()->json([
            'status'=> true
        ],200);
    }

    public function checkout(Request $request) 
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id' => 'required',
            'vendors' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        $user_id = $post_data['user_id'];
        $comment = $post_data['comment'];
        $number  = $post_data['number'];
        $vendors = $post_data['vendors'];
        $address_id = $post_data['address_id'];
       
        foreach ($vendors as $vendor) {
            $order_data = [
                'vendor_id' => $vendor['vendor_id'],
                'user_id' => $user_id,
                'number' => $number,
                'comment' => $comment,
                'delivery_price' => $vendor['delivery_price'],
                'total' => $vendor['total'],
                'company_id' => $vendor['company_id'],
                'discount' => $vendor['discount'],
                'address_id' => $address_id,
                'delivery_at' => @$vendor['delivery_at']
            ];
            $order_id = DB::table('op_orders')->insertGetId($order_data);
            foreach ($vendor['products'] as $product) {
                $order_items = [
                    'order_id' => $order_id,
                    'product_id' => $product['product_id'],
                    'price' => $product['price'],
                    'count' => $product['count']
                ];
                $res = DB::table('op_order_items')->insert($order_items);
            }
        }
        return response()->json([
            'status'=> true,
            'data' => ['message' => 'Order Placed']
        ],200);
    }

    public function test($id){
        return response() -> json([
            'status'=> true,
            'hdsj' => $id
        ],200);
    }

    public function get_past_orders($user_id,$lang)
    {
        if (!$user_id) {
            return response()->json(['status'=> false, 'error'=> ['user id is missing']],200);
        }

        $orders = DB::table('op_orders')->where('user_id', $user_id)->get();
        $data = [];
        if ($orders) {
            foreach ($orders as $key=> $order) {
                $data[$key]['order_details'] = $order;
                $order_products = DB::table('op_order_items')->where('order_id', $order->id)->get();
                $products = [];
                foreach ($order_products as $product) {
                    $sql = "SELECT vp.id as vendor_product_id,vp.vendor_id,vp.price,vp.min_count,vp.unit,b.name_".$lang." as brand_name, v.min_order,v.name_".$lang." as vendor_name, p.*,p.name_".$lang." as name FROM op_products p join op_brand b on p.brand_id = b.id join op_product_to_category pc on pc.product_id = p.id join op_vendor_products vp on vp.product_id = p.id join op_vendor v on v.id = vp.vendor_id where vp.id = ".$product->product_id;
                    //echo $sql;
                    $product = DB::select($sql);
                    $product = $product ? $product[0] : [];
                    array_push($products, $product);
                }
                $data[$key]['order_products'] = $products;         
            }
            return response()->json([
                'status'=> true,
                'data' => ['orders' => $data]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'error' => ['orders' => []]
            ],200);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Library\SslCommerz\SslCommerzNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request){

        // Capture the total amount from the request
        $totalAmount = 0;

        // Iterate through the products to calculate the total
        $products = $request->input('products');
        foreach ($products as $product) {
            $totalAmount += $product['price'];
        }

        $vat = $totalAmount*5/100;

        $post_data = array();
        $post_data['total_amount'] = $totalAmount; # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = uniqid(); // tran_id must be unique

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = 'Customer Name';
        $post_data['cus_email'] = 'customer@mail.com';
        $post_data['cus_add1'] = 'Customer Address';
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = '8801XXXXXXXXX';
        $post_data['cus_fax'] = "";

        # SHIPMENT INFORMATION
        $post_data['ship_name'] = "Store Test";
        $post_data['ship_add1'] = "Dhaka";
        $post_data['ship_add2'] = "Dhaka";
        $post_data['ship_city'] = "Dhaka";
        $post_data['ship_state'] = "Dhaka";
        $post_data['ship_postcode'] = "1000";
        $post_data['ship_phone'] = "";
        $post_data['ship_country'] = "Bangladesh";

        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = "Computer";
        $post_data['product_category'] = "Goods";
        $post_data['product_profile'] = "physical-goods";

        $update_product = DB::table('invoices')
            ->where('transaction_id', $post_data['tran_id'])
            ->updateOrInsert([
                'user_id' => 1,
                'total' => $totalAmount,
                'vat' => $vat,
                'payable' => ($totalAmount+$vat),
                'transaction_id' => $post_data['tran_id'],
                'customer_detail' => $post_data['cus_name']." ".$post_data['cus_add1'],
                'shipping_detail' => $post_data['ship_name']." ".$post_data['ship_add1'],
                'payment_status' => "Pending"
            ]);
        
        $sslc = new SslCommerzNotification();
        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->makePayment($post_data, 'hosted');

        if (!is_array($payment_options)) {
            print_r($payment_options);
            $payment_options = array();
        }
    }

    public function success(Request $request)
    {
        echo "Transaction is Successful";

        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        $sslc = new SslCommerzNotification();

        #Check order status in order tabel against the transaction id or order id.
        $order_details = DB::table('invoices')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'payment_status', 'currency', 'payable')->first();

        if ($order_details->payment_status == 'Pending') {
            $validation = $sslc->orderValidate($request->all(), $tran_id, $amount, $currency);

            if ($validation) {

                /*
                That means IPN did not work or IPN URL was not set in your merchant panel. Here you need to update order status
                in order table as Processing or Complete.
                Here you can also sent sms or email for successfull transaction to customer
                */
                $update_product = DB::table('invoices')
                    ->where('transaction_id', $tran_id)
                    ->update(['payment_status' => 'Complete']);

                echo "<br >Transaction is successfully Completed";
            }
        } else if ($order_details->payment_status == 'Processing' || $order_details->payment_status == 'Complete') {
            /*
             That means through IPN Order status already updated. Now you can just show the customer that transaction is completed. No need to udate database.
             */
            echo "Transaction is successfully Completed";
        } else {
            #That means something wrong happened. You can redirect customer to your product page.
            echo "Invalid Transaction";
        }


    }

    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order_details = DB::table('invoices')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'payment_status', 'currency', 'payable')->first();

        if ($order_details->payment_status == 'Pending') {
            $update_product = DB::table('invoices')
                ->where('transaction_id', $tran_id)
                ->update(['payment_status' => 'Failed']);
            echo "Transaction is Falied";
        } else if ($order_details->payment_status == 'Processing' || $order_details->payment_status == 'Complete') {
            echo "Transaction is already Successful";
        } else {
            echo "Transaction is Invalid";
        }

    }

    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order_details = DB::table('invoices')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'payment_status', 'currency', 'payable')->first();

        if ($order_details->payment_status == 'Pending') {
            $update_product = DB::table('invoices')
                ->where('transaction_id', $tran_id)
                ->update(['payment_status' => 'Canceled']);
            echo "Transaction is Cancel";
        } else if ($order_details->payment_status == 'Processing' || $order_details->payment_status == 'Complete') {
            echo "Transaction is already Successful";
        } else {
            echo "Transaction is Invalid";
        }


    }

    public function ipn(Request $request)
    {
        #Received all the payement information from the gateway
        if ($request->input('tran_id')) #Check transation id is posted or not.
        {

            $tran_id = $request->input('tran_id');

            #Check order status in order tabel against the transaction id or order id.
            $order_details = DB::table('invoices')
                ->where('transaction_id', $tran_id)
                ->select('transaction_id', 'payment_status', 'currency', 'payable')->first();

            if ($order_details->payment_status == 'Pending') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($request->all(), $tran_id, $order_details->payable, $order_details->currency);
                if ($validation == TRUE) {
                    /*
                    That means IPN worked. Here you need to update order status
                    in order table as Processing or Complete.
                    Here you can also sent sms or email for successful transaction to customer
                    */
                    $update_product = DB::table('invoices')
                        ->where('transaction_id', $tran_id)
                        ->update(['payment_status' => 'Processing']);

                    echo "Transaction is successfully Completed";
                }
            } else if ($order_details->payment_status == 'Processing' || $order_details->payment_status == 'Complete') {

                #That means Order status already updated. No need to udate database.

                echo "Transaction is already successfully Completed";
            } else {
                #That means something wrong happened. You can redirect customer to your product page.

                echo "Invalid Transaction";
            }
        } else {
            echo "Invalid Data";
        }
    }
}

<?php
//INCLUDED from api/class-base-api.php ~ main plugin file

class RevertTxns
{

    public $error;

    public function __construct()
    {

        add_action('rest_api_init', array($this, 'register_api_routes'));
    }

    public function register_api_routes()
    {
        register_rest_route('rimplenet/v1', '/transactions/revert', array(
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => array($this, 'api_reverse_txns'),
        ));
    }


    public function api_retrieve_txns(WP_REST_Request $request)
    {

        if ($this->validate()) {
            if ($this->checkIfAlreadyRefunded($_POST['post_id'])) {

                $data = [
                    'status_code' => 401,
                    'status' => false,
                    'message' => "Transaction Already Reversed!!",
                    'data' => ''
                ];
                return $this->returndata($data);
            } elseif ($this->checkIfAlreadyReversed($_POST['post_id'])) {
                // return false;
                $data = [
                    'status_code' => 401,
                    'status' => false,
                    'message' => "Transaction Already Reversed!!",
                    'data' => ''
                ];
                return $this->returndata($data);
            } else {
                $rimplewallet = new Rimplenet_Wallets();
                if ($rimplewallet->add_user_mature_funds_to_wallet($_POST['user_id'], $_POST['amount_to_add'], $_POST['wallet_id'], $_POST['note'], $tags = [])) {
                    $this->addAlreadyRefundedMeta($_POST['post_id']);
                    $data['amount_to_add'] = 0 - $_POST['amount_to_add'];

                    $rimplewallet->add_user_mature_funds_to_wallet($data['user_id2'], $data['amount_to_add'], $data['wallet_id2'], $data['note'], $tags = []);
                    $this->addAlreadyRefundedMeta($data['post_id2']);

                    $data = [
                        'status_code' => 200,
                        'status' => true,
                        'message' => "Transaction Refunded!!",
                        'data' => ''
                    ];
                    return $this->returndata($data);
                } else {
                    $data = [
                        'status_code' => 500,
                        'status' => false,
                        'message' => "Something went wrong!!",
                        'data' => ''
                    ];

                    return $this->returndata($data);
                }
            }
        }else{
            return $this->returndata($this->error);
        }
    }

    public function returndata($data)
    {
        //RETURN RESPONSE
        if ($data['status_code']) {
            //learn more about status_code to return at https://developer.mozilla.org/en-US/docs/Web/HTTP/Status

            //OR if !is_wp_error($request) 
            return new WP_REST_Response(
                array(
                    'status_code' => $data['status_code'],
                    'status' => $data['status'],
                    'message' => $data['message'],
                    'data' => $data['data']
                )
            );
        } else {

            $status_code = 400;
            $response_message = "Unknown Error";
            $data = array(
                "error" => "unknown_error"
            );
            return new WP_Error($status_code, $response_message, $data);
        }
    }




    private function checkIfAlreadyReversed($post_id)
    {
        $data = get_metadata('post', $post_id, "already_reversed", "true");


        if (empty($data) || $data == "" || $data == null || $data == false) {
            return false;
        } else {
            return true;
        }
    }

    private function addAlreadyReversedMeta($post_id)
    {
        if (add_post_meta($post_id, "already_reversed", "true")) {
            return true;
        } else {
            return false;
        }
    }

    private function addAlreadyRefundedMeta($post_id)
    {
        if (add_post_meta($post_id, "already_refunded", "true")) {
            return true;
        } else {
            return false;
        }
    }

    private function checkIfAlreadyRefunded($post_id)
    {
        $data = get_metadata('post', $post_id, "already_refunded", "true");


        if (empty($data) || $data == "" || $data == null || $data == false) {
            return false;
        } else {
            return true;
        }
    }

    public function getAllWallets($include_only = '')
    { //$exclude can be default, woocommerce, or db
        if (empty($include_only)) {
            $include_only = array('default', 'woocommerce', 'db');
        }

        $activated_wallets = array();
        $wallet_type = array('mature', 'immature');


        if (in_array('default', $include_only)) {

            $activated_wallets['rimplenetcoin'] = array(
                "id" => "rimplenetcoin",
                "name" => "RIMPLENET Coin",
                "symbol" => "RMPNCOIN",
                "symbol_position" => "right",
                "value_1_to_base_cur" => 0.01,
                "value_1_to_usd" => 1,
                "value_1_to_btc" => 0.01,
                "decimal" => 0,
                "min_wdr_amount" => 0,
                "max_wdr_amount" => INF,
                "include_in_withdrawal_form" => "yes",
                "include_in_woocommerce_currency_list" => "no",
                "action" => array(
                    "deposit" => "yes",
                    "withdraw" => "yes",
                )
            );
        }

        if (in_array('woocommerce', $include_only) and in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            //For Woocommerce
            $activated_wallets['woocommerce_base_cur']  = apply_filters('rimplenet_filter_woocommerce_base_cur', get_option('rimplenet_woocommerce_wallet_and_currency'));
        }

        /*
     $activated_wallets['btc'] = array( 
          "id" => "btc",  
          "name" => "Bitcoin", 
          "symbol" => "BTC", 
          "value_1_to_base_cur" => 0.01, 
          "value_1_to_usd" => 0.01, 
          "value_1_to_btc" => 0.01, 
          "decimal" => 8, 
          "include_in_woocommerce_currency_list" => 'no',
          "action" => array( 
              "deposit" => "yes",  
              "withdraw" => "yes", 
          ) 
      ); 
      
      */



        if (in_array('db', $include_only)) {
            //Add Wallets saved in database
            $WALLET_CAT_NAME = 'RIMPLENET WALLETS';
            $txn_loop = new WP_Query(
                array(
                    'post_type' => 'rimplenettransaction',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'rimplenettransaction_type',
                            'field'    => 'name',
                            'terms'    => $WALLET_CAT_NAME,
                        ),
                    ),
                )
            );
            if ($txn_loop->have_posts()) {
                while ($txn_loop->have_posts()) {
                    $txn_loop->the_post();
                    $txn_id = get_the_ID();
                    $status = get_post_status();
                    $wallet_name = get_the_title();
                    $wallet_desc  = get_the_content();

                    $wallet_decimal = get_post_meta($txn_id, 'rimplenet_wallet_decimal', true);
                    $min_wdr_amount = get_post_meta($txn_id, 'rimplenet_min_withdrawal_amount', true);
                    if (empty($min_wdr_amount)) {
                        $min_wdr_amount = 0;
                    }

                    $max_wdr_amount = get_post_meta($txn_id, 'rimplenet_max_withdrawal_amount', true);
                    if (empty($max_wdr_amount)) {
                        $max_wdr_amount = INF;
                    }

                    $wallet_symbol = get_post_meta($txn_id, 'rimplenet_wallet_symbol', true);
                    $wallet_symbol_position = get_post_meta($txn_id, 'rimplenet_wallet_symbol_position', true);
                    $wallet_id = get_post_meta($txn_id, 'rimplenet_wallet_id', true);
                    $include_in_withdrawal_form = get_post_meta($txn_id, 'include_in_withdrawal_form', true);
                    $include_in_woocommerce_currency_list = get_post_meta($txn_id, 'include_in_woocommerce_currency_list', true);

                    $activated_wallets[$wallet_id] = array(
                        "id" => $wallet_id,
                        "name" => $wallet_name,
                        "symbol" => $wallet_symbol,
                        "symbol_position" => $wallet_symbol_position,
                        "value_1_to_base_cur" => 0.01,
                        "value_1_to_usd" => 1,
                        "value_1_to_btc" => 0.01,
                        "decimal" => $wallet_decimal,
                        "min_wdr_amount" => $min_wdr_amount,
                        "max_wdr_amount" => $max_wdr_amount,
                        "include_in_withdrawal_form" => "yes",
                        "include_in_woocommerce_currency_list" => $include_in_woocommerce_currency_list,
                        "action" => array(
                            "deposit" => "yes",
                            "withdraw" => "yes",

                        )
                    );
                }
            }

            wp_reset_postdata();
        }


        return $activated_wallets;
    }


    public function validate()
    {
        $inputed_data = array(
            //    "request_id"=>$request_id, 
               "post_id"=>$_POST['post_id'], 
               "user_id"=>$_POST['user_id'], 
               "security_code"=>$_POST['security_code'],
               "user_id2"=>$_POST['user_id2'],
               "wallet_id"=>$_POST['wallet_id'],
               "wallet_id2"=>$_POST['wallet_id2'],
               "amount_to_add"=>$_POST['amount_to_add']
            );

           //Filter out empty inputs
            $empty_input_array = array(); 
            foreach($inputed_data as $input_key=>$single_data){ 
                if(empty($single_data)){
                  $empty_input_array[$input_key]  = "field_required" ;
                }
            } 



            if(!empty($empty_input_array)){
                //if atleast one required input is empty
                $data['status_code'] = 400;
                $data['status'] = "one_or_more_input_required";
                $data['message'] = "One or more input field is required";
                $data['data'] = $empty_input_array;
                $data["error"] = "one_or_more_input_required";
                $this->error=$data;
           }
           elseif(!empty($security_code) AND $security_code!=$security_code_ret ){
                // throw error if security fails 
                $data['status_code'] = 401;
                $data['status'] = "incorrect_security_credentials";
                $data['message'] = "Security verification failed";
                $data['data'] = array(
                    "error"=>"incorrect_security_credentials"
                ); 
                $this->error=$data;
           }
           elseif(!empty($extra_data) AND json_last_error() === JSON_ERROR_NONE ){
                // throw error if extra_data is not json 
                $data['status_code'] = 406;
                $data['status'] = "extra_data_not_json";
                $data['message'] = "extra_data input field should be json";
                $data['data'] = array(
                    "extra_data"=>$extra_data,
                    "error"=>json_last_error()
                ); 
                $this->error=$data;
           }
           else{
               return true;
           }
    }
}

$RetrieveTxns = new RevertTxns();

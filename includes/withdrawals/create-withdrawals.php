<?php

// namespace Debits\CreateDebits;

// use Rimplenet_Wallets;
use Withdrawals\Base;
use Traits\Wallet\RimplenetWalletTrait;

class RimplenetCreateWithdrawals extends Base
{

    use RimplenetWalletTrait;

    public $inputed_data;

    public $rules_data;

    protected function createWithdrawals(array $req = [])
    {
        // $request_id, $user_id, $amount_to_withdraw, $wallet_id, $wdr_dest, $wdr_dest_data, $note='Withdrawal',$extra_data=''


        $prop = empty($req) ? $this->req : $req;
        extract($prop);

        $wallet_obj = $this->getWallet($wallet_id);




        // $wallet_obj = new Rimplenet_Wallets();
        // $all_wallets = $wallet_obj->getWallets();
         
        // $user_wdr_bal = $wallet_obj->get_withdrawable_wallet_bal($user_id, $wallet_id);
        $user_wdr_bal =$this->get_withdrawable_wallet_bal($user_id, $wallet_id);
        // $user_non_wdr_bal = $wallet_obj->get_nonwithdrawable_wallet_bal($user_id, $wallet_id);
        $user_non_wdr_bal = $this->get_nonwithdrawable_wallet_bal($user_id, $wallet_id);
        
        // $amount_to_withdraw_formatted = getRimplenetWalletFormattedAmount($amount_to_withdraw,$wallet_id);
        $this->getRimplenetWalletFormattedAmount($amount,$wallet_id,$include_data='');
         
         
        // $walllets = $wallet_obj->getWallets();
        $walllets = $wallet_obj;
        $dec = $walllets['wallet_decimal'];
        $min_wdr_amount = $walllets['wallet_min_wdr_amount'];
        $min_wdr_amount_formatted = $this->getRimplenetWalletFormattedAmount($min_wdr_amount,$wallet_id);
        $max_wdr_amount = $walllets['wallet_max_wdr_amount'];
        $max_wdr_amount_formatted = $this->getRimplenetWalletFormattedAmount($max_wdr_amount,$wallet_id);
        $symbol = $walllets['wallet_symbol'];
        $name = $walllets['wallet_name'];    
        $balance = $symbol.number_format($balance,$dec);
        
        
          $this->inputed_data = array(
             "request_id"=>$request_id,"user_id"=>$user_id, "amount_to_withdraw"=>$amount_to_withdraw, "wallet_id"=>$wallet_id, "wdr_dest"=>$wdr_dest, "wdr_dest_data"=>$wdr_dest_data);
          $this->rules_data = array("amount_to_withdraw"=>$amount_to_withdraw, "user_wdr_bal"=>$user_wdr_bal, "min_wdr_amount"=>$min_wdr_amount, "max_wdr_amount"=>$max_wdr_amount,
        "amount_to_withdraw_formatted"=>$amount_to_withdraw_formatted);

          if ($this->checkEmpty()) return $this->response;
          if($this->withdrawalRules()) return $this->response;
      
           $amount_to_withdraw_ready = $amount_to_withdraw * -1;
           $meta_input = $wdr_dest_data;
           
           $txn_wdr_id = $this->rimplenet_fund_user_mature_wallet($request_id,$user_id, $amount_to_withdraw_ready, $wallet_id, $note);
           
           if (is_int($txn_wdr_id)) {
               
             
              $this->update($txn_wdr_id, $note);
               
               $this->response['status'] = "success";
               $this->response['message'] = "Withdrawal Request Submitted Successful";
               do_action('rimplenet_withdraw_user_wallet_bal_submitted_success',$txn_wdr_id, $wallet_id, $amount_to_withdraw, $user_id_withdrawing );
               $this->response['data'] = array("txn_id"=>$txn_wdr_id);
            }
            else{
                $wdr_info = json_decode($txn_wdr_id);
                $this->response['status'] = $wdr_info->status;
                $this->response['message'] = $wdr_info->message;
                $this->response['data'] = $wdr_info->data;
                
            }
       wp_reset_postdata();
      $this->response = [
        'status_code' => 201,
        'status' => 'success',
        'message' => "Wallet was successfully created",
        'data' => $wallet
      ];
      return true;
    }


    /**
     * Check Transaction Exists
     * @return
     */
    protected function debitsExists($value, string $type = '')
    {
        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE meta_key='txn_request_id' AND meta_value='$value'");
        if ($row) :
            $this->response['status_code'] = 409;
            $this->response['response_message'] = "Transaction Already Executed";
            $this->response['data']['txn_id'] = $row->post_id;
            return false;
            exit;
        endif;
        return true;
    }


    public function insertWithdrawal(Type $var = null)
    {
      # code...
    }


    public function checkEmpty(array $req = [])
    {
      // $empty_input_array = array(); 
      //Loop & Find out empty inputs
      foreach($this->inputed_data as $input_key=>$single_data){ 
          if(empty($single_data)){
            $this->error[$input_key]  = "field_required" ;
          }
      } 

      if (!empty($this->error)) {
        $this->response['message'] = "One or two fields are required";
        $this->response['error'] = $this->error;
        return true; exit;
    }

    return false;
    }


    public function withdrawalRules()
    {
      extract($this->rules_data);


      if($user_wdr_bal<=0){
        $this->response['error'] = "user_wdr_bal_is_zero_or_less";
        $this->response['message'] = "User Withdrawable Balance should not be Zero or Less";
        return true; exit;
        // $data = array("error"=>"User Withdrawable Balance should not be Zero or Less");
     }
      elseif($amount_to_withdraw<=0){
        $this->response['error'] = "amount_is_zero_or_less";
        $this->response['message'] = "Amount should not be Zero or Less";
        return true; exit;
        // $data = array("error"=>"Amount is zero or less");
     }
     elseif ($amount_to_withdraw<$min_wdr_amount) {
        $message = 'Requested amount ['.$amount_to_withdraw_formatted.'] is below minimum withdrawal amount, input amount not less than '.$min_wdr_amount_formatted;
        
        $this->response['error'] = "minimum_withdrawal_amount_error";
        $this->response['message'] = $message;
        return true; exit;
        // $data = array("error"=>"Amount Requested is not up to Minimum Withdrawal Amount");
     } 
     elseif ($amount_to_withdraw>$max_wdr_amount) {
        $message = 'Requested amount ['.$amount_to_withdraw_formatted.'] is above maximum withdrawal amount, input amount not more than '.$max_wdr_amount_formatted;
        
        $this->response['error'] = "maximum_withdrawal_amount_error";
        $this->response['message'] = $message;
        return true; exit;
        // $data = array("error"=>"Amount Requested is more than Maximum Withdrawal Amount");
     }
     return false;
    }


    public function update($txn_wdr_id, $note)
    {
      wp_set_object_terms($txn_wdr_id, 'WITHDRAWAL', 'rimplenettransaction_type', true);
             $modified_title = 'WITHDRAWAL ~ '.get_the_title( $txn_wdr_id);
             $meta_input["note"] = $note;
             $args = 
                array(
                'ID'    =>  $txn_wdr_id,
                'post_title'   => $modified_title,
                'post_status'   =>  'pending',
                'meta_input' => $meta_input
                );
                
      
               wp_update_post($args);
    }
}

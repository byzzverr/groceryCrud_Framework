<?php

class Cards_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->library('payhost');
      $this->load->model('financial_model');
   }

   public function get_card($card_id){
    
      $query = $this->db->query("SELECT * FROM cards WHERE id = ?", array($card_id));
      $result = $query->result_array();
      return $result;
   }

   public function get_cards($user_id){
    
      $query = $this->db->query("SELECT * FROM cards WHERE user_id = ?", array($user_id));
      $result = $query->result_array();
      return $result;
   }

   public function delete_card($id){
    
      if($this->db->query("DELETE FROM cards WHERE id = ? limit 1", array($id))){
        return true;
      }
      return false;
   }

    public function add_card($data){

      $clean_data = array(
        "CardNumber" => $data['card_number'],
        "CardExpiryDate" => $data['exp_month'].$data['exp_year']
        );

      $card_clean = array();
      $card = $this->payhost->save_card($clean_data);
      foreach ($card as $key => $value) {
        $card_clean[$key] = trim($value);
      }

      if($card_clean['StatusName'] == 'Completed' && $card_clean['StatusDetail'] == 'Vault successfull'){

        $data['token'] = $card_clean['VaultId']; // fake token for now
        $data['name'] = '------------' . substr($data['card_number'], 12); // fake name for now
        $data['createdate'] = date("Y-m-d H:i:s");

        unset($data['cvv']);
        unset($data['card_number']);

        if($this->is_token_unique($data['token'])){
          if($this->db->insert('cards',$data)){
            return $this->db->insert_id();
          }
          return false;
        }
      }
    
      return false;
   }  

  public function is_token_unique($token){

   
      $query = $this->db->query("SELECT * FROM cards WHERE token = ?", array($token));
      if($query->num_rows() == 0){
        return true;
      }
      return false;
   }  

  public function process_payment($data, $user){
      
      //make sure it exists in db.
      if(!$this->is_token_unique($data['card_token'])){

          $result = $this->payhost->card_payment($data, $user);

          $result->Method = $result->PaymentType->Method;
          $result->Detail = $result->PaymentType->Detail;
          unset($result->PaymentType);
          $res = $result;
          //back to rands
          $res->Amount = $res->Amount/100;
          $this->financial_model->update_cref($res);

          if($res->TransactionStatusCode == 1){

              $return = array(
                'success' =>  true,
                'data'    =>  $res
              );

          }else{


            $return = array(
                'success' =>  false,
                'reason'  =>  $res->ResultDescription,
                'data'  =>  $res,
              );
          }

      }else{

        $return = array(
            'success' =>  false,
            'reason'  =>  'token does not exist.'
          );

      }

      return $return;
  }

  function check_ownership($token, $user_id){

      $query = $this->db->query("SELECT * FROM cards WHERE token = ?", array($token));
      $card = $query->row_array();
      if($card && $card['user_id'] == $user_id){
        return true;
      }
      return false;
  }  


}

/*
138160_TEST-32n2mnpmn-eioniubn290nef-2n2fmn
*/
<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Monthly_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        
        $this->load->helper('url');
        $this->load->library('csvimport');
        $this->load->model('event_model');
        $this->load->model('product_model');
        $this->load->model('insurance_model');
        $this->load->model('financial_model');
        $this->load->model('comms_model');
        
    }

    function populate_sams_basic(){

        //check sam report here first always.
        //get the latest date and then go from there.

        $start_date = '2017-01-01 00:00:00';
        $end_date = '2017-12-01 00:00:00';

        $policies = $this->insurance_model->get_policies_by_branch($branch_id='all', $start_date, $end_date);

        foreach ($policies as $key => $value) {
            $this->db->insert('ins_sam_report', $value);
        }
    }

    function populate_sams_splits(){

        //get the application. get the product. add the 6 entities and 5 sales splits.

        $sams = $this->insurance_model->get_unprocessed_sams();
        $update = array();
        
        foreach ($sams as $key => $sam) {

            $data['application'] = $this->insurance_model->get_application_basic($sam['policy_number']);
            $data['sales_user'] = $this->insurance_model->define_user($data['application']['sold_by']);
            $data['product'] = $this->insurance_model->get_product($sam['ins_prod_id'], $data['sales_user']['link']['agency_id']);
            $premium = $data['application']['premium'];

            foreach ($data['product']['split'] as $entity => $value) {               

                if(strpos($entity, "_split") !== false){

                    $update[$entity.'_percentage'] = $value;
                    $update[$entity] = $premium * ($value/100);
                    if($entity != 'sales_channel_split' && $entity != 'insurer_split'){
                        $update[str_replace('_split', '', $entity)] = $data['product']['split'][str_replace('_split', '', $entity).'_name'];
                    }
                }
            }

            $total_prem = $premium;
            $premium = $update['sales_channel_split'];

            $update['agency_split'] = $premium * ($data['product']['sales_split']['agency']/100);
            $update['agency_split_percentage'] = ($update['agency_split']/$total_prem)*100;

            $update['branch_split'] = $premium * ($data['product']['sales_split']['branch']/100);
            $update['branch_split_percentage'] = ($update['branch_split']/$total_prem)*100;

            foreach ($data['product']['sales_split'] as $entity => $value) {

                if(strpos($entity, "tier_") !== false){

                    $update[$entity.'_split'] = $premium * ($data['product']['sales_split'][$entity]/100);
                    $update[$entity.'_split_percentage'] = ($update[$entity.'_split']/$total_prem)*100;
                }
            }

            $update['processed'] = 1;

            $this->db->where('id', $sam['id']);
            $this->db->update('ins_sam_report', $update);
        }
    }


function delete_demo_sales_comms(){

    $branch_users = $this->insurance_model->get_branch_sales_agents_id(14);
    $branch_users_string = implode(',', $branch_users);
    $query = $this->db->query("SELECT * FROM ins_applications WHERE sale_complete = 1 AND sold_by in ($branch_users_string)");

    if($query){
        foreach ($query->result_array() as $key => $application) {

        $this->insurance_model->update_application_status($application['policy_number'], 0);
        $this->comms_wallet_model->delete_policy_comms($application['policy_number']);
        $this->financial_model->delete_policy_comms($application['policy_number']);

        }
    }
}

    function comm_wallet_audit(){

        $start_date = '2017-11-01 00:00:01';
        $end_date = '2017-11-30 23:59:59';

        $totals = array(
            'premium' => 0,
            'wallet' => 0,
            'sam' => 0,
            'balance' => 0
            );

        $query = $this->db->query("SELECT a.policy_number, a.application_date, a.premium, a.sale_complete, s.premium as 'sam' FROM ins_applications a LEFT JOIN ins_sam_report s ON a.policy_number = s.policy_number WHERE a.application_date > '$start_date' AND a.application_date < '$end_date'");
        $apps = $query->result_array();

        echo '<h3>'.$start_date.'-'.$end_date.'</h3>';
        echo '<table cellpadding="5" cellspacing="5">';
        echo '<tr><th>Policy Number</th><th>Date</th><th>Premium</th><th>Wallet Total</th><th>SAM</th><th>Sale Complete</th><th>Flag</th><th>balance</th></tr>';

        foreach ($apps as $key => $app) {

            $app['flag_sam'] = 'green';
            $app['flag_wallet'] = 'green';
            $app['flag_balance'] = 'green';
            $balance = 0;

            $totals['premium'] += $app['premium'];

            $query = $this->db->query("SELECT sum(credit) as premium, (sum(credit)-sum(debit)) as balance FROM comm_wallet_transactions WHERE reference like '%".$app['policy_number']."'");
            $tr = $query->row_array();

            $app['wallet_total'] = 0;

            if($tr){
                $app['wallet_total'] = $tr['premium'];
                $totals['wallet'] += $app['wallet_total'];
                $balance = $tr['balance'];
                $totals['balance'] += $balance;
            }

            if ($app['sam']) {
                $totals['sam'] += $app['sam'];
            }

            if($app['wallet_total'] != $app['sam']){
                $app['flag_sam'] = 'red';
            }

            if($app['premium'] != $app['wallet_total'] && $app['wallet_total']){
                $app['flag_wallet'] = 'red';
            }

            if($balance != 0){
             $app['flag_balance'] = 'red';   
            }

            if($app['premium']){
                echo '<tr><td>'.$app['policy_number'].'</td><td>'.$app['application_date'].'</td><td style="background-color:'.$app['flag_wallet'].';">'.$app['premium'].'</td><td>'.$app['wallet_total'].'</td><td>'.$app['sam'].'</td><td>'.$app['sale_complete'].'</td><td style="background-color:'.$app['flag_sam'].';">'.$app['flag_sam'].'</td><td style="background-color:'.$app['flag_balance'].';">'.$balance.'</td></tr>';
            }
        }

        echo '<tr><td>TOTALS</td><td>&nbsp;</td><td>'.$totals['premium'].'</td><td>'.$totals['wallet'].'</td><td>'.$totals['sam'].'</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
        echo '</table>';

    }

}
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Transactions extends CI_Controller {
    private $geoCountryCode, $userDetails;
    function __construct() {
        parent::__construct();  
        $this->geoCountryCode = '';
        $user_id = $this->session->userdata('user_id');
        if ($user_id == "") {
            //redirect(base_url() . 'logout');
        }
        $this->load->helper('mall');
        $this->load->library("pagination");
        // $this->load->library("Pdf");
        $this->load->library('membership_plan_upgrade_methods');
        $this->geo->locate();
        $this->geoCountryCode = $this->geo->countryCode;
        $this->userDetails = getUserDetails($user_id);
        $this->load->model('Transaction_model');
        $this->load->model('Useraccount_model');
        $this->load->model('Report_model');
        $this->load->model('Cashback_model');
        $this->load->model('Email_model');
        $this->load->model('Product_model');
        $this->load->model('Transaction_model', 'export');
    }
    function reports() {
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $this->load->model('Transaction_model');
            $data['reoprts'] = $this->Transaction_model->get_report_data();
            $this->load->view('eazmemall/transactions/reports', $data);
        }
    }
    public function report_details() {
        $user_id = $this->uri->segment(4);
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $data['report_details'] = $this->Transaction_model->getReportDetails(['report_id' => $user_id]);
            $this->load->view('eazmemall/transactions/transaction_report_details', $data);
        }
    }
    public function transaction_report() {
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $data['ReportList'] = [];
            $this->session->set_userdata('filters', $this->input->post());
            $fromDate = $this->input->post('fromdate');
            //$transactionSummeryINR = $this->Transaction_model->getTransactionSummeryInINR($fromDate);
            //print_r($transactionSummeryINR);
            $data['sum_Transaction'] = $this->Transaction_model->totalTransaction();
            $this->load->view('eazmemall/transactions/transaction_report', $data);
        }
    }
    public function ajaxTransactionsReportData() {
        $dataList = [];
        if ($this->input->is_ajax_request()) {
            $postData = $this->input->post();
            $draw = $postData['draw'];
            $row = $postData['start'];
            $totalCount = $this->Transaction_model->getAllTransactionCount();
            $filterCount = $this->Transaction_model->getTransactionReportFiltered($postData);
            $ReportList = $this->Transaction_model->getAllTransactionReoprtAjax($postData);
            $i = 1;
            foreach ($ReportList as $data) {
                $stsClass = $stsText = '';
                $changeStatus = 1;
                if ($data->status == 0) {
                    $stsClass = 'info';
                    $stsText = 'Pending';
                } else if ($data->status == 1) {
                    $stsClass = 'success';
                    $stsText = 'Approved';
                    $changeStatus = 0;
                } else if ($data->status == 2) {
                    $stsClass = 'danger';
                    $stsText = 'Rejected';
                } else if ($data->status == 3) {
                    $stsClass = 'warning';
                    $stsText = 'N/A';
                }
                $stsicon = '<div><span class="badge-lg rounded-pill bg-' . $stsClass . ' text-white">' . $stsText . '</span></div>';
                $acticons = '<a title="View Details" href="' . base_url() . 'admin/transactions/details/' . $data->report_id . '" class="text-success"><i class="fa fa-eye mx-1"></i>View</a>';
                $dataList[] = [
                    "id" => $i,
                    "store_name" => str_replace(" [] ", " ", $data->store_name),
                    "username" => $data->username . '<span class="badge bgc-orange-d1 text-white badge-sm"> ' . $data->api_user_unique_id . '</span>',
                    "affiliate_network" => $data->affiliate_network,
                    "transaction_id" => $data->transaction_id,
                    "transaction_amount" => $data->membership_cashback_amount,
                    "transaction_date" => ($data->transaction_date != '0' ) ? date(APPDATEFORMAT, $data->transaction_date) : '-- -- --',
                    "status" => $stsicon,
                    "Actions" => $acticons
                ];
                $i++;
            }
        }
        echo json_encode(array("draw" => $draw, "recordsTotal" => $totalCount, "recordsFiltered" => $filterCount, "data" => $dataList), true);
        exit();
    }
    public function export_csv() {
        $this->load->helper('csv');
        $export_arr = array();
        $postData = $this->input->post();
        // $transaction_report_csv = $this->Transaction_model->get_ExportData();
        $transaction_report_csv = $this->Transaction_model->getAllTransactionReoprtAjax($postData);
        $title = array("Store Name", "User Name", "Affiliate Network", "Eazme id", "Transactions Amount", "Transactions Date", "Status");
        array_push($export_arr, $title);

        if (!empty($transaction_report_csv)) {
            foreach ($transaction_report_csv as $list) {
                if ($list->status == 0) {
                    $stsText = 'Pending';
                } else if ($list->status == 1) {
                    $stsText = 'Completed';
                } else if ($list->status == 2) {
                    $stsText = 'Rejected';
                }
                $list->store_name = str_replace(" [] ", " ", $list->store_name);
                array_push($export_arr, array($list->store_name, $list->username, $list->api_user_unique_id, $list->affiliate_network, $list->membership_cashback_amount, date(APPDATEFORMAT, $list->transaction_date), $stsText));
            }
        }
        convert_to_csv($export_arr, 'Transaction_Report-' . date('F d Y') . '.csv', ',');
    }
    public function nintyDaysHistoryDetails() {
        $cb_id = $this->uri->segment(4);
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $data['nintydays_transaction_details'] = $this->Transaction_model->getTransactionHistoryDetails(['cashback_id' => $cb_id]);
            $this->load->view('eazmemall/transactions/nintydays-transaction-history-details', $data);
        }
    }
    function threeMonthHistory() {
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $this->session->set_userdata('filters', $this->input->post());
            $data['transactionslist'] = $this->Transaction_model->getThreeMonthTransHistory();
            $this->load->view('eazmemall/transactions/three-month-history', $data);
        }
    }
    function sixMonthHistory() {
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $this->session->set_userdata('filters', $this->input->post());
            $data['threeMonthList'] = $this->Transaction_model->getSixMonthTransHistory();
            $this->load->view('eazmemall/transactions/six-month-history', $data);
        }
    }

    function totalManualCashbackHistory() {
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $this->session->set_userdata('filters', $this->input->post());
            $data['transactionslist'] = $this->Transaction_model->getAllManualCashbackTransactionHistory();
            $this->load->view('eazmemall/transactions/total-manual-cashback-history.php', $data);

        }
    }
    public function changeTransactionHistoryStatus() {
        $proc_result = ['status' => 0, 'message' => 'Invalid request'];
        if ($this->input->is_ajax_request() && $this->input->post('cashback-id') > 0 && $this->input->post('cashback_status') != '') {
            $cbObj = $this->Transaction_model->checkCashback(['cashback_id' => $this->input->post('cashback-id')]);
            if (isset($cbObj->cashback_id) && $cbObj->cashback_id > 0) { 
                $userInfo = getUserDetails($cbObj->user_id);
                $affiliate_type = $cbObj->affiliate_network;
                $store_name = str_replace(" [] ", " ", $cbObj->store_name);
                $sale_amount = $cbObj->sale_amount;
                $type = $cbObj->type;
                $accId = NULL;
                $isReward = 1;
                if ($type == 1) {
                    $isReward = 0;
                }
                $status = $this->input->post('cashback_status');                
                if($status == 1){
                    $tmplate = CASHBACK_EMAIL_TEMPLATE['approved'];
                    $accData = [
                        'create_date' => date("Y-m-d h:i:s"),
                        'user_id' => $cbObj->user_id,
                        'account_head_id' => 300,
                        'transaction_id' => $cbObj->transaction_id,
                        'amount' => $cbObj->membership_cashback,
                        'currency_id' => $userInfo->currency_id,
                        'affiliate_network' => $affiliate_type,
                        'is_approved' => 1,
                        'note' => 'Cashback approved from ' . $store_name . ' Store for ' . $affiliate_type . ' affiliate',
                        'is_tranfered' => 0,
                        'is_reward' => $isReward
                    ];
                    $whereClause = [
                        'user_id' => $cbObj->user_id,
                        'account_head_id' => 300,
                        'transaction_id' => $cbObj->transaction_id,
                    ];
                    $accId = 0;
                    $accArr = $this->Useraccount_model->getUseraccount($whereClause);
                    if(empty($accArr)){
                       $accId = $this->Useraccount_model->insertUseraccount($accData); 
                    }else{
                       $accId = $accArr->id;
                    }
                }else if($status == 2){
                    $tmplate = CASHBACK_EMAIL_TEMPLATE['rejected'];
                }else{
                    $tmplate = CASHBACK_EMAIL_TEMPLATE['pending'];
                }           
                $repData = [
                    'api_current_status' => 'verified',
                    'manual_status' => 1,
                ];
                $this->Report_model->updateReport($repData, $cbObj->report_id);

                //Update data into cashback table         
                $cbData = [
                    'approved_date' => date('Y-m-d'),
                    'api_current_status' => 'approved',
                    'user_account_id' => $accId,
                    'status' => $status,
                    'api_updated_date' => date('Y-m-d')
                ];
                $this->Cashback_model->updateCashback($cbData, $cbObj->cashback_id);
                $amt = $userInfo->currency_code . $cbObj->membership_cashback;
                if ($type == 2) {
                    $amt = $cbObj->membership_cashback . ' rewards';
                }
                $params = [
                    '###CASHBACK_AMOUNT###' => $amt,
                    '###STORE_NAME###' => $store_name,
                    '###TRANSACTION_DATE###' => date("d-M-Y", strtotime($cbObj->transaction_id)),
                    '###TRANSACTION_ID###' => $cbObj->transaction_id,
                    '###SALE_AMOUNT###' => $userInfo->currency_code . $sale_amount
                ];
                //$this->Email_model->sendMail('hakim@riseoo.com', '', '', $tmplate, $params);
                $this->Email_model->sendMail($userInfo->email, '', '', $tmplate, $params);
                $proc_result = ['status' => 1, 'message' => 'Transaction status updated successfully.'];
            }
        }
        echo json_encode($proc_result);
        exit();
    }

    public function ordersPaymentReport() {
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {
            mall_admin_redirect();
        } else {
            $this->session->set_userdata('filters', $this->input->post());
            $data['orders_payment_stats'] = $this->Transaction_model->getOrderPaymentReport();
            $data['product_currency'] = $this->Transaction_model->getProductCurrency();
            $this->load->view('eazmemall/transactions/orders-payment-report', $data);
        }
    }

    function claimcashbackAdmin() { 
        $sessionvar = $this->session->userdata('mallloggeduser');
        if ($sessionvar == "") {    
            mall_admin_redirect();
        } else {
            $this->session->set_userdata('filters', $this->input->post());
            $data['manage_claimcashback'] = $this->Transaction_model->getClaimCashback();
            $this->load->view('eazmemall/transactions/manage-claim-cashback', $data);
        }
    }
    public function changeClaimCashbackStatus() {
        $proc_result = ['status' => 0, 'message' => 'Invalid request'];
        if ($this->input->is_ajax_request() && $this->input->post('miss-cashback-id') > 0 && $this->input->post('claim_cashback_status') != '') {
            $cbObj = $this->Transaction_model->checkClaimCashback(['cashback_id' => $this->input->post('miss-cashback-id')]);
            if (isset($cbObj->cashback_id) && $cbObj->cashback_id > 0) { 
                $userInfo = getUserDetails($cbObj->user_id);
                $affiliate_type = $cbObj->affiliate_network;
                $store_name = str_replace(" [] ", " ", $cbObj->store_name);
                $sale_amount = $cbObj->ordervalue;
                $type = $cbObj->type;
                $coupon_code = $cbObj->coupon_code;
                $accId = NULL;
                $isReward = 1;
                if ($type == 1) {
                    $isReward = 0;
                }
                $status = $this->input->post('claim_cashback_status');                
                if($status == 1){
                    $tmplate = CASHBACK_EMAIL_TEMPLATE['approved'];
                    $accData = [
                        'create_date' => date("Y-m-d h:i:s"),
                        'user_id' => $cbObj->user_id,
                        'account_head_id' => 300,
                        'transaction_id' => $cbObj->transaction_ref_id,
                        'amount' => $cbObj->amount,
                        'currency_id' => $userInfo->currency_id,
                        'affiliate_network' => $affiliate_type,
                        'is_approved' => 1,
                        'note' => 'Cashback approved from ' . $store_name . ' Store for ' . $affiliate_type . ' affiliate',
                        'is_tranfered' => 0,
                        'is_reward' => $isReward
                    ];
                    $whereClause = [
                        'user_id' => $cbObj->user_id,
                        'account_head_id' => 300,
                        'transaction_id' => $cbObj->transaction_ref_id,
                    ];
                    $accId = 0;
                    $accArr = $this->Useraccount_model->getUseraccount($whereClause);
                    if(empty($accArr)){
                       $accId = $this->Useraccount_model->insertUseraccount($accData); 
                    }else{
                       $accId = $accArr->id;
                    }
                }else if($status == 2){
                    $tmplate = CASHBACK_EMAIL_TEMPLATE['rejected'];
                }else{
                    $tmplate = CASHBACK_EMAIL_TEMPLATE['pending'];
                }           
                $repData = [
                    'api_current_status' => 'verified',
                    'manual_status' => 1,
                ];
                $this->Report_model->updateReport($repData, $cbObj->report_id);

                //Update data into cashback table         
                $cbData = [
                    'approved_date' => date('Y-m-d'),
                    'api_current_status' => 'approved',
                    'user_account_id' => $accId,
                    'status' => $status,
                    'api_updated_date' => date('Y-m-d')
                ];
                $missCbData =[
                    'coupon_code'=>1,
                    'amount' => $cbObj->amount,
                    'status' => $status
                ];
                $this->Cashback_model->updateCashback($cbData, $cbObj->cashback_id);
                $this->Cashback_model->updateCoupon_code($missCbData, $cbObj->cashback_id);
                $amt = $userInfo->currency_code . $cbObj->membership_cashback;
                if ($type == 2) {
                    $amt = $cbObj->amount . ' rewards';
                }
                $params = [
                    '###CASHBACK_AMOUNT###' => $amt,
                    '###STORE_NAME###' => $store_name,
                    '###TRANSACTION_DATE###' => date("d-M-Y", strtotime($cbObj->transaction_ref_id)),
                    '###TRANSACTION_ID###' => $cbObj->transaction_ref_id,
                    '###SALE_AMOUNT###' => $userInfo->currency_code . $sale_amount
                ];
                $this->Email_model->sendMail('sajid30@riseoo.com', '', '', $tmplate, $params);
                // $this->Email_model->sendMail($userInfo->email, '', '', $tmplate, $params);
                $proc_result = ['status' => 1, 'message' => 'Transaction status updated successfully.'];
            }
        }
        echo json_encode($proc_result);
        exit();
    }

function claimCashbackAdminReport() { 
    $sessionvar = $this->session->userdata('mallloggeduser');
    if ($sessionvar == "") {
        mall_admin_redirect();
    } else {
        $this->session->set_userdata('filters', $this->input->post());
        $data['claim_cashback_report'] = $this->Transaction_model->getClaimCashbackReport();
        $this->load->view('eazmemall/transactions/claim-cashback-report', $data);
    }
}
  
}
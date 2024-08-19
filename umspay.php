<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * Payment Gateway Ums Pay portal.umeskiasoftwares.com
 *
 * created by @alvin-kiveu
 *
 **/

function umspay_validate_config()
{
  global $config;
  if (empty($config['umspay_api_key']) || empty($config['umspay_email'])) {
    sendTelegram("Ums Pay payment gateway not configured");
    r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup Ums Pay payment gateway, please tell admin"));
  }
}


function umspay_show_config()
{
  global $ui, $config;
  $ui->assign('_title', 'Ums Pay - Payment Gateway - ' . $config['CompanyName']);
  $ui->display('umspay.tpl');
}


function umspay_save_config()
{
  global $admin, $_L;
  $apikey = _post('apikey');
  $email = _post('email');
  $account_id = _post('account_id');

  $checkApiKey = ORM::for_table('tbl_appconfig')->where('setting', 'umspay_api_key')->find_one();
  if ($checkApiKey) {
    $checkApiKey->value = $apikey;
    $checkApiKey->save();
  } else {
    $checkApiKey = ORM::for_table('tbl_appconfig')->create();
    $checkApiKey->setting = 'umspay_api_key';
    $checkApiKey->value = $apikey;
    $checkApiKey->save();
  }

  $checkEmail = ORM::for_table('tbl_appconfig')->where('setting', 'umspay_email')->find_one();
  if ($checkEmail) {
    $checkEmail->value = $email;
    $checkEmail->save();
  } else {
    $checkEmail = ORM::for_table('tbl_appconfig')->create();
    $checkEmail->setting = 'umspay_email';
    $checkEmail->value = $email;
    $checkEmail->save();
  }

  $checkAccountId = ORM::for_table('tbl_appconfig')->where('setting', 'umspay_account_id')->find_one();
  if ($checkAccountId) {
    $checkAccountId->value = $account_id;
    $checkAccountId->save();
  } else {
    $checkAccountId = ORM::for_table('tbl_appconfig')->create();
    $checkAccountId->setting = 'umspay_account_id';
    $checkAccountId->value = $account_id;
    $checkAccountId->save();
  }

  

  _log('[' . $admin['username'] . ']: Ums Pay ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

  r2(U . 'paymentgateway/umspay', 's', $_L['Settings_Saved_Successfully']);
}




function umspay_create_transaction($trx, $user)
{
  global $config, $routes;
  $url = 'https://api.umeskiasoftwares.com/api/v1/intiatestk';
  $fields = [
    'api_key'   => '' . $config['umspay_api_key'] . '',
    'email'     => '' . $config['umspay_email'] . '',
    'amount'    => '' . $trx['price'] . '',
    'msisdn'    => '' . $user['phonenumber'] . '',
    'reference' => '' . $user['phonenumber'] . '',
  ];
  if (!empty($fields)) {
    $fields['account_id'] = $config['umspay_account_id'];
  }
  $payloadJson = json_encode($fields);
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $payloadJson,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
  ]);
  $response = curl_exec($curl);
  curl_close($curl);
  $responseData = json_decode($response);
  if (isset($responseData->tranasaction_request_id) && $responseData->success == "200") {
    $tranasaction_request_id = $responseData->tranasaction_request_id;
    $Time_Stamp = date("Ymdhis");
    $d = ORM::for_table('tbl_payment_gateway')
      ->where('username', $user['username'])
      ->where('status', 1)
      ->find_one();
    $pg_url_payment = 'index.php?_route=order/view/' . $d['id'] . '';
    $d->gateway_trx_id = $tranasaction_request_id;
    $d->pg_url_payment = $pg_url_payment;
    $d->pg_request = $user['id'];
    $d->expired_date = date('Y-m-d H:i:s', strtotime("+5 minutes"));
    $d->save();
    r2(U . "order/view/" . $d['id'], 's', Lang::T("Create Transaction Success, Please check your phone to process payment"));
  } else {
    sendTelegram("UmsPay payment failed\n\n" . json_encode($responseData, JSON_PRETTY_PRINT));
    r2(U . 'order/package', 'e', Lang::T("Failed to create transaction. $response"));
  }
}


function umspay_payment_notification()
{
  global $config;
  header("Content-Type: application/json");
  $UMSPayStkCallbackResponse = file_get_contents('php://input');
  $logFile = "UMSPayMpesaStkResponse.json";
  $log = fopen($logFile, "a");
  fwrite($log, $UMSPayStkCallbackResponse);
  fclose($log);
  $callbackContent = json_decode($UMSPayStkCallbackResponse);
  $ResponseCode = $callbackContent->ResponseCode;
  $TransactionID = $callbackContent->TransactionID;
  $trx = ORM::for_table('tbl_payment_gateway')
    ->where('gateway_trx_id', $TransactionID)
    ->find_one();
  if (!$trx) {
    return;
  }
  if ($ResponseCode == 0) {
    $user = ORM::for_table('tbl_customers')
      ->where('username', $trx['username'])
      ->find_one();
    if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], 'Umspay')) {
      _log("Ums Pay Payment Successfull,But Failed to activate your Package");
    }
    _log("Ums Pay Payment Successfull");
    $trx->pg_paid_response = json_encode($callbackContent);
    $trx->payment_method = 'Ums Pay';
    $trx->payment_channel = 'Ums Pay StkPush';
    $trx->paid_date = date('Y-m-d H:i:s');
    $trx->status = 2;
    $trx->save();
  } else {
    $trx->status = 1;
    $trx->save();
    exit();
  }
}



function umspay_get_status($trx, $user)
{
  global $config;
  $url = 'https://api.umeskiasoftwares.com/api/v1/transactionstatus';
  $fields = [
    'api_key'   => '' . $config['umspay_api_key'] . '',
    'email'     => '' . $config['umspay_email'] . '',
    'tranasaction_request_id' => '' . $trx['gateway_trx_id'] . '',
  ];
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
  $response = curl_exec($ch);
  curl_close($ch);
  $responseData = json_decode($response);
  if (isset($responseData->TransactionStatus) && $responseData->TransactionStatus == "Completed") {
    r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction successful."));
  } else {
    r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
  }
}

<?php

class jeeb extends base
{
  public $code;
  public $title;
  public $description;
  public $sort_order;
  public $enabled;

  private $api_key;
  private $test_mode;

  function jeeb()
  {
    $this->code             = 'jeeb';
    $this->title            = MODULE_PAYMENT_JEEB_TEXT_TITLE;
    $this->description      = MODULE_PAYMENT_JEEB_TEXT_DESCRIPTION;
    $this->api_key          = MODULE_PAYMENT_JEEB_API_KEY;
    $this->sort_order       = MODULE_PAYMENT_JEEB_SORT_ORDER;
    $this->testMode         = ((MODULE_PAYMENT_JEEB_TEST == 'True') ? true : false);
    $this->enabled          = ((MODULE_PAYMENT_JEEB_STATUS == 'True') ? true : false);
    $this->base_cur         = "";
    $this->target_cur       = "";
    $this->lang             = "";

    switch (MODULE_PAYMENT_JEEB_LANG) {
      case 'Auto-Select':
        $this->lang = NULL;
        break;
      case 'English':
        $this->lang = "en";
        break;
      case 'Persian':
        $this->lang = "fa";
        break;
      default:
        break;
    }

    switch (MODULE_PAYMENT_JEEB_BASE_CUR) {
      case 'BTC':
        $this->base_cur = "btc";
        break;
      case 'EUR':
        $this->base_cur = "eur";
        break;
      case 'IRR':
        $this->base_cur = "irr";
        break;
      case 'TOMAN':
        $this->base_cur = "toman";
        break;
      case 'USD':
        $this->base_cur = "usd";
        break;
      default:
        break;
    }

    $this->target_cur_arr       = explode(", ", MODULE_PAYMENT_JEEB_TARGET_CUR);
    for($i = 0; $i<sizeof($this->target_cur_arr); $i++){
      switch ($this->target_cur_arr[$i]) {
        case 'BTC':
          $this->target_cur .= "btc/";
          break;
        case 'XRP':
          $this->target_cur .= "xrp/";
          break;
        case 'XMR':
          $this->target_cur .= "xmr/";
          break;
        case 'LTC':
          $this->target_cur .= "ltc/";
          break;
        case 'BCH':
          $this->target_cur .= "bch/";
          break;
        case 'ETH':
          $this->target_cur .= "eth/";
          break;
        case 'TEST-BTC':
          $this->target_cur .= "test-btc/";
          break;
        default:
          break;
      }
    }
  }

  function javascript_validation()
  {
    return false;
  }

  function log($contents){
    error_log($contents);
  }

  function selection()
  {
    return array('id' => $this->code, 'module' => $this->title);
  }

  function pre_confirmation_check()
  {
    return false;
  }

  function confirmation()
  {
    return false;
  }

  function process_button()
  {
    return false;
  }

  function before_process()
  {
    return false;
  }

  function after_process()
  {
    global $insert_id, $db, $order;

    $info = $order->info;

    $configuration = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key='STORE_NAME' limit 1");

    $notification = zen_href_link('jeeb_notification.php', $parameters='', $connection='NONSSL', $add_session_id=true, $search_engine_safe=true, $static=true );


    require_once(dirname(__FILE__) . "/Jeeb/init.php");
    require_once(dirname(__FILE__) . "/Jeeb/version.php");

    $baseUri   = "https://core.jeeb.io/api/" ;
    $signature = $this->api_key;
    $baseCur   = $this->base_cur;
    $targetCur = $this->target_cur;
    $lang      = $this->lang;
    $order_total= $order->info['total'];

    if($baseCur=='toman'){
      $baseCur='irr';
      $order_total *= 10;
    }

    $amount = \Jeeb\Merchant\Order::convertBaseToTarget($baseUri, $order_total, $signature, $baseCur);
    $this->log('Signature ='.$signature.' base Url = '.$baseUri.' Convertion = '.$amount, true);

    $params = array(
      'orderNo'          => $insert_id,
      'value'            => (float) $amount,
      'webhookUrl'       => $notification,
      'callBackUrl'      => zen_href_link('account'),
      'allowReject'      => MODULE_PAYMENT_JEEB_TEST == "True" ? false : true,
      "coins"            => $targetCur,
      "allowTestNet"     => MODULE_PAYMENT_JEEB_TEST == "True" ? true : false,
      "language"         => $lang
    );



    $token = \Jeeb\Merchant\Order::createInvoice($baseUri, $amount, $params, $signature);
    $this->log('Token = '.$token.' Params ='.$params['requestAmount'], true);

    echo \Jeeb\Merchant\Order::redirectPayment($baseUri, $token);

    return false;

  }

  function check()
  {
      global $db;

      if (!isset($this->_check)) {
          $check_query  = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_JEEB_STATUS'");
          $this->_check = $check_query->RecordCount();
      }

      return $this->_check;
  }

  function install()
  {
    global $db, $messageStack;

    if (defined('MODULE_PAYMENT_JEEB_STATUS')) {
      $messageStack->add_session('Jeeb module already installed.', 'error');
      zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=jeeb', 'NONSSL'));

      return 'failed';
    }

    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Enable Jeeb Module', 'MODULE_PAYMENT_JEEB_STATUS', 'False', 'Enable the Jeeb bitcoin plugin?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Select Language', 'MODULE_PAYMENT_JEEB_LANG', 'False', 'Select the language of the Jeeb\'s payment page.', '6', '8', 'zen_cfg_select_option(array(\'Auto-Select\', \'English\', \'Persian\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values('Jeeb Signature', 'MODULE_PAYMENT_JEEB_API_KEY', '0', 'Your Jeeb Signature', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values('Sort order of display.', 'MODULE_PAYMENT_JEEB_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '8', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Enable test mode?', 'MODULE_PAYMENT_JEEB_TEST', 'False', 'Enable test mode to test your configuration and user experience.', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Select Base currency', 'MODULE_PAYMENT_JEEB_BASE_CUR', 'False', 'Select the currency of your site.', '6', '1', 'zen_cfg_select_option(array(\'BTC\', \'IRR\', \'EUR\', \'TOMAN\', \'USD\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Select Target currency', 'MODULE_PAYMENT_JEEB_TARGET_CUR', 'False', 'The target currency to which your base currency will get converted.', '6', '1', 'zen_cfg_select_multioption(array(\'BTC\', \'XRP\', \'ETH\', \'XMR\', \'BCH\', \'LTC\', \'TEST-BTC\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values('Set Pending Order Status', 'MODULE_PAYMENT_JEEB_PENDING_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) .  "', 'Status in your store when Jeeb Invoice status is pending.<br />(\'Pending\' recommended)', '6', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values('Set Awaiting Order confimation Status', 'MODULE_PAYMENT_JEEB_PAID_STATUS_ID', '2', 'Status in your store when Jeeb Invoice status is paid and awaiting confirmation from bitcoin network.<br />(\'Awaiting Confirmation\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values('Set Paid Order Status', 'MODULE_PAYMENT_JEEB_CONFIRMED_STATUS_ID', '2', 'Status in your store when Jeeb confirms the Invoice payment.<br />(\'Confirmed\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values('Set Expired Order Status', 'MODULE_PAYMENT_JEEB_EXPIRED_STATUS_ID', '7', 'Status in your store when Jeeb Invoice status is expired.<br />(\'Expired\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values('Set Canceled Order Status', 'MODULE_PAYMENT_JEEB_CANCELED_STATUS_ID', '7', 'Status in your store when Jeeb Invoice status is canceled.<br />(\'Canceled\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
  }

  function remove()
  {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE\_PAYMENT\_JEEB\_%'");
  }

  function keys()
  {
    return array(
      'MODULE_PAYMENT_JEEB_STATUS',
      'MODULE_PAYMENT_JEEB_API_KEY',
      'MODULE_PAYMENT_JEEB_SORT_ORDER',
      'MODULE_PAYMENT_JEEB_TEST',
      'MODULE_PAYMENT_JEEB_PENDING_STATUS_ID',
      'MODULE_PAYMENT_JEEB_CONFIRMED_STATUS_ID',
      'MODULE_PAYMENT_JEEB_PAID_STATUS_ID',
      'MODULE_PAYMENT_JEEB_EXPIRED_STATUS_ID',
      'MODULE_PAYMENT_JEEB_CANCELED_STATUS_ID',
      'MODULE_PAYMENT_JEEB_BASE_CUR',
      'MODULE_PAYMENT_JEEB_TARGET_CUR',
      'MODULE_PAYMENT_JEEB_LANG'
    );
  }
}

function jeeb_censorize($value) {
  return "(hidden for security reasons)";
}

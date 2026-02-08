<?php
class ControllerExtensionModuleShiprocket extends Controller {

    public function index() {
        
        
        
       $data['sync_now_url'] = $this->url->link('extension/module/shiprocket/syncNow', 'user_token=' . $this->session->data['user_token'], true);
       $data['url_search'] = $this->url->link(    'extension/module/shiprocket/search',    'user_token=' . $this->session->data['user_token'],    true);
     

        $this->load->language('extension/module/shiprocket');
        $this->document->setTitle('Shiprocket');

        $this->load->model('setting/setting');
        $this->load->model('sale/order');
        // Load order statuses for dropdown
        $this->load->model('localisation/order_status');

        $data['order_statuses'] =
         $this->model_localisation_order_status->getOrderStatuses();

            $data['shiprocket_after_status_id'] =
         (int)$this->config->get('shiprocket_after_status_id');


        // Active tab
        $data['tab'] = $this->request->get['tab'] ?? 'settings';

        // Messages
        $data['success'] = $this->session->data['success'] ?? '';
        $data['error']   = $this->session->data['error'] ?? '';
        unset($this->session->data['success'], $this->session->data['error']);

        // Settings
        $data['shiprocket_email']    = $this->config->get('shiprocket_email') ?? '';
        $data['shiprocket_password'] = $this->config->get('shiprocket_password') ?? '';

        // URLs
        $token = 'user_token=' . $this->session->data['user_token'];

        $data['url_settings'] = $this->url->link('extension/module/shiprocket', $token, true);
        $data['url_orders']   = $this->url->link('extension/module/shiprocket', $token . '&tab=orders', true);

        $data['action_save']  = $this->url->link('extension/module/shiprocket/save', $token, true);
        $data['action_fetch'] = $this->url->link('extension/module/shiprocket/fetch', $token, true);
        $data['action_test']  = $this->url->link('extension/module/shiprocket/testApi', $token, true);




$page = isset($this->request->get['page'])
    ? (int)$this->request->get['page']
    : 1;

$limit = 20;
$start = ($page - 1) * $limit;

$total_orders = $this->model_sale_order->getTotalOrders();


      // Orders tab
//$data['orders'] = [];

if ($data['tab'] === 'orders') {

   $orders = $this->model_sale_order->getOrders([
    'start' => $start,
    'limit' => $limit,
     'order' =>'DESC'
    ]);
    
    $this->load->library('pagination');

$pagination = new Pagination();
$pagination->total = $total_orders;
$pagination->page  = $page;
$pagination->limit = $limit;
$pagination->url   = $this->url->link(
    'extension/module/shiprocket',
    'user_token=' . $this->session->data['user_token'] . '&tab=orders&page={page}',
    true
);

$data['pagination'] = $pagination->render();
$data['results'] = sprintf(
    'Showing %d to %d of %d (%d Pages)',
    ($total_orders) ? $start + 1 : 0,
    ((($start + $limit) > $total_orders) ? $total_orders : ($start + $limit)),
    $total_orders,
    ceil($total_orders / $limit)
);

    
    foreach ($orders as $order) {
        
          $order_info = $this->model_sale_order->getOrder($order['order_id']);

        // Default values
        $shiprocket_status = 'not_pushed';
        $shipment_status  = '';
        $tracking_url     = '';

        // Get Shiprocket row
        $sr = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "shiprocket_order
            WHERE order_id = " . (int)$order['order_id'] . "
            ORDER BY date_added DESC
            LIMIT 1
        ");

        if ($sr->num_rows) {

            // Push status
            $shiprocket_status = $sr->row['status'];

            // Shipment status (cached only, no live fetch)
            $shipment_status = $sr->row['shipment_status'] ?? '';
            $tracking_url    = $sr->row['tracking_url'] ?? '';
        }

        $data['orders'][] = [
            'order_id'        => $order['order_id'],
            'customer'        => $order['customer'],
            'oc_status'       => $order['order_status'],
         'payment_method' => $order_info['payment_method'],
        'payment_code'   => $order_info['payment_code'],
            'sr_status'       => $shiprocket_status,
            'shipment_status' => $shipment_status,
            'tracking_url'    => $tracking_url,
            'push' => $this->url->link(
                'extension/module/shiprocket/pushOrder',
                'user_token=' . $this->session->data['user_token'] .
                '&order_id=' . $order['order_id'],
                true
            )
        ];
    }
}

         

        // Layout
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/module/shiprocket', $data)
        );
    }

    public function save() {
        $this->load->model('setting/setting');

        $this->model_setting_setting->editSetting('shiprocket', [
            'shiprocket_email'    => $this->request->post['shiprocket_email'] ?? '',
            'shiprocket_password' => $this->request->post['shiprocket_password'] ?? ''
        ]);

        $this->session->data['success'] = 'Shiprocket credentials saved';
        $this->response->redirect($this->url->link(
            'extension/module/shiprocket',
            'user_token=' . $this->session->data['user_token'],
            true
        ));
    }

    public function fetch() {
        $this->session->data['success'] = 'Credentials fetched from database';
        $this->response->redirect($this->url->link(
            'extension/module/shiprocket',
            'user_token=' . $this->session->data['user_token'],
            true
        ));
    }

    
    
    public function testApi() {

    $token = $this->getShiprocketToken();

    if ($token) {
        $this->session->data['success'] =
            'Shiprocket connected successfully. Token verified.';
    } else {
        $this->session->data['error'] =
            'Shiprocket connection failed. Check credentials.';
    }

    $this->redirectHome();
  }

    
 
 
private function getShiprocketPaymentMethod($order_info) {

    // payment_code is the source of truth
    if (!empty($order_info['payment_code']) && $order_info['payment_code'] === 'cod') {
        return 'COD';
    }

    return 'Prepaid';
}

    
   
   public function pushOrder() {

    $order_id = (int)($this->request->get['order_id'] ?? 0);

    if (!$order_id) {
        $this->session->data['error'] = 'Invalid order';
        $this->redirectOrders();
    }

   $token = $this->getShiprocketToken();

    if (!$token) {
    $this->session->data['error'] =
        'Shiprocket login failed. Check email/password in Settings.';
    $this->redirectOrders();
    }


    $this->load->model('sale/order');
    $order = $this->model_sale_order->getOrder($order_id);
    $order_products = $this->model_sale_order->getOrderProducts($order_id);

    if (!$order) {
        $this->session->data['error'] = 'Order not found';
        $this->redirectOrders();
    }

    // Prevent duplicate push ---removed
    
 $exists = $this->db->query("
    SELECT * FROM " . DB_PREFIX . "shiprocket_order
    WHERE order_id = " . (int)$order_id . "
    ORDER BY date_added DESC
    LIMIT 1
");

if ($exists->num_rows && $exists->row['status'] === 'pushed') {
    $this->session->data['error'] = 'Order already pushed to Shiprocket';
    return $this->redirectOrders();
}

// If previous attempt failed, allow retry
if ($exists->num_rows && $exists->row['status'] === 'failed') {
    // Clean up old failed attempt
    $this->db->query("
        DELETE FROM " . DB_PREFIX . "shiprocket_order
        WHERE order_id = " . (int)$order_id . "
    ");
}


    
    $order_items = [];

foreach ($order_products as $product) {
    $order_items[] = [
        'name'          => $product['name'],
        'sku'           => $product['model'] ?: 'SKU-' . $product['product_id'],
        'units'         => (int)$product['quantity'],
        'selling_price' => (float)$product['price'],
        'discount'      => 0,
        'tax'           => 0,
        'hsn'           => ''
    ];
}

$shiprocket_payment = $this->getShiprocketPaymentMethod($order);
    // Build Shiprocket payload

$payload = [
    'order_id' => (string)$order['order_id'],
    'order_date' => date('Y-m-d'),
    
    'billing_customer_name' => $order['firstname'],
    'billing_last_name'     => $order['lastname'],
    'billing_address'       => $order['payment_address_1'],
    'billing_city'          => $order['payment_city'],
    'billing_pincode'       => $order['payment_postcode'],
    'billing_state'         => $order['payment_zone'],
    'billing_country'       => $order['payment_country'],
    'billing_email'         => $order['email'],
    'billing_phone'         => $order['telephone'],
    'shipping_is_billing'   => true,
    'order_items'           => $order_items,

    'payment_method' => $shiprocket_payment,
    'cod' => ($shiprocket_payment === 'COD')
        ? (float)$order['total']
        : 0,

    'sub_total'             => (float)$order['total'],

    // Basic dimensions (required by Shiprocket)
    'length'  => 10,
    'breadth' => 10,
    'height'  => 10,
    'weight'  => 0.5
];

    // Call Shiprocket Create Order API
    $ch = curl_init('https://apiv2.shiprocket.in/v1/external/orders/create/adhoc');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log everything
    file_put_contents(
        DIR_LOGS . 'shiprocket.log',
        "Order {$order_id}\nHTTP {$httpcode}\n{$response}\n\n",
        FILE_APPEND
    );

if ($httpcode == 200) {

    $json = json_decode($response, true);

    $this->db->query("
        INSERT INTO oc_shiprocket_order SET
        order_id = {$order_id},
        sr_order_id = '" . $this->db->escape($json['order_id'] ?? '') . "',
        shipment_id = '" . $this->db->escape($json['shipment_id'] ?? '') . "',
        status = 'pushed',
        response = '" . $this->db->escape($response) . "'
    ");

    $this->session->data['success'] =
        'Order pushed to Shiprocket successfully';
        
        
   // ===============================
// UPDATE OPENCART ORDER STATUS
// ===============================

// CHANGE THIS to your actual "Processed" order_status_id
$processed_status_id =
    (int)$this->config->get('shiprocket_after_status_id');


// 1. Update main order table
$this->db->query("
    UPDATE " . DB_PREFIX . "order
    SET order_status_id = " . (int)$processed_status_id . "
    WHERE order_id = " . (int)$order_id . "
");

// 2. Insert order history record
$this->db->query("
    INSERT INTO " . DB_PREFIX . "order_history SET
    order_id = " . (int)$order_id . ",
    order_status_id = " . (int)$processed_status_id . ",
    notify = 0,
    comment = 'Order pushed to Shiprocket',
    date_added = NOW()
");


} else {

    $error_message = $this->getShiprocketErrorMessage($response, $httpcode);

    $this->db->query("
        INSERT INTO oc_shiprocket_order SET
        order_id = {$order_id},
        status = 'failed',
        response = '" . $this->db->escape($response) . "'
    ");

    $this->session->data['error'] =
        'Shiprocket Error: ' . $error_message;
}


    $this->redirectOrders();
}

   
   private function redirectOrders() {
    $this->response->redirect($this->url->link(
        'extension/module/shiprocket',
        'user_token=' . $this->session->data['user_token'] . '&tab=orders',
        true
    ));
}


private function getShiprocketToken() {

    // If token already saved, use it
    $token = $this->config->get('shiprocket_token');
    if ($token) {
        return $token;
    }

    // Otherwise login automatically
    $email    = $this->config->get('shiprocket_email');
    $password = $this->config->get('shiprocket_password');

    if (!$email || !$password) {
        return false;
    }

    $payload = json_encode([
        'email'    => $email,
        'password' => $password
    ]);

    $ch = curl_init('https://apiv2.shiprocket.in/v1/external/auth/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload
    ]);

    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        file_put_contents(
            DIR_LOGS . 'shiprocket.log',
            "Token fetch failed\n{$response}\n",
            FILE_APPEND
        );
        return false;
    }

    $json = json_decode($response, true);

    if (empty($json['token'])) {
        return false;
    }

    // Save token
    $this->load->model('setting/setting');
    $this->model_setting_setting->editSettingValue(
        'shiprocket',
        'shiprocket_token',
        $json['token']
    );

    return $json['token'];
}


private function getShiprocketErrorMessage($response, $httpcode) {

    if (!$response) {
        return 'No response from Shiprocket (HTTP ' . $httpcode . ')';
    }

    $json = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Invalid response from Shiprocket (HTTP ' . $httpcode . ')';
    }

    $messages = [];

    // 1️⃣ Detailed validation errors (most important)
    if (!empty($json['errors']) && is_array($json['errors'])) {
        foreach ($json['errors'] as $field => $errors) {
            if (is_array($errors)) {
                foreach ($errors as $err) {
                    $messages[] =
                        ucwords(str_replace('_', ' ', $field)) . ': ' . $err;
                }
            } else {
                $messages[] =
                    ucwords(str_replace('_', ' ', $field)) . ': ' . $errors;
            }
        }
    }

    // 2️⃣ Fallback to message
    if (!$messages && !empty($json['message'])) {
        $messages[] = $json['message'];
    }

    // 3️⃣ Last fallback
    if (!$messages) {
        $messages[] = 'Shiprocket request failed (HTTP ' . $httpcode . ')';
    }

    return implode(' | ', $messages);
}


private function fetchShipmentStatus($shipment_id) {

    $token = $this->getShiprocketToken();

    if (!$token || !$shipment_id) {
        return false;
    }

    $url = 'https://apiv2.shiprocket.in/v1/external/courier/track/shipment/' . (int)$shipment_id;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token
        ]
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode != 200) {
        return false;
    }

    $json = json_decode($response, true);

    if (empty($json['tracking_data']['shipment_status'])) {
        return false;
    }

    return [
        'status' => $json['tracking_data']['shipment_status'],
        'tracking_url' => $json['tracking_data']['track_url'] ?? ''
    ];
}


    private function redirectHome() {
        $this->response->redirect($this->url->link(
            'extension/module/shiprocket',
            'user_token=' . $this->session->data['user_token'],
            true
        ));
    }
    
    public function search() {
        
       

    $this->load->model('sale/order');

    $order_id = (int)($this->request->post['search_order_id'] ?? 0);

    $data = [];
    $data['orders'] = [];
    $data['tab'] = 'orders';

    if ($order_id) {

        $order = $this->model_sale_order->getOrder($order_id);

        if ($order) {

            // Shiprocket data
            $sr = $this->db->query("
                SELECT *
                FROM " . DB_PREFIX . "shiprocket_order
                WHERE order_id = " . (int)$order_id . "
                ORDER BY date_added DESC
                LIMIT 1
            ");

            $shiprocket_status = 'not_pushed';
            $shipment_status  = '';
            $tracking_url     = '';

            if ($sr->num_rows) {
                $shiprocket_status = $sr->row['status'];
                $shipment_status  = $sr->row['shipment_status'] ?? '';
                $tracking_url     = $sr->row['tracking_url'] ?? '';
            }

            $data['orders'][] = [
                'order_id'        => $order['order_id'],
                'customer'        => $order['firstname'] . ' ' . $order['lastname'],
                'oc_status'       => $order['order_status'],
                'sr_status'       => $shiprocket_status,
                'shipment_status' => $shipment_status,
                'tracking_url'    => $tracking_url,
                'push' => $this->url->link(
                    'extension/module/shiprocket/pushOrder',
                    'user_token=' . $this->session->data['user_token'] .
                    '&order_id=' . $order['order_id'],
                    true
                )
            ];
        } else {
            $this->session->data['error'] = 'Order not found';
        }
    } else {
        $this->session->data['error'] = 'Please enter a valid Order ID';
    }

    // Reload common data
    $this->load->language('extension/module/shiprocket');
    $this->document->setTitle('Shiprocket');

    $data['success'] = $this->session->data['success'] ?? '';
    $data['error']   = $this->session->data['error'] ?? '';
    unset($this->session->data['success'], $this->session->data['error']);

    $data['header']      = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer']      = $this->load->controller('common/footer');

    $this->response->setOutput(
        $this->load->view('extension/module/shiprocket', $data)
    );
}


public function syncNow() {

    $this->load->model('extension/module/shiprocket_sync');

    // 1. Get Shiprocket token
    $token = $this->getShiprocketToken();
    if (!$token) {
        $this->session->data['error'] = 'Shiprocket token missing';
        return $this->redirectOrders();
    }

    // 2. Get shipments to sync
    $shipments = $this->model_extension_module_shiprocket_sync->getShipmentsToSync();

    $updated = 0;
    $skipped = 0;

    foreach ($shipments as $row) {

        $shipment_id = (int)$row['shipment_id'];

        // Fetch live status from Shiprocket
        $data = $this->fetchShipmentStatus($shipment_id, $token);

        if (!$data) {
            $skipped++;
            continue;
        }

        // Update DB
        $this->model_extension_module_shiprocket_sync->updateShipment(
            $shipment_id,
            $data['status'],
            $data['tracking_url']
        );

        $updated++;
    }

    // 3. Show clear result message
    $this->session->data['success'] =
        "Shipment sync completed: {$updated} updated, {$skipped} skipped";

    $this->redirectOrders();
}




}


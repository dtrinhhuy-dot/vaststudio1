<?php
/**
 * Copy file nay thanh `includes/sepay.local.php` (da duoc .gitignore)
 * hoac cau hinh truc tiep trong Admin -> Cai dat Shop -> Cong thanh toan SePay
 * hoac thiet lap bien moi truong SEPAY_*.
 *
 * Lay thong tin tai: https://my.sepay.vn/company/ordersuccess
 */
return [
    'enabled' => true,
    'company_url' => 'https://my.sepay.vn/company/ordersuccess',
    'bank_code' => 'Vietcombank',
    'bank_name' => 'Vietcombank',
    'bank_account' => '',
    'bank_owner' => 'CONG TY TNHH VAST STUDIO VIETNAM',
    'prefix' => 'VAST',
    // API Token trong muc Cau hinh Cong ty -> API Access tren my.sepay.vn (chay duoc ca tren localhost!)
    'api_token' => '',
    // API Key Webhook trong muc Tich hop Webhooks tren my.sepay.vn (Authorization: Apikey <KEY>)
    'api_key' => '',
    'require_auth' => false,
];

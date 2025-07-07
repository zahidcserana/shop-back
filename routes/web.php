<?php

use Illuminate\Support\Facades\Hash;

$router->get('/', function () use ($router) {
    // $aa = base64_encode("6DAA0C0782E44BA5A4C3AD8A37724FA6:AcZnJGHA9yeqVF8N4JwRIZVnp_0jfREOLBWcQDLPnhW_zTfX");
    // dd(Hash::make('pFashion'));

    return $router->app->version();
});

$router->group(
    ['prefix' => 'api'],
    function () use ($router) {
        $router->get('/', function () use ($router) {
            return $router->app->version();
        });

        $router->post('auth/login', ['uses' => 'Auth\AuthController@userAuthenticate']);
        $router->post('users/verify', ['uses' => 'UserController@verifyUser']);
        $router->post('users/verification-code', ['uses' => 'UserController@getVerificationCode']);
        $router->post('cities', ['uses' => 'HomeController@districtList']);
        $router->post('areas/{id}', ['uses' => 'HomeController@areaList']);
        $router->post('companies/all', ['uses' => 'HomeController@CompanyList']);
        $router->post('users/mr/add', ['uses' => 'MrController@add']);
        $router->post('users/pharmacy-mr', ['uses' => 'MrController@addMR']);
        $router->post('subscription-response', ['uses' => 'SubscriptionController@subscriptionResponse']);

        $router->group(
            ['middleware' => 'jwt.auth'],
            function () use ($router) {
                /** Stock Balance */
                $router->get('stock-balance-create', ['uses' => 'StockBalanceController@stockBalance']);
                $router->get('stock-balances', ['uses' => 'StockBalanceController@index']);

                $router->get('users', ['uses' => 'UserController@showAllUsers']);
                $router->post('users', ['uses' => 'UserController@create']);
                $router->post('users/password', ['uses' => 'UserController@password']);

                /** Users */
                $router->post('users/admin/check', ['uses' => 'UserController@adminCheck']);
                $router->post('users/{id}', ['uses' => 'UserController@update']);
                $router->delete('users/{userId}', ['uses' => 'UserController@destroy']);

                /** MRs */
                $router->post('mrs/{id}', ['uses' => 'MrController@update']);
                $router->get('mrs', ['uses' => 'MrController@index']);
                $router->get('orders/smart-mr', ['uses' => 'MrController@smartMrOrderList']);

                /** Home */
                $router->get('data-sync', ['uses' => 'HomeController@dataSync']);
                $router->get('sale-data-sync', ['uses' => 'HomeController@saleDataSync']);
                /* for mr light */
                $router->get('summary', ['uses' => 'HomeController@summary']);
                $router->get('summary/sale-purchase', ['uses' => 'HomeController@salePurchasSummary']);

                /** Subscription Plan */
                $router->post('subscription', ['uses' => 'SubscriptionController@subscription']);
                $router->get('subscription-plan', ['uses' => 'SubscriptionController@subscriptionPlan']);
                $router->post('subscription-count', ['uses' => 'SubscriptionController@subscriptionCount']);
                $router->get('subscription-coupon', ['uses' => 'SubscriptionController@subscriptionCoupon']);
                $router->get('subscription-data', ['uses' => 'SubscriptionController@getSubscriptions']);

                /** Dashboard */
                $router->get('dashboard/summary', ['uses' => 'DashboardController@summary']);
                $router->get('dashboard/statistics', ['uses' => 'DashboardController@getStatistics']);
                $router->get('expired/medicines', ['uses' => 'MedicineController@getExpiryMedicine']);

                /** Medicine */
                $router->get('medicines/expired-date', ['uses' => 'MedicineController@medicineWithExpiredDate']);
                $router->get('medicines/search', ['uses' => 'MedicineController@search']);
                $router->get('medicines/searchFromInventory', ['uses' => 'MedicineController@searchMedicineFromInventory']);
                $router->post('medicines/company', ['uses' => 'MedicineController@searchByCompany']);
                $router->get('companies', ['uses' => 'CompanyController@index']); // only name of all companies
                $router->get('companies/inventory', ['uses' => 'CompanyController@getCompaniesByInventory']); // only name of all companies
                $router->get('company-list', ['uses' => 'CompanyController@companyList']);
                $router->post('supplier/store', ['uses' => 'CompanyController@store']);
                $router->post('supplier/{id}/update', ['uses' => 'CompanyController@update']);
                $router->delete('supplier/{id}/delete', ['uses' => 'CompanyController@destroy']);


                /** Carts */
                $router->post('carts/add-to-cart', ['uses' => 'CartController@addToCart']);
                $router->get('carts/{token}', ['uses' => 'CartController@view']);
                $router->get('carts/{token}/check', ['uses' => 'CartController@tokenCheck']);
                $router->post('carts/delete-item', ['uses' => 'CartController@deleteItem']);
                $router->get('carts/{token}/delete', ['uses' => 'CartController@destroy']);
                $router->post('carts/quantity-update', ['uses' => 'CartController@quantityUpdate']);
                $router->post('carts/price-update', ['uses' => 'CartController@priceUpdate']);

                /** Report Sale */
                $router->get('sale/report', ['uses' => 'SaleController@saleReport']);
                $router->get('sale/return/report', ['uses' => 'SaleController@saleReturnReport']);
                $router->get('sale/due/report', ['uses' => 'SaleController@saleDueReport']);
                $router->get('payment/types', ['uses' => 'SaleController@paymentTypes']);

                /** Sale Order */
                $router->post('orders/sale', ['uses' => 'SaleController@create']);
                $router->post('orders/sale/delete-item', ['uses' => 'SaleController@deleteItem']);
                $router->post('orders/sale/return-item', ['uses' => 'SaleController@update']);
                $router->post('orders/sale/upload-image', ['uses' => 'SaleController@uploadimage']);
                $router->get('sale/{saleId}', ['uses' => 'SaleController@view']);
                $router->get('sales', ['uses' => 'SaleController@index']);
                $router->get('sales/report', ['uses' => 'SaleController@report']);
                $router->get('sales/due', ['uses' => 'SaleController@saleDueList']);
                $router->post('sales/payout', ['uses' => 'SaleController@payout']);
                $router->post('sales/discount', ['uses' => 'SaleController@discount']);
                $router->get('reports/sale/latest', ['uses' => 'SaleController@latestSale']);
                $router->get('medicines/search/sale', ['uses' => 'MedicineController@searchByPharmacy']);
                $router->post('medicines/batch', ['uses' => 'MedicineController@batchList']);
                $router->post('medicines/quantity', ['uses' => 'MedicineController@getAvailableQuantity']);

                /** Products */
                $router->get('products/master-list', ['uses' => 'ProductController@index']);
                $router->post('products/{id}', ['uses' => 'ProductController@edit']);
                $router->get('products/{id}/delete', ['uses' => 'ProductController@delete']);

                /** Purchase Order */
                $router->get('orders/latest', ['uses' => 'OrderController@latestPurchase']);
                $router->post('orders', ['uses' => 'OrderController@create']); // unused
                $router->post('orders/manual', ['uses' => 'OrderController@manualOrder']);
                $router->post('orders/purchase/manual', ['uses' => 'OrderController@manualPurchase']);
                $router->get('orders', ['uses' => 'OrderController@index']);
                $router->get('orders/items', ['uses' => 'OrderController@orderItems']);
                $router->get('orders/{token}', ['uses' => 'OrderController@view']);
                $router->get('orders/{orderId}/details', ['uses' => 'OrderController@details']);
                $router->post('orders/update', ['uses' => 'OrderController@update']);
                $router->post('orders/update-status', ['uses' => 'OrderController@statusUpdate']);
                $router->post('orders/delete-item', ['uses' => 'OrderController@deleteItem']);
                $router->get('orders/check-is-last-item/{item_id}', ['uses' => 'OrderController@checkIsLastItem']); // unused

                /** Purchase Order list for report */
                $router->get('purchase-report', ['uses' => 'OrderController@purchaseReport']);
                $router->get('purchase-report/filter', ['uses' => 'OrderController@purchaseFilter']);

                $router->post('purchase/save', ['uses' => 'OrderController@purchaseSave']);
                $router->post('purchase/due/save', ['uses' => 'OrderController@purchaseDueSave']);
                $router->post('purchase/item/details/update', ['uses' => 'OrderController@purchaseItemDetailsUpdate']);
                $router->post('purchase/details/delete', ['uses' => 'OrderController@purchaseDetailsDelete']);
                $router->post('lowStockQty/update', ['uses' => 'OrderController@lowStockQtyupdate']);
                $router->post('MrpTp/update', ['uses' => 'OrderController@updateMRPTP']);
                $router->post('purchase/item/delete', ['uses' => 'OrderController@purchaseItemDetailsDelete']);

                $router->post('purchase/previous/details', ['uses' => 'OrderController@previousPurchaseDetails']);
                $router->post('item/unit/details', ['uses' => 'OrderController@medicineUnitPriceDetails']);
                //purchase/list
                $router->get('purchase/list', ['uses' => 'OrderController@purchaseList']);
                $router->get('master/purchase/list', ['uses' => 'OrderController@masterPurchaseList']);
                $router->post('master/purchase/list/filter', ['uses' => 'OrderController@masterPurchaseListFilter']);

                //master/purchase/due/list
                $router->get('master/purchase/due/list', ['uses' => 'OrderController@masterPurchaseDueList']);
                $router->post('master/purchase/due/list/filter', ['uses' => 'OrderController@masterPurchaseDueListFilter']);

                $router->post('purchase/item/filter', ['uses' => 'OrderController@purchaseListFilter']);
                $router->get('purchase/due/list', ['uses' => 'OrderController@purchaseDueList']);

                $router->get('purchase/details/{orderId}', ['uses' => 'OrderController@purchaseDetails']);


                /** Sales List for report */
                $router->get('sales-report', ['uses' => 'OrderController@salesReport']);
                $router->get('sales-report/filter', ['uses' => 'OrderController@saleFilter']);

                /** MR Connection */
                $router->post('mr-connection', ['uses' => 'UserController@mrConnection']);

                /** Order/Reports */
                $router->get('reports/purchase-manual', ['uses' => 'OrderController@manualPurchaseList']);
                $router->get('reports/orders', ['uses' => 'OrderController@getOrderList']);
                $router->get('reports/ordersFilter', ['uses' => 'OrderController@orderFilterList']);
                $router->get('orders/orders/{order_id}', ['uses' => 'OrderController@getOrderDetails']);
                $router->get('reports/orders/items', ['uses' => 'OrderController@getItemList']);
                $router->post('order/fillReceive', ['uses' => 'OrderController@fullReceive']);
                $router->post('order/updateinfo', ['uses' => 'OrderController@orderUpdate']);

                /** Items  */
                $router->post('item/receive', ['uses' => 'OrderController@receiveItem']);

                /** Inventory   */
                $router->get('inventory/list', ['uses' => 'OrderController@productList']);
                $router->get('reports/inventory', ['uses' => 'OrderController@inventoryList']);
                $router->get('reports/inventoryFilter', ['uses' => 'OrderController@inventoryFilter']);
                $router->post('inventory/damages', ['uses' => 'OrderController@receiveDamageItem']);
                $router->get('reports/inventory/damagesList', ['uses' => 'OrderController@damagesList']);

                //Products
                $router->get('type/search', ['uses' => 'OrderController@typeSearch']);
                $router->post('product/type/save', ['uses' => 'OrderController@productTypeSave']);
                $router->post('inventory/generic/search', ['uses' => 'ProductController@genericSearch']);

                //Settings
                $router->get('type/list', ['uses' => 'SettingsController@types']);
                $router->post('type/update', ['uses' => 'SettingsController@typeSave']);
                $router->delete('type/{id}', ['uses' => 'SettingsController@destroyType']);
                $router->get('brand/list', ['uses' => 'SettingsController@brands']);
                $router->post('brand/save', ['uses' => 'SettingsController@brandSave']);
                $router->delete('brand/{id}', ['uses' => 'SettingsController@destroyBrand']);

                $router->post('product/save', ['uses' => 'OrderController@productSave']);
                $router->post('product/update/{id}', ['uses' => 'OrderController@productUpdate']);
                $router->get('product/list', ['uses' => 'OrderController@userAddedProductList']);
                $router->delete('product/{id}/delete', ['uses' => 'OrderController@destroy']);

                //Company
                $router->post('company/save', ['uses' => 'OrderController@saveCompanyInformation']);
                $router->post('company/update', ['uses' => 'OrderController@UpdateCompanyInformation']);

                //inventory/list
                $router->get('inventory/listFilter', ['uses' => 'OrderController@inventoryListFilter']);
                $router->get('master/inventory/listFilter', ['uses' => 'OrderController@masterInventoryListFilter']);
                $router->post('master/inventory/list/filter', ['uses' => 'OrderController@masterInventoryFilterList']);

                /** Notification List */
                $router->get('notification/list', ['uses' => 'HomeController@getNotificationList']);
                $router->get('notification/list/all', ['uses' => 'HomeController@getAllNotificationList']);
                $router->post('notification/generateLowStockNotification', ['uses' => 'HomeController@generateLowStockNotification']);

                /** sales/persons/list */
                $router->get('sales/persons/list', ['uses' => 'HomeController@getSalePersonsList']);
            }
        );
    }
);
/** Script for database migration */
$router->get('medicine-scripe', ['uses' => 'TestController@medicineScript']);
$router->get('medicine-type', ['uses' => 'TestController@medicineTypeScript']);
$router->get('test', ['uses' => 'TestController@test']);


//$router->get('type/list', ['uses' => 'OrderController@typeList']);

$router->post('orders/sync/data', ['uses' => 'HomeController@awsData']);

$router->post('data_sync', ['uses' => 'HomeController@dataSyncToDB']);
$router->post('sale_data_sync', ['uses' => 'HomeController@saleDataSyncToDB']);
$router->post('sync-data-to-server', ['uses' => 'HomeController@syncDataToServer']);

$router->get('companyScript', ['uses' => 'HomeController@companyScript']);
$router->get('api/orders/{id}/pdf', ['uses' => 'OrderController@downloadPDF']);

/** Notification open url for checking */

$router->get('notification/generate', ['uses' => 'HomeController@generateNotification']);
$router->get('test', ['uses' => 'UserController@test']);

/** Insert Consumer products */
$router->get('UpdateConsumerProductType', ['uses' => 'OrderController@UpdateConsumerProductType']);
$router->get('insertconsumerproducts', ['uses' => 'OrderController@insertconsumerproducts']);
$router->get('purchaseReportToExcels',  ['uses' => 'OrderController@purchaseReportToExcels']);
$router->get('updateMedicineDetails',  ['uses' => 'HomeController@updateMedicineDetails']);

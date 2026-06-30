<?php
require_once dirname(__DIR__, 2) . '/app/config/session.php';
require_once dirname(__DIR__, 2) . '/app/Models/BulkInquiryModel.php';
require_once dirname(__DIR__, 2) . '/app/config/wholesale_config.php';

class WholesaleController
{
    public function index()
    {
        if (!wholesaleHasAccess()) {
            include __DIR__ . '/../Views/products/wholesale-gate.php';
            return;
        }

        $bulkInquiryModel = new BulkInquiryModel();
        $products = $bulkInquiryModel->getB2BProducts();
        error_log('WholesaleController: Fetched ' . count($products) . ' B2B products at ' . date('Y-m-d H:i:s'));
        include __DIR__ . '/../Views/products/wholesale.php';
    }
}

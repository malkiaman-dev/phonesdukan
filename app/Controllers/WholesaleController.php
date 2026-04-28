<?php
require_once dirname(__DIR__, 2) . '/app/Models/BulkInquiryModel.php';

class WholesaleController
{
    public function index()
    {
        $bulkInquiryModel = new BulkInquiryModel();
        // Fetch B2B products with primary images
        $products = $bulkInquiryModel->getB2BProducts();
        // Log for debugging
        error_log("WholesaleController: Fetched " . count($products) . " B2B products at " . date('Y-m-d H:i:s'));
        include __DIR__ . '/../Views/products/wholesale.php';
    }
}
?>
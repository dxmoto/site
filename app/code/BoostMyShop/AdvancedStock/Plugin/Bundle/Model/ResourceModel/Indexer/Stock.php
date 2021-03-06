<?php

namespace BoostMyShop\AdvancedStock\Plugin\Bundle\Model\ResourceModel\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;


class Stock extends \Magento\Bundle\Model\ResourceModel\Indexer\Stock
{
    private $queryProcessorComposite;

    protected function _getStockStatusSelect($entityIds = null, $usePrimaryTable = false)
    {
        $select = parent::_getStockStatusSelect($entityIds, $usePrimaryTable);


        $whereParts = $select->getPart("where");
        foreach($whereParts as $key => $whereOption)
        {
            if ($whereOption == "(cis.website_id = 0)")
                $whereParts[$key] = "(1=1)";
        }

        $select->reset("where");
        foreach($whereParts as $part)
        {
            $part = str_replace("AND ", "", $part);
            $select->where($part);
        }

        return $select;
    }

    /**
     * Update Stock status index by product ids
     *
     * @param array|int $entityIds
     * @return $this
     */
    protected function _updateIndex($entityIds)
    {
        $connection = $this->getConnection();
        $select = $this->_getStockStatusSelect($entityIds, true);
        $select = $this->getQueryProcessorComposite()->processQuery($select, $entityIds, true);
        $query = $connection->query($select);

        $i = 0;
        $data = [];
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $i++;
            $data[] = [
                'product_id' => (int)$row['entity_id'],
                'website_id' => (int)$row['website_id'],
                'stock_id' => (int)$row['stock_id'],    //BMS Changes
                'qty' => (double)$row['qty'],
                'stock_status' => (int)$row['status'],
            ];

            if ($i % 1000 == 0) {
                $this->_updateIndexTable($data);
                $data = [];
            }
        }
        $this->_updateIndexTable($data);

        $this->_cleanBundleOptionStockData();

        return $this;
    }



    private function getQueryProcessorComposite()
    {
        if (null === $this->queryProcessorComposite) {
            $this->queryProcessorComposite = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\CatalogInventory\Model\ResourceModel\Indexer\Stock\QueryProcessorComposite');
        }
        return $this->queryProcessorComposite;
    }

}

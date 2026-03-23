<?php

namespace Comfino\Common\Frontend;

final class PaywallItemDetails
{
    /**
     * @var string
     */
    public $productDetails;
    /**
     * @var string
     */
    public $listItemData;
    /**
     * @param string $productDetails
     * @param string $listItemData
     */
    public function __construct(string $productDetails, string $listItemData)
    {
        $this->productDetails = $productDetails;
        $this->listItemData = $listItemData;
    }
}

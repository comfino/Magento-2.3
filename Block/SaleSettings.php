<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\CategoryTree\BuildStrategy;
use Comfino\Common\Shop\Product\CategoryTree;
use Comfino\Common\Shop\Product\CategoryTree\NodeIterator;
use Comfino\Configuration\SettingsManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SaleSettings extends Field
{
    private const TREE_CLOSE_DEPTH = 3;

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $categoriesTree = new CategoryTree(new BuildStrategy());
        $allCategoryIds = $this->collectLeafIds($categoriesTree->getNodes());

        $availableProductTypes = SettingsManager::getCatFilterAvailProdTypes();
        $productCategoryFilters = SettingsManager::getProductCategoryFilters();

        if (empty($availableProductTypes)) {
            return '<p>' . __('No financial product types available. Please check your API key configuration.') . '</p>';
        }

        /* Library tree.min.js is a webpack UMD bundle. Loading it via a plain <script> tag while RequireJS is on the page
           causes errors. Using require([url], cb) makes RequireJS own the load and the callback receives the Tree class. */
        $treeJsUrl = $this->getViewFileUrl('Comfino_ComfinoGateway::js/tree.min.js');

        $html = '<style>' .
            '.comfino-cat-tree { max-height: 300px; overflow-y: auto; border: 1px solid #cccccc; padding: 5px; margin-bottom: 5px; }' .
            '.comfino-cat-tree-section { margin-bottom: 20px; }' .
            '.comfino-cat-tree-section h4 { margin: 0 0 8px; font-weight: bold; }' .
            '</style>';

        $aggregateFieldId = $element->getHtmlId();
        $aggregateFieldName = $element->getName();

        $html .= '<input id="' . $aggregateFieldId . '" name="' . $aggregateFieldName . '" type="hidden" />';

        $treeInits = '';

        foreach ($availableProductTypes as $prodTypeCode => $prodTypeName) {
            $selectedCategories = isset($productCategoryFilters[$prodTypeCode])
                ? array_diff($allCategoryIds, $productCategoryFilters[$prodTypeCode])
                : $allCategoryIds;

            // Embed JSON directly as a JS value (same pattern as PS/WC plugins).
            // json_encode() must NOT be called on the full options object — that would double-encode
            // $treeNodes (already a JSON string) into a JSON string-of-string, breaking data: "[]".
            $treeNodes = json_encode($this->buildTreeNodes($categoriesTree->getNodes(), array_values($selectedCategories))) ?: '[]';
            $containerId = 'product_categories_' . $prodTypeCode;
            $inputId = $containerId . '_input';

            $html .= '<div class="comfino-cat-tree-section">';
            $html .= '<h4>' . htmlspecialchars((string) $prodTypeName, ENT_QUOTES) . '</h4>';
            $html .= '<div id="' . $containerId . '" class="comfino-cat-tree"></div>';
            $html .= '<input id="' . $inputId . '" name="product_categories[' . $prodTypeCode . ']" type="hidden" />';
            $html .= '</div>';

            // loaded: populate the per-tree hidden input immediately so the aggregate field
            // has correct initial values even if the user never touches the tree.
            // onChange: update on user interaction.
            // Both call comfinoUpdateFilters() to keep the Magento aggregate field in sync.
            $treeInits .= 'new Tree("#' . $containerId . '", {'
                . 'data: ' . $treeNodes . ','
                . 'closeDepth: ' . self::TREE_CLOSE_DEPTH . ','
                . 'loaded: function () {'
                . 'document.getElementById("' . $inputId . '").value = this.values.join();'
                . '},'
                . 'onChange: function () {'
                . 'document.getElementById("' . $inputId . '").value = this.values.join();'
                . 'comfinoUpdateFilters();'
                . '}'
                . '});';
        }

        // comfinoUpdateFilters() reads all per-tree hidden inputs, computes the excluded-category
        // map (allIds − selectedIds per product type), and writes JSON to the Magento field.
        // Called once after all trees initialise (to set the initial aggregate value) and on every
        // onChange. No submit-event listener needed — programmatic form.submit() skips it.
        $html .= '<script>'
            . 'var comfinoAllCategoryIds = ' . json_encode($allCategoryIds) . ';'
            . 'function comfinoUpdateFilters() {'
            . '  var filters = {};'
            . '  document.querySelectorAll("[name^=\'product_categories[\']").forEach(function (input) {'
            . '    var match = input.name.match(/product_categories\[([^\]]+)\]/);'
            . '    if (match) {'
            . '      var selected = input.value.split(",").filter(Boolean).map(Number);'
            . '      filters[match[1]] = comfinoAllCategoryIds.filter(function (id) {'
            . '        return selected.indexOf(id) === -1;'
            . '      });'
            . '    }'
            . '  });'
            . '  document.getElementById("' . $aggregateFieldId . '").value = JSON.stringify(filters);'
            . '}'
            . 'require(["' . $treeJsUrl . '"], function (Tree) {'
            . $treeInits
            // After all trees initialise synchronously, populate the aggregate field once.
            . 'comfinoUpdateFilters();'
            . '});'
            . '</script>';

        return $html;
    }

    /**
     * Recursively collects IDs of leaf nodes (nodes without children) from the category tree.
     *
     * Only leaf IDs are used for comfinoAllCategoryIds in JS because tree.min.js::getValues()
     * returns only leaf node IDs — parent nodes are never included in this.values regardless of
     * their check state. Using all node IDs (including parents) would cause parent IDs to always
     * appear in the excluded set.
     *
     * @param NodeIterator $nodes
     * @return int[]
     */
    private function collectLeafIds(NodeIterator $nodes): array
    {
        $leafIds = [];

        foreach ($nodes as $node) {
            if ($node->hasChildren()) {
                $leafIds = array_merge($leafIds, $this->collectLeafIds($node->getChildren()));
            } else {
                $leafIds[] = $node->getId();
            }
        }

        return $leafIds;
    }

    /**
     * Recursively converts CategoryTree nodes into the tree.min.js data format.
     *
     * @param NodeIterator $nodes
     * @param int[] $selectedCategories IDs of leaf categories that should be checked (not excluded)
     * @return array
     */
    private function buildTreeNodes(NodeIterator $nodes, array $selectedCategories): array
    {
        $treeData = [];

        foreach ($nodes as $node) {
            $treeNode = ['id' => $node->getId(), 'text' => $node->getName()];

            if ($node->hasChildren()) {
                $treeNode['children'] = $this->buildTreeNodes($node->getChildren(), $selectedCategories);
            } elseif (in_array($node->getId(), $selectedCategories, true)) {
                $treeNode['checked'] = true;
            }

            $treeData[] = $treeNode;
        }

        return $treeData;
    }
}

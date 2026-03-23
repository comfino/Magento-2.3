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
        $allCategoryIds = array_values($categoriesTree->getNodeIds());

        $availProdTypes = SettingsManager::getCatFilterAvailProdTypes();
        $productCategoryFilters = SettingsManager::getProductCategoryFilters();

        if (empty($availProdTypes)) {
            return '<p>' . __('No financial product types available. Please check your API key configuration.') . '</p>';
        }

        $treeJsUrl = $this->getViewFileUrl('Comfino_ComfinoGateway::js/tree.min.js');

        $html = '<script src="' . $treeJsUrl . '"></script>';
        $html .= '<style>'
            . '.comfino-cat-tree { max-height: 300px; overflow-y: auto; border: 1px solid #cccccc; padding: 5px; margin-bottom: 5px; }'
            . '.comfino-cat-tree-section { margin-bottom: 20px; }'
            . '.comfino-cat-tree-section h4 { margin: 0 0 8px; font-weight: bold; }'
            . '</style>';

        foreach ($availProdTypes as $prodTypeCode => $prodTypeName) {
            $selectedCategories = isset($productCategoryFilters[$prodTypeCode])
                ? array_diff($allCategoryIds, $productCategoryFilters[$prodTypeCode])
                : $allCategoryIds;

            $treeNodes = json_encode($this->buildTreeNodes($categoriesTree->getNodes(), array_values($selectedCategories)));
            $containerId = 'product_categories_' . $prodTypeCode;
            $inputId = $containerId . '_input';

            $html .= '<div class="comfino-cat-tree-section">';
            $html .= '<h4>' . htmlspecialchars((string) $prodTypeName, ENT_QUOTES) . '</h4>';
            $html .= '<div id="' . $containerId . '" class="comfino-cat-tree"></div>';
            $html .= '<input id="' . $inputId . '" name="product_categories[' . $prodTypeCode . ']" type="hidden" />';
            $html .= '<script>'
                . 'new Tree("#' . $containerId . '", {'
                . 'data: ' . $treeNodes . ','
                . 'closeDepth: ' . self::TREE_CLOSE_DEPTH . ','
                . 'onChange: function () {'
                . 'document.getElementById("' . $inputId . '").value = this.values.join();'
                . '}'
                . '});'
                . '</script>';
            $html .= '</div>';
        }

        // Magento-compatible aggregate hidden field: collects all tree selections as JSON before save.
        $aggregateFieldId = $element->getHtmlId();
        $aggregateFieldName = $element->getName();

        $html .= '<input id="' . $aggregateFieldId . '" name="' . $aggregateFieldName . '" type="hidden" />';
        $html .= '<script>'
            . 'var comfinoAllCategoryIds = ' . json_encode($allCategoryIds) . ';'
            . '(function () {'
            . '  var form = document.getElementById("config-edit-form");'
            . '  if (!form) { return; }'
            . '  form.addEventListener("submit", function () {'
            . '    var filters = {};'
            . '    document.querySelectorAll("[name^=\'product_categories[\']").forEach(function (input) {'
            . '      var match = input.name.match(/product_categories\[([^\]]+)\]/);'
            . '      if (match) {'
            . '        var selected = input.value.split(",").filter(Boolean).map(Number);'
            . '        filters[match[1]] = comfinoAllCategoryIds.filter(function (id) {'
            . '          return selected.indexOf(id) === -1;'
            . '        });'
            . '      }'
            . '    });'
            . '    document.getElementById("' . $aggregateFieldId . '").value = JSON.stringify(filters);'
            . '  });'
            . '}());'
            . '</script>';

        return $html;
    }

    /**
     * Recursively converts CategoryTree nodes into the tree.min.js data format.
     *
     * @param NodeIterator $nodes
     * @param int[] $selectedCategories IDs of categories that should be checked (not excluded)
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

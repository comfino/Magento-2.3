<?php

namespace Comperia\ComperiaGateway\Controller\Result;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;

/**
 * Class Index
 *
 * @package Comperia\ComperiaGateway\Controller\Result
 */
class Index extends Action
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * Index constructor.
     *
     * @param Context          $context
     * @param RequestInterface $request
     * @param PageFactory      $pageFactory
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        PageFactory $pageFactory
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute()
    {
        return $this->pageFactory->create();
    }
}

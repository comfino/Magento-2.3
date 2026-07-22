<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Payment;

/**
     * @property int[]|null $terms
     */
class AllowedProductConfig
{
    /**
     * @var \Comfino\Api\Dto\Payment\LoanTypeEnum
     */
    public $type;
    /**
     * @var int|null
     */
    public $maxTerm;
    /**
     * @var int|null
     */
    public $minTerm;
    /**
     * @var mixed[]|null
     */
    public $terms;
    public function __construct(LoanTypeEnum $type, ?int $maxTerm = null, ?int $minTerm = null, ?array $terms = null)
    {
        $this->type = $type;
        $this->maxTerm = $maxTerm;
        $this->minTerm = $minTerm;
        $this->terms = $terms;
    }
}

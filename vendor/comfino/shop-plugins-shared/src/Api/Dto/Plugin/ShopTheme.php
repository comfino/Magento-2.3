<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Plugin;

final class ShopTheme
{
    /**
     * @var string
     */
    public $code;
    /**
     * @var string
     */
    public $family;
    /**
     * @var string[]
     */
    public $parents = [];
    /**
     * @var bool|null
     */
    public $isPwa;
    /**
     * @param string $code
     * @param string $family
     * @param string[] $parents
     * @param bool|null $isPwa
     */
    public function __construct(string $code, string $family, array $parents = [], ?bool $isPwa = null)
    {
        $this->code = $code;
        $this->family = $family;
        $this->parents = $parents;
        $this->isPwa = $isPwa;
    }
}

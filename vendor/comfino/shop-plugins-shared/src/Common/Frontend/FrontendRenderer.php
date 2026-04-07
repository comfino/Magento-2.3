<?php

namespace Comfino\Common\Frontend;

abstract class FrontendRenderer
{
    /**
     * @return string[]
     */
    abstract public function getStyles(): array;

    /**
     * @return string[]
     */
    abstract public function getScripts(): array;
}

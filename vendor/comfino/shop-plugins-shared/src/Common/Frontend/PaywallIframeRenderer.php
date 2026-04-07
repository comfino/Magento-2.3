<?php

namespace Comfino\Common\Frontend;

final class PaywallIframeRenderer extends FrontendRenderer
{
    /**
     * @return string[]
     */
    public function getStyles(): array
    {
        return ['paywall-frontend.css'];
    }

    /**
     * @return string[]
     */
    public function getScripts(): array
    {
        return ['paywall-frontend.js'];
    }
}

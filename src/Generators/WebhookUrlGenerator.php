<?php

namespace Pixelpillow\LunarMollie\Generators;

abstract class WebhookUrlGenerator extends BaseUrlGenerator
{
    /**
     * Generate the webhook URL.
     */
    abstract public function generate(): string;
}

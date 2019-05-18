<?php

namespace Bundle\Asmb\Common;

use Bolt\Extension\SimpleExtension;
use Bundle\Asmb\Common\Extension\TwigBackendTrait;

/**
 * Asmb Common bundle extension loader.
 *
 * @see https://docs.bolt.cm/3.6/extensions/advanced/storage-repositories
 */
class CommonExtension extends SimpleExtension
{
    use TwigBackendTrait;

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        /** @see https://stackoverflow.com/questions/52754936/overwrite-backend-template-in-bolt-cms */
        if ($this->getEnd() == 'backend') {
            return [
                'view/backend'      => ['position' => 'prepend', 'namespace' => 'bolt'],
                'templates/backend' => ['namespace' => 'AsmbCommon'],
            ];
        }

        return [
            'templates' => ['namespace' => 'AsmbCommon'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/extensions/guide' => new Controller\Backend\GuideController(),
        ];
    }
}

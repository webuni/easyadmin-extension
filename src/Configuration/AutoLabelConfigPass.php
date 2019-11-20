<?php

declare(strict_types=1);

/*
 * This is part of the webuni/easyadmin-extensions package.
 *
 * (c) Martin Hasoň <martin.hason@gmail.com>
 * (c) Webuni s.r.o. <info@webuni.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webuni\EasyAdminExtensions\Configuration;

use Webuni\SymfonyExtensions\Translation\ChainMessages;
use Doctrine\Common\Inflector\Inflector;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigPassInterface;

class AutoLabelConfigPass implements ConfigPassInterface
{
    public function process(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (['form', 'edit', 'list', 'new', 'search', 'show', 'delete'] as $view) {
                if (!isset($backendConfig['entities'][$entityName][$view])) {
                    continue;
                }

                $config = &$backendConfig['entities'][$entityName][$view];
                if (!isset($config['title'])) {
                    /** @see \EasyCorp\Bundle\EasyAdminBundle\Configuration\ViewConfigPass::processPageTitleConfig() and templates */
                    $title = Inflector::tableize($entityName.'.'.$view.'.page_title');
                    $config['title'] = $this->createMessage($title, $backendConfig[$view]['title'] ?? ['EasyAdminBundle' => $view.'.page_title']);
                }

                /* @see \EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfigPass::doNormalizeActionsConfig() */
                foreach ($config['actions'] ?? [] as $actionName => $actionConfig) {
                    if (isset($actionConfig['label']) && in_array($actionConfig['label'], ['action.'.$actionName, $this->humanizeString($actionName)])) {
                        $config['actions'][$actionName]['label'] = $this->createMessage(Inflector::tableize($entityName.'.action.'.$actionName), 'action.'.$actionName, $actionConfig['label']);
                    }
                }

                if (!isset($config['fields'])) {
                    continue;
                }

                foreach ($config['fields'] as $fieldName => $fieldConfig) {
                    if (isset($fieldConfig['label'])) {
                        continue;
                    }

                    if (!isset($fieldConfig['property'])) {
                        continue;
                    }

                    $label = Inflector::tableize($entityName.'.'.$fieldConfig['property']);
                    $config['fields'][$fieldName]['label'] = $this->createMessage($label, $fieldConfig['property']);
                }
            }
        }

        return $backendConfig;
    }

    private function createMessage(...$messages)
    {
        if (class_exists(ChainMessages::class)) {
            return new ChainMessages($messages);
        }

        return $messages;
    }

    /**
     * @see \EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfigPass::humanizeString()
     */
    private function humanizeString($content)
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $content))));
    }
}

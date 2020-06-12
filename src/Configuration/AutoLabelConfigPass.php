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

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigPassInterface;
use Webuni\SymfonyExtensions\Translation\ChainMessages;

class AutoLabelConfigPass implements ConfigPassInterface
{
    private $inflector;

    public function __construct()
    {
        $this->inflector = class_exists(InflectorFactory::class) ? InflectorFactory::create()->build() : new Inflector();
    }

    public function process(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $className = $this->humanizeString(substr(strrchr($entityConfig['class'], '\\'), 1));
            foreach (['form', 'edit', 'list', 'new', 'search', 'show', 'delete'] as $view) {
                if (!isset($backendConfig['entities'][$entityName][$view])) {
                    continue;
                }

                $config = &$backendConfig['entities'][$entityName][$view];
                if (!isset($config['title'])) {
                    /** @see \EasyCorp\Bundle\EasyAdminBundle\Configuration\ViewConfigPass::processPageTitleConfig() and templates */
                    $titleEntityName = $this->inflector->tableize($entityName.'.'.$view.'.page_title');
                    $titleClassName = $this->inflector->tableize($className.'.'.$view.'.page_title');
                    $config['title'] = $this->createMessage($titleEntityName, $titleClassName, $backendConfig[$view]['title'] ?? ['EasyAdminBundle' => $view.'.page_title']);
                }

                /* @see \EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfigPass::doNormalizeActionsConfig() */
                foreach ($config['actions'] ?? [] as $actionName => $actionConfig) {
                    if (isset($actionConfig['label']) && \in_array($actionConfig['label'], ['action.'.$actionName, $this->humanizeString($actionName)])) {
                        $labelEntityName = $this->inflector->tableize($entityName.'.action.'.$actionName);
                        $labelClassName = $this->inflector->tableize($className.'.action.'.$actionName);
                        $config['actions'][$actionName]['label'] = $this->createMessage($labelEntityName, 'action.'.$actionName, $actionConfig['label']);
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

                    $labelEntityName = $this->inflector->tableize($entityName.'.'.$fieldConfig['property']);
                    $labelClassName = $this->inflector->tableize($entityName.'.'.$fieldConfig['property']);
                    $config['fields'][$fieldName]['label'] = $this->createMessage($labelEntityName, $labelClassName, $fieldConfig['property']);
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

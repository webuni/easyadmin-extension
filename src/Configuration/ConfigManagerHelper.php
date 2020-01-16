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

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;

final class ConfigManagerHelper
{
    private $configManager;
    private $backendConfigReflection;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
        $this->backendConfigReflection = new \ReflectionProperty(ConfigManager::class, 'backendConfig');
        $this->backendConfigReflection->setAccessible(true);
    }

    public function getBackendConfig($propertyPath = null)
    {
        return $this->configManager->getBackendConfig($propertyPath);
    }

    public function setBackendConfig(array $backendConfig): void
    {
        $this->backendConfigReflection->setValue($this->configManager, $backendConfig);
    }

    public function getEntityConfig($entity): ?array
    {
        try {
            return $this->configManager->getEntityConfig($entity);
        } catch (UndefinedEntityException $e) {
            return $this->configManager->getEntityConfigByClass($entity);
        }
    }

    public function setEntityConfig($name, $config): void
    {
        $backendConfig = $this->configManager->getBackendConfig();
        $backendConfig['entities'][$name] = $config;

        $this->setBackendConfig($backendConfig);
    }
}

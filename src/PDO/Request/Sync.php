<?php
/**
 * This file is part of the jigius/acc-core-pdata-examples library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2020 Jigius <jigius@gmail.com>
 * @link https://github.com/jigius/acc-core-pdata-examples GitHub
 */

declare(strict_types=1);

namespace Acc\Core\PersistentData\Examples\PDO\Request;

use Acc\Core\PersistentData\Example\Foo\EntityInterface;
use Acc\Core\PersistentData\PDO\ExtendedPDOInterface;
use Acc\Core\PersistentData\RegistryInterface;
use Acc\Core\PersistentData\RequestInterface;
use Acc\Core\PersistentData\VanillaRegistry;
use Acc\Core\PrinterInterface;

/**
 * Class Sync
 * Syncs an entity with persistent storage
 *
 * @package Acc\Core\PersistentData\Examples\PDO\Request
 */
final class Sync implements RequestInterface
{
    /**
     * @var EntityInterface
     */
    private EntityInterface $entity;

    /**
     * The set of attributes for the entity
     * @var RegistryInterface|null
     */
    private RegistryInterface $attrs;

    /**
     * @var RequestInterface|null
     */
    private ?RequestInterface $insert;

    /**
     * @var RequestInterface|null
     */
    private ?RequestInterface $update;

    /**
     * @var RequestInterface|null
     */
    private ?RequestInterface $resolved = null;

    /**
     * Sync constructor.
     * @param EntityInterface $entity
     * @param RegistryInterface|null $attrs
     * @param RequestInterface|null $insert
     * @param RequestInterface|null $update
     */
    public function __construct(
        EntityInterface $entity,
        ?RegistryInterface $attrs = null,
        ?RequestInterface $insert = null,
        ?RequestInterface $update = null
    ) {
        $this->entity = $entity;
        $this->attrs = $attrs ?? new VanillaRegistry();
        $this->insert = $insert;
        $this->update = $update;
    }

    /**
     * @inheritDoc
     */
    public function withAttr(string $name, $val): RequestInterface
    {
        $obj = $this->blueprinted();
        $obj->attrs = $this->attrs->with($name, $val);
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function attrs(): RegistryInterface
    {
        return $this->attrs;
    }

    /**
     * @inheritDoc
     */
    public function printed(PrinterInterface $printer)
    {
        return $this->resolved()->printed($printer);
    }

    /**
     * @inheritDoc
     */
    public function executed(ExtendedPDOInterface $pdo): RequestInterface
    {
        return $this->resolved()->resolved->executed($pdo);
    }

    /**
     * Resolves a target object that will be executing the contract
     * @return $this
     */
    private function resolved(): self
    {
        if ($this->resolved !== null) {
            return $this;
        }
        $obj = $this->blueprinted();
        if ($this->entity->attrs()->value('persisted', false)) {
            $obj->resolved = $this->update ?? new Update($this->entity);
        } else {
            $obj->resolved = $this->insert ?? new Insert($this->entity);
        }
        return $obj;
    }

    /**
     * The clones the instance
     * @return $this
     */
    private function blueprinted(): self
    {
        $obj = new self($this->entity, $this->attrs, $this->insert, $this->update);
        $obj->resolved = $this->resolved;
        return $obj;
    }
}

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

namespace Acc\Core\PersistentData\Examples;

use Acc\Core\Inventory\{
    Inventory,
    PositionsInterface,
    VanillaPositions
};
use Acc\Core\PersistentData\{
    EntityInterface,
    RegistryInterface
};
use Acc\Core\PrinterInterface;
use DateTimeInterface, LogicException;

/**
 * Class WithChangesOnlyEntity
 * Tracks changes and prints out their only.
 * But primary key - `id` and the attributes of current entity prints out always!
 * @package Acc\Core\PersistentData\Examples
 */
final class WithChangesOnlyEntity implements EntityInterface
{
    /**
     * @var EntityInterface
     */
    private EntityInterface $orig;

    /**
     * @var EntityInterface
     */
    private EntityInterface $current;

    /**
     * Entity constructor.
     * @param EntityInterface $entity
     */
    public function __construct(EntityInterface $entity)
    {
        $this->orig = $entity;
        $this->current = $entity;
    }

    /**
     * @inheritDoc
     * @param PrinterInterface $printer
     * @return mixed
     */
    public function printed(PrinterInterface $printer)
    {
        $i = new Inventory(true, new VanillaPositions());
        $op = $this->orig->printed($i)->positions();
        $cp = $this->current->printed($i)->positions();
        if (!($op instanceof PositionsInterface) || !($cp instanceof PositionsInterface)) {
            throw new LogicException("an unexpected result has gotten");
        }
        foreach ($cp->iterator() as $key => $val) {
            if ($key == "id" || $key == "attrs" || $op->fetch($key) === $val) {
                $printer = $printer->with($key, $val);
            }
        }
        return $printer->finished();
    }

    /**
     * @inheritDoc
     * @param string $id
     * @return EntityInterface
     */
    public function withId(string $id): EntityInterface
    {
        $obj = $this->blueprinted();
        $obj->current = $this->current->withId($id);
        return $obj;
    }

    /**
     * @inheritDoc
     * @param string $memo
     * @return EntityInterface
     */
    public function withMemo(string $memo): EntityInterface
    {
        $obj = $this->blueprinted();
        $obj->current = $this->current->withMemo($memo);
        return $obj;
    }

    /**
     * @inheritDoc
     * @param DateTimeInterface $dt
     * @return EntityInterface
     */
    public function withCreated(DateTimeInterface $dt): EntityInterface
    {
        $obj = $this->blueprinted();
        $obj->current = $this->current->withCreated($dt);
        return $obj;
    }

    /**
     * @inheritDoc
     * @param DateTimeInterface|null $dt
     * @return EntityInterface
     */
    public function withUpdated(DateTimeInterface $dt = null): EntityInterface
    {
        $obj = $this->blueprinted();
        $obj->current = $this->current->withUpdated($dt);
        return $obj;
    }

    /**
     * @inheritDoc
     * @param string $key
     * @param mixed $val
     * @return EntityInterface
     */
    public function withAttr(string $key, $val): EntityInterface
    {
        $obj = $this->blueprinted();
        $obj->current = $this->current->withAttr($key, $val);
        return $obj;
    }

    /**
     * @inheritDoc
     * @return RegistryInterface
     */
    public function attrs(): RegistryInterface
    {
        return $this->current->attrs();
    }

    /**
     * Clones the instance
     * @return EntityInterface
     */
    private function blueprinted(): EntityInterface
    {
        $obj = new self($this->orig);
        $obj->current = $this->current;
        return $obj;
    }
}

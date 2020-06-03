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

use Acc\Core\PersistentData\{
    EntityInterface,
    RegistryInterface,
    VanillaRegistry
};
use Acc\Core\PrinterInterface;
use DateTimeInterface, LogicException;

/**
 * Class Entity
 * @package Acc\Core\PersistentData\Examples
 */
final class Entity implements EntityInterface
{
    /**
     * @var array An input data
     */
    private array $i;

    /**
     * @var RegistryInterface
     */
    private RegistryInterface $attrs;

    /**
     * @inheritDoc
     * Entity constructor.
     * @param PrinterInterface|null $attrs
     */
    public function __construct(RegistryInterface $attrs = null)
    {
        $this->i = [];
        $this->attrs = $attrs ?? new VanillaRegistry();
    }

    /**
     * @inheritDoc
     * @param PrinterInterface $printer
     * @return mixed
     */
    public function printed(PrinterInterface $printer)
    {
        foreach ($this->i as $key => $val) {
            $printer = $printer->with($key, $val);
        }
        return
            $printer
                ->with('attrs', $this->attrs)
                ->finished();
    }

    /**
     * @inheritDoc
     * @param string $id
     * @return EntityInterface
     */
    public function withId(string $id): EntityInterface
    {
        if (!empty($this->i['id']) && $this->i['id'] !== $id && $this->attrs()->value('persisted', false)) {
            throw new LogicException("the changing value of pk is prohibited");
        }
        return $this->with('id', $id);
    }

    /**
     * @inheritDoc
     * @param string $memo
     * @return EntityInterface
     */
    public function withMemo(string $memo): EntityInterface
    {
        return $this->with('memo', $memo);
    }

    /**
     * @inheritDoc
     * @param DateTimeInterface $dt
     * @return EntityInterface
     */
    public function withCreated(DateTimeInterface $dt): EntityInterface
    {
        return $this->with('created', $dt);
    }

    /**
     * @inheritDoc
     * @param DateTimeInterface|null $dt
     * @return EntityInterface
     */
    public function withUpdated(DateTimeInterface $dt = null): EntityInterface
    {
        return $this->with('updated', $dt);
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
        $obj->attrs = $this->attrs->with($key, $val);
        return $obj;
    }

    /**
     * @inheritDoc
     * @return RegistryInterface
     */
    public function attrs(): RegistryInterface
    {
        return $this->attrs;
    }

    /**
     * Clones the instance
     * @return EntityInterface
     */
    private function blueprinted(): EntityInterface
    {
        $obj = new self($this->attrs);
        if ($this->attrs()->value('originalHashCode') === null) {
            $obj = $this->withAttr('originalHashCode', spl_object_hash($obj));
        }
        $obj->i = $this->i;
        return $obj;
    }

    /**
     * @param string $k A name of the part of a data
     * @param $v int|string|float|bool The value of the part of a data
     * @return EntityInterface
     */
    private function with(string $k, $v): EntityInterface
    {
        $changed = isset($this->i[$k]) && $this->i[$k] !== $v || $v !== null || !in_array($k, $this->i);
        if (!$changed) {
            return $this;
        }
        $obj = $this->withAttr('dirty', true);
        $obj->i[$k] = $v;
        return $obj;
    }
}

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

use Acc\Core\Inventory\{Asset, Inventory, InventoryInterface};
use Acc\Core\PrinterInterface;
use DomainException;
use DateTimeImmutable, DateTimeZone;

/**
 * Class EntityFactory
 * Creates a new `EntityInterface` instance from feeded input data
 * @package Acc\Core\PersistentData\Examples
 */
final class EntityFactory implements PrinterInterface
{
    /**
     * @var InventoryInterface An input data transformed into an inventory
     */
    private InventoryInterface $i;

    /**
     * @var EntityInterface An injected entity is going to used for building of an entity
     */
    private EntityInterface $entity;

    /**
     * EntityFactory constructor.
     * @param EntityInterface|null $entity
     */
    public function __construct(EntityInterface $entity = null)
    {
        $this->entity = $entity ?? new Entity();
        $this->i = new Inventory();
    }

    /**
     * @inheritDoc
     * @param string $key
     * @param mixed $val
     * @return PrinterInterface
     */
    public function with(string $key, $val): PrinterInterface
    {
        $obj = $this->blueprinted();
        $obj->i = $this->i->with($key, $val);
        return $obj;
    }

    /**
     * Creates a new `EntityInterface` instance from feeded input data
     * @return EntityInterface
     * @throws DomainException
     */
    public function finished(): EntityInterface
    {
        if (!$this->i->isSealed()) {
            $obj = $this->blueprinted();
            $obj->i = $this->i->sealed();
            return $obj->finished();
        }
        $p = $this->i->positions();
        try {
            return
                $this
                    ->entity
                        ->withId(
                            $p
                                ->fetch('id')
                                ->withAsset(
                                    new Asset\IsString(
                                        new Asset\IsNotEmpty()
                                    )
                                )
                                ->orig()
                        )
                        ->withMemo(
                            $p
                                ->fetch('memo')
                                ->withAsset(
                                    new Asset\IsString(
                                        new Asset\IsNotEmpty()
                                    )
                                )
                                ->orig()
                        )
                        ->withCreated(
                            $p
                                ->fetch('created')
                                ->withAsset(
                                    new Asset\IsString(
                                        new Asset\IsNotEmpty()
                                    )
                                )
                                ->orig(function (string $dt): DateTimeImmutable {
                                    return
                                        DateTimeImmutable::createFromFormat(
                                            "Y-m-d H:i:s",
                                            $dt
                                        )
                                            ->setTimeZone(
                                                new DateTimeZone('UTC')
                                            );
                                })
                        )
                        ->withUpdated(
                            $p
                                ->fetch('updated')
                                ->withAsset(
                                    new Asset\IsString()
                                )
                                ->orig(function (string $dt = null): ?DateTimeImmutable {
                                    if ($dt === null) {
                                        return null;
                                    }
                                    return
                                        DateTimeImmutable::createFromFormat(
                                            "Y-m-d H:i:s",
                                            $dt
                                        )
                                            ->setTimeZone(
                                                new DateTimeZone('UTC')
                                            );
                                })
                        )
                        ->withAttr('persisted', true)
                        ->withAttr('dirty', false);
        } catch (Asset\FailureException $ex) {
            throw new DomainException("invalid data", 0, $ex);
        }
    }

    /**
     * Clones the instance
     * @return $this
     */
    public function blueprinted(): self
    {
        $obj = new self($this->entity);
        $obj->i = $this->i;
        return $obj;
    }
}

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

namespace Acc\Core\PersistentData\Examples\PDO;

use Acc\Core\Inventory\{
    Inventory,
    InventoryInterface,
    Asset,
    Value as InventoryValue,
    PositionsInterface
};
use Acc\Core\PersistentData\{
    RegistryInterface,
    VanillaRegistry
};
use DateTimeImmutable, DateTimeZone;

/**
 * Class EntityInventory
 * @package Acc\Core\PersistentData\Examples\PDO
 */
final class EntityInventory implements InventoryInterface
{
    /**
     * @var InventoryInterface
     */
    private InventoryInterface $orig;

    /**
     * EntityInventory constructor.
     * @param InventoryInterface|null $inventory
     */
    public function __construct(?InventoryInterface $inventory = null)
    {
        $this->orig = $inventory ?? new Inventory(true);
    }

    /**
     * @inheritDoc
     */
    public function with(string $key, $val): InventoryInterface
    {
        return new self($this->orig->with($key, $val));
    }

    /**
     * @inheritDoc
     */
    public function finished(): InventoryInterface
    {
        $p = $this->orig->finished()->positions();
        return
            (new Inventory(true))
                ->with(
                    'id',
                    $p
                        ->fetch('id')
                        ->withAssetIfDefined(
                            new Asset\IsString()
                        )
                )
                ->with(
                    'memo',
                    $p
                        ->fetch('memo')
                        ->withAsset(
                            new Asset\IsString()
                        )
                        ->withProcessor(
                            function (string $val): string {
                                if (mb_strlen($val) > 255) {
                                    $val = mb_substr($val, 255);
                                }
                                return $val;
                            }
                        )
                )
                ->with(
                    'created',
                    $p
                        ->fetch('created')
                        ->withAsset(
                            new Asset\HasContract(DateTimeImmutable::class)
                        )
                        ->withProcessor(
                            function (DateTimeImmutable $dt) {
                                return
                                    $dt
                                        ->setTimezone(
                                            new DateTimeZone("UTC")
                                        )
                                        ->format("Y-m-d H:i:s");
                            }
                        )
                )
                ->with(
                    'updated',
                    $p
                        ->fetch('updated')
                        ->withAssetIfDefined(
                            new Asset\HasContract(DateTimeImmutable::class)
                        )
                        ->withProcessor(
                            function (DateTimeImmutable $iUpdated = null) use ($p): ?DateTimeImmutable {
                                if ($iUpdated === null) {
                                    return null;
                                }
                                $created = $p->fetch("created")->orig();
                                $updated =
                                    $iUpdated
                                        ->setTimezone(
                                            new DateTimeZone("UTC")
                                        );
                                if ($created < $updated) {
                                    throw new Asset\FailureException(
                                        "the value for `updated` is invalid. It's greater then the value for `created`"
                                    );
                                }
                                return $updated;
                            }
                        )
                )
                ->with(
                    'attrs',
                    $p
                        ->fetch(
                            'attrs',
                            (new InventoryValue())
                                ->withOrig(
                                    new VanillaRegistry()
                                )
                        )
                        ->withAsset(
                            new Asset\HasContract(RegistryInterface::class)
                        )
                )
                ->finished();
    }

    /**
     * @inheritDoc
     */
    public function serialized(): array
    {
        return $this->orig->serialized();
    }

    /**
     * @inheritDoc
     */
    public function positions(): PositionsInterface
    {
        return $this->orig->positions();
    }

    /**
     * @inheritDoc
     */
    public function sealed(): InventoryInterface
    {
        return new self($this->orig->sealed());
    }

    public function isSealed(): bool
    {
        return $this->orig->isSealed();
    }

    /**
     * @inheritDoc
     */
    public function unserialized(array $data): InventoryInterface
    {
        return new self($this->orig->sealed());
    }

    /**
     * @inheritDoc
     */
    public function withKeyPrefix(string $str): InventoryInterface
    {
        return new self($this->orig->withKeyPrefix($str));
    }
}

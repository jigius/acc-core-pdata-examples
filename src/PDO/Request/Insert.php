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

use Acc\Core\Inventory\{
    InventoryInterface,
    Asset,
};
use Acc\Core\PersistentData\{
    Example\Foo\PDO\EntityInventory,
    Example\Foo\EntityInterface,
    RegistryInterface,
    RequestInterface,
    VanillaRegistry
};
use Acc\Core\PersistentData\PDO\{PDOStatementInterface,
    ExtendedPDOInterface,
    Value,
    ValueInterface,
    Values,
    ValuesInterface};
use Acc\Core\PrinterInterface;
use DateTimeImmutable, DateTimeZone;
use DomainException;

/**
 * Class Insert
 * Inserts an entity into persistent storage
 * @package Acc\Core\PersistentData\Examples\PDO\Request
 */
final class Insert implements RequestInterface
{
    /**
     * @var EntityInterface
     */
    private EntityInterface $entity;

    /**
     * @var InventoryInterface An input data in form of an inventory
     */
    private InventoryInterface $i;

    /**
     * @var RegistryInterface An attributes of the entity
     */
    private RegistryInterface $attrs;

    /**
     * @var PDOStatementInterface|null
     */
    private ?PDOStatementInterface $statement = null;

    /**
     * Insert constructor.
     * @param EntityInterface $entity
     * @param RegistryInterface|null $attrs
     * @param InventoryInterface|null $inventory
     */
    public function __construct(
        EntityInterface $entity,
        ?RegistryInterface $attrs = null,
        ?InventoryInterface $inventory = null
    ) {
        $this->entity = $entity;
        $this->i = $inventory ?? new EntityInventory();
        $this->attrs = $attrs ?? new VanillaRegistry();
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
    public function withAttr(string $name, $val): RequestInterface
    {
        $obj = $this->blueprinted();
        $obj->attrs = $this->attrs->with($name, $val);
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function printed(PrinterInterface $printer)
    {
        return
            $printer
                ->with('statement', $this->statement)
                ->with('attrs', $this->attrs)
                ->finished();
    }

    /**
     * @inheritDoc
     * @param ExtendedPDOInterface $pdo
     * @return RequestInterface
     * @throws DomainException
     */
    public function executed(ExtendedPDOInterface $pdo): RequestInterface
    {
        if (!$this->i->isSealed()) {
            $obj = $this->blueprinted();
            $obj->i = $this->entity->printed($this->i);
            return $obj->executed($pdo);
        }
        try {
            $obj = $this->withAttr("type", "insert");
            $query = $this->sqlStatement();
            if (empty($query)) {
                return
                    $obj
                        ->withAttr('processed', false);
            }
            if ($this->i->positions()->fetch('attrs')->orig()->value('persisted', false)) {
                throw new DomainException("`insert` operation is prohibited for the entity");
            }
            $obj
                ->statement =
                    $pdo
                        ->prepared(
                            $query
                        )
                        ->withValues(
                            $this->values()
                        )
                        ->executed();
            $obj =
                $obj
                    ->withAttr('processed', true)
                    ->withAttr('insertedId', $pdo->lastInsertedId());
        } catch (Asset\FailureException $ex) {
            throw new DomainException("invalid value", 0, $ex);
        }
        return $obj;
    }

    /**
     * Query statement
     * @return string
     */
    private function sqlStatement(): string
    {
        $p = $this->i->positions();
        $chunks = [
            "INSERT INTO `foo`",
            "(",
                implode(
                    ",",
                    array_filter(
                        [
                            $p
                                ->fetch('id')
                                ->withProcessor(
                                    function (?string $id = null): ?string {
                                        if ($id === null) {
                                            return null;
                                        }
                                        return "`id`";
                                    }
                                )
                                ->orig(),
                            $p
                                ->fetch('memo')
                                ->withProcessor(
                                    function (): ?string {
                                        return "`memo`";
                                    }
                                )
                                ->orig(),
                            $p
                                ->fetch('created')
                                ->withProcessor(
                                    function (): ?string {
                                        return "`created`";
                                    }
                                )
                                ->orig(),
                            $p
                                ->fetch('updated')
                                ->withProcessor(
                                    function (DateTimeImmutable $dt = null): ?string {
                                        if ($dt === null) {
                                            return null;
                                        }
                                        return "`updated`";
                                    }
                                )
                                ->orig()
                        ]
                    )
                ),
            ") VALUES (",
                implode(
                    ",",
                    array_filter(
                        [
                            $p
                                ->fetch('id')
                                ->withProcessor(
                                    function (?string $id = null): ?string {
                                        if ($id === null) {
                                            return null;
                                        }
                                        return ":id";
                                    }
                                )
                                ->orig(),
                            $p
                                ->fetch('memo')
                                ->withProcessor(
                                    function (): ?string {
                                        return ":memo";
                                    }
                                )
                                ->orig(),
                            $p
                                ->fetch('created')
                                ->withProcessor(
                                    function (): ?string {
                                        return ":created";
                                    }
                                )
                                ->orig(),
                            $p
                                ->fetch('updated')
                                ->withProcessor(
                                    function (DateTimeImmutable $dt = null): ?string {
                                        if ($dt === null) {
                                            return null;
                                        }
                                        return ":updated";
                                    }
                                )
                                ->orig()
                        ],
                        function ($itm): bool {
                            return $itm !== null;
                        }
                    )
                ),
            ")"
        ];
        return implode(" ", $chunks);
    }

    /**
     * Query's values
     * @return ValuesInterface
     */
    private function values(): ValuesInterface
    {
        $p = $this->i->positions();
        $bp = new Value();
        return
            (new Values())
                ->withFilteredOutItems(
                    function (ValueInterface $val): bool {
                        return $val->defined();
                    }
                )
                ->with(
                    $bp
                        ->withName(':id')
                        ->withValue(
                            $p
                                ->fetch('id')
                                ->withProcessor(
                                    function (?string $id = null): ?string {
                                        if ($id === null) {
                                            return null;
                                        }
                                        return $id;
                                    }
                                )
                                ->orig()
                        )
                )
                ->with(
                    $bp
                        ->withName(':memo')
                        ->withValue(
                            $p
                                ->fetch('memo')
                                ->orig()
                        )
                )
                ->with(
                    $bp
                        ->withName(':created')
                        ->withValue(
                            $p
                                ->fetch('created')
                                ->withProcessor(
                                    function (DateTimeImmutable $dt): string {
                                        return
                                            $dt
                                                ->setTimezone(
                                                    new DateTimeZone("UTC")
                                                )
                                                ->format("Y-m-d H:i:s");
                                    }
                                )
                                ->orig()
                        )
                )
                ->with(
                    $bp
                        ->withName(':updated')
                        ->withValue(
                            $p
                                ->fetch('updated')
                                ->withProcessor(
                                    function (?DateTimeImmutable $dt = null): ?string {
                                        if ($dt === null) {
                                            return null;
                                        }
                                        return
                                            $dt
                                                ->setTimezone(
                                                    new DateTimeZone("UTC")
                                                )
                                                ->format("Y-m-d H:i:s");
                                    }
                                )
                                ->orig()
                        )
                );
    }

    /**
     * Clones the instance
     * @return $this
     */
    private function blueprinted(): self
    {
        $obj = new self($this->entity, $this->attrs, $this->i);
        $obj->statement = $this->statement;
        return $obj;
    }
}

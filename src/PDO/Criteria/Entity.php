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

namespace Acc\Core\PersistentData\Examples\PDO\Criteria;

use Acc\Core\PersistentData\PDO\{
    ExtendedPDOInterface,
    PDOStatementInterface,
    Value,
    Values,
    ValuesInterface
};
use Acc\Core\PersistentData\RequestInterface;
use Acc\Core\PersistentData\Example\Foo\EntityInterface;
use Acc\Core\PrinterInterface;
use DomainException, LogicException, DateTimeInterface;

/**
 * Class Entity
 * @package Acc\Core\PersistentData\Examples\PDO\Criteria
 */
final class Entity implements RequestInterface, PrinterInterface
{
    /**
     * @var EntityInterface An entity. Its params are used for query
     */
    private EntityInterface $entity;

    /**
     * Defines if a query has to be locked
     * @var bool
     */
    private bool $locked;

    /**
     * @var array An input data
     */
    private array $i;

    /**
     * @var array|null A prepared data for printing
     */
    private ?array $o = null;

    /**
     * A last executed request
     * @var PDOStatementInterface|null
     */
    private ?PDOStatementInterface $statement = null;

    /**
     * Entity constructor.
     * @param EntityInterface $entity
     * @param bool $locked
     */
    public function __construct(EntityInterface $entity, bool $locked = false)
    {
        $this->locked = $locked;
        $this->entity = $entity;
        $this->i = [];
    }

    /**
     * @inheritDoc
     * @param string $key
     * @param mixed $val
     * @return $this
     * @throws LogicException
     */
    public function with(string $key, $val): self
    {
        if ($this->o !== null) {
            throw new LogicException("print job is already finished");
        }
        $obj = $this->blueprinted();
        $obj->i[$key] = $val;
        return $obj;
    }

    /**
     * @inheritDoc
     * @return $this
     * @throws LogicException
     */
    public function finished(): self
    {
        if (empty($this->i)) {
            throw new LogicException("print job has not been run");
        }
        $obj = $this->blueprinted();
        $obj->o = $obj->i;
        $obj->i = [];
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function printed(PrinterInterface $printer)
    {
        if ($this->o === null) {
            return $this->entity->printed($this)->printed($printer);
        }
        return
            $printer
                ->with('statement', $this->statement)
                ->finished();
    }

    /**
     * @inheritDoc
     */
    public function executed(ExtendedPDOInterface $pdo): RequestInterface
    {
        if ($this->o === null) {
            return $this->entity->printed($this)->executed($pdo);
        }
        $this->validate();
        $obj = $this->blueprinted();
        $obj
            ->statement =
                $pdo
                    ->prepared(
                        $this->sqlStatement()
                    )
                    ->withValues(
                        $this->values()
                    )
                    ->executed();
        return $obj;
    }

    /**
     * @param mixed $val
     * @param callable $processor
     * @return mixed
     */
    private function processed($val, callable $processor)
    {
        return call_user_func($processor, $val);
    }

    /**
     * @param string $key
     * @param mixed|null $defined
     * @param mixed|null $undefined
     * @param mixed|null $unknown
     * @return mixed|null
     */
    private function v3(
        string $key,
        $defined = null,
        $undefined = null,
        $unknown = null
    ) {
        if (!array_key_exists($key, $this->o)) {
            return $unknown;
        }
        return
            $this->o[$key] !== null?
                (
                    $defined === null? $this->o[$key]: $defined
                ):
                    $undefined;
    }

    /**
     * A query statement
     * @return string
     */
    private function sqlStatement(): string
    {
        $chunks = [
            "SELECT * FROM `foo`",
            "WHERE",
            implode(
                " AND ",
                array_filter(
                    [
                        $this->v3('id', "`id`=:id", "`id` IS NULL"),
                        $this->v3('memo', "`memo`=:memo", "`memo` IS NULL"),
                        $this->v3('created', "`created`=:created", "`created` IS NULL"),
                        $this->v3('updated', "`updated`=:updated", "`updated` IS NULL")
                    ]
                )
            ),
            $this->locked? "FOR UPDATE": ""
        ];
        return implode(" ", array_filter($chunks));
    }

    /**
     * Query's values
     * @return ValuesInterface
     */
    private function values(): ValuesInterface
    {
        $arr =
            array_filter(
                [
                    ':id' => $this->v3('id'),
                    ':memo' => $this->v3('memo'),
                    ':created' =>
                        $this->processed(
                            $this->v3('created'),
                            function (DateTimeInterface $dt = null): ?string {
                                if ($dt === null) {
                                    return null;
                                }
                                return $dt->format("Y-m-d H:i:s");
                            }
                        ),
                    ':updated' =>
                        $this->processed(
                            $this->v3('updated'),
                            function (DateTimeInterface $dt = null): ?string {
                                if ($dt === null) {
                                    return $dt;
                                }
                                return $dt->format("Y-m-d H:i:s");
                            }
                        )
                ],
                function ($itm) {
                    return $itm !== null;
                }
            );
        $vals = new Values();
        $bp = new Value();
        foreach ($arr as $key => $val) {
            $vals =
                $vals
                    ->with(
                        $bp
                            ->withName($key)
                            ->withValue($val)
                    );
        }
        return $vals;
    }

    /**
     * Validates an input data
     * @throws DomainException
     */
    private function validate(): void
    {
        $fields = [
            'id',
            'memo',
            'created'
        ];
        if (array_reduce(
                $fields,
                function ($carry, $f) {
                    return $carry || isset($this->o[$f]);
                },
                false
            ) === false
        ) {
            throw new DomainException("invalid data");
        }
    }

    /**
     * Clones the instance
     * @return $this
     */
    private function blueprinted(): self
    {
        $obj = new self($this->entity, $this->locked);
        $obj->i = $this->i;
        $obj->o = $this->o;
        $obj->statement = $this->statement;
        return $obj;
    }
}

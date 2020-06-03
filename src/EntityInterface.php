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

use Acc\Core\PersistentData\EntityInterface as EntityType;
use DateTimeInterface;

/**
 * Interface EntityInterface
 * @package Acc\Core\PersistentData\Examples
 */
interface EntityInterface extends EntityType
{
    /**
     * Appends info about id
     * @param string $id
     * @return EntityInterface
     */
    public function withId(string $id): EntityInterface;

    /**
     * Appends info about memo
     * @param string $memo
     * @return EntityInterface
     */
    public function withMemo(string $memo): EntityInterface;

    /**
     * Appends info about date of created
     * @param DateTimeInterface $dt
     * @return EntityInterface
     */
    public function withCreated(DateTimeInterface $dt): EntityInterface;

    /**
     * Appends info about date of changed
     * @param DateTimeInterface|null $dt
     * @return EntityInterface
     */
    public function withUpdated(DateTimeInterface $dt = null): EntityInterface;

    /**
     * @inheritDoc
     * @param string $key
     * @param mixed $val
     * @return EntityInterface
     */
    public function withAttr(string $key, $val): EntityInterface;
}

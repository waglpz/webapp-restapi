<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\UI\Http\Rest;

use Phpro\ApiProblem\Http\BadRequestProblem;
use Phpro\ApiProblem\Http\ConflictProblem;
use Phpro\ApiProblem\Http\NotFoundProblem;
use Waglpz\Webapp\Common\ValueObjectIdentifier;
use Waglpz\Webapp\RestApi\APIProblem;

abstract class ApiProblemChecker
{
    /** @var array<class-string,mixed> */
    private array $data;
    /** @var array<mixed> */
    protected array $problems;
    private APIProblem $problem;

    public function __get(string $type): mixed
    {
        return $this->data[$type] ?? null;
    }

    /** @param class-string $type */
    public function __set(string $type, mixed $value): void
    {
        $this->data[$type] = $value;
    }

    public function __isset(string $type): bool
    {
        return (bool) ($this->data[$type] ?? null);
    }

    /**
     * @param array<mixed> $data
     * @param class-string $type
     *
     * @throws \ReflectionException
     */
    protected function required(array $data, string $type): self
    {
        $name = \classShortNameLCFirst($type);

        $value = $data[$name] ?? null;

        if ($value === '' || $value === null) {
            $this->problems[$name] = (new BadRequestProblem('Wert darf nicht leer sein'))->toArray();

            return $this;
        }

        $this->applyValue($type, $value, $name);

        return $this;
    }

    /**
     * @param array<mixed> $data
     * @param class-string $type
     *
     * @throws \ReflectionException
     */
    protected function optionally(array $data, string $type): self
    {
        $name = \classShortNameLCFirst($type);

        $value = $data[$name] ?? null;

        if ($value === null) {
            $this->$name = null;

            return $this;
        }

        $this->applyValue($type, $value, $name);

        return $this;
    }

    /**
     * @param array<mixed> $data
     * @param class-string $type
     *
     * @throws \ReflectionException
     */
    protected function optionallySame(array $data, string $type, mixed $otherValue): self
    {
        $name = \classShortNameLCFirst($type);

        $checkedValue = $data[$name] ?? null;

        if ($checkedValue === null) {
            $this->$name = null;

            return $this;
        }

        $this->applyValue($type, $checkedValue, $name);

        if ($otherValue === $this->$name) {
            return $this;
        }

        if ($otherValue instanceof \UnitEnum && $this->$name instanceof \UnitEnum && $otherValue === $this->$name) {
            return $this;
        }

        if (
            $this->$name instanceof ValueObjectIdentifier
            && $otherValue instanceof ValueObjectIdentifier
            && $this->$name->value->equals($otherValue->value)
        ) {
            return $this;
        }

        if (
            $this->$name instanceof \DateTimeInterface
            && $otherValue instanceof \DateTimeInterface
            && $this->$name->format('U') === $otherValue->format('U')
        ) {
            return $this;
        }

        if (
            \is_object($this->$name)
            && \is_object($otherValue)
            && \method_exists($this->$name, '__toString')
            && \method_exists($otherValue, '__toString')
            && $this->$name->__toString() === $otherValue->__toString()
        ) {
            return $this;
        }

        /* phpcs:disable */
        /** @noinspection TypeUnsafeComparisonInspection */
        if (\is_scalar($otherValue) && \is_scalar($this->$name) && $otherValue == $this->$name) {
            /* phpcs:enable */
            return $this;
        }

        $detail                = \sprintf(
            'Expected a value equals to %s. Got: %s',
            $this->valueToString($otherValue),
            $this->valueToString($this->$name),
        );
        $this->problems[$name] = (new BadRequestProblem($detail))->toArray();

        return $this;
    }

    /**
     * @param array<mixed> $data
     * @param class-string $type
     *
     * @throws \ReflectionException
     */
    protected function optionallyAndExist(array $data, string $type, callable $finder): self
    {
        $name = \classShortNameLCFirst($type);

        $value = $data[$name] ?? null;

        if ($value === null) {
            $this->$name = null;

            return $this;
        }

        $this->applyValue($type, $value, $name);

        if (! isset($this->$name) || $this->$name === []) {
            return $this;
        }

        if (\is_array($this->$name)) {
            foreach ($this->$name as $index => $itemValue) {
                if ($finder($itemValue)) {
                    continue;
                }

                $this->problems[$name . '.' . $index] = (
                new NotFoundProblem(\sprintf('Wert "%s" nicht gefunden', $itemValue))
                )->toArray();
            }
        } else {
            if (! $finder($this->$name)) {
                $this->problems[$name] = (new NotFoundProblem('Wert nicht gefunden'))->toArray();

                return $this;
            }
        }

        return $this;
    }

    /**
     * @param array<mixed> $data
     * @param class-string $type
     *
     * @throws \ReflectionException
     */
    protected function optionallyAndNotExist(array $data, string $type, callable $finder): self
    {
        $name = \classShortNameLCFirst($type);

        $value = $data[$name] ?? null;

        if ($value === null) {
            $this->$name = null;

            return $this;
        }

        $this->applyValue($type, $value, $name);

        if ($this->$name === null) {
            return $this;
        }

        if ($finder($this->$name)) {
            $this->problems[$name] = (new ConflictProblem('Wert existiert bereits'))->toArray();

            return $this;
        }

        return $this;
    }

    /**
     * @param array<mixed>         $data
     * @param class-string<object> $type
     *
     * @throws \ReflectionException
     */
    public function requiredAndExist(array $data, string $type, callable $finder): self
    {
        $name = \classShortNameLCFirst($type);

        $value = $data[$name] ?? null;

        if ($value === '' || $value === null) {
            $this->problems[$name] = (new BadRequestProblem('Wert darf nicht leer sein'))->toArray();

            return $this;
        }

        $this->applyValue($type, $value, $name);

        if ($this->$name === null) {
            return $this;
        }

        if (! $finder($this->$name)) {
            $this->problems[$name] = (new NotFoundProblem('Wert nicht gefunden'))->toArray();

            return $this;
        }

        return $this;
    }

    /**
     * @param array<mixed>         $data
     * @param class-string<object> $type
     *
     * @throws \ReflectionException
     */
    public function allRequiredAndExist(array $data, string $type, callable $finder): self
    {
        $name = \classShortNameLCFirst($type);

        $values = $data[$name] ?? null;

        if ($values === [] || $values === null || $values === '') {
            $this->problems[$name] = (new BadRequestProblem('Wert darf nicht leer sein'))->toArray();

            return $this;
        }

        $this->applyValue($type, $values, $name);

        if (! isset($this->$name) || ! \is_array($this->$name)) {
            return $this;
        }

        foreach ($this->$name as $index => $value) {
            if ($finder($value)) {
                continue;
            }

            $this->problems[$name . '.' . $index] = (
            new NotFoundProblem(\sprintf('Wert "%s" nicht gefunden', $value))
            )->toArray();
        }

        return $this;
    }

    /**
     * @param array<mixed>         $data
     * @param class-string<object> $type
     * @param callable             $finder callback function that returns
     *                                     boolean true if value was found and false otherwise
     *
     * @throws \ReflectionException
     */
    public function requiredAndNotExist(array $data, string $type, callable $finder): self
    {
        $name = \classShortNameLCFirst($type);

        $value = $data[$name] ?? null;

        if ($value === '' || $value === null) {
            $this->problems[$name] = (new BadRequestProblem('Wert darf nicht leer sein'))->toArray();

            return $this;
        }

        $this->applyValue($type, $value, $name);

        if ($this->$name === null) {
            return $this;
        }

        if ($finder($this->$name)) {
            $this->problems[$name] = (new ConflictProblem('Wert existiert bereits'))->toArray();

            return $this;
        }

        return $this;
    }

    /** @param ?array<mixed> $data */
    public function problem(array|null $data = null): APIProblem|null
    {
        if ($data === null) {
            return null;
        }

        $this->check($data);

        if (! isset($this->problems) || \count($this->problems) < 1) {
            return null;
        }

        $problem             = (new BadRequestProblem('Client sendet Falsche Daten'))->toArray();
        $problem['problems'] = $this->problems;

        return APIProblem::fromArray($problem);
    }

    /** @param class-string $type */
    private function applyValue(string $type, mixed $value, string $name): void
    {
        try {
            $setter = 'set' . \ucfirst($name);
            if (\is_array($value)) {
                $valueObject = [];
                $errors      = [];
                $lastThrown  = false;
                foreach ($value as $index => $valueItem) {
                    try {
                        $valueObject[$index] = \createValueObject($type, $valueItem);
                        $errors[$index]      = false;
                    } catch (\Throwable $exception) {
                        $errors[$index] = true;
                        $lastThrown     = $exception;
                    }
                }

                if ($lastThrown !== false) {
                    throw $lastThrown;
                }
            } else {
                $valueObject = \createValueObject($type, $value);
            }

            if (\method_exists($this, $setter)) {
                /* @phpstan-ignore-next-line */
                $this->$setter($valueObject);
            } else {
                $this->$name = $valueObject;
            }
        } catch (\Throwable) {
            if (\is_array($value) && isset($errors)) {
                foreach ($errors as $index => $item) {
                    if (! $item) {
                        continue;
                    }

                    $this->problems[$name . '.' . $index] = (
                    new BadRequestProblem(\sprintf('Wert %s ist falsch', \var_export($value, true)))
                    )->toArray();
                }
            } else {
                $this->problems[$name] = (
                new BadRequestProblem(\sprintf('Wert %s ist falsch', \var_export($value, true)))
                )->toArray();
            }
        }
    }

    /** @param array<mixed> $data */
    abstract protected function check(array $data): void;

    private function valueToString(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if (\is_array($value)) {
            return 'array';
        }

        if (\is_object($value)) {
            if (\method_exists($value, '__toString')) {
                return $this->valueToString($value->__toString());
            }

            if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
                return $this->valueToString($value->format('U'));
            }

            return $value::class;
        }

        if (\is_resource($value)) {
            return 'resource';
        }

        if (\is_string($value)) {
            return '"' . $value . '"';
        }

        /** @phpstan-ignore-next-line */
        return (string) $value;
    }
}

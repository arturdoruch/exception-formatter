<?php

namespace ArturDoruch\ExceptionFormatter\Exception;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class FlattenException implements \Serializable
{
    /**
     * @var \Throwable
     */
    protected $original;
    private $class;
    private $message;
    private $code;
    protected $file;
    private $line;
    private $trace;
    protected $traceAsString;

    /**
     * @var self
     */
    private $previous;

    public function __construct(\Throwable $exception)
    {
        $this->original = $exception;
        $this->class = get_class($exception);
        $this->message = $exception->getMessage();
        $this->code = $exception->getCode();
        $this->line = $exception->getLine();
        $this->file = $exception->getFile();

        if ($previous = $this->original->getPrevious()) {
            $this->previous = $this->create($previous);
        }
    }


    protected function create(\Throwable $exception)
    {
        return new static($exception);
    }

    /**
     * @return \Throwable|null Not modified exception or null when the object was serialized.
     */
    public function getOriginal(): ?\Throwable
    {
        return $this->original;
    }


    public function getMessage(): string
    {
        return $this->message;
    }


    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Gets an exception class name.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }


    public function getFile(): string
    {
        return $this->file;
    }


    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * Gets flatten stack trace.
     *
     * @return array
     */
    public function getTrace(): array
    {
        if ($this->trace === null) {
            $this->trace = self::flattenTrace($this->original);
        }

        return $this->trace;
    }


    final public function getTraceAsString(): string
    {
        if ($this->traceAsString === null) {
            $this->traceAsString = $this->prepareTraceString();
        }

        return $this->traceAsString;
    }


    protected function prepareTraceString(): string
    {
        return $this->original->getTraceAsString();
    }

    /**
     * @return static
     */
    public function getPrevious()
    {
        return $this->previous;
    }


    public function serialize()
    {
        return serialize($this->getProperties());
    }

    /**
     * Gets object properties to serialize.
     *
     * @return array
     */
    protected function getProperties(): array
    {
        return [
            'class' => $this->class,
            'message' => $this->message,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $this->getTrace(),
            'traceAsString' => $this->getTraceAsString(),
            'previous' => $this->getPrevious(),
        ];
    }


    public function unserialize($serialized)
    {
        $properties = unserialize($serialized);

        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }
    }


    private static function flattenTrace(\Throwable $exception): array
    {
        $trace[] = [
            'class' => null,
            'type' => null,
            'function' => null,
            'arguments' => [],
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        foreach ($exception->getTrace() as $entry) {
            $trace[] = [
                'class' => $entry['class'] ?? null,
                'type' => $entry['type'] ?? null,
                'function' => $entry['function'],
                'arguments' => isset($entry['args']) ? self::flattenArguments($entry['args']) : [],
                'file' => $entry['file'] ?? null,
                'line' => $entry['line'] ?? null,
            ];
        }

        return $trace;
    }


    private static function flattenArguments($arguments, $level = 0, &$count = 0)
    {
        $result = [];

        foreach ($arguments as $key => $value) {
            if (++$count > 10000) {
                return ['array', '*SKIPPED over 10000 entries*'];
            }

            if (is_array($value)) {
                $result[$key] = ['array', $level <= 10 ? self::flattenArguments($value, $level + 1, $count) : '*DEEP NESTED ARRAY*'];
            } elseif ($value instanceof \__PHP_Incomplete_Class) {
                // is_object() returns false on PHP <= 7.1
                $result[$key] = ['incomplete-object', (new \ArrayObject($value))['__PHP_Incomplete_Class_Name']];
            } elseif (is_object($value)) {
                $result[$key] = ['object', get_class($value)];
            } elseif (null === $value) {
                $result[$key] = ['null', $value];
            } elseif (is_bool($value)) {
                $result[$key] = ['boolean', $value];
            } elseif (is_int($value)) {
                $result[$key] = ['integer', $value];
            } elseif (is_float($value)) {
                $result[$key] = ['float', $value];
            } elseif (is_resource($value)) {
                $result[$key] = ['resource', get_resource_type($value)];
            } else {
                $result[$key] = ['string', (string) $value];
            }
        }

        return $result;
    }
}

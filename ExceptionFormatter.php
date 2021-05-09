<?php

namespace ArturDoruch\ExceptionFormatter;

use ArturDoruch\ExceptionFormatter\Exception\FormattedException;
use ArturDoruch\ExceptionFormatter\Formatter\AbstractExceptionFormatter;
use ArturDoruch\ExceptionFormatter\Formatter\HtmlExceptionFormatter;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class ExceptionFormatter
{
    /**
     * @var AbstractExceptionFormatter
     */
    private $formatter;

    /**
     * @param array $options Formatting options.
     *  - file_base_dir (string) default: null
     *    Base directory for shorting filename of the exception stack track classes.
     *
     *  - argument_max_length (int) default: 0
     *    The maximum length of the function argument (in trace entry) with type of string.
     *    The longer string will be truncated. If 0 not limit will be used.
     *
     * @param array $templates Associative array with templates formatting an exception properties
     *                         and the stack trace entries. Available keys:
     *  - class (string)
     *  - file_line (string)
     *  - trace (string)
     *  - trace_item (string)
     *  - function (string)
     *  - arguments (array)
     *
     * @param string $formatter The fully-qualified class name of the exception formatter to use.
     */
    public function __construct(array $options = [], array $templates = [], string $formatter = HtmlExceptionFormatter::class)
    {
        if (!(new \ReflectionClass($formatter))->isSubclassOf(AbstractExceptionFormatter::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid formatter class "%s". The formatter must extends the "%s" class.',
                $formatter, AbstractExceptionFormatter::class
            ));
        }

        $this->formatter = new $formatter($options, $templates);
    }


    public function setArgumentMaxLength(int $argumentMaxLength)
    {
        $this->formatter->setArgumentMaxLength($argumentMaxLength);
    }

    /**
     * Flattens the exception and formats properties such as class, file with line, and the stack trace
     * to format supported by specified formatter.
     *
     * @param \Throwable $exception
     *
     * @return FormattedException
     */
    public function format(\Throwable $exception): FormattedException
    {
        return new FormattedException($exception, $this->formatter);
    }
}

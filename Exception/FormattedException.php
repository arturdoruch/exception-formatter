<?php

namespace ArturDoruch\ExceptionFormatter\Exception;

use ArturDoruch\ExceptionFormatter\Formatter\AbstractExceptionFormatter;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class FormattedException extends FlattenException
{
    /**
     * @var AbstractExceptionFormatter
     */
    private $formatter;
    protected $formattedClass;
    protected $formattedFileLine;

    public function __construct(\Throwable $exception, AbstractExceptionFormatter $formatter)
    {
        $this->formatter = $formatter;
        parent::__construct($exception);
        $this->file = $formatter->shortenFilename($exception->getFile());
        $this->formattedClass = $formatter->formatClass($this->getClass());
        $this->formattedFileLine = $formatter->formatFileLine($exception->getFile(), $exception->getLine());
    }


    protected function create(\Throwable $exception): self
    {
        return new self($exception, $this->formatter);
    }

    /**
     * Gets formatted an exception class name.
     *
     * @return string
     */
    public function getFormattedClass(): string
    {
        return $this->formattedClass;
    }

    /**
     * Gets formatted an exception file and line.
     *
     * @return string
     */
    public function getFormattedFileLine(): string
    {
        return $this->formattedFileLine;
    }

    /**
     * Prepares formatted stack trace.
     *
     * @return string
     */
    public function prepareTraceString(): string
    {
        return $this->formatter->formatTrace($this->getTrace());
    }


    protected function getProperties(): array
    {
        return [
            'formattedClass' => $this->formattedClass,
            'formattedFileLine' => $this->formattedFileLine,
        ] + parent::getProperties();
    }
}

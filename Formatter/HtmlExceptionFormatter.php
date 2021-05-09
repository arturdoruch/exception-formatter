<?php

namespace ArturDoruch\ExceptionFormatter\Formatter;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class HtmlExceptionFormatter extends AbstractExceptionFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTemplates(): array
    {
        return [
            'trace' => '<ol class="exception-trace">{items}</ol>',
            'trace_item' => '<li>{item}</li>',
            'class' => '<abbr title="{class}">{classShort}</abbr>',
            'function' => '{class}{type}<b>{function}</b><span class="text-muted">({arguments})</span>',
            'arguments' => [
                //'object' => '<em>object</em>(%s)',
                'array' => '[%s]',
                'array_item' => "'%s' => %s",
                'null' => '<em>null</em>',
                'boolean' => '<em>%s</em>',
                'resource' => '<em>%s</em>',
                //'string' => '%s',
            ],
            'file_line' => '<span class="exception-file_line"> in <span class="text-danger">{path}<b>{baseName}</b> (line {line})</span></span>',
        ];
    }
}

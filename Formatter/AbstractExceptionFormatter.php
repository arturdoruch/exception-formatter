<?php

namespace ArturDoruch\ExceptionFormatter\Formatter;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 *
 * @internal
 */
abstract class AbstractExceptionFormatter
{
    /**
     * @var array
     */
    protected $templates;

    /**
     * @var string
     */
    private $shortenFilenameRegexp;

    /**
     * @var int
     */
    private $argumentMaxLength = 0;

    private static $dirSeparatorMap = [
        '/' => '\\',
        '\\' => '/',
    ];

    private static $requiredTemplateKeys = ['class', 'file_line', 'trace', 'trace_item', 'function', 'arguments'];

    /**
     * @param array $options
     *  - file_base_dir (string) default: null
     *    Base directory for shorting filename of the exception stack track classes.
     *
     *  - argument_max_length (int) default: 0
     *    The maximum length of the function argument (in trace entry) with type of string.
     *    The longer string will be truncated. If 0 not limit will be used.
     *
     * @param array $templates Templates formatting an exception properties and the stack trace entries.
     *  - trace (string)
     *  - trace_item (string)
     *  - class (string)
     *  - function (string)
     *  - arguments (array)
     *  - file_line (string)
     */
    public function __construct(array $options = [], array $templates = [])
    {
        if (isset($options['file_base_dir'])) {
            if (!$path = realpath($options['file_base_dir'])) {
                throw new \InvalidArgumentException(sprintf('File base directory "%s" does not exist.', $options['file_base_dir']));
            }

            $this->shortenFilenameRegexp = '~^' . str_replace('~', '\~', preg_quote(rtrim($path, '\/') . DIRECTORY_SEPARATOR)) . '~';
        }

        if (isset($options['argument_max_length'])) {
            $this->setArgumentMaxLength($options['argument_max_length']);
        }

        $this->templates = array_merge($this->getTemplates(), $templates);

        if ($missingKeys = array_diff(self::$requiredTemplateKeys, array_keys($this->templates))) {
            throw new \InvalidArgumentException(sprintf('Missing formatter templates keys: "%s".', join('", "', $missingKeys)));
        }
    }

    /**
     * Gets templates formatting an exception properties and the stack trace entries.
     *
     * @return array
     */
    abstract protected function getTemplates(): array;

    /*
     * Sets option to remove base directory from the class file path. It is used when the "file_base_dir" option is set.
     *
     * @param bool $shortenFilename
     */
    /*public function setShortenFilename(bool $shortenFilename)
    {
        $this->shortenFilename = $shortenFilename;
    }*/

    /**
     * Sets the maximum length of the function argument (in trace entry) with type of string.
     * The longer string will be truncated. If 0 not limit will be used.
     *
     * @param int $argumentMaxLength
     */
    public function setArgumentMaxLength(int $argumentMaxLength)
    {
        $this->argumentMaxLength = $argumentMaxLength;
    }


    final public function shortenFilename(string $filename): string
    {
        if (!$this->shortenFilenameRegexp) {
            return $filename;
        }

        return preg_replace($this->shortenFilenameRegexp, '', $filename);
    }

    /**
     * Formats exception stack trace.
     *
     * @param array $trace An exception stack trace entries.
     *
     * @return string
     */
    public function formatTrace(array $trace): string
    {
        $items = '';

        foreach ($trace as $i => $entry) {
            $item = '';

            if ($entry['function']) {
                $item .= str_replace(['{class}', '{type}', '{function}', '{arguments}'], [
                    $entry['class'] ? $this->formatClass($entry['class']) : '',
                    $entry['type'],
                    $entry['function'],
                    $this->formatArguments($entry['arguments'])
                ], $this->templates['function']);
            }

            if ($entry['file']) {
                $item .= $this->formatFileLine($entry['file'], $entry['line']);
            }
            $items .= str_replace(['{number}', '{item}'], [$i+1, $item], $this->templates['trace_item']);
        }

        return str_replace('{items}', $items, $this->templates['trace']);
    }


    final public function formatClass(string $class): string
    {
        $parts = explode('\\', $class);

        return str_replace(['{class}', '{classShort}'], [$class, array_pop($parts)], $this->templates['class']);
    }

    /**
     * Formats exception file path and line number.
     *
     * @param string $file
     * @param int $line
     *
     * @return string
     */
    final public function formatFileLine(string $file, int $line): string
    {
        $file = $this->shortenFilename($file);

        if (!$lastDirSeparatorPosition = strrpos($file, DIRECTORY_SEPARATOR)) {
            $lastDirSeparatorPosition = strrpos($file, self::$dirSeparatorMap[DIRECTORY_SEPARATOR]);
        }

        $path = substr($file, 0, $lastDirSeparatorPosition + 1);
        $baseName = substr($file, $lastDirSeparatorPosition + 1);

        return str_replace(['{path}', '{baseName}', '{line}'], [$path, $baseName, $line], $this->templates['file_line']);
    }


    protected function formatArguments(array $arguments): string
    {
        $result = [];
        $templates = $this->templates['arguments'];

        foreach ($arguments as $key => $item) {
            $type = $item[0];
            $value = $item[1];

            if ('object' === $type) {
                $value = $this->formatClass($value);
            } elseif ('array' === $type) {
                $value = is_array($value) ? $this->formatArguments($value) : $value;
            } elseif ('boolean' === $type) {
                $value = $value === true ? 'true' : 'false';
            } elseif ('string' === $type) {
                $this->truncate($value);
                $value = str_replace("\n", "", self::escapeHtml(var_export($value, true)));
            }

            if (isset($templates[$type])) {
                $value = sprintf($templates[$type], $value);
            }

            $result[] = is_int($key) ? $value : sprintf($templates['array_item'], $key, $value);
        }

        return implode(', ', $result);
    }

    
    protected static function escapeHtml(string $string)
    {
        return htmlspecialchars($string, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    }


    protected function truncate(string &$string)
    {
        if ($this->argumentMaxLength && strlen($string) > $this->argumentMaxLength) {
            $string = mb_substr($string, 0, $this->argumentMaxLength) . '...';
        }
    }
}

<?php

namespace ArturDoruch\ExceptionFormatter\Tests;

use ArturDoruch\ExceptionFormatter\ExceptionFormatter;
use ArturDoruch\ExceptionFormatter\Tests\Fixtures\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class ExceptionFormatterTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^Invalid formatter class/
     */
    public function testCreateWithInvalidFormatter()
    {
        new ExceptionFormatter([], [], \stdClass::class);
    }


    public function testFormat()
    {
        $exceptionFormatter = new ExceptionFormatter([], $templates = [
            'class' => '<abbr title="{class}">{classShort}</abbr>',
            'file_line' => ' at <span class="text-danger">{path}<b>{baseName}</b>:{line}</span>'
        ]);

        $exception = $this->createException('Lorem ipsum', null, false, fopen(__FILE__, 'r'), 12, 2.35);
        $formattedException = $exceptionFormatter->format($exception);

        self::assertEquals('Test runtime exception.', $formattedException->getMessage());
        self::assertEquals(RuntimeException::class, $formattedException->getClass());
        self::assertEquals(str_replace(
            ['{class}', '{classShort}'],
            [RuntimeException::class, (new \ReflectionClass($formattedException->getClass()))->getShortName()],
            $templates['class']
        ), $formattedException->getFormattedClass());

        $trace = $formattedException->getTraceAsString();
        self::assertRegExp('/<b>ExceptionFormatterTest\.php<\/b>:\d+/', $formattedException->getFormattedFileLine());
        self::assertContains("'Lorem ipsum', <em>null</em>, <em>false</em>, <em>stream</em>, 12, 2.35", $trace);

        $previousException = $formattedException->getPrevious();
        self::assertEquals(\Error::class, $previousException->getClass());

        //file_put_contents(__DIR__ . '/trace.html', $formattedException->getTraceAsString());
        //file_put_contents(__DIR__ . '/previous-trace.html', $previousException->getTraceAsString());
    }


    public function testShortenFilename()
    {
        $exceptionFormatter = new ExceptionFormatter([
            'file_base_dir' => __DIR__ . '/../../'
        ]);
        $formattedException = $exceptionFormatter->format($this->createException());

        $file = 'exception-formatter\Tests\ExceptionFormatterTest.php';
        self::assertEquals($file, $formattedException->getFile());

        /*$exceptionFormatter->setOptions([
            'shortenFilename' => false
        ]);
        $formattedException = $exceptionFormatter->format($this->createException());

        self::assertRegExp('/^.+'.preg_quote($file).'$/', $formattedException->getFile());*/
    }


    public function testTruncateTraceArgument()
    {
        $exceptionFormatter = new ExceptionFormatter([
            'argument_max_length' => 1000
        ]);
        $exceptionFormatter->setArgumentMaxLength($maxLength = 50);

        $string = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore'
            .' et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip.';
        $exception = $this->createException($string);

        $formattedException = $exceptionFormatter->format($exception);
        self::assertContains(sprintf("'%s...'", mb_substr($string, 0, $maxLength)), $formattedException->getTraceAsString());

        //file_put_contents(__DIR__ . '/truncate-string.html', $formattedException->getTraceAsString());
    }


    private function createException($string = '', $null = null, $bool = false, $stream = null, $int = 0, $float = 0.0): \Throwable
    {
        try {
            new \Exception([]);
        } catch (\Error $error) {
            return new RuntimeException('Test runtime exception.', 2, $error);
        }
    }
}

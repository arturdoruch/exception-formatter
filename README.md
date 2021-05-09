# ExceptionFormatter

Flattens the exception and formats properties such as class, file with line, and the stack trace to HTML or any other format.

## Installation

```sh
composer require arturdoruch/exception-formatter
```

## Usage

```php
<?php

use ArturDoruch\ExceptionFormatter\ExceptionFormatter;

$exceptionFormatter = new ExceptionFormatter();
$formattedException = $exceptionFormatter->format($exception);

// Get formatted stack trace.
$formattedException->getTraceAsString();
```
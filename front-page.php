<?php
declare(strict_types=1);

use Core\Framework\RouteResolver;
use Core\Framework\Shell;

Shell::render(RouteResolver::current());

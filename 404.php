<?php
declare(strict_types=1);

use Core\Framework\Shell;

status_header(404);
Shell::render(null);

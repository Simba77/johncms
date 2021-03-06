<?php
/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Checker\DBChecker;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

$schema = Capsule::Schema();
$connection = Capsule::connection();

$db_checker = new DBChecker();
$version_info = $db_checker->versionInfo();
if ($version_info['error']) {
    $schema::defaultStringLength(191);
}

if (! $schema->hasTable('files')) {
    $schema->create(
        'files',
        static function (Blueprint $table) {
            $table->id();
            $table->string('storage')->index();
            $table->string('name');
            $table->string('path');
            $table->integer('size')->unsigned()->nullable();
            $table->string('md5', 32)->nullable();
            $table->string('sha1', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();
        }
    );
}

echo 'Update complete!';

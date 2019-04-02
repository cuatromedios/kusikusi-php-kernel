<?php namespace Cuatromedios\Kusikusi\Console\Commands;

/**
 * Created by PhpStorm.
 * User: alograg
 * Date: 2/04/19
 * Time: 07:57 PM
 */

use Illuminate\Console\Command;

class PostInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kusikusi:post-install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = <<<TEXT
Task afterInstall
TEXT;

    /**
     * Función 'handle'
     *
     * Ejecuta el comando de consola.
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = snake_case($this->ask('What is the name of the application?', 'Blog KusiKusi'));
        $env = $this->ask('Enviroment?', 'production');
        $debug = json_encode($this->confirm('Debug?'));
        $key = str_random(32);
        $baseUrl = $this->ask('Site URL?', 'http://localhost');
        $db = $this->ask('Database?', 'mysql');
        $host = $this->ask('Database host?', '127.0.0.1');
        $port = $this->ask('Database port?', '3306');
        $user = $this->ask('Database user?', 'homestead');
        $password = $this->secret('Database password?') ?: 'secret';
        $dbName = $this->ask('Database name?', 'homestead');
        $envFile = <<<INI
APP_NAME=$name
APP_ENV=$env
APP_DEBUG=$debug
APP_KEY=$key
APP_TIMEZONE=UTC
APP_URL=$baseUrl

DB_CONNECTION=$db
DB_HOST=$host
DB_PORT=$port
DB_DATABASE=$user
DB_USERNAME=$dbName
DB_PASSWORD=$password

CACHE_DRIVER=file
QUEUE_CONNECTION=database
INI;
        $this->info('Enviromen write: '
            . (file_put_contents(base_path('.env'), $envFile) ? 'success' : 'fail'));
        if ($this->confirm('Do seed?')) {
            Artisan::call('migrate --seed');}
        }
    }

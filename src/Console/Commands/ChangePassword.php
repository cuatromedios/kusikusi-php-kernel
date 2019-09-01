<?php

namespace Cuatromedios\Kusikusi\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ChangePassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kk:set-password {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the password of an account identified by email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $user = User::where("email", $email)->first();
        if ($user) {
            $user->password = $password;
            $user->save();
            $this->info("Password of user {$user->name} changed to {$password}");
        } else {
            $this->info("User with email {$email} not found.");
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('email', null, InputOption::VALUE_REQUIRED, 'The email of the user to change the password', 'admin@example.com'),
            array('password', null, InputOption::VALUE_REQUIRED, 'New password', 'admin'),
        );
    }
}

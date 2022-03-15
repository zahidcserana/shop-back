<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MontlyReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly:reminder';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update cloud database';

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
     * Go to your terminal, ssh into your server, cd into your project and run this command: crontab -e
     * This will open the server Crontab file, paste the code below into the file, save and then exit: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
     * @return mixed
     */
    public function handle()
    {
        $data = Order::where('is_sync', 0)->get();

        $db_ext = \DB::connection('live');
        $itemIds = array();
        foreach ($data as $item) {
            $itemIds[] = $item->id;
            unset($item->id);
            unset($item->is_sync);
            $item = $item->toArray();
            $db_ext->table('orders')->insert($item);
        }
        DB::table('orders')->whereIn('id', $itemIds)->update(array('is_sync' => 1));

    }
}

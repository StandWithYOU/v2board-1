<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerShadowsocks;
use App\Models\ServerTrojan;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class CheckServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '节点检查任务';

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
     * @return void
     */
    public function handle()
    {
        $this->checkOffline();
    }

    /**
     * check offline
     *
     * @return void
     */
    private function checkOffline()
    {
        $shadowFaultNodes = ServerShadowsocks::faultNodeNames();
        $v2rayFaultNodes = Server::faultNodeNames();
        $trojanFaultServers = ServerTrojan::faultNodeNames();

        $faultNodes = array_merge($shadowFaultNodes, $v2rayFaultNodes, $trojanFaultServers);
        $faultNodesTotal = count($faultNodes);

        if ($faultNodesTotal > 0) {
           $message = "📮节点检查提醒：\n 现在有{$faultNodesTotal}节点处于离线状态，请立即检查: \n". join("\n", $faultNodes);
           TelegramService::sendMessageWithAdmin($message);
        }
    }
}

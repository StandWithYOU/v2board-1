<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\MailLog;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->delay(now()->addSeconds(2));
        $this->onQueue('send_email');
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $params = $this->params;
        $email = $params['email'];
        $subject = $params['subject'];
        $templateName = 'mail.' . config('v2board.email_template', 'default') . '.' . $params['template_name'];
        try {
            Mail::send(
                $templateName,
                $params['template_value'],
                function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                }
            );
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $mailLog = new MailLog();
        $mailLog->setAttribute(MailLog::FIELD_EMAIL, $email);
        $mailLog->setAttribute(MailLog::FIELD_SUBJECT, $subject);
        $mailLog->setAttribute(MailLog::FIELD_TEMPLATE_NAME, $templateName);
        $mailLog->setAttribute(MailLog::FIELD_ERROR, $error ?? NULL);
        $mailLog->save();
    }
}

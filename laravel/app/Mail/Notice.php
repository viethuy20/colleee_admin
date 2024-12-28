<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Notice extends Mailable
{
    use Queueable, SerializesModels;

    protected $type;
    protected $options;
    
    private static $SUBJECT_MAP = ['friend' => '【友達紹介】レポート',];
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $type, array $options)
    {
        //
        $this->type = $type;
        $this->options = $options;
        $this->subject = self::$SUBJECT_MAP[$type]. date('(Y年m月d日)');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to(env('MAIL_ADDRESS_NOTICE'))
                ->text('emails.notices.'.$this->type)
                ->with($this->options);
    }
}

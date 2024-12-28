<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExchangeAlert extends Mailable
{
    use Queueable, SerializesModels;

    protected $type;
    protected $options;
    
    private static $SUBJECT_MAP = ['exchange_alert' => 'Exchange Point Duplicate Alert!',];
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $type, array $options)
    {
        $this->type = $type;
        $this->options = $options;
        $this->subject = '[' . env('APP_ENV') .']'. self::$SUBJECT_MAP[$type];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to(env('MAIL_ADDRESS_EXCHANGE_DUPLICATE'))
                ->text('emails.'.$this->type)
                ->with($this->options);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaypayAlert extends Mailable
{
    use Queueable, SerializesModels;

    protected $type;
    protected $options;
    protected $email;
    
    
    private static $SUBJECT_MAP = [
        'not_enough_money'=>'GMOポイ活 PayPay残高不足通知',
        'maintenance'=>'GMOポイ活 PayPayメンテナンス通知',
        'error'=>'GMOポイ活 PayPay500系エラー通知',
    ];
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $email,string $type, array $options)
    {
        $this->type = $type;
        $this->options = $options;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject(self::$SUBJECT_MAP[$this->type])
                ->to($this->email)
                ->text('emails.paypay.'.$this->type)
                ->with($this->options);
    }
}

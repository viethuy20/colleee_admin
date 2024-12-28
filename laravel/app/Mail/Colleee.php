<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Colleee extends Mailable
{
    use Queueable, SerializesModels;

    protected $email;
    protected $type;
    protected $options;

    private static $SUBJECT_MAP = [
        'bonus_birthday' => '【GMOポイ活誕生日ポイント】お誕生日おめでとうございます！',
        'email_reminder' => '【GMOポイ活】メールアドレス再設定URLをお送りしました',
    ];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $email, string $type, array $options = [])
    {
        //
        $this->email = $email;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $options = array_merge(['email' => $this->email], $this->options);
        return $this->subject(self::$SUBJECT_MAP[$this->type])
            ->to(email_quote($this->email))
            ->text('emails.'.$this->type)
            ->with($options);
    }
}

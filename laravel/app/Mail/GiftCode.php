<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GiftCode extends Mailable
{
    use Queueable, SerializesModels;

    protected $email;
    protected $type;
    protected $options;
    protected $exchange_request_number;

    private static $SUBJECT_MAP = [
        'amazon' => 'Amazonギフトカード',
        'itunes' => 'Apple Gift Card',
        'pex' => 'PeXポイントギフト',
        'nanaco' => 'nanacoギフト',
        'edy' => 'EdyギフトID',
        'google_play' => 'Google Play ギフトコード',
        'waon' => 'WAONポイントID',
        'ponta' => 'Pontaポイント コード',
        'pssticket' => 'プレイステーション ストアチケット',
    ];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $email, string $type, string $exchange_request_number, array $options = [])
    {
        //
        $this->email = $email;
        $this->type = $type;
        $this->options = $options;
        $this->exchange_request_number = $exchange_request_number;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $options = array_merge(
            ['email' => $this->email, 'exchange_request_number' => $this->exchange_request_number],
            $this->options
        );
        return $this->subject(
            '【GMOポイ活】ポイント交換実施・ギフトコード発行のお知らせ（'.
            self::$SUBJECT_MAP[$this->type].
            '）【受付番号:'.
            $this->exchange_request_number.
            '】'
        )
            ->to(email_quote($this->email))
            ->text('emails.gift_codes.'.$this->type)
            ->with($options);
    }
}

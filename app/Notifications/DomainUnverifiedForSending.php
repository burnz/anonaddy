<?php

namespace App\Notifications;

use App\Helpers\OpenPGPSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Swift_SwiftException;

class DomainUnverifiedForSending extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    protected $domain;
    protected $reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($domain, $reason)
    {
        $this->domain = $domain;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $openpgpsigner = null;
        $recipient = $notifiable->defaultRecipient;
        $fingerprint = $recipient->should_encrypt ? $recipient->fingerprint : null;

        if ($fingerprint) {
            try {
                $openpgpsigner = OpenPGPSigner::newInstance(config('anonaddy.signing_key_fingerprint'), [], "~/.gnupg");
                $openpgpsigner->addRecipient($fingerprint);
            } catch (Swift_SwiftException $e) {
                info($e->getMessage());
                $openpgpsigner = null;

                $recipient->update(['should_encrypt' => false]);

                $recipient->notify(new GpgKeyExpired);
            }
        }

        return (new MailMessage)
            ->subject("Your domain has been unverified for sending on AnonAddy")
            ->markdown('mail.domain_unverified_for_sending', [
                'domain' => $this->domain,
                'reason' => $this->reason
            ])
            ->withSwiftMessage(function ($message) use ($openpgpsigner) {
                $message->getHeaders()
                        ->addTextHeader('Feedback-ID', 'DUS:anonaddy');

                if ($openpgpsigner) {
                    $message->attachSigner($openpgpsigner);
                }
            });
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

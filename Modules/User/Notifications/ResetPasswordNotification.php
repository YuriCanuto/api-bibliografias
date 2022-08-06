<?php

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                ->subject('Cadastrar nova senha')
                ->line('Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.')
                ->action('Cadastrar senha', url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()])))
                ->line('Este link de redefinição de senha expirará em :count minutos.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])
                ->line('Se você não solicitou uma alteração de senha, desconsidere este e-mail.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

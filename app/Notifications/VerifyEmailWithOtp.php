<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailWithOtp extends VerifyEmail
{
    use Queueable;

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(mixed $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $otp = $this->generateAndStoreOtp($notifiable);

        return (new MailMessage)
            ->subject('Verify your email address')
            ->line('Please verify your email using either option below.')
            ->line('Option 1: Click the button below.')
            ->action('Verify email address', $verificationUrl)
            ->line('Option 2: Enter this 6-digit code in the app: ' . $otp)
            ->line('This code expires in 60 minutes.')
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Generate a 6-digit OTP and store in cache.
     */
    protected function generateAndStoreOtp(mixed $notifiable): string
    {
        $otp = (string) random_int(100000, 999999);
        Cache::put('verification_otp:' . $notifiable->getKey(), $otp, now()->addMinutes(60));

        return $otp;
    }
}

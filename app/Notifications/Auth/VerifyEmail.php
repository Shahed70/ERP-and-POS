<?php

namespace App\Notifications\Auth;

use App\Models\ShopSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmail extends Notification
{
    /**
     * The verification URL.
     */
    public string $url = '';

    /**
     * Get the notification's channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     * Formatted to avoid spam filters and ensure inbox delivery.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $shop = ShopSetting::getSettings();
        $shopName = $shop->shop_name ?? 'বিসমিল্লাহ ফিশ ফিড স্টোর';

        return (new MailMessage)
            ->subject("ইমেইল ভেরিফিকেশন | Verify Your Email Address — {$shopName}")
            ->greeting("আসসালামু আলাইকুম / Hello, {$notifiable->name}!")
            ->line("{$shopName} (AquaFeed ERP) সিস্টেমে আপনার অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে।")
            ->line('অ্যাকাউন্টটি সক্রিয় করতে এবং ড্যাশবোর্ড ব্যবহারের অনুমতি পেতে অনুগ্রহ করে নিচের বাটনে ক্লিক করে আপনার ইমেইল ঠিকানাটি ভেরিফাই করুন:')
            ->action('ইমেইল ঠিকানা নিশ্চিত করুন (Verify Email)', $this->url)
            ->line('নিরাপত্তার স্বার্থে এই ভেরিফিকেশন লিঙ্কটি আগামী ৬০ মিনিট পর্যন্ত কার্যকর থাকবে।')
            ->line('যদি উপরের বাটনে ক্লিক করতে সমস্যা হয়, তবে নিচের লিঙ্কটি কপি করে আপনার ব্রাউজারে প্রবেশ করুন:')
            ->line($this->url)
            ->line('আপনি যদি এই অ্যাকাউন্ট না খুলে থাকেন, তবে কোনো পদক্ষেপ নেওয়ার প্রয়োজন নেই।')
            ->salutation("ধন্যবাদান্তে,\n{$shopName} অ্যাডমিন টিম");
    }
}

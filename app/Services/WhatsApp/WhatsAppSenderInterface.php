<?php

namespace App\Services\WhatsApp;

interface WhatsAppSenderInterface
{
    /**
     * Format isi pesan berdasarkan template dan variabel-variabel data.
     */
    public function formatMessage(string $template, array $variables): string;

    /**
     * Buat URL pengiriman (misal wa.me) atau payload ke penyedia API.
     */
    public function generateUrl(string $noWa, string $message): string;

    /**
     * Dapatkan template pesan default standar SD.
     */
    public function getDefaultTemplate(): string;
}

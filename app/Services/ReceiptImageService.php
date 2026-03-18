<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class ReceiptImageService
{
    public function generate(Order $order): string
    {
        $storeName = Setting::get('business.store_name', 'TOKO KOPI NUSANTARA');
        $storeAddress = Setting::get('business.address', '');
        $storePhone = Setting::get('business.phone', '');

        $width = 800;
        $padding = 50;

        $itemHeight = 55;
        $itemsCount = $order->items->count();
        $itemsSectionHeight = max($itemsCount * $itemHeight, 120);

        $headerHeight = 140;
        $orderInfoHeight = 80;
        $totalsHeight = 140;
        $footerHeight = 80;
        $spacing = 35;

        $totalHeight = $headerHeight + $orderInfoHeight + $itemsSectionHeight + $totalsHeight + $footerHeight + ($spacing * 4);

        // Create image
        $image = imagecreatetruecolor($width, $totalHeight);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 26, 26, 26);
        $darkGray = imagecolorallocate($image, 51, 51, 51);
        $gray = imagecolorallocate($image, 102, 102, 102);
        $lightGray = imagecolorallocate($image, 229, 231, 235);
        $borderGray = imagecolorallocate($image, 200, 200, 200);
        $blue = imagecolorallocate($image, 37, 99, 235);
        $green = imagecolorallocate($image, 34, 197, 94);
        $red = imagecolorallocate($image, 220, 38, 38);
        $accentBlue = imagecolorallocate($image, 59, 130, 246);

        // White background
        imagefilledrectangle($image, 0, 0, $width, $totalHeight, $white);

        // Top accent bar
        imagefilledrectangle($image, 0, 0, $width, 8, $accentBlue);

        $currentY = $padding;

        // Store Name (centered, bold)
        $this->drawText($image, $storeName, $width / 2, $currentY, $black, 36, 'center', true);
        $currentY += 55;

        // Store Address
        if ($storeAddress) {
            $this->drawText($image, $storeAddress, $width / 2, $currentY, $gray, 16, 'center');
            $currentY += 28;
        }

        // Store Phone
        if ($storePhone) {
            $this->drawText($image, $storePhone, $width / 2, $currentY, $gray, 16, 'center');
            $currentY += 35;
        }

        // Divider line
        imagefilledrectangle($image, $padding, $currentY, $width - $padding, $currentY + 3, $lightGray);
        $currentY += $spacing;

        // Invoice Info (in a box)
        $infoBoxHeight = 70;
        $infoBoxY = $currentY;
        
        // Draw info box background
        imagefilledrectangle($image, $padding, $infoBoxY, $width - $padding, $infoBoxY + $infoBoxHeight, $white);
        imagerectangle($image, $padding, $infoBoxY, $width - $padding, $infoBoxY + $infoBoxHeight, $borderGray);
        
        // Invoice Number
        $this->drawText($image, 'INVOICE', $padding + 15, $infoBoxY + 18, $gray, 12, 'left');
        $this->drawText($image, $order->invoice_number, $padding + 15, $infoBoxY + 42, $black, 20, 'left', true);
        
        // Date
        $date = \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i');
        $this->drawText($image, 'DATE', $width - $padding - 15, $infoBoxY + 18, $gray, 12, 'right');
        $this->drawText($image, $date, $width - $padding - 15, $infoBoxY + 42, $black, 18, 'right');
        
        $currentY += $infoBoxHeight + $spacing;

        // Items Header
        $headerY = $currentY;
        $this->drawText($image, 'ITEM', $padding, $headerY, $gray, 14, 'left', true);
        $this->drawText($image, 'QTY', $width - $padding - 180, $headerY, $gray, 14, 'right', true);
        $this->drawText($image, 'PRICE', $width - $padding - 90, $headerY, $gray, 14, 'right', true);
        $this->drawText($image, 'TOTAL', $width - $padding, $headerY, $gray, 14, 'right', true);
        $currentY += 40;

        // Items with alternating background
        foreach ($order->items as $index => $item) {
            // Alternating row background
            if ($index % 2 == 0) {
                imagefilledrectangle($image, $padding, $currentY - 10, $width - $padding, $currentY + $itemHeight - 5, $white);
            } else {
                imagefilledrectangle($image, $padding, $currentY - 10, $width - $padding, $currentY + $itemHeight - 5, $lightGray);
            }
            
            $this->drawText($image, $item->product_name, $padding, $currentY, $darkGray, 18, 'left');
            $this->drawText($image, 'x' . $item->quantity, $width - $padding - 180, $currentY, $gray, 16, 'right');
            $this->drawText($image, 'Rp ' . number_format($item->price, 0, ',', '.'), $width - $padding - 90, $currentY, $gray, 14, 'right');
            $this->drawText($image, 'Rp ' . number_format($item->total, 0, ',', '.'), $width - $padding, $currentY, $black, 18, 'right', true);
            $currentY += $itemHeight;
        }

        // Divider line
        imagefilledrectangle($image, $padding, $currentY, $width - $padding, $currentY + 3, $borderGray);
        $currentY += $spacing;

        // Subtotal
        $this->drawText($image, 'Subtotal', $width - $padding - 200, $currentY, $gray, 16, 'right');
        $this->drawText($image, 'Rp ' . number_format($order->subtotal, 0, ',', '.'), $width - $padding, $currentY, $black, 16, 'right');
        $currentY += 40;

        // Discount
        if ($order->discount_total > 0) {
            $this->drawText($image, 'Discount', $width - $padding - 200, $currentY, $gray, 16, 'right');
            $this->drawText($image, '-Rp ' . number_format($order->discount_total, 0, ',', '.'), $width - $padding, $currentY, $red, 18, 'right', true);
            $currentY += 40;
        }

        // Tax
        if ($order->tax_total > 0) {
            $this->drawText($image, 'Tax', $width - $padding - 200, $currentY, $gray, 16, 'right');
            $this->drawText($image, 'Rp ' . number_format($order->tax_total, 0, ',', '.'), $width - $padding, $currentY, $orange ?? $gray, 16, 'right');
            $currentY += 40;
        }

        // Grand Total (highlighted box)
        $totalBoxY = $currentY;
        $totalBoxHeight = 60;
        
        // Total box background
        imagefilledrectangle($image, $width - $padding - 220, $totalBoxY - 10, $width - $padding, $totalBoxY + $totalBoxHeight + 10, $green);
        
        $this->drawText($image, 'TOTAL', $width - $padding - 110, $totalBoxY + 15, $white, 16, 'center', true);
        $this->drawText($image, 'Rp ' . number_format($order->grand_total, 0, ',', '.'), $width - $padding - 110, $totalBoxY + 45, $white, 26, 'center', true);
        
        $currentY += $totalBoxHeight + $footerHeight;

        // Footer with payment info
        $this->drawText($image, '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', $width / 2, $currentY, $gray, 14, 'center');
        $currentY += 35;
        
        // Payment method
        $paymentMethod = $order->payments->first()?->paymentMethod?->name ?? 'Cash';
        $this->drawText($image, 'Payment: ' . $paymentMethod, $width / 2, $currentY, $darkGray, 16, 'center');
        $currentY += 30;
        
        $this->drawText($image, 'Thank you for your purchase!', $width / 2, $currentY, $accentBlue, 18, 'center', true);
        $currentY += 35;
        
        $this->drawText($image, 'See you again soon! ☕', $width / 2, $currentY, $gray, 14, 'center');

        // Save image
        $filename = 'receipt_' . $order->invoice_number . '_' . time() . '.png';
        $path = 'receipts/' . $filename;

        // Save to temp file first
        $tempFile = tempnam(sys_get_temp_dir(), 'receipt_') . '.png';
        imagepng($image, $tempFile);

        // Put to storage
        Storage::disk('public')->put($path, file_get_contents($tempFile));

        // Cleanup
        unlink($tempFile);

        return Storage::disk('public')->url($path);
    }
    
    /**
     * Draw text on image
     */
    private function drawText($image, $text, $x, $y, $color, $size = 12, $align = 'left', $bold = false)
    {
        // Use built-in font (size 1-5) or freetype if available
        $font = __DIR__ . '/../../resources/fonts/Arial.ttf';
        
        if (function_exists('imagettftext') && file_exists($font)) {
            // FreeType text
            $bbox = imagettfbbox((float)$size, 0, $font, $text);
            $textWidth = $bbox[2] - $bbox[0];
            
            if ($align === 'center') {
                $x = $x - $textWidth / 2;
            } elseif ($align === 'right') {
                $x = $x - $textWidth;
            }
            
            imagettftext($image, (float)$size, 0, (int)$x, (int)$y + (int)$size, $color, $font, $text);
        } else {
            // Fallback to imagestring (limited sizes)
            $fontWidth = imagefontwidth(5);
            $fontHeight = imagefontheight(5);
            $textWidth = strlen($text) * $fontWidth;
            
            if ($align === 'center') {
                $x = $x - $textWidth / 2;
            } elseif ($align === 'right') {
                $x = $x - $textWidth;
            }
            
            imagestring($image, 5, (int)$x, (int)$y, $text, $color);
        }
    }
}
